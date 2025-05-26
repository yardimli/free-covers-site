<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Str; // For Str::limit if needed, though usually in Blade
	use Carbon\Carbon; // For creating dates
	use App\Models\Cover; // Added for real data
// Template model is not directly used here but accessed via Cover relationship

	class DashboardController extends Controller
	{
		public function index(Request $request)
		{
			$user = Auth::user();

			// --- Fetch Real eBook Covers ---
			$ebookCovers = Cover::with('templates')
				->where('cover_type_id', 1) // Assuming 1 is for eBook
				->whereNotNull('mockup_2d_path') // Ensure there's a base image to show
				->where('mockup_2d_path', '!=', '')
				->inRandomOrder()
				->take(4)
				->get();

			foreach ($ebookCovers as $cover) {
				$cover->active_template_overlay_url = null;
				if ($cover->templates->isNotEmpty()) {
					$randomTemplate = $cover->templates->random();
					if ($randomTemplate->cover_image_path) {
						$cover->active_template_overlay_url = asset('storage/' . $randomTemplate->cover_image_path);
					}
				}
				// Ensure updated_at is a Carbon instance if not already (it should be by default)
				if (is_string($cover->updated_at)) {
					$cover->updated_at = Carbon::parse($cover->updated_at);
				}
			}

			// --- Fetch Real Print Covers ---
			$printCovers = Cover::with('templates')
				->where('cover_type_id', 2) // Assuming 2 is for Print
				->whereNotNull('mockup_2d_path') // Ensure there's a base image to show
				->where('mockup_2d_path', '!=', '')
				->inRandomOrder()
				->take(2)
				->get();

			foreach ($printCovers as $cover) {
				$cover->active_template_overlay_url = null;
				if ($cover->templates->isNotEmpty()) {
					$randomTemplate = $cover->templates->random();
					if ($randomTemplate->cover_image_path) {
						$cover->active_template_overlay_url = asset('storage/' . $randomTemplate->cover_image_path);
					}
				}
				if (is_string($cover->updated_at)) {
					$cover->updated_at = Carbon::parse($cover->updated_at);
				}
			}

			// --- Fetch Real Favorite Covers (simulated as random for now) ---
			// If you have a proper favorites relationship on User model, e.g., $user->favoriteCovers()
			// you would use: $favoriteCovers = $user->favoriteCovers()->with('templates')->take(3)->get();
			// For now, we'll pick random covers and simulate the pivot data.
			$favoriteCovers = Cover::with('templates')
				->whereNotNull('mockup_2d_path') // Ensure there's a base image to show
				->where('mockup_2d_path', '!=', '')
				->inRandomOrder()
				->take(3)
				->get();

			foreach ($favoriteCovers as $cover) {
				$cover->active_template_overlay_url = null;
				if ($cover->templates->isNotEmpty()) {
					$randomTemplate = $cover->templates->random();
					if ($randomTemplate->cover_image_path) {
						$cover->active_template_overlay_url = asset('storage/' . $randomTemplate->cover_image_path);
					}
				}
				// Simulate pivot data for favorites
				$cover->pivot = (object)[
					'created_at' => Carbon::now()->subWeeks(rand(1, 5)),
				];
			}

			// --- Dummy User Images (kept as is, as UserImage model/logic is not defined) ---
			$userImages = collect([]);
			if (true) { // Control whether to show dummy data or empty state
				for ($i = 1; $i <= 5; $i++) {
					$userImages->push((object)[
						'id' => 400 + $i,
						'name' => "my_uploaded_image_{$i}.jpg",
						'path' => 'user_uploads/sample_image.jpg', // Relative to storage/app/public
						'created_at' => Carbon::now()->subHours($i * 10),
					]);
				}
				if ($userImages->isNotEmpty()) {
					$userImages = $userImages->map(function ($item, $key) {
						// Using placeholder for display_url as in original dummy data
						// If you have real images, you'd use asset('storage/' . $item->path)
						$item->display_url = 'https://via.placeholder.com/150x120.png/17a2b8/ffffff?text=Image+' . ($key + 1);
						// If you want to use the 'path' for real images, ensure your blade uses asset('storage/' . $image->path)
						// For this example, we'll keep the placeholder logic for display_url
						return $item;
					});
				}
			}

			return view('dashboard', [
				'user' => $user,
				'ebookCovers' => $ebookCovers,
				'printCovers' => $printCovers,
				'favoriteCovers' => $favoriteCovers,
				'userImages' => $userImages,
				'footerClass' => 'bj_footer_area_two',
			]);
		}
	}
