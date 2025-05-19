<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use App\Models\Cover; // Assuming you'll fetch user's covers
// use App\Models\UserImage; // If you create a model for user-uploaded images

	class DashboardController extends Controller
	{
		public function index(Request $request)
		{
			$user = Auth::user();

			// --- Fetch User's eBook Covers ---
			// This is a placeholder. You'll need a way to associate covers with users.
			// Option 1: Add a user_id to the 'covers' table.
			// Option 2: If covers are created through a "project" or "session" linked to the user.
			// For now, let's assume a 'user_id' column on the 'covers' table.
			$ebookCovers = Cover::where('user_id', $user->id)
				->whereHas('coverType', function ($query) {
					$query->where('type_name', 'ebook'); // Or based on cover_type_id
				})
				->with('templates') // Eager load for overlays
				->orderBy('updated_at', 'desc')
				->take(6) // Example: show recent 6
				->get();

			foreach ($ebookCovers as $cover) {
				if ($cover->image_path) {
					$cover->mockup_url = asset('storage/' . str_replace(['covers/', '.jpg'], ['cover-mockups/', '-front-mockup.png'], $cover->image_path));
				} else {
					$cover->mockup_url = asset('template/assets/img/placeholder-mockup.png');
				}
				$cover->active_template_overlay_url = null;
				if ($cover->templates->isNotEmpty()) {
					// You might want to store the "last used" or "primary" template for a user's cover
					$activeTemplate = $cover->templates->first(); // Or random, or a specific one
					if ($activeTemplate && $activeTemplate->thumbnail_path) {
						$cover->active_template_overlay_url = asset('storage/' . $activeTemplate->thumbnail_path);
					}
				}
			}

			// --- Fetch User's Print Covers (similar logic) ---
			$printCovers = Cover::where('user_id', $user->id)
				->whereHas('coverType', function ($query) {
					$query->where('type_name', 'print'); // Or based on cover_type_id
				})
				->with('templates')
				->orderBy('updated_at', 'desc')
				->take(6)
				->get();
			// Add mockup_url and active_template_overlay_url for printCovers too...


			// --- Fetch User's Favorites ---
			// This requires a pivot table like 'user_favorite_covers' (user_id, cover_id)
			// Or a JSON column 'favorited_by_users' on the covers table (less ideal for querying)
			// For now, let's assume a pivot table and a 'favorites' relationship on the User model.
			$favoriteCovers = $user->favoriteCovers()->with('templates')->take(6)->get();
			// Add mockup_url and active_template_overlay_url for favoriteCovers too...


			// --- Fetch User's Images ---
			// This would require a new table 'user_images' (id, user_id, path, name, created_at, updated_at)
			// and a UserImage model.
			// $userImages = UserImage::where('user_id', $user->id)->orderBy('created_at', 'desc')->take(8)->get();
			$userImages = collect([]); // Placeholder

			return view('dashboard', [
				'user' => $user,
				'ebookCovers' => $ebookCovers,
				'printCovers' => $printCovers,
				'favoriteCovers' => $favoriteCovers,
				'userImages' => $userImages,
				'footerClass' => 'bj_footer_area_two', // Example for different footer style
			]);
		}
	}
