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
		$selectedKeyword = $request->input('keyword'); // New: For keyword filtering

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
			$dbQueryCategoryNameLower = Str::lower($selectedCategory);
			$query->where(function ($q) use ($dbQueryCategoryNameLower, $selectedCategory) {
				$q->whereJsonContains('categories', $dbQueryCategoryNameLower);
				if ($dbQueryCategoryNameLower !== $selectedCategory) {
					$q->orWhereJsonContains('categories', $selectedCategory);
				}
			});
		}

		// New: Filter by selected keyword
		if ($selectedKeyword) {
			$query->where(function ($q) use ($selectedKeyword) {
				// Search for the keyword as provided (likely TitleCase from URL)
				$q->whereJsonContains('keywords', $selectedKeyword);

				// Also search for the lowercase version
				$lowerKeyword = Str::lower($selectedKeyword);
				if ($lowerKeyword !== $selectedKeyword) { // Avoid redundant query if already lowercase
					$q->orWhereJsonContains('keywords', $lowerKeyword);
				}
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
			$cover->random_template_overlay_url = null;
			if ($cover->templates->isNotEmpty()) {
				$randomTemplate = $cover->templates->random();
				if ($randomTemplate->cover_image_path) {
					$cover->random_template_overlay_url = asset('storage/' . $randomTemplate->cover_image_path);
				}
			}
		}

		return view('shop.index', compact('covers', 'searchTerm', 'sortBy', 'availableCategories', 'selectedCategory', 'selectedKeyword'));
	}
}
