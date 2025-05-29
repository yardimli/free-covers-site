<?php namespace App\Http\Controllers;

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
		// Eager load templates and coverType for the single cover view
		$cover->load('templates', 'coverType');
		$cover->active_template_overlay_url = null;
		$activeTemplateForView = null;
		$activeTemplateFullCoverOverlayUrl = null; // New variable for full cover overlay

		// Determine the active template for the main view
		if ($template) {
			if ($cover->templates->contains($template->id)) {
				$activeTemplateForView = $template;
			} else {
				Log::warning("Template ID {$template->id} (Name: {$template->name}) requested for cover {$cover->id}, but it's not associated. Falling back.");
				if ($cover->templates->isNotEmpty()) {
					$activeTemplateForView = $cover->templates->random();
				}
			}
		} elseif ($cover->templates->isNotEmpty()) {
			$activeTemplateForView = $cover->templates->random();
		}

		if ($activeTemplateForView) {
			if ($activeTemplateForView->cover_image_path) {
				$cover->active_template_overlay_url = asset('storage/' . $activeTemplateForView->cover_image_path);
			}
			// Check for full_cover_image_path for the active template
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

		// Prepare cover variations with each associated template
		$coverVariations = [];
		if ($cover->templates->isNotEmpty()) {
			foreach ($cover->templates as $t) {
				$variationOverlayUrl = null;
				if ($t->cover_image_path) {
					$variationOverlayUrl = asset('storage/' . $t->cover_image_path);
				}
				$coverVariations[] = [
					'template_overlay_url' => $variationOverlayUrl,
					'template_name' => $t->name,
					'template_id' => $t->id,
				];
			}
		}

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
			'coverVariations',
			'activeTemplateForView',
			'activeTemplateFullCoverOverlayUrl', // Pass to view
			'keywordData',
			'isFavorited' // Pass to view
		));
	}
}
