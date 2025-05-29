<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Str;
	use Carbon\Carbon;
	use App\Models\Cover;
	use App\Models\Favorite;
	use App\Models\UserDesign;
	use App\Services\ImageUploadService;

	class DashboardController extends Controller
	{
		public function index(Request $request)
		{
			$user = Auth::user();

			$userSavedDesigns = UserDesign::where('user_id', $user->id)
				->orderBy('updated_at', 'desc')
				->take(12) // Example: limit to 12, or implement pagination
				->get();

			$imageUploadService = app(ImageUploadService::class); // Get service instance
			foreach ($userSavedDesigns as $design) {
				$design->preview_image_url = $imageUploadService->getUrl($design->preview_image_path);
				if (is_string($design->updated_at)) {
					$design->updated_at = Carbon::parse($design->updated_at);
				}
				if (is_string($design->created_at)) { // Also parse created_at if needed
					$design->created_at = Carbon::parse($design->created_at);
				}
			}

			// --- Fetch Real Favorite Covers ---
			$userFavorites = Favorite::with(['cover.templates', 'template']) // Eager load cover, its templates, and the specific favorited template
			->where('user_id', $user->id)
				->orderBy('created_at', 'desc')
				->take(12) // Show up to 12 favorites, adjust as needed
				->get();

			$favoriteCoversData = $userFavorites->map(function ($favorite) {
				$cover = $favorite->cover;
				$specificFavoritedTemplate = $favorite->template;

				// Create a new object or clone to avoid modifying shared Cover instances from cache/other queries
				$displayCover = new \stdClass();
				$displayCover->id = $cover->id;
				$displayCover->name = $cover->name;
				$displayCover->mockup_2d_path = $cover->mockup_2d_path;
				$displayCover->caption = $cover->caption; // If needed for tooltips or display
				$displayCover->updated_at = $cover->updated_at; // For consistency

				$displayCover->active_template_overlay_url = null;
				$displayCover->favorited_template_name = null;
				$displayCover->favorite_id = $favorite->id; // ID of the Favorite record itself for deletion
				$displayCover->favorited_template_id = $favorite->template_id; // ID of the template that was part of the favorite

				if ($specificFavoritedTemplate && $specificFavoritedTemplate->cover_image_path) {
					$displayCover->active_template_overlay_url = asset('storage/' . $specificFavoritedTemplate->cover_image_path);
					$displayCover->favorited_template_name = $specificFavoritedTemplate->name;
				}
				// If no specific template was favorited ($favorite->template_id is null),
				// active_template_overlay_url will remain null, which is correct.

				// Ensure updated_at is Carbon
				if (is_string($displayCover->updated_at)) {
					$displayCover->updated_at = Carbon::parse($displayCover->updated_at);
				}
				// Add pivot-like data for "Favorited on" date
				$displayCover->pivot = (object)['created_at' => $favorite->created_at];

				return $displayCover;
			});


			return view('dashboard', [
				'user' => $user,
				'favoriteCoversData' => $favoriteCoversData,
				'userSavedDesigns' => $userSavedDesigns,
				'footerClass' => 'bj_footer_area_two',
			]);
		}
	}
