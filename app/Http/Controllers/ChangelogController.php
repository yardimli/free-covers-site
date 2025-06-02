<?php

	namespace App\Http\Controllers;

	use App\Models\Changelog;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Log;
	use Symfony\Component\Process\Process;
	use Symfony\Component\Process\Exception\ProcessFailedException;
	use Carbon\Carbon;
	use Illuminate\View\View;

	class ChangelogController extends Controller
	{
		/**
		 * Display a listing of the resource.
		 */
		public function index(): View
		{
			try {
				$this->syncChangelogFromGit();
			} catch (\Exception $e) {
				Log::error('Failed to sync changelog from Git: ' . $e->getMessage());
				// Optionally, you could pass an error message to the view
				// For now, we'll just log it and proceed with existing data
			}

			$changelogs = Changelog::where('hide', false)
				->orderBy('commit_date', 'desc')
				->paginate(20); // Paginate results, 20 per page

			return view('changelog', [
				'changelogs' => $changelogs,
				'footerClass' => '', // Consistent with other page controller setups
			]);
		}

		/**
		 * Fetches commits from Git and stores new ones in the database.
		 */
		private function syncChangelogFromGit(): void
		{
			// Ensure git is available and we are in a git repository
			if (!is_dir(base_path('.git'))) {
				Log::warning('Changelog: Not a Git repository or .git directory not accessible at ' . base_path('.git'));
				return;
			}

			// Git command to get log in a parseable format: Hash||AuthorDateISO8601||AuthorName||AuthorEmail||Subject
			// --no-merges excludes merge commits, which are often not useful for a user-facing changelog.
			$process = new Process(['git', 'log', '--pretty=format:%H||%aI||%an||%ae||%s', '--no-merges']);
			$process->setWorkingDirectory(base_path()); // Run command from project root

			try {
				$process->mustRun();
				$output = $process->getOutput();
			} catch (ProcessFailedException $exception) {
				Log::error("Changelog: Git log command failed: " . $exception->getMessage());
				throw $exception; // Re-throw to be caught by the index method
			}

			$commits = explode("\n", trim($output));
			$newCommitsCount = 0;

			foreach ($commits as $commitLine) {
				if (empty($commitLine)) {
					continue;
				}

				// Explode by '||', limit to 5 parts in case message contains '||' (though unlikely for subject)
				$parts = explode('||', $commitLine, 5);
				if (count($parts) < 5) {
					Log::warning("Changelog: Skipping malformed commit line: " . $commitLine);
					continue;
				}

				list($hash, $dateStr, $authorName, $authorEmail, $message) = $parts;

				// Check if commit already exists in the database
				if (Changelog::where('commit_hash', $hash)->exists()) {
					// Optimization: `git log` returns commits in reverse chronological order (newest first).
					// If we find a commit that's already in the DB, we assume all older commits
					// (further down the log) are also synced.
					break;
				}

				try {
					Changelog::create([
						'commit_hash' => $hash,
						'commit_date' => Carbon::parse($dateStr)->setTimezone(config('app.timezone')),
						'author_name' => $authorName,
						'author_email' => $authorEmail,
						'message' => trim($message),
						// 'hide' defaults to false as per migration
					]);
					$newCommitsCount++;
				} catch (\Exception $e) {
					Log::error("Changelog: Failed to create changelog entry for commit {$hash}: " . $e->getMessage());
					// Continue to the next commit
				}
			}

			if ($newCommitsCount > 0) {
				Log::info("Changelog: Added {$newCommitsCount} new commits to the changelog.");
			}
		}
	}
