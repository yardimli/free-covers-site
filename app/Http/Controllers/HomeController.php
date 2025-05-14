<?php namespace App\Http\Controllers;

use App\Models\Cover;
use App\Models\Template; // Although not directly used for display logic on index page based on clarification
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomeController extends Controller
{
	public function index()
	{
		// Fetch all covers to determine categories and their counts
		$allCoversForCategories = Cover::select(['id', 'categories'])->where('cover_type_id', 1)->get();
		$genreCounts = [];
		foreach ($allCoversForCategories as $cover) {
			if (is_array($cover->categories)) {
				foreach ($cover->categories as $category) {
					$categoryName = Str::title(trim($category));
					if (!empty($categoryName)) {
						$genreCounts[$categoryName] = ($genreCounts[$categoryName] ?? 0) + 1;
					}
				}
			}
		}

		// Filter genres to include only those with 4 or more covers
		$filteredGenreCounts = array_filter($genreCounts, function ($count) {
			return $count >= 4; // Keep this filter, or adjust if 16 items are mandatory even if less are available
		});
		ksort($filteredGenreCounts); // Sort filtered genres alphabetically

		$coversForFirstTab = [];
		$firstGenreName = null;
		if (!empty($filteredGenreCounts)) {
			// Get the first genre name from the filtered list
			$firstGenreName = array_key_first($filteredGenreCounts);
			if ($firstGenreName) {
				$dbQueryGenreName = Str::lower($firstGenreName);
				$coversForFirstTab[$firstGenreName] = Cover::whereJsonContains('categories', $dbQueryGenreName)
					->inRandomOrder()
					->take(16) // Number of items for the slider (changed from 4 to 16)
					->get();
				foreach ($coversForFirstTab[$firstGenreName] as &$cover) {
					if ($cover->image_path) {
						$cover['mockup'] = str_replace('covers/', 'cover-mockups/', $cover->image_path);
						$cover['mockup'] = str_replace('.jpg', '-front-mockup.png', $cover['mockup']);
					} else {
						$cover['mockup'] = 'path/to/default/mockup.png'; // Fallback mockup
					}
				}
				unset($cover); // Unset reference
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
		unset($cover); // Unset reference

		return view('index', [
			'genreCounts' => $filteredGenreCounts, // Pass filtered counts
			'coversForTabs' => $coversForFirstTab, // Covers for the first tab only
			'newArrivals' => $newArrivals,
		]);
	}

	/**
	 * Fetch covers for a specific genre via AJAX.
	 */
	public function getCoversForGenre(Request $request, $genreSlug)
	{
		// Convert slug (e.g., "science-fiction") to lowercase category name for DB query (e.g., "science fiction")
		$dbQueryGenreName = Str::lower(str_replace('-', ' ', $genreSlug));
		$covers = Cover::whereJsonContains('categories', $dbQueryGenreName)
			->inRandomOrder()
			->take(16) // Number of items for the slider (changed from 4 to 16)
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
				'limited_name' => Str::limit($cover->name, 30), // Add any other fields needed by the JS template
				// Ensure all fields used by the JS template are present
				'caption' => $cover->caption, // Example: if caption is needed
			];
		});

		return response()->json(['covers' => $formattedCovers]);
	}
}
