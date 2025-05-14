<?php

	namespace App\Http\Controllers;

	use App\Models\Cover;
	use Illuminate\Http\Request;
	use Illuminate\Support\Str;

	class ShopController extends Controller
	{
		public function index(Request $request)
		{
			$searchTerm = $request->input('s');
			$sortBy = $request->input('orderby', 'latest'); // 'latest', 'name_asc', 'name_desc'

			$query = Cover::with('templates')->where('cover_type_id', 1); // Assuming cover_type_id 1 for Kindle covers

			if ($searchTerm) {
					$query->where(function ($q) use ($searchTerm) {
						$q->where('keywords', 'LIKE', "%{$searchTerm}%")
							->orWhere('caption', 'LIKE', "%{$searchTerm}%");
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
					if ($randomTemplate->thumbnail_path) { // Assuming template's thumbnail_path is the overlay
						$cover->random_template_overlay_url = asset('storage/' . $randomTemplate->thumbnail_path);
					}
				}
			}

			return view('shop.index', compact('covers', 'searchTerm', 'sortBy'));
		}
	}
