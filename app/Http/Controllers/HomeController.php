<?php namespace App\Http\Controllers;

use App\Models\Cover;
use App\Models\Template; // Ensure this is present
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
				foreach ($cover->categories as $category) {
					$categoryName = Str::title(trim($category));
					if (!empty($categoryName)) {
						$genreCounts[$categoryName] = ($genreCounts[$categoryName] ?? 0) + 1;
					}
				}
			}
		}
		$filteredGenreCounts = array_filter($genreCounts, function ($count) {
			return $count >= 4;
		});
		//sort by count descending
		arsort($filteredGenreCounts);

		$coversForFirstTab = [];
		$firstGenreName = null;
		if (!empty($filteredGenreCounts)) {
			$firstGenreName = array_key_first($filteredGenreCounts);
			if ($firstGenreName) {
				$dbQueryGenreName = Str::lower($firstGenreName);
				$coversForFirstTab[$firstGenreName] = Cover::with('templates') // Eager load templates
				->whereJsonContains('categories', $dbQueryGenreName)
					->where('cover_type_id', 1) // Added this condition
					->inRandomOrder()
					->take(18)
					->get();

				foreach ($coversForFirstTab[$firstGenreName] as &$cover) {
					if ($cover->image_path) {
						$cover['mockup'] = str_replace('covers/', 'cover-mockups/', $cover->image_path);
						$cover['mockup'] = str_replace('.jpg', '-front-mockup.png', $cover['mockup']);
					} else {
						$cover['mockup'] = 'path/to/default/mockup.png';
					}
					// Add random template overlay URL
					$cover['random_template_overlay_url'] = null;
					if ($cover->templates->isNotEmpty()) {
						$randomTemplate = $cover->templates->random();
						// Assuming template's thumbnail_path stores the transparent PNG overlay
						if ($randomTemplate->thumbnail_path) {
							$cover['random_template_overlay_url'] = asset('storage/' . $randomTemplate->thumbnail_path);
						}
					}
				}
				unset($cover);
			}
		}

		$newArrivals = Cover::with('templates') // Eager load templates
		->orderBy('created_at', 'desc')
			->where('cover_type_id', 1)
			->take(6)
			->get();

		foreach ($newArrivals as &$cover) {
			if ($cover->image_path) {
				$cover['mockup'] = str_replace('covers/', 'cover-mockups/', $cover->image_path);
				$cover['mockup'] = str_replace('.jpg', '-front-mockup.png', $cover['mockup']);
			} else {
				$cover['mockup'] = 'path/to/default/mockup.png'; // Fallback mockup
			}
			// Add random template overlay URL
			$cover['random_template_overlay_url'] = null;
			if ($cover->templates->isNotEmpty()) {
				$randomTemplate = $cover->templates->random();
				if ($randomTemplate->thumbnail_path) {
					$cover['random_template_overlay_url'] = asset('storage/' . $randomTemplate->thumbnail_path);
				}
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
		$genreDisplayName = $request->query('name');
		if (empty($genreDisplayName)) {
			Log::warning("Required 'name' query parameter missing or empty for genre slug: " . $genreSlug . ". AJAX call might be misconfigured from JS.");
			return response()->json(['covers' => [], 'message' => 'Genre specification is incomplete. The "name" query parameter is missing.'], 400);
		}

		$dbQueryGenreName = Str::lower($genreDisplayName);
		$covers = Cover::with('templates') // Eager load templates
		->where(function ($query) use ($dbQueryGenreName, $genreDisplayName) { // Group OR conditions
			$query->whereJsonContains('categories', $dbQueryGenreName)
				->orWhereJsonContains('categories', $genreDisplayName);
		})
			->where('cover_type_id', 1)
			->inRandomOrder()
			->take(18)
			->get();

		$formattedCovers = $covers->map(function ($cover) {
			$mockupPath = 'path/to/default/mockup.png'; // Fallback
			if ($cover->image_path) {
				$mockupPath = str_replace('covers/', 'cover-mockups/', $cover->image_path);
				$mockupPath = str_replace('.jpg', '-front-mockup.png', $mockupPath);
			}

			$randomTemplateOverlayUrl = null;
			if ($cover->templates->isNotEmpty()) {
				$randomTemplate = $cover->templates->random();
				if ($randomTemplate->thumbnail_path) { // Assuming thumbnail_path is the overlay
					$randomTemplateOverlayUrl = asset('storage/' . $randomTemplate->thumbnail_path); // Full URL
				}
			}

			return [
				'id' => $cover->id,
				'name' => $cover->name,
				'mockup' => $mockupPath, // This is relative to /storage/ for JS
				'random_template_overlay_url' => $randomTemplateOverlayUrl, // Full URL for direct use in <img> src
				'show_url' => route('covers.show', $cover->id),
				'limited_name' => Str::limit($cover->caption, 40),
				'caption' => $cover->caption,
			];
		});

		return response()->json(['covers' => $formattedCovers]);
	}
}
