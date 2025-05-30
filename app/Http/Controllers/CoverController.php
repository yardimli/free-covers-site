<?php

	namespace App\Http\Controllers;

	use App\Models\Cover;
	use App\Models\Favorite;
	use App\Models\Template; // Added use
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Str;
	use Illuminate\Support\Facades\Log; // Added for logging

	class CoverController extends Controller
	{
		/**
		 * Display the specified cover.
		 *
		 * @param \App\Models\Cover $cover
		 * @param \App\Models\Template|null $template Optional template passed via URL
		 * @return \Illuminate\View\View
		 */
		public function show(Cover $cover, Template $template = null)
		{
			// Eager load templates (assigned) and coverType for the single cover view
			$cover->load('templates', 'coverType');

			$cover->active_template_overlay_url = null;
			$activeTemplateForView = null;
			$activeTemplateFullCoverOverlayUrl = null;

			// Determine the active template for the main view
			if ($template) {
				// Check if the requested template is *assignable* (i.e., exists and matches cover type)
				// And if it's *actually assigned* to this cover
				$potentialActiveTemplate = Template::find($template->id);
				if ($potentialActiveTemplate && $potentialActiveTemplate->cover_type_id == $cover->cover_type_id) {
					$activeTemplateForView = $potentialActiveTemplate;
				} else {
					Log::warning("Template ID {$template->id} (Name: {$template->name}) requested for cover {$cover->id}, but it's not compatible or not found. Falling back.");
					// Fallback to a random *assigned* template if the requested one isn't suitable
					if ($cover->templates->isNotEmpty()) {
						$activeTemplateForView = $cover->templates->random();
					}
				}
			} elseif ($cover->templates->isNotEmpty()) {
				$activeTemplateForView = $cover->templates->random();
			} elseif ($cover->coverType && Template::where('cover_type_id', $cover->cover_type_id)->exists()) {
				// If no template assigned, but templates of this type exist, pick a random one of that type
				// This ensures a style is shown if possible, even if not explicitly assigned
				$activeTemplateForView = Template::where('cover_type_id', $cover->cover_type_id)->inRandomOrder()->first();
			}


			if ($activeTemplateForView) {
				if ($activeTemplateForView->cover_image_path) {
					$cover->active_template_overlay_url = asset('storage/' . $activeTemplateForView->cover_image_path);
				}
				if ($activeTemplateForView->full_cover_image_path) {
					$activeTemplateFullCoverOverlayUrl = asset('storage/' . $activeTemplateForView->full_cover_image_path);
				}
			}

			// Check if the current cover (with active template) is favorited by the user
			$isFavorited = false;
			if (Auth::check()) {
				$isFavorited = Favorite::where('user_id', Auth::id())
					->where('cover_id', $cover->id)
					->where('template_id', $activeTemplateForView ? $activeTemplateForView->id : null)
					->exists();
			}

			// Fetch all compatible templates for the "Available Styles" section
			$compatibleCoverTypeId = $cover->cover_type_id ?: 1; // Default to Kindle if cover has no type
			$allCompatibleTemplates = Template::where('cover_type_id', $compatibleCoverTypeId)
				->orderBy('name')
				->get();

			// Fetch related covers
			$relatedCoversQuery = Cover::with('templates')
				->where('id', '!=', $cover->id)
				->where('cover_type_id', $cover->cover_type_id ?: 1);

			if ($cover->categories && !empty(array_filter($cover->categories))) {
				$primaryCategory = Str::lower($cover->categories[0]);
				$relatedCoversQuery->whereJsonContains('categories', $primaryCategory);
			}
			$relatedCovers = $relatedCoversQuery->inRandomOrder()->take(4)->get();

			if ($relatedCovers->count() < 4) {
				$additionalCoversNeeded = 4 - $relatedCovers->count();
				$existingIds = $relatedCovers->pluck('id')->push($cover->id)->all();
				$additionalCovers = Cover::with('templates')
					->whereNotIn('id', $existingIds)
					->where('cover_type_id', $cover->cover_type_id ?: 1)
					->inRandomOrder()
					->take($additionalCoversNeeded)
					->get();
				$relatedCovers = $relatedCovers->merge($additionalCovers);
			}

			foreach ($relatedCovers as $related) {
				$related->random_template_overlay_url = null;
				if ($related->templates->isNotEmpty()) {
					$randomTemplate = $related->templates->random();
					if ($randomTemplate->cover_image_path) {
						$related->random_template_overlay_url = asset('storage/' . $randomTemplate->cover_image_path);
					}
				}
			}

			// Prepare keyword data
			$keywordData = [];
			if ($cover->keywords && is_array($cover->keywords) && !empty(array_filter($cover->keywords))) {
				$uniqueOriginalKeywords = array_values(array_unique(array_map('trim', array_filter($cover->keywords))));
				$processedDisplayNames = [];
				foreach ($uniqueOriginalKeywords as $originalKeyword) {
					if (empty($originalKeyword)) continue;
					$displayName = Str::title($originalKeyword);
					if (in_array($displayName, $processedDisplayNames)) continue;
					$processedDisplayNames[] = $displayName;
					$termsForCounting = array_unique([$displayName, Str::lower($displayName)]);
					$count = Cover::where(function ($query) use ($termsForCounting) {
						$firstTerm = true;
						foreach ($termsForCounting as $term) {
							if ($firstTerm) {
								$query->whereJsonContains('keywords', $term);
								$firstTerm = false;
							} else {
								$query->orWhereJsonContains('keywords', $term);
							}
						}
					})->count();
					$keywordData[] = ['displayName' => $displayName, 'count' => $count];
				}
			}

			return view('covers.show', compact(
				'cover',
				'relatedCovers',
				'activeTemplateForView',
				'activeTemplateFullCoverOverlayUrl',
				'keywordData',
				'isFavorited',
				'allCompatibleTemplates' // Pass all compatible templates
			));
		}
	}
