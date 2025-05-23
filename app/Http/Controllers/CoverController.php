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

		if ($cover->image_path) {
			$cover->mockup_url = asset('storage/' . str_replace(['covers/', '.jpg'], ['cover-mockups/', '-front-mockup.png'], $cover->image_path));
		} else {
			$cover->mockup_url = asset('template/assets/img/placeholder-mockup.png'); // Fallback mockup
		}

		$cover->active_template_overlay_url = null; // Renamed from random_template_overlay_url
		$activeTemplateForView = null; // Renamed from randomTemplateForView

		// Determine the active template for the main view
		if ($template) { // A template ID was present in the URL and resolved to a Template model
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
		} elseif ($cover->templates->isNotEmpty()) { // No template ID in URL, pick random if available
			$activeTemplateForView = $cover->templates->random();
		}
		// If $activeTemplateForView is still null here, it means either:
		// 1. No template ID in URL AND cover has no templates.
		// 2. Invalid template ID in URL (and not associated) AND cover has no templates.

		if ($activeTemplateForView && $activeTemplateForView->thumbnail_path) {
			$cover->active_template_overlay_url = asset('storage/' . $activeTemplateForView->thumbnail_path);
		}

		// Prepare cover variations with each associated template
		$coverVariations = [];
		if ($cover->templates->isNotEmpty()) {
			foreach ($cover->templates as $template) {
				$variationOverlayUrl = null;
				if ($template->thumbnail_path) {
					$variationOverlayUrl = asset('storage/' . $template->thumbnail_path);
				}
				$coverVariations[] = [
					'mockup_url' => $cover->mockup_url, // All variations use the same base cover mockup
					'template_overlay_url' => $variationOverlayUrl,
					'template_name' => $template->name, // For alt text or title
					'template_id' => $template->id, // Pass template ID for actions
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
			if ($related->image_path) {
				$related->mockup_url = asset('storage/' . str_replace(['covers/', '.jpg'], ['cover-mockups/', '-front-mockup.png'], $related->image_path));
			} else {
				$related->mockup_url = asset('template/assets/img/placeholder-mockup.png');
			}
			$related->random_template_overlay_url = null; // Keep this for related covers as they always show random
			if ($related->templates->isNotEmpty()) {
				$randomTemplate = $related->templates->random();
				if ($randomTemplate->thumbnail_path) {
					$related->random_template_overlay_url = asset('storage/' . $randomTemplate->thumbnail_path);
				}
			}
		}

		return view('covers.show', compact('cover', 'relatedCovers', 'coverVariations', 'activeTemplateForView'));
	}
}
