<?php

	namespace App\Http\Controllers;

	use App\Models\Cover;
	use Illuminate\Http\Request;
	use Illuminate\Support\Str;

	class CoverController extends Controller
	{
		/**
		 * Display the specified cover.
		 *
		 * @param  \App\Models\Cover  $cover
		 * @return \Illuminate\View\View
		 */
		public function show(Cover $cover)
		{
			// Eager load templates and coverType for the single cover view
			$cover->load('templates', 'coverType');

			if ($cover->image_path) {
				$cover->mockup_url = asset('storage/' . str_replace(['covers/', '.jpg'], ['cover-mockups/', '-front-mockup.png'], $cover->image_path));
			} else {
				$cover->mockup_url = asset('template/assets/img/placeholder-mockup.png'); // Fallback mockup
			}

			$cover->random_template_overlay_url = null;
			$randomTemplateForView = null; // To store the actual Template model instance

			if ($cover->templates->isNotEmpty()) {
				$randomTemplate = $cover->templates->random();
				$randomTemplateForView = $randomTemplate; // Store the template object
				if ($randomTemplate->thumbnail_path) {
					$cover->random_template_overlay_url = asset('storage/' . $randomTemplate->thumbnail_path);
				}
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
				->where('cover_type_id', 1); // Assuming cover_type_id 1 for Kindle, adjust if needed or use $cover->cover_type_id

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
					->where('cover_type_id', 1) // Adjust as needed
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
				$related->random_template_overlay_url = null;
				if ($related->templates->isNotEmpty()) {
					$randomTemplate = $related->templates->random();
					if ($randomTemplate->thumbnail_path) {
						$related->random_template_overlay_url = asset('storage/' . $randomTemplate->thumbnail_path);
					}
				}
			}

			return view('covers.show', compact('cover', 'relatedCovers', 'coverVariations', 'randomTemplateForView'));
		}
	}
