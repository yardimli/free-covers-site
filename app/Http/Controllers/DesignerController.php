<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
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
				->get(['id', 'cover_type_id', 'name', 'thumbnail_path', 'image_path', 'caption', 'keywords', 'categories', 'text_placements']) // Added text_placements
				->map(function ($cover) {
					return [
						'id' => $cover->id,
						'coverTypeId' => $cover->cover_type_id,
						'name' => $cover->name,
						'thumbnailPath' => $cover->thumbnail_path ? Storage::url($cover->thumbnail_path) : null,
						'imagePath' => $cover->image_path ? Storage::url($cover->image_path) : null,
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
				->get(['id', 'cover_type_id', 'name', 'thumbnail_path', 'json_content', 'text_placements']) // Added text_placements
				->map(function ($template) {
					return [
						'id' => $template->id,
						'coverTypeId' => $template->cover_type_id,
						'name' => $template->name,
						'thumbnailPath' => $template->thumbnail_path ? Storage::url($template->thumbnail_path) : null,
						'jsonData' => $template->json_content, // Already an array/object due to model casting
						'textPlacements' => $template->text_placements, // Already an array
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
						Log::error("DesignerController: page-numbers.json did not decode into an array. Path: " . storage_path('app/public/' . $page_numbers_json_public_path));
					}
				} catch (\Exception $e) {
					Log::error("DesignerController: Error reading or parsing page-numbers.json: " . $e->getMessage());
				}
			} else {
				Log::error("DesignerController: page-numbers.json not found in public storage. Expected at: " . storage_path('app/public/' . $page_numbers_json_public_path));
			}

			return view('designer.index', [
				'cover_types_json' => json_encode($cover_types_data),
				'covers_json' => json_encode($covers_data),
				'overlays_json' => json_encode($overlays_data),
				'templates_json' => json_encode($templates_data),
				'elements_json' => json_encode($elements_data),
				'page_numbers_json_for_modal' => json_encode($page_numbers_data),
			]);
		}


		public function setupCanvas(Request $request)
		{
			$validated = $request->validate([
				'template_id' => 'nullable|integer|exists:templates,id',
				'cover_id' => 'required|integer|exists:covers,id',
			]);

			$cover = Cover::findOrFail($validated['cover_id']);

			// Use the image_path from the cover model directly for security and consistency
			$coverImagePath = $cover->image_path;

			if (!$coverImagePath) {
				return redirect()->route('covers.show', $cover->id)->with('error', 'This cover does not have a source image suitable for customization.');
			}

			$template = null;
			$templateOverlayUrlForPreview = null;
			$templateJsonUrlForDesigner = null;

			if (!empty($validated['template_id'])) {
				$template = Template::find($validated['template_id']);
				if ($template) {
					if ($template->thumbnail_path && Storage::disk('public')->exists($template->thumbnail_path)) {
						$templateOverlayUrlForPreview = Storage::url($template->thumbnail_path);
					}
					// Use the new route to fetch JSON content
					$templateJsonUrlForDesigner = route('api.templates.json_data', ['template' => $template->id]);
				}
			}

			$pageNumbersJson = '[]';
			$jsonFilePath = public_path('data/page-numbers.json');
			if (File::exists($jsonFilePath)) {
				$pageNumbersJson = File::get($jsonFilePath);
			}

			return view('designer.setup_canvas',  [
				'cover' => $cover,
				'coverImageUrlForPreview' => Storage::url($coverImagePath), // Full URL for <img src>
				'originalCoverImagePathForDesigner' => $coverImagePath, // Relative path for query param
				'templateOverlayUrlForPreview' => $templateOverlayUrlForPreview,
				'templateJsonUrlForDesigner' => $templateJsonUrlForDesigner,
				'page_numbers_json_for_modal' => $pageNumbersJson,
				'default_cover_type_id' => $cover->cover_type_id ?? 1, // Example default
			]);
		}

		public function getTemplateJsonData(Template $template)
		{
			if ($template->json_content) {
				return response()->json($template->json_content);
			} elseif ($template->json_path && Storage::disk('public')->exists($template->json_path)) {
				return response()->json(json_decode(Storage::disk('public')->get($template->json_path)));
			}
			return response()->json(['error' => 'Template JSON not found'], 404);
		}
	}
