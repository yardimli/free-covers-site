<?php namespace App\Http\Controllers;

use App\Models\Cover;
use App\Models\Template; // Added
use Illuminate\Http\Request;
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
		$cover->active_template_overlay_url = null; // Renamed from random_template_overlay_url
		$activeTemplateForView = null; // Renamed from randomTemplateForView

		// Determine the active template for the main view
		if ($template) {
			// A template ID was present in the URL and resolved to a Template model
			// Verify this template actually belongs to this cover
			if ($cover->templates->contains($template->id)) {
				$activeTemplateForView = $template;
			} else {
				// Log or handle invalid template for this cover
				Log::warning("Template ID {$template->id} (Name: {$template->name}) requested for cover {$cover->id}, but it's not associated. Falling back.");
				// Fallback to random if cover has templates, or null if not
				if ($cover->templates->isNotEmpty()) {
					$activeTemplateForView = $cover->templates->random();
				}
			}
		} elseif ($cover->templates->isNotEmpty()) {
			// No template ID in URL, pick random if available
			$activeTemplateForView = $cover->templates->random();
		}

		// If $activeTemplateForView is still null here, it means either:
		// 1. No template ID in URL AND cover has no templates.
		// 2. Invalid template ID in URL (and not associated) AND cover has no templates.
		if ($activeTemplateForView && $activeTemplateForView->cover_image_path) {
			$cover->active_template_overlay_url = asset('storage/' . $activeTemplateForView->cover_image_path);
		}

		// Prepare cover variations with each associated template
		$coverVariations = [];
		if ($cover->templates->isNotEmpty()) {
			foreach ($cover->templates as $t) { // Changed $template to $t to avoid conflict
				$variationOverlayUrl = null;
				if ($t->cover_image_path) {
					$variationOverlayUrl = asset('storage/' . $t->cover_image_path);
				}
				$coverVariations[] = [
					'template_overlay_url' => $variationOverlayUrl,
					'template_name' => $t->name, // For alt text or title
					'template_id' => $t->id, // Pass template ID for actions
				];
			}
		}

		// Fetch related covers (e.g., 4 random covers from the same cover_type_id, excluding the current one)
		$relatedCoversQuery = Cover::with('templates')
			->where('id', '!=', $cover->id)
			->where('cover_type_id', $cover->cover_type_id ?: 1); // Use actual cover's type or fallback

		if ($cover->categories && !empty(array_filter($cover->categories))) {
			$primaryCategory = Str::lower($cover->categories[0]);
			$relatedCoversQuery->whereJsonContains('categories', $primaryCategory);
		}
		$relatedCovers = $relatedCoversQuery->inRandomOrder()
			->take(4)
			->get();

		// If not enough related covers by category, fetch random ones
		if ($relatedCovers->count() < 4) {
			$additionalCoversNeeded = 4 - $relatedCovers->count();
			$existingIds = $relatedCovers->pluck('id')->push($cover->id)->all();
			$additionalCovers = Cover::with('templates')
				->whereNotIn('id', $existingIds)
				->where('cover_type_id', $cover->cover_type_id ?: 1) // Use actual cover's type or fallback
				->inRandomOrder()
				->take($additionalCoversNeeded)
				->get();
			$relatedCovers = $relatedCovers->merge($additionalCovers);
		}

		foreach ($relatedCovers as $related) {
			$related->random_template_overlay_url = null; // Keep this for related covers as they always show random
			if ($related->templates->isNotEmpty()) {
				$randomTemplate = $related->templates->random();
				if ($randomTemplate->cover_image_path) {
					$related->random_template_overlay_url = asset('storage/' . $randomTemplate->cover_image_path);
				}
			}
		}

		// Prepare keyword data with counts
		$keywordData = [];
		if ($cover->keywords && is_array($cover->keywords) && !empty(array_filter($cover->keywords))) {
			$uniqueOriginalKeywords = array_values(array_unique(array_map('trim', array_filter($cover->keywords))));
			$processedDisplayNames = []; // To track display names already handled

			foreach ($uniqueOriginalKeywords as $originalKeyword) {
				if (empty($originalKeyword)) {
					continue;
				}

				$displayName = Str::title($originalKeyword);

				if (in_array($displayName, $processedDisplayNames)) {
					continue;
				}
				$processedDisplayNames[] = $displayName;

				$termsForCounting = array_unique([
					$displayName,
					Str::lower($displayName)
				]);

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

				$keywordData[] = [
					'displayName' => $displayName,
					'count' => $count,
				];
			}
		}

		return view('covers.show', compact('cover', 'relatedCovers', 'coverVariations', 'activeTemplateForView', 'keywordData'));
	}
}
