<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Str;
	use Carbon\Carbon;
	use App\Models\Cover;
	use App\Models\Favorite; // Added

	class DashboardController extends Controller
	{
		public function index(Request $request)
		{
			$user = Auth::user();

			// --- Fetch Real eBook Covers ---
			$ebookCovers = Cover::with('templates')
				->where('cover_type_id', 1)
				->whereNotNull('mockup_2d_path')
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
				if (is_string($cover->updated_at)) {
					$cover->updated_at = Carbon::parse($cover->updated_at);
				}
			}

			// --- Fetch Real Print Covers ---
			$printCovers = Cover::with('templates')
				->where('cover_type_id', 2)
				->whereNotNull('mockup_2d_path')
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
				'ebookCovers' => $ebookCovers,
				'printCovers' => $printCovers,
				'favoriteCoversData' => $favoriteCoversData, // Use the new variable
				'footerClass' => 'bj_footer_area_two',
			]);
		}
	}
