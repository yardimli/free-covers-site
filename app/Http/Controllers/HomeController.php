<?php namespace App\Http\Controllers;

use App\Models\Cover;
use App\Models\Template; // Although not directly used for display logic on index page based on clarification
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; // Import Log facade

class HomeController extends Controller
{
	public function index()
	{
		// Fetch all covers to determine categories and their counts
		$allCoversForCategories = Cover::select(['id', 'categories'])->where('cover_type_id', 1)->get();
		$genreCounts = [];
		foreach ($allCoversForCategories as $cover) {
			if (is_array($cover->categories)) {
//				echo "Category: " . json_encode($cover->categories) . "<br>"; // Debugging output
				foreach ($cover->categories as $category) {
					$categoryName = Str::title(trim($category)); // This is TitleCased, e.g., "Action & Adventure"
					if (!empty($categoryName)) {
						$genreCounts[$categoryName] = ($genreCounts[$categoryName] ?? 0) + 1;
					}
				}
			}
		}

		// Filter genres to include only those with 4 or more covers
		$filteredGenreCounts = array_filter($genreCounts, function ($count) {
			return $count >= 4; // Or $count >= 6 if you want at least one full 3x2 slide
		});
		ksort($filteredGenreCounts); // Sort filtered genres alphabetically

		$coversForFirstTab = [];
		$firstGenreName = null; // This will be TitleCased
		if (!empty($filteredGenreCounts)) {
			$firstGenreName = array_key_first($filteredGenreCounts);
			if ($firstGenreName) {
				// Convert TitleCased name to lowercase for DB query, consistent with expected DB storage
				$dbQueryGenreName = Str::lower($firstGenreName); // e.g., "action & adventure"
				$coversForFirstTab[$firstGenreName] = Cover::whereJsonContains('categories', $dbQueryGenreName)
					->inRandomOrder()
					->take(18) // Changed from 16 to 18 (for 3 slides of 3x2)
					->get();
				foreach ($coversForFirstTab[$firstGenreName] as &$cover) {
					if ($cover->image_path) {
						$cover['mockup'] = str_replace('covers/', 'cover-mockups/', $cover->image_path);
						$cover['mockup'] = str_replace('.jpg', '-front-mockup.png', $cover['mockup']);
					} else {
						$cover['mockup'] = 'path/to/default/mockup.png'; // Fallback mockup
					}
				}
				unset($cover);
			}
		}

		$newArrivals = Cover::orderBy('created_at', 'desc')->where('cover_type_id', 1)->take(6)->get();
		foreach ($newArrivals as &$cover) {
			if ($cover->image_path) {
				$cover['mockup'] = str_replace('covers/', 'cover-mockups/', $cover->image_path);
				$cover['mockup'] = str_replace('.jpg', '-front-mockup.png', $cover['mockup']);
			} else {
				$cover['mockup'] = 'path/to/default/mockup.png'; // Fallback mockup
			}
		}
		unset($cover);

		return view('index', [
			'genreCounts' => $filteredGenreCounts,
			'coversForTabs' => $coversForFirstTab,
			'newArrivals' => $newArrivals,
		]);
	}

	/**
	 * Fetch covers for a specific genre via AJAX.
	 */
	public function getCoversForGenre(Request $request, $genreSlug)
	{
		$genreDisplayName = $request->query('name'); // Sent by JS, e.g., "Action & Adventure"
		if (empty($genreDisplayName)) {
			Log::warning("Required 'name' query parameter missing or empty for genre slug: " . $genreSlug . ". AJAX call might be misconfigured from JS.");
			return response()->json(['covers' => [], 'message' => 'Genre specification is incomplete. The "name" query parameter is missing.'], 400);
		}

		$dbQueryGenreName = Str::lower($genreDisplayName); // e.g., "action & adventure"
		$covers = Cover::whereJsonContains('categories', $dbQueryGenreName)
			->orWhereJsonContains('categories', $genreDisplayName)
			->where('cover_type_id', 1)
			->inRandomOrder()
			->take(18) // Changed from 16 to 18
			->get();

		$formattedCovers = $covers->map(function ($cover) {
			$mockupPath = 'path/to/default/mockup.png'; // Fallback
			if ($cover->image_path) {
				$mockupPath = str_replace('covers/', 'cover-mockups/', $cover->image_path);
				$mockupPath = str_replace('.jpg', '-front-mockup.png', $mockupPath);
			}
			return [
				'id' => $cover->id,
				'name' => $cover->name,
				'mockup' => $mockupPath,
				'show_url' => route('covers.show', $cover->id),
				'limited_name' => Str::limit($cover->caption, 40),
				'caption' => $cover->caption,
			];
		});

		return response()->json(['covers' => $formattedCovers]);
	}
}
