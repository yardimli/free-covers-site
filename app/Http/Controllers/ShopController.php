<?php namespace App\Http\Controllers;

use App\Models\Cover;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopController extends Controller
{
	public function index(Request $request)
	{
		$searchTerm = $request->input('s');
		$sortBy = $request->input('orderby', 'latest'); // 'latest', 'name_asc', 'name_desc'
		$selectedCategory = $request->input('category'); // This will be TitleCase from the dropdown

		// Fetch all unique categories for the filter dropdown
		$allCoversForCategories = Cover::select(['categories'])
			->where('cover_type_id', 1) // Assuming Kindle covers
			->whereNotNull('categories') // Ensure categories column is not null
			->whereRaw("JSON_LENGTH(categories) > 0") // Ensure categories array is not empty
			->get();

		$categoryCounts = [];
		foreach ($allCoversForCategories as $cover) {
			if (is_array($cover->categories)) {
				foreach ($cover->categories as $category) {
					$categoryName = Str::title(trim($category)); // Normalize to TitleCase for keys
					if (!empty($categoryName)) {
						$categoryCounts[$categoryName] = ($categoryCounts[$categoryName] ?? 0) + 1;
					}
				}
			}
		}
		// Filter categories that have at least one cover
		$availableCategories = array_filter($categoryCounts, function ($count) {
			return $count >= 1;
		});
		ksort($availableCategories); // Sort category names alphabetically

		$query = Cover::with('templates')->where('cover_type_id', 1);

		if ($searchTerm) {
			$query->where(function ($q) use ($searchTerm) {
				$q->where('name', 'LIKE', "%{$searchTerm}%")
					->orWhere('caption', 'LIKE', "%{$searchTerm}%")
					->orWhereJsonContains('keywords', $searchTerm); // Search in keywords array
			});
		}

		if ($selectedCategory) {
			// $selectedCategory is TitleCase (e.g., "Fiction")
			$dbQueryCategoryNameLower = Str::lower($selectedCategory); // e.g., "fiction"

			$query->where(function ($q) use ($dbQueryCategoryNameLower, $selectedCategory) {
				// Search for the lowercase version
				$q->whereJsonContains('categories', $dbQueryCategoryNameLower);
				// If the lowercase version is different from the original TitleCase version,
				// also search for the TitleCase version. This covers categories stored as "Fiction".
				if ($dbQueryCategoryNameLower !== $selectedCategory) {
					$q->orWhereJsonContains('categories', $selectedCategory);
				}
				// As an alternative, more direct way if $selectedCategory is always TitleCase:
				// $q->whereJsonContains('categories', $dbQueryCategoryNameLower)
				//   ->orWhereJsonContains('categories', $selectedCategory);
			});
		}

		switch ($sortBy) {
			case 'name_asc':
				$query->orderBy('name', 'asc');
				break;
			case 'name_desc':
				$query->orderBy('name', 'desc');
				break;
			case 'latest':
			default:
				$query->orderBy('created_at', 'desc');
				break;
		}

		$covers = $query->paginate(20)->withQueryString(); // 20 items per page

		// Prepare covers with mockup and random template overlay
		foreach ($covers as $cover) {
			if ($cover->image_path) {
				$cover->mockup_url = asset('storage/' . str_replace(['covers/', '.jpg'], ['cover-mockups/', '-front-mockup.png'], $cover->image_path));
			} else {
				$cover->mockup_url = asset('template/assets/img/placeholder-mockup.png'); // Fallback mockup
			}
			$cover->random_template_overlay_url = null;
			if ($cover->templates->isNotEmpty()) {
				$randomTemplate = $cover->templates->random();
				if ($randomTemplate->thumbnail_path) {
					$cover->random_template_overlay_url = asset('storage/' . $randomTemplate->thumbnail_path);
				}
			}
		}

		return view('shop.index', compact('covers', 'searchTerm', 'sortBy', 'availableCategories', 'selectedCategory'));
	}
}
