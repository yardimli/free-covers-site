<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request; // Make sure Request is imported
	use Illuminate\Support\Facades\File;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Log; // Added for logging
	use App\Models\CoverType;
	use App\Models\Cover;
	use App\Models\Template;
	use App\Models\Element;
	use App\Models\Overlay;

	class DesignerController extends Controller
	{
		public function index()
		{
			// Fetch Cover Types
			$cover_types_data = CoverType::orderBy('type_name')->get(['id', 'type_name'])->toArray();

			// Fetch Covers
			$covers_data = Cover::orderBy('name')
				->get(['id', 'cover_type_id', 'name', 'cover_thumbnail_path', 'cover_path', 'caption', 'keywords', 'categories', 'text_placements']) // Added text_placements
				->map(function ($cover) {
					return [
						'id' => $cover->id,
						'coverTypeId' => $cover->cover_type_id,
						'name' => $cover->name,
						'thumbnailPath' => $cover->cover_thumbnail_path ? Storage::url($cover->cover_thumbnail_path) : null,
						'imagePath' => $cover->cover_path ? Storage::url($cover->cover_path) : null,
						'caption' => $cover->caption,
						'keywords' => $cover->keywords, // Already an array due to model casting
						'categories' => $cover->categories, // Already an array
						'textPlacements' => $cover->text_placements, // Already an array
					];
				})->toArray();

			// Fetch Overlays
			$overlays_data = Overlay::orderBy('name')
				->get(['id', 'name', 'thumbnail_path', 'image_path', 'keywords'])
				->map(function ($overlay) {
					return [
						'id' => $overlay->id,
						'name' => $overlay->name,
						'thumbnailPath' => $overlay->thumbnail_path ? Storage::url($overlay->thumbnail_path) : null,
						'imagePath' => $overlay->image_path ? Storage::url($overlay->image_path) : null,
						'keywords' => $overlay->keywords,
					];
				})->toArray();

			// Fetch Templates
			$templates_data = Template::orderBy('name')
				// Added full_cover_json_content and full_cover_image_thumbnail_path to the get
				->get(['id', 'cover_type_id', 'name', 'cover_image_path', 'json_content', 'text_placements', 'full_cover_json_content', 'full_cover_image_thumbnail_path'])
				->map(function ($template) {
					return [
						'id' => $template->id,
						'coverTypeId' => $template->cover_type_id,
						'name' => $template->name,
						// cover_image_path is the overlay for the front part of the template
						'thumbnailPath' => $template->cover_image_path ? Storage::url($template->cover_image_path) : null,
						'jsonData' => $template->json_content, // Already an array/object due to model casting (for Kindle/front)
						'fullCoverJsonData' => $template->full_cover_json_content, // For print/full cover
						'textPlacements' => $template->text_placements, // Already an array
						// Optional: if you want a specific thumbnail for full cover templates in the sidebar
						// 'fullCoverThumbnailPath' => $template->full_cover_image_thumbnail_path ? Storage::url($template->full_cover_image_thumbnail_path) : null,
					];
				})->toArray();

			// Fetch Elements
			$elements_data = Element::orderBy('name')
				->get(['id', 'name', 'thumbnail_path', 'image_path', 'keywords'])
				->map(function ($element) {
					return [
						'id' => $element->id,
						'name' => $element->name,
						'thumbnailPath' => $element->thumbnail_path ? Storage::url($element->thumbnail_path) : null,
						'imagePath' => $element->image_path ? Storage::url($element->image_path) : null,
						'keywords' => $element->keywords,
					];
				})->toArray();

			// For canvasSizeModal.php (page-numbers.json)
			$page_numbers_data = [];
			$page_numbers_json_path = public_path('data/page-numbers.json');
			if (file_exists($page_numbers_json_path)) {
				try {
					$json_content = file_get_contents($page_numbers_json_path);
					$decoded_data = json_decode($json_content, true);
					if (is_array($decoded_data)) {
						$page_numbers_data = $decoded_data;
					} else {
						// Corrected Log path if page_numbers_json_public_path was a typo
						Log::error("DesignerController: page-numbers.json did not decode into an array. Path: " . $page_numbers_json_path);
					}
				} catch (\Exception $e) {
					Log::error("DesignerController: Error reading or parsing page-numbers.json: " . $e->getMessage());
				}
			} else {
				// Corrected Log path
				Log::error("DesignerController: page-numbers.json not found. Expected at: " . $page_numbers_json_path);
			}

			return view('designer.index', [
				'cover_types_json' => json_encode($cover_types_data),
				'covers_json' => json_encode($covers_data),
				'overlays_json' => json_encode($overlays_data),
				'templates_json' => json_encode($templates_data),
				'elements_json' => json_encode($elements_data),
				'page_numbers_json_for_modal' => json_encode($page_numbers_data), // Used by CanvasSizeModal.js
			]);
		}

		public function setupCanvas(Request $request)
		{
			$validated = $request->validate([
				'template_id' => 'nullable|integer|exists:templates,id',
				'cover_id' => 'required|integer|exists:covers,id',
			]);

			$cover = Cover::findOrFail($validated['cover_id']);

			// For the setup page preview, we might still use the front slice (cover_path)
			// or the full cover thumbnail (full_cover_thumbnail_path) as background.
			// The key is what we send to the *designer* as the main image.
			$frontSliceImagePath = $cover->cover_path; // Front slice
			$fullCoverImagePath = $cover->full_cover_path; // Full cover image

			// If full_cover_path is missing, we might fall back or show an error.
			// For now, we assume if we are in setupCanvas, we prefer full_cover_path for the designer.
			if (!$fullCoverImagePath && !$frontSliceImagePath) { // Check if at least one is available
				return redirect()->route('covers.show', $cover->id)->with('error', 'This cover does not have a suitable source image for customization.');
			}

			$template = null;
			$templateOverlayUrlForPreview = null;
			$templateJsonUrlForDesigner = null;

			if (!empty($validated['template_id'])) {
				$template = Template::find($validated['template_id']);
				if ($template) {
					if ($template->cover_image_path && Storage::disk('public')->exists($template->cover_image_path)) {
						$templateOverlayUrlForPreview = Storage::url($template->cover_image_path);
					}
					$templateJsonUrlForDesigner = route('api.templates.json_data', ['template' => $template->id, 'type' => 'full']);
				}
			}

			$pageNumbersJson = '[]';
			$jsonFilePath = public_path('data/page-numbers.json');
			if (File::exists($jsonFilePath)) {
				$pageNumbersJson = File::get($jsonFilePath);
			}

			$defaultPresetValue = "1840x2775";

			return view('designer.setup_canvas', [
				'cover' => $cover,
				// For setup page preview (front slice image and its overlay)
				'coverImageUrlForPreview' => $frontSliceImagePath ? Storage::url($frontSliceImagePath) : null,
				'templateOverlayUrlForPreview' => $templateOverlayUrlForPreview,

				// For setup page background preview (full cover thumbnail)
				'fullCoverThumbnailUrlForPreview' => $cover->full_cover_thumbnail_path ? Storage::url($cover->full_cover_thumbnail_path) : null,

				// Image paths for the designer URL (JS will pick based on context)
				// ORIGINAL_COVER_IMAGE_PATH_DESIGNER will be the front slice
				'originalCoverImagePathForDesigner' => $frontSliceImagePath, // Relative path for front slice
				// FULL_COVER_IMAGE_PATH_DESIGNER will be the full cover image
				'fullCoverImagePathForDesigner' => $fullCoverImagePath, // Relative path for full cover

				'templateJsonUrlForDesigner' => $templateJsonUrlForDesigner,
				'page_numbers_json_for_modal' => $pageNumbersJson,
				'default_cover_type_id' => $cover->cover_type_id ?? 1,
				'default_preset_value_for_setup_page' => $defaultPresetValue,
			]);
		}

		public function getTemplateJsonData(Template $template, Request $request) // Injected Request
		{
			// Check if 'full' type is requested and full_cover_json_content exists
			if ($request->input('type') === 'full' && $template->full_cover_json_content) {
				return response()->json($template->full_cover_json_content);
			}

			// Fallback to standard json_content (for Kindle/front or if full is not available)
			if ($template->json_content) {
				return response()->json($template->json_content);
			}

			// Legacy fallback to json_path (if you still use it)
			// if ($template->json_path && Storage::disk('public')->exists($template->json_path)) {
			//     return response()->json(json_decode(Storage::disk('public')->get($template->json_path)));
			// }

			return response()->json(['error' => 'Template JSON not found for the specified type or in general.'], 404);
		}
	}
