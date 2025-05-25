<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Str; // For Str::limit if needed, though usually in Blade
	use Carbon\Carbon; // For creating dates

	class DashboardController extends Controller
	{
		public function index(Request $request)
		{
			$user = Auth::user(); // We'll still get the authenticated user

			// --- Dummy eBook Covers ---
			$ebookCovers = collect([]);
			if (true) { // Control whether to show dummy data or empty state
				for ($i = 1; $i <= 4; $i++) {
					$ebookCovers->push((object)[
						'id' => 100 + $i,
						'name' => "My Awesome eBook Title {$i}: A Gripping Tale of Adventure and Mystery",
						'active_template_overlay_url' => ($i % 2 == 0) ? asset('template/assets/img/overlays/sample-overlay-1.png') : null, // Example overlay
						'updated_at' => Carbon::now()->subDays($i * 5),
						'templates' => collect([ // Mimic templates collection for overlay logic
							(object)['thumbnail_path' => 'template_thumbnails/overlay_A.png'],
							(object)['thumbnail_path' => 'template_thumbnails/overlay_B.png']
						]),
						// Add any other properties your Blade view might expect from a Cover model
					]);
				}
			}

			// --- Dummy Print Covers ---
			$printCovers = collect([]);
			if (true) { // Control whether to show dummy data or empty state
				for ($i = 1; $i <= 2; $i++) {
					$printCovers->push((object)[
						'id' => 200 + $i,
						'name' => "The Definitive Print Edition {$i}",
						'active_template_overlay_url' => null, // Print covers might not always have overlays in the same way
						'updated_at' => Carbon::now()->subDays($i * 3),
						'templates' => collect([]), // Empty if not applicable
					]);
				}
			}

			// --- Dummy Favorite Covers ---
			$favoriteCovers = collect([]);
			if (true) { // Control whether to show dummy data or empty state
				for ($i = 1; $i <= 3; $i++) {
					$favoriteCovers->push((object)[
						'id' => 300 + $i,
						'name' => "A Favorite Story Vol. {$i}",
						'active_template_overlay_url' => ($i % 2 != 0) ? asset('template/assets/img/overlays/sample-overlay-2.png') : null,
						'templates' => collect([
							(object)['thumbnail_path' => 'template_thumbnails/overlay_C.png']
						]),
						'pivot' => (object)[ // Mimic pivot data
							'created_at' => Carbon::now()->subWeeks($i),
						],
					]);
				}
			}

			// --- Dummy User Images ---
			$userImages = collect([]);
			if (true) { // Control whether to show dummy data or empty state
				for ($i = 1; $i <= 5; $i++) {
					$userImages->push((object)[
						'id' => 400 + $i,
						'name' => "my_uploaded_image_{$i}.jpg",
						// Use a real placeholder or a generic one
						'path' => 'user_uploads/sample_image.jpg', // Relative to storage/app/public
						// For a direct asset link if you place dummy images in public/images/dummy_uploads:
						// 'asset_path' => asset("images/dummy_uploads/user_image_{$i}.jpg"),
						'created_at' => Carbon::now()->subHours($i * 10),
					]);
				}
				// If using asset_path, you'd need to create these dummy images in public/images/dummy_uploads
				// For 'path', you'd need to ensure the Blade view correctly constructs the URL with asset('storage/' . $image->path)
			}
			// To use the 'path' correctly with asset('storage/...') ensure you have a symlink
			// and place dummy images in `storage/app/public/user_uploads/`
			// For simplicity, I'll adjust the blade to use a generic placeholder if path is not a full URL
			// Or, you can provide full placeholder URLs here:
			if ($userImages->isNotEmpty()) {
				$userImages = $userImages->map(function ($item, $key) {
					$item->display_url = 'https://via.placeholder.com/150x120.png/17a2b8/ffffff?text=Image+' . ($key + 1);
					return $item;
				});
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
