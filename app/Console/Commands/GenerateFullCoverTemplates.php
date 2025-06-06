<?php

	namespace App\Console\Commands;

	use App\Models\Template;
	use Illuminate\Console\Command;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;

	class GenerateFullCoverTemplates extends Command
	{
		/**
		 * The name and signature of the console command.
		 *
		 * @var string
		 */
		protected $signature = 'templates:generate-full-cover';

		/**
		 * The console command description.
		 *
		 * @var string
		 */
		protected $description = 'Generates full cover templates (front, spine, back) from front-only templates';

		/**
		 * Common author names to identify author text layers
		 *
		 * @var array
		 */
		protected $authorNames = ['Morgan', 'Casey', 'Peyton', 'Emerson', 'Jordan', 'Parker', 'Avery', 'Rowan', 'Taylor'];

		/**
		 * Execute the console command.
		 *
		 * @return int
		 */
		public function handle()
		{
			$this->info('Starting generation of full cover templates...');

			$updatedCount = 0;
			$skippedCount = 0;
			$errorCount = 0;

			Template::chunk(100, function ($templates) use (&$updatedCount, &$skippedCount, &$errorCount) {
				foreach ($templates as $template) {
					try {
						$jsonContent = $template->json_content;

						if (!is_array($jsonContent) || empty($jsonContent)) {
							$this->warn("Skipping Template ID: {$template->id} - json_content is empty or not an array.");
							$skippedCount++;
							continue;
						}

						// Check if backWidth and spineWidth are 0
						if (!isset($jsonContent['canvas']) ||
							!isset($jsonContent['canvas']['backWidth']) ||
							!isset($jsonContent['canvas']['spineWidth']) ||
							$jsonContent['canvas']['backWidth'] != 0 ||
							$jsonContent['canvas']['spineWidth'] != 0) {
							$this->comment("Skipping Template ID: {$template->id} - already has spine/back or canvas data missing.");
							$skippedCount++;
							continue;
						}

						// Clone the json content for full cover
						$fullCoverJson = $jsonContent;

						// Set spine width
						$spineWidth = 300;
						$frontWidth = $fullCoverJson['canvas']['frontWidth'] ?? 1540;

						// Calculate back width to match front width
						$backWidth = $frontWidth;

						// Update canvas dimensions
						$fullCoverJson['canvas']['spineWidth'] = $spineWidth;
						$fullCoverJson['canvas']['backWidth'] = $backWidth;
						$fullCoverJson['canvas']['width'] = $frontWidth + $spineWidth + $backWidth;

						// Offset for moving front cover elements
						$offset = $backWidth + $spineWidth;

						// Find author and title layers
						$authorLayer = null;
						$titleLayer = null;
						$smallestTextLayer = null;
						$smallestFontSize = PHP_INT_MAX;

						// Process existing layers
						if (isset($fullCoverJson['layers']) && is_array($fullCoverJson['layers'])) {
							// First pass: identify author, title, and smallest text
							foreach ($fullCoverJson['layers'] as $layer) {
								if (!isset($layer['type']) || $layer['type'] !== 'text') {
									continue;
								}

								$content = $layer['content'] ?? '';

								// Check if this is an author layer
								foreach ($this->authorNames as $authorName) {
									if (stripos($content, $authorName) !== false) {
										$authorLayer = $layer;
										break;
									}
								}

								// Track smallest font size
								$fontSize = $layer['fontSize'] ?? 16;
								if ($fontSize < $smallestFontSize) {
									$smallestFontSize = $fontSize;
									$smallestTextLayer = $layer;
								}
							}

							// Find title layer (largest font size that isn't author)
							$largestFontSize = 0;
							foreach ($fullCoverJson['layers'] as $layer) {
								if (!isset($layer['type']) || $layer['type'] !== 'text') {
									continue;
								}

								$fontSize = $layer['fontSize'] ?? 16;
								$content = $layer['content'] ?? '';

								// Skip if this is the author layer
								if ($authorLayer && $content === $authorLayer['content']) {
									continue;
								}

								if ($fontSize > $largestFontSize) {
									$largestFontSize = $fontSize;
									$titleLayer = $layer;
								}
							}

							// Offset all existing layers to the right
							foreach ($fullCoverJson['layers'] as &$layer) {
								if (isset($layer['x'])) {
									$layer['x'] += $offset;
								}
							}
							//unset($layer);

							// Add spine elements if we found author and title
							if ($authorLayer && $titleLayer) {
								// Clone author for spine (top)
								$spineAuthor = $authorLayer;
								$spineAuthor['id'] = 'spine-author';
								$spineAuthor['name'] = 'Spine Author';
								$spineAuthor['x'] = $backWidth - 300;
								$spineAuthor['y'] = 400; // Top of spine
								$spineAuthor['align'] = 'left';
								$spineAuthor['rotation'] = 90;
								$spineAuthor['fontSize'] = max(14, $authorLayer['fontSize'] * 0.3); // Reduce font size by 70%
								$spineAuthor['width'] = 1200; // Adjust for rotated text
								$spineAuthor['height'] = 200; // Set height to spine width
								$spineAuthor['content'] = str_replace("\n", " ", $spineAuthor['content']); // Remove newlines
								$spineAuthor['definition'] = 'spine_text';

								// Clone title for spine (middle)
								$spineTitle = $titleLayer;
								$spineTitle['id'] = 'spine-title';
								$spineTitle['name'] = 'Spine Title';
								$spineTitle['x'] = $backWidth - 300;
								$spineTitle['y'] = 1400;
								$spineTitle['align'] = 'left';
								$spineTitle['rotation'] = 90;
								$spineTitle['fontSize'] = max(18, $titleLayer['fontSize'] * 0.3); // Reduce font size by 70%
								$spineTitle['width'] = 1200; // Adjust for rotated text
								$spineTitle['height'] = 200; // Set height to spine width
								$spineTitle['content'] = str_replace("\n", " ", $spineTitle['content']); // Remove newlines
								$spineTitle['definition'] = 'spine_text';

								// Add spine layers
								$fullCoverJson['layers'][] = $spineAuthor;
								$fullCoverJson['layers'][] = $spineTitle;

								// Add back cover elements
								// Back cover title
								$backTitle = $titleLayer;
								$backTitle['id'] = 'back-title';
								$backTitle['name'] = 'Back Title';
								$backTitle['x'] = 100;
								$backTitle['y'] = 100;
								$backTitle['align'] = 'left';
								$backTitle['vAlign'] = 'top';
								$backTitle['rotation'] = 0;
								$backTitle['width'] = 1400;
								$backTitle['height'] = round($titleLayer['height'] * 0.6); // Reduce height by 40%
								$backTitle['fontSize'] = $titleLayer['fontSize'] * 0.6; // Reduce font size by 50%
								$backTitle['content'] = str_replace("\n", " ", $backTitle['content']); // Remove newlines
								$backTitle['definition'] = 'back_cover_text';

								// Back cover author
								$backAuthor = $authorLayer;
								$backAuthor['id'] = 'back-author';
								$backAuthor['name'] = 'Back Author';
								$backAuthor['x'] = 100;
								$backAuthor['y'] = 100 + $backTitle['height'] + 50; // Changed from 300 to 400
								$backAuthor['align'] = 'left';
								$backAuthor['vAlign'] = 'top';
								$backAuthor['rotation'] = 0;
								$backAuthor['width'] = 1400;
								$backAuthor['height'] = round($authorLayer['height'] * 0.6); // Reduce height by 40%
								$backAuthor['fontSize'] = $authorLayer['fontSize'] * 0.6; // Reduce font size by 50%
								$backAuthor['content'] = str_replace("\n", " ", $backAuthor['content']); // Remove newlines
								$backAuthor['definition'] = 'back_cover_text';

								// Back cover text (lorem ipsum)
								$backText = $smallestTextLayer ?: $authorLayer; // Fallback to author style if no smallest found
								$backText['id'] = 'back-text';
								$backText['name'] = 'Back Cover Text';
								$backText['x'] = 100;
								$backText['y'] = $backAuthor['y'] + $backAuthor['height'] + 50; // Changed from 500 to 650
								$backText['align'] = 'left';
								$backText['rotation'] = 0;
								$backText['width'] = 1400;
								$backText['height'] = 800;
								$backText['content'] = $this->generateLoremIpsum();
								$backText['fontSize'] = ($smallestTextLayer ? $smallestTextLayer['fontSize'] : $authorLayer['fontSize']) * 1; // Reduce font size by 50%
								$backText['definition'] = 'back_cover_text';
								$backText['align'] = 'left';
								$backText['vAlign'] = 'top';

								// Add back cover layers
								$fullCoverJson['layers'][] = $backTitle;
								$fullCoverJson['layers'][] = $backAuthor;
								$fullCoverJson['layers'][] = $backText;

							} else {
								$this->warn("Template ID: {$template->id} - Could not identify author and/or title layers.");
							}
						}

						// Save to full_cover_json_content field
						$template->full_cover_json_content = $fullCoverJson;
						$template->save();

						$updatedCount++;
						$this->line("Generated full cover for Template ID: {$template->id}");

					} catch (\Exception $e) {
						$this->error("Error processing Template ID: {$template->id} - " . $e->getMessage());
						Log::error("Error generating full cover for ID {$template->id}: " . $e->getMessage(), ['exception' => $e]);
						$errorCount++;
					}
				}
			});

			$this->info("-----------------------------------------");
			$this->info("Full cover template generation complete.");
			$this->info("Successfully generated: {$updatedCount} templates.");
			$this->info("Skipped: {$skippedCount} templates.");
			$this->info("Errors encountered: {$errorCount} templates (check logs).");
			$this->info("-----------------------------------------");

			return Command::SUCCESS;
		}

		/**
		 * Generate Lorem Ipsum text for back cover
		 *
		 * @return string
		 */
		protected function generateLoremIpsum()
		{
			$paragraphs = [
				"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.",

				"Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",

				"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo."
			];

			return implode("\n\n", $paragraphs);
		}
	}
