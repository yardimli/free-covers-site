<?php namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Cover;
use App\Models\Template;
use App\Models\Element;
use App\Models\Overlay;
use App\Models\CoverType;
use App\Services\ImageUploadService;
use App\Services\OpenAiService;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image as InterventionImageFacade;

use Illuminate\Support\Facades\File;
use ZipArchive;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

// Renamed to avoid conflict

class DashboardController extends Controller
{
	protected ImageUploadService $imageUploadService;
	protected OpenAiService $openAiService;

	public function __construct(ImageUploadService $imageUploadService, OpenAiService $openAiService)
	{
		$this->imageUploadService = $imageUploadService;
		$this->openAiService = $openAiService;
	}

	public function index()
	{
		return view('admin.dashboard.index');
	}

	public function listCoverTypes()
	{
		try {
			$types = CoverType::orderBy('type_name')->get(['id', 'type_name']);
			return response()->json(['success' => true, 'data' => ['cover_types' => $types]]);
		} catch (\Exception $e) {
			Log::error("Error fetching cover types: " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'Error fetching cover types: ' . $e->getMessage()], 500);
		}
	}

	private function getModelInstance(string $itemType)
	{
		return match ($itemType) {
			'covers' => new Cover(),
			'templates' => new Template(),
			'elements' => new Element(), // Assuming Element and Overlay models also have getAllImagePaths() or similar if they have multiple images
			'overlays' => new Overlay(),
			default => null,
		};
	}

	public function listItems(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'type' => ['required', Rule::in(['covers', 'templates', 'elements', 'overlays'])],
			'page' => 'integer|min:1',
			'limit' => 'integer|min:1',
			'search' => 'nullable|string|max:255',
			'cover_type_id' => 'nullable|integer|exists:cover_types,id',
			'filter_no_templates' => 'nullable|in:true,false,0,1',
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Invalid input.', 'errors' => $validator->errors()], 422);
		}

		$itemType = $request->input('type');
		$page = $request->input('page', 1);
		$limit = $request->input('limit', config('admin_settings.items_per_page', 30));
		$search = $request->input('search');
		$coverTypeIdFilter = $request->input('cover_type_id');
		$filterNoTemplates = $request->input('filter_no_templates', false);

		$model = $this->getModelInstance($itemType);
		if (!$model) {
			return response()->json(['success' => false, 'message' => 'Invalid item type.'], 400);
		}

		$query = $model->query();

		if ($itemType === 'covers') {
			$query->with(['coverType:id,type_name', 'templates:id,name']);
		} elseif ($itemType === 'templates') {
			$query->with('coverType:id,type_name');
		}

		if ($search) {
			$query->where(function ($q) use ($search, $itemType) {
				$q->where('name', 'LIKE', "%{$search}%");
				if ($itemType === 'covers') {
					$q->orWhere('caption', 'LIKE', "%{$search}%")
						->orWhereJsonContains('keywords', $search)
						->orWhereJsonContains('categories', $search);
				} elseif (in_array($itemType, ['elements', 'overlays', 'templates'])) {
					$q->orWhereJsonContains('keywords', $search);
				}
			});
		}

		if (($itemType === 'covers' || $itemType === 'templates') && $coverTypeIdFilter) {
			$query->where('cover_type_id', $coverTypeIdFilter);
		}

		if ($itemType === 'covers' && filter_var($filterNoTemplates, FILTER_VALIDATE_BOOLEAN)) {
			$query->whereDoesntHave('templates');
		}

		$paginatedItems = $query->orderBy('id', 'desc')->paginate($limit, ['*'], 'page', $page);

		$items = $paginatedItems->getCollection()->map(function ($item) use ($itemType) {
			// Common image fields for Elements & Overlays (assuming they also change image_path/thumbnail_path)
			// For this refactoring, I'm focusing on Cover and Template as per request.
			// If Element/Overlay also change, their models and this section need updates.
			if (property_exists($item, 'image_path')) { // Old field name, for elements/overlays if not updated
				$item->image_url = $this->imageUploadService->getUrl($item->image_path);
			}
			if (property_exists($item, 'thumbnail_path')) { // Old field name
				$item->thumbnail_url = $this->imageUploadService->getUrl($item->thumbnail_path);
			}


			if ($itemType === 'covers') {
				$item->cover_url = $this->imageUploadService->getUrl($item->cover_path);
				$item->cover_thumbnail_url = $this->imageUploadService->getUrl($item->cover_thumbnail_path);
				$item->mockup_2d_url = $this->imageUploadService->getUrl($item->mockup_2d_path);
				$item->mockup_3d_url = $this->imageUploadService->getUrl($item->mockup_3d_path);
				$item->full_cover_url = $this->imageUploadService->getUrl($item->full_cover_path);
				$item->full_cover_thumbnail_url = $this->imageUploadService->getUrl($item->full_cover_thumbnail_path);

				$item->cover_type_name = $item->coverType->type_name ?? null;
				$item->assigned_templates_count = $item->templates->count();
				$item->assigned_templates_names = $item->templates->isNotEmpty() ? $item->templates->pluck('name')->implode(', ') : 'None';
			} elseif ($itemType === 'templates') {
				$item->cover_image_url = $this->imageUploadService->getUrl($item->cover_image_path);
				$item->full_cover_image_url = $this->imageUploadService->getUrl($item->full_cover_image_path);
				$item->full_cover_image_thumbnail_url = $this->imageUploadService->getUrl($item->full_cover_image_thumbnail_path);
				$item->cover_type_name = $item->coverType->type_name ?? null;
			}
			// Keywords, categories, text_placements are already cast to arrays by the model
			return $item;
		});

		return response()->json([
			'success' => true,
			'data' => [
				'items' => $items,
				'pagination' => [
					'totalItems' => $paginatedItems->total(),
					'itemsPerPage' => $paginatedItems->perPage(),
					'currentPage' => $paginatedItems->currentPage(),
					'totalPages' => $paginatedItems->lastPage(),
				],
			]
		]);
	}

	public function uploadItem(Request $request)
	{
		$itemType = $request->input('item_type');
		$rules = [
			'item_type' => ['required', Rule::in(['covers', 'templates', 'elements', 'overlays'])],
			'name' => 'required|string|max:255', // Name is now required as per single upload
			'keywords' => 'nullable|string|max:1000',
		];

		if ($itemType === 'covers' || $itemType === 'templates') {
			$rules['cover_type_id'] = 'nullable|integer|exists:cover_types,id';
		}

		if ($itemType === 'covers') {
			$rules['caption'] = 'nullable|string|max:500';
			$rules['categories'] = 'nullable|string|max:1000';
			$rules['main_image_file'] = 'required|image|mimes:jpg,jpeg,png,gif|max:5120'; // Renamed from image_file
			$rules['mockup_2d_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['mockup_3d_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['full_cover_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
		} elseif ($itemType === 'elements' || $itemType === 'overlays') {
			// Assuming elements/overlays still use 'image_file' for their primary image
			$rules['image_file'] = 'required|image|mimes:jpg,jpeg,png,gif|max:5120';
		} elseif ($itemType === 'templates') {
			$rules['cover_image_file'] = 'required|image|mimes:jpg,jpeg,png,gif|max:5120'; // Renamed from thumbnail_file
			$rules['json_file'] = 'required|file|mimes:json|max:2048';
			$rules['full_cover_image_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['full_cover_json_file'] = 'nullable|file|mimes:json|max:2048';
		}

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
		}

		$model = $this->getModelInstance($itemType);
		if (!$model) return response()->json(['success' => false, 'message' => 'Invalid item type.'], 400);

		$data = [
			'name' => $request->input('name'),
			'keywords' => $request->input('keywords') ? array_map('trim', explode(',', $request->input('keywords'))) : [],
		];

		if ($itemType === 'covers' || $itemType === 'templates') {
			$data['cover_type_id'] = $request->input('cover_type_id');
		}

		try {
			if ($itemType === 'covers') {
				if ($request->hasFile('main_image_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('main_image_file'), 'covers_main');
					$data['cover_path'] = $paths['original_path'];
					$data['cover_thumbnail_path'] = $paths['thumbnail_path'];
				}
				if ($request->hasFile('mockup_2d_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('mockup_2d_file'), 'covers_mockup_2d');
					$data['mockup_2d_path'] = $paths['original_path']; // Assuming no thumb for mockup
				}
				if ($request->hasFile('mockup_3d_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('mockup_3d_file'), 'covers_mockup_3d');
					$data['mockup_3d_path'] = $paths['original_path']; // Assuming no thumb for mockup
				}
				if ($request->hasFile('full_cover_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('full_cover_file'), 'covers_full_cover');
					$data['full_cover_path'] = $paths['original_path'];
					$data['full_cover_thumbnail_path'] = $paths['thumbnail_path'];
				}
				$data['caption'] = $request->input('caption');
				$data['categories'] = $request->input('categories') ? array_map('trim', explode(',', $request->input('categories'))) : [];
				$data['text_placements'] = [];
			} elseif ($itemType === 'elements' || $itemType === 'overlays') {
				// Assuming 'elements_main' and 'overlays_main' are config keys
				if ($request->hasFile('image_file')) {
					$uploadConfigKey = $itemType . '_main'; // e.g. 'elements_main'
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('image_file'), $uploadConfigKey);
					$data['image_path'] = $paths['original_path'];
					$data['thumbnail_path'] = $paths['thumbnail_path'];
				}
			} elseif ($itemType === 'templates') {
				if ($request->hasFile('cover_image_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('cover_image_file'), 'templates_cover_image');
					$data['cover_image_path'] = $paths['original_path']; // Assuming templates_cover_image config might not generate a separate thumb
				}
				if ($request->hasFile('json_file')) {
					$jsonContent = file_get_contents($request->file('json_file')->getRealPath());
					if (json_decode($jsonContent) === null && json_last_error() !== JSON_ERROR_NONE) {
						return response()->json(['success' => false, 'message' => 'Invalid JSON content: ' . json_last_error_msg()], 400);
					}
					$data['json_content'] = $jsonContent;
					// Optionally store the JSON file itself if 'templates_main_json' config exists
					// $data['cover_json_file_path'] = $this->imageUploadService->storeUploadedFile($request->file('json_file'), 'templates_main_json');
				}
				if ($request->hasFile('full_cover_image_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('full_cover_image_file'), 'templates_full_cover_image');
					$data['full_cover_image_path'] = $paths['original_path'];
					$data['full_cover_image_thumbnail_path'] = $paths['thumbnail_path'];
				}
				if ($request->hasFile('full_cover_json_file')) {
					$jsonContent = file_get_contents($request->file('full_cover_json_file')->getRealPath());
					if (json_decode($jsonContent) === null && json_last_error() !== JSON_ERROR_NONE) {
						return response()->json(['success' => false, 'message' => 'Invalid Full Cover JSON: ' . json_last_error_msg()], 400);
					}
					$data['full_cover_json_content'] = $jsonContent;
				}
				if (!isset($data['text_placements'])) {
					$data['text_placements'] = [];
				}
			}

			$item = $model->create($data);
			return response()->json(['success' => true, 'message' => ucfirst(Str::singular($itemType)) . ' uploaded successfully.', 'data' => ['id' => $item->id]]);

		} catch (\Exception $e) {
			Log::error("Upload item error ({$itemType}): " . $e->getMessage() . "\n" . $e->getTraceAsString());
			return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
		}
	}

	public function getItemDetails(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'item_type' => ['required', Rule::in(['covers', 'templates', 'elements', 'overlays'])],
			'id' => 'required|integer',
		]);

		if ($validator->fails()) return response()->json(['success' => false, 'message' => 'Invalid input.', 'errors' => $validator->errors()], 422);

		$itemType = $request->input('item_type');
		$id = $request->input('id');
		$model = $this->getModelInstance($itemType);
		if (!$model) return response()->json(['success' => false, 'message' => 'Invalid item type.'], 400);

		$query = $model->query();
		if ($itemType === 'covers') {
			$query->with(['coverType:id,type_name', 'templates:id']);
		} elseif ($itemType === 'templates') {
			$query->with('coverType:id,type_name');
		}
		$item = $query->find($id);

		if (!$item) return response()->json(['success' => false, 'message' => ucfirst(Str::singular($itemType)) . ' not found.'], 404);

		$item->keywords_string_for_form = is_array($item->keywords) ? implode(', ', $item->keywords) : '';

		if ($itemType === 'covers') {
			$item->categories_string_for_form = is_array($item->categories) ? implode(', ', $item->categories) : '';
			$item->text_placements_string_for_form = is_array($item->text_placements) ? implode(', ', $item->text_placements) : '';
			$item->assigned_template_ids = $item->templates->pluck('id')->toArray();
			unset($item->templates);

			$item->cover_url = $this->imageUploadService->getUrl($item->cover_path);
			$item->cover_thumbnail_url = $this->imageUploadService->getUrl($item->cover_thumbnail_path);
			$item->mockup_2d_url = $this->imageUploadService->getUrl($item->mockup_2d_path);
			$item->mockup_3d_url = $this->imageUploadService->getUrl($item->mockup_3d_path);
			$item->full_cover_url = $this->imageUploadService->getUrl($item->full_cover_path);
			$item->full_cover_thumbnail_url = $this->imageUploadService->getUrl($item->full_cover_thumbnail_path);

		} elseif ($itemType === 'templates') {
			$item->text_placements_string_for_form = is_array($item->text_placements) ? implode(', ', $item->text_placements) : '';
			$item->cover_image_url = $this->imageUploadService->getUrl($item->cover_image_path);
			$item->full_cover_image_url = $this->imageUploadService->getUrl($item->full_cover_image_path);
			$item->full_cover_image_thumbnail_url = $this->imageUploadService->getUrl($item->full_cover_image_thumbnail_path);
			// For edit form, if JSON content is too large, don't send it all. Or send a flag.
			// $item->json_content_preview = Str::limit(is_array($item->json_content) ? json_encode($item->json_content) : $item->json_content, 200);
			// $item->full_cover_json_content_preview = Str::limit(is_array($item->full_cover_json_content) ? json_encode($item->full_cover_json_content) : $item->full_cover_json_content, 200);
		} elseif ($itemType === 'elements' || $itemType === 'overlays') {
			// Assuming old field names for these, adjust if they also get refactored
			if (property_exists($item, 'image_path')) $item->image_url = $this->imageUploadService->getUrl($item->image_path);
			if (property_exists($item, 'thumbnail_path')) $item->thumbnail_url = $this->imageUploadService->getUrl($item->thumbnail_path);
		}


		if (($itemType === 'covers' || $itemType === 'templates') && $item->coverType) {
			$item->cover_type_name = $item->coverType->type_name ?? null;
		}

		return response()->json(['success' => true, 'data' => $item]);
	}

	public function updateItem(Request $request)
	{
		$itemType = $request->input('item_type');
		$id = $request->input('id');
		$rules = [
			'id' => 'required|integer',
			'item_type' => ['required', Rule::in(['covers', 'templates', 'elements', 'overlays'])],
			'name' => 'required|string|max:255',
			'keywords' => 'nullable|string|max:1000',
		];

		// Add rules for new file inputs, all nullable for update
		if ($itemType === 'covers') {
			$rules['caption'] = 'nullable|string|max:500';
			$rules['categories'] = 'nullable|string|max:1000';
			$rules['text_placements'] = 'nullable|string|max:1000'; // Comma-separated
			$rules['main_image_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['mockup_2d_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['mockup_3d_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['full_cover_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
		} elseif ($itemType === 'templates') {
			$rules['text_placements'] = 'nullable|string|max:1000'; // Comma-separated
			$rules['cover_image_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['json_file'] = 'nullable|file|mimes:json|max:2048';
			$rules['full_cover_image_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['full_cover_json_file'] = 'nullable|file|mimes:json|max:2048';
		} elseif ($itemType === 'elements' || $itemType === 'overlays') {
			$rules['image_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
		}

		if ($itemType === 'covers' || $itemType === 'templates') {
			$rules['cover_type_id'] = 'nullable|integer|exists:cover_types,id';
		}

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);

		$model = $this->getModelInstance($itemType);
		if (!$model) return response()->json(['success' => false, 'message' => 'Invalid item type.'], 400);
		$item = $model->find($id);
		if (!$item) return response()->json(['success' => false, 'message' => ucfirst(Str::singular($itemType)) . ' not found.'], 404);

		$data = ['name' => $request->input('name')];
		$keywordsInput = $request->input('keywords');
		if ($keywordsInput !== null) {
			$keywordsArray = $keywordsInput ? array_map('trim', explode(',', $keywordsInput)) : [];
			$data['keywords'] = array_values(array_filter($keywordsArray, fn($value) => $value !== ''));
		}

		if ($itemType === 'covers' || $itemType === 'templates') {
			$data['cover_type_id'] = $request->input('cover_type_id', $item->cover_type_id);
		}

		try {
			if ($itemType === 'covers') {
				if ($request->hasFile('main_image_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('main_image_file'), 'covers_main', $item->cover_path, $item->cover_thumbnail_path);
					$data['cover_path'] = $paths['original_path'];
					$data['cover_thumbnail_path'] = $paths['thumbnail_path'];
				}
				if ($request->hasFile('mockup_2d_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('mockup_2d_file'), 'covers_mockup_2d', $item->mockup_2d_path);
					$data['mockup_2d_path'] = $paths['original_path'];
				}
				if ($request->hasFile('mockup_3d_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('mockup_3d_file'), 'covers_mockup_3d', $item->mockup_3d_path);
					$data['mockup_3d_path'] = $paths['original_path'];
				}
				if ($request->hasFile('full_cover_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('full_cover_file'), 'covers_full_cover', $item->full_cover_path, $item->full_cover_thumbnail_path);
					$data['full_cover_path'] = $paths['original_path'];
					$data['full_cover_thumbnail_path'] = $paths['thumbnail_path'];
				}
				$data['caption'] = $request->input('caption', $item->caption);
				$categoriesInput = $request->input('categories');
				if ($categoriesInput !== null) {
					$categoriesArray = $categoriesInput ? array_map('trim', explode(',', $categoriesInput)) : [];
					$data['categories'] = array_values(array_filter($categoriesArray, fn($value) => $value !== ''));
				}
				// Text Placements (from main edit form, if applicable)
				$tpInput = $request->input('text_placements');
				if ($tpInput !== null) {
					$tpArray = $tpInput ? array_map('trim', explode(',', $tpInput)) : [];
					$validTpArray = [];
					$pattern = '/^(top|middle|bottom|left|right)-(light|dark)$/';
					foreach ($tpArray as $placement) {
						if (preg_match($pattern, $placement)) {
							$validTpArray[] = $placement;
						} else if (!empty($placement)) {
							Log::warning("Invalid text_placement format '{$placement}' submitted via main edit form for Cover ID {$id}. Skipped.");
						}
					}
					$data['text_placements'] = $validTpArray;
				}

			} elseif ($itemType === 'templates') {
				if ($request->hasFile('cover_image_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('cover_image_file'), 'templates_cover_image', $item->cover_image_path);
					$data['cover_image_path'] = $paths['original_path'];
				}
				if ($request->hasFile('json_file')) {
					$jsonContent = file_get_contents($request->file('json_file')->getRealPath());
					if (json_decode($jsonContent) === null && json_last_error() !== JSON_ERROR_NONE) {
						return response()->json(['success' => false, 'message' => 'Invalid JSON content for update: ' . json_last_error_msg()], 400);
					}
					$data['json_content'] = $jsonContent;
				}
				if ($request->hasFile('full_cover_image_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('full_cover_image_file'), 'templates_full_cover_image', $item->full_cover_image_path, $item->full_cover_image_thumbnail_path);
					$data['full_cover_image_path'] = $paths['original_path'];
					$data['full_cover_image_thumbnail_path'] = $paths['thumbnail_path'];
				}
				if ($request->hasFile('full_cover_json_file')) {
					$jsonContent = file_get_contents($request->file('full_cover_json_file')->getRealPath());
					if (json_decode($jsonContent) === null && json_last_error() !== JSON_ERROR_NONE) {
						return response()->json(['success' => false, 'message' => 'Invalid Full Cover JSON for update: ' . json_last_error_msg()], 400);
					}
					$data['full_cover_json_content'] = $jsonContent;
				}
				// Text Placements for templates (from main edit form, if applicable)
				$tpInput = $request->input('text_placements');
				if ($tpInput !== null) {
					$tpArray = $tpInput ? array_map('trim', explode(',', $tpInput)) : [];
					$validTpArray = [];
					$pattern = '/^(top|middle|bottom|left|right)-(light|dark)$/';
					foreach ($tpArray as $placement) {
						if (preg_match($pattern, $placement)) {
							$validTpArray[] = $placement;
						} else if (!empty($placement)) {
							Log::warning("Invalid text_placement format '{$placement}' submitted via main edit form for Template ID {$id}. Skipped.");
						}
					}
					$data['text_placements'] = $validTpArray;
				}
			} elseif ($itemType === 'elements' || $itemType === 'overlays') {
				if ($request->hasFile('image_file')) {
					$uploadConfigKey = $itemType . '_main';
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('image_file'), $uploadConfigKey, $item->image_path, $item->thumbnail_path);
					$data['image_path'] = $paths['original_path'];
					$data['thumbnail_path'] = $paths['thumbnail_path'];
				}
			}

			$item->update($data);
			return response()->json(['success' => true, 'message' => ucfirst(Str::singular($itemType)) . ' updated successfully.']);

		} catch (\Exception $e) {
			Log::error("Update item error ({$itemType} ID {$id}): " . $e->getMessage() . "\n" . $e->getTraceAsString());
			return response()->json(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()], 500);
		}
	}

	public function deleteItem(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'item_type' => ['required', Rule::in(['covers', 'templates', 'elements', 'overlays'])],
			'id' => 'required|integer',
		]);

		if ($validator->fails()) return response()->json(['success' => false, 'message' => 'Invalid input.', 'errors' => $validator->errors()], 422);

		$itemType = $request->input('item_type');
		$id = $request->input('id');
		$model = $this->getModelInstance($itemType);
		if (!$model) return response()->json(['success' => false, 'message' => 'Invalid item type.'], 400);

		$item = $model->find($id);
		if (!$item) return response()->json(['success' => true, 'message' => ucfirst(Str::singular($itemType)) . ' not found or already deleted.']);

		try {
			$pathsToDelete = [];
			if (method_exists($item, 'getAllImagePaths')) {
				$pathsToDelete = $item->getAllImagePaths();
			} elseif (property_exists($item, 'image_path') && property_exists($item, 'thumbnail_path')) { // Fallback for simple models
				$pathsToDelete[] = $item->image_path;
				$pathsToDelete[] = $item->thumbnail_path;
			}
			// Add logic for JSON file paths if they are stored and need deletion
			// e.g., if ($itemType === 'templates' && $item->cover_json_file_path) $pathsToDelete[] = $item->cover_json_file_path;


			$item->delete(); // This should be after collecting paths, or paths will be null from the model instance

			if (!empty($pathsToDelete)) {
				$this->imageUploadService->deleteImageFiles(array_filter($pathsToDelete));
			}

			return response()->json(['success' => true, 'message' => ucfirst(Str::singular($itemType)) . ' deleted successfully.']);
		} catch (\Exception $e) {
			Log::error("Delete item error ({$itemType} ID {$id}): " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()], 500);
		}
	}

	public function getCoversNeedingMetadata(Request $request)
	{
		try {
			// Broadly fetch covers that *might* need updates.
			$potentialCovers = Cover::query()
				->where(function ($query) {
					$query->whereNull('caption')
						->orWhere('caption', '=', '')
						->orWhereNull('keywords')
						->orWhere('keywords', '=', '[]') // Check for empty JSON array string
						->orWhereNull('categories')
						->orWhere('categories', '=', '[]'); // Check for empty JSON array string
				})
				// Or names that are very short (less likely to be 3 words)
				// This is a loose pre-filter; precise check is in PHP.
				->orWhereRaw('LENGTH(name) < 15') // Example: names shorter than 15 chars
				->orderBy('id')
				->get(['id', 'name', 'caption', 'keywords', 'categories']);

			$coversNeedingUpdate = $potentialCovers->filter(function ($cover) {
				$nameWordCount = str_word_count(trim($cover->name ?? ''));
				$needsNameUpdate = $nameWordCount < 3;
				$needsCaptionUpdate = empty(trim($cover->caption ?? ''));
				// Model casts 'keywords' and 'categories' to arrays.
				$needsKeywordsUpdate = empty($cover->keywords); // Checks if the array is empty
				$needsCategoriesUpdate = empty($cover->categories); // Checks if the array is empty
				return $needsNameUpdate || $needsCaptionUpdate || $needsKeywordsUpdate || $needsCategoriesUpdate;
			})->map(function ($cover) {
				$fields_to_generate = [];
				if (str_word_count(trim($cover->name ?? '')) < 3) $fields_to_generate[] = 'name';
				if (empty(trim($cover->caption ?? ''))) $fields_to_generate[] = 'caption';
				if (empty($cover->keywords)) $fields_to_generate[] = 'keywords';
				if (empty($cover->categories)) $fields_to_generate[] = 'categories';
				return [
					'id' => $cover->id,
					'current_name' => $cover->name, // For logging/display by JS
					'fields_to_generate' => $fields_to_generate
				];
			})->values(); // Reset keys for JSON array

			return response()->json(['success' => true, 'data' => ['covers' => $coversNeedingUpdate]]);
		} catch (\Exception $e) {
			Log::error("Error fetching covers needing metadata: " . $e->getMessage() . "\n" . $e->getTraceAsString());
			return response()->json(['success' => false, 'message' => 'Error fetching covers: ' . $e->getMessage()], 500);
		}
	}

	public function generateAiMetadata(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'item_type' => ['required', Rule::in(['covers', 'templates', 'elements', 'overlays'])],
			'id' => 'required|integer',
			'fields_to_generate' => 'nullable|string', // Comma-separated: name,caption,keywords,categories
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Invalid input.', 'errors' => $validator->errors()], 422);
		}

		$itemType = $request->input('item_type');
		$id = $request->input('id');
		$fieldsToGenerateInput = $request->input('fields_to_generate');
		$fieldsToGenerate = $fieldsToGenerateInput ? explode(',', $fieldsToGenerateInput) : [];
		$isBatchTargetedMode = !empty($fieldsToGenerate);

		$model = $this->getModelInstance($itemType);
		if (!$model) {
			return response()->json(['success' => false, 'message' => 'Invalid item type.'], 400);
		}
		$item = $model->find($id);
		if (!$item) {
			return response()->json(['success' => false, 'message' => ucfirst(Str::singular($itemType)) . ' not found.'], 404);
		}

		$imagePathForAi = null;
		if ($itemType === 'covers') {
			$imagePathForAi = $item->cover_path ?? $item->cover_thumbnail_path;
		} elseif ($itemType === 'templates') {
			// For templates, use cover_image_path or full_cover_image_path if more detailed
			$imagePathForAi = $item->cover_image_path ?? $item->full_cover_image_path;
		} elseif ($itemType === 'elements' || $itemType === 'overlays') {
			// Assuming old field names, adjust if these models are also refactored
			$imagePathForAi = $item->image_path ?? $item->thumbnail_path;
		}


		if (!$imagePathForAi || !Storage::disk('public')->exists($imagePathForAi)) {
			return response()->json(['success' => false, 'message' => 'Image file not found on server for AI processing.'], 404);
		}

		try {
			$imageContent = Storage::disk('public')->get($imagePathForAi);
			$base64Image = base64_encode($imageContent);
			$mimeType = Storage::disk('public')->mimeType($imagePathForAi) ?: 'image/jpeg';
			$aiGeneratedData = [];

			// Name Generation (only for covers)
			if ($itemType === 'covers' && (!$isBatchTargetedMode || in_array('name', $fieldsToGenerate))) {
				$namePrompt = "Generate a concise and descriptive 3-word name for this image based on its visual elements, style, and potential use case. The name should be suitable as a title. Output only the 3-word name. Example: 'Mystic Forest Path' or 'Cosmic Abstract Swirls'.";
				$nameResponse = $this->openAiService->generateMetadataFromImageBase64($namePrompt, $base64Image, $mimeType);
				if (isset($nameResponse['content'])) {
					$generatedName = trim($nameResponse['content']);
					if (str_word_count($generatedName) >= 2 && str_word_count($generatedName) <= 4) {
						$aiGeneratedData['name'] = Str::title($generatedName);
					} else {
						Log::warning("AI Name Generation for Cover ID {$id}: Did not return 2-4 words. Got: '{$generatedName}'");
					}
				} elseif (isset($nameResponse['error'])) {
					Log::warning("AI Name Error for cover ID {$id}: " . $nameResponse['error']);
				}
			}

			// Keywords Generation
			if (!$isBatchTargetedMode || in_array('keywords', $fieldsToGenerate)) {
				$keywordsPrompt = "Generate a list of 10-15 relevant keywords for this image, suitable for search or tagging. Include single words and relevant two-word phrases. Focus on visual elements, style, and potential use case. Output only a comma-separated list.";
				$keywordsResponse = $this->openAiService->generateMetadataFromImageBase64($keywordsPrompt, $base64Image, $mimeType);
				if (isset($keywordsResponse['content'])) {
					$parsedKeywords = $this->openAiService->parseAiListResponse($keywordsResponse['content']);
					if (!empty($parsedKeywords)) $aiGeneratedData['keywords'] = $parsedKeywords;
				} elseif (isset($keywordsResponse['error'])) {
					Log::warning("AI Keywords Error for {$itemType} ID {$id}: " . $keywordsResponse['error']);
				}
			}

			// Caption and Categories (only for covers)
			if ($itemType === 'covers') {
				if (!$isBatchTargetedMode || in_array('caption', $fieldsToGenerate)) {
					$captionPrompt = "Describe this book cover image concisely for use as an alt text or short caption. Focus on the main visual elements and mood. Do not include or describe any text visible on the image. Maximum 140 characters.";
					$captionResponse = $this->openAiService->generateMetadataFromImageBase64($captionPrompt, $base64Image, $mimeType);
					if (isset($captionResponse['content'])) {
						$aiGeneratedData['caption'] = Str::limit(trim($captionResponse['content']), 250);
					} elseif (isset($captionResponse['error'])) {
						Log::warning("AI Caption Error for cover ID {$id}: " . $captionResponse['error']);
					}
				}
				if (!$isBatchTargetedMode || in_array('categories', $fieldsToGenerate)) {
					$categoriesPrompt = "Categorize this book cover image into 1-3 relevant genres from the following list: Mystery, Thriller & Suspense, Fantasy, Science Fiction, Horror, Romance, Erotica, Children's, Action & Adventure, Chick Lit, Historical Fiction, Literary Fiction, Teen & Young Adult, Royal Romance, Western, Surreal, Paranormal & Urban, Apocalyptic, Nature, Poetry, Travel, Religion & Spirituality, Business, Self-Improvement, Education, Health & Wellness, Cookbooks & Food, Environment, Politics & Society, Family & Parenting, Abstract, Medical, Fitness, Sports, Science, Music. Output only a comma-separated list of the chosen categories.";
					$categoriesResponse = $this->openAiService->generateMetadataFromImageBase64($categoriesPrompt, $base64Image, $mimeType);
					if (isset($categoriesResponse['content'])) {
						$parsedCategories = $this->openAiService->parseAiListResponse($categoriesResponse['content']);
						if (!empty($parsedCategories)) $aiGeneratedData['categories'] = $parsedCategories;
					} elseif (isset($categoriesResponse['error'])) {
						Log::warning("AI Categories Error for cover ID {$id}: " . $categoriesResponse['error']);
					}
				}
			}

			if (empty($aiGeneratedData)) {
				$message = $isBatchTargetedMode ? 'AI did not return any usable metadata for the requested fields. Check logs.' : 'AI did not return any usable metadata or an error occurred. Check logs.';
				return response()->json(['success' => false, 'message' => $message], 500);
			}

			$item->update($aiGeneratedData);
			$updatedFieldsList = implode(', ', array_keys($aiGeneratedData));

			return response()->json([
				'success' => true,
				'message' => ucfirst(Str::singular($itemType)) . " AI metadata updated successfully for fields: {$updatedFieldsList}.",
				'data' => ['updated_fields' => array_keys($aiGeneratedData)]
			]);

		} catch (\Exception $e) {
			Log::error("AI Metadata generation error ({$itemType} ID {$id}): " . $e->getMessage() . "\n" . $e->getTraceAsString());
			return response()->json(['success' => false, 'message' => 'AI metadata generation failed: ' . $e->getMessage()], 500);
		}
	}

	public function generateSimilarTemplate(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'original_template_id' => 'required|integer|exists:templates,id',
			'user_prompt' => 'required|string|min:10|max:2000',
			'original_json_content' => 'required|json', // This comes from JS, which gets it from item details
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Invalid input.', 'errors' => $validator->errors()], 422);
		}

		$originalTemplateId = $request->input('original_template_id');
		$userPromptText = $request->input('user_prompt');
		$originalJsonContent = $request->input('original_json_content'); // This is already a string from the request

		$googleFontsConfig = config('googlefonts.fonts', []);
		$googleFontNames = array_keys($googleFontsConfig);
		$googleFontString = implode(', ', $googleFontNames);

		$systemMessage = "You are an expert JSON template designer. Based on the provided example JSON and the user's request, generate a new, complete, and valid JSON object. The output MUST be ONLY the raw JSON content, without any surrounding text, explanations, or markdown ```json ... ``` tags. Ensure all structural elements from the example are considered and adapted according to the user's request. Choose suitable fonts to substitute the example from the following google fonts based on the users request: {$googleFontString}. Ensure the generated JSON is a single, valid JSON object.";
		$userMessageContent = "User Request: \"{$userPromptText}\"\n\nExample JSON:\n{$originalJsonContent}";

		$messages = [
			["role" => "system", "content" => $systemMessage],
			["role" => "user", "content" => $userMessageContent]
		];

		try {
			$responseFormat = (str_contains(config('admin_settings.openai_text_model'), 'gpt-4') || str_contains(config('admin_settings.openai_text_model'), '1106')) ? ['type' => 'json_object'] : null;
			$aiResponse = $this->openAiService->generateText($messages, 0.6, 4000, $responseFormat);

			if (isset($aiResponse['error'])) {
				Log::error("AI Similar Template Error for ID {$originalTemplateId}: " . $aiResponse['error']);
				return response()->json(['success' => false, 'message' => "AI Error: " . $aiResponse['error']], 500);
			}

			$generatedJsonString = $aiResponse['content'];
			if (!$responseFormat && preg_match('/```json\s*([\s\S]*?)\s*```/', $generatedJsonString, $matches)) {
				$generatedJsonString = $matches[1];
			}
			$generatedJsonString = trim($generatedJsonString);
			$decodedJson = json_decode($generatedJsonString);

			if (json_last_error() !== JSON_ERROR_NONE) {
				Log::error("AI Similar Template: Invalid JSON response for ID {$originalTemplateId}. Error: " . json_last_error_msg() . ". Raw: " . $aiResponse['content']);
				return response()->json(['success' => false, 'message' => 'AI returned invalid JSON: ' . json_last_error_msg() . ". Raw AI output: " . Str::limit($aiResponse['content'], 200) . "..."], 500);
			}

			$filename = "template_ai_origID{$originalTemplateId}_" . time() . ".json";
			$prettyJsonToDownload = json_encode($decodedJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

			return response()->json([
				'success' => true,
				'message' => 'AI-generated template ready for download.',
				'data' => [
					'filename' => $filename,
					'generated_json_content' => $prettyJsonToDownload
				]
			]);

		} catch (\Exception $e) {
			Log::error("AI Similar Template generation error (ID {$originalTemplateId}): " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'AI template generation failed: ' . $e->getMessage()], 500);
		}
	}

	public function generateAiTextPlacements(Request $request, Cover $cover)
	{
		$imagePathForAi = $cover->cover_path ?? $cover->cover_thumbnail_path;
		if (!$imagePathForAi || !Storage::disk('public')->exists($imagePathForAi)) {
			return response()->json(['success' => false, 'message' => 'Image file not found on server for AI processing.'], 404);
		}

		try {
			$imageContent = Storage::disk('public')->get($imagePathForAi);
			$base64Image = base64_encode($imageContent);
			$mimeType = Storage::disk('public')->mimeType($imagePathForAi) ?: 'image/jpeg';

			$prompt = "Analyze this image for suitable text placement. Identify clear, relatively flat areas (top, bottom, left, right, middle) and determine if the background in that specific area is predominantly light or dark. Return ONLY a raw JSON array of strings, where each string is 'area-tone' (e.g., 'top-light', 'bottom-light', 'left-dark', 'right-light', 'middle-dark'). Only include areas genuinely suitable for overlaying text. If no area is clearly suitable, return an empty array. Only return one pair. Choose the largest area suitable for text placement and return that. For example: [\"top-light\"] or [\"bottom-dark\"]. Do not include any explanations or markdown.";
			$aiResponse = $this->openAiService->generateMetadataFromImageBase64($prompt, $base64Image, $mimeType);

			if (isset($aiResponse['error'])) {
				Log::error("AI Text Placements Error for Cover ID {$cover->id}: " . $aiResponse['error']);
				return response()->json(['success' => false, 'message' => "AI Error: " . $aiResponse['error']], 500);
			}

			$generatedJsonString = $aiResponse['content'];
			if (preg_match('/```json\s*([\s\S]*?)\s*```/', $generatedJsonString, $matches)) {
				$generatedJsonString = $matches[1];
			}
			$generatedJsonString = trim($generatedJsonString);
			$decodedPlacements = json_decode($generatedJsonString, true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				Log::error("AI Text Placements: Invalid JSON response for Cover ID {$cover->id}. Error: " . json_last_error_msg() . ". Raw: " . $aiResponse['content']);
				return response()->json(['success' => false, 'message' => 'AI returned invalid JSON for text placements: ' . json_last_error_msg() . ". Raw AI output: " . Str::limit($aiResponse['content'], 200) . "..."], 500);
			}

			$validPlacements = [];
			if (is_array($decodedPlacements)) {
				$pattern = '/^(top|middle|bottom|left|right)-(light|dark)$/';
				foreach ($decodedPlacements as $placement) {
					if (is_string($placement) && preg_match($pattern, $placement)) {
						$validPlacements[] = $placement;
					} else {
						Log::warning("AI Text Placements: Invalid placement string '{$placement}' received for Cover ID {$cover->id}. Filtered out.");
					}
				}
			} else {
				Log::error("AI Text Placements: JSON response was not an array for Cover ID {$cover->id}. Raw: " . $generatedJsonString);
				return response()->json(['success' => false, 'message' => 'AI returned an unexpected JSON structure (not an array) for text placements.'], 500);
			}

			$cover->text_placements = $validPlacements;
			$cover->save();

			return response()->json(['success' => true, 'message' => 'AI text placements analyzed and updated successfully.']);

		} catch (\Exception $e) {
			Log::error("AI Text Placements generation error (Cover ID {$cover->id}): " . $e->getMessage() . "\n" . $e->getTraceAsString());
			return response()->json(['success' => false, 'message' => 'AI text placements generation failed: ' . $e->getMessage()], 500);
		}
	}

	public function getUnprocessedCoversForTextPlacement(Request $request)
	{
		$coverIds = Cover::whereNull('text_placements')
			->orWhere('text_placements', '=', '[]') // For empty JSON arrays
			->pluck('id');
		return response()->json(['success' => true, 'data' => ['cover_ids' => $coverIds]]);
	}

	public function listAssignableTemplates(Request $request, Cover $cover)
	{
		$coverImageUrl = $this->imageUploadService->getUrl($cover->cover_path ?? $cover->cover_thumbnail_path);

		if (!$cover->cover_type_id) {
			return response()->json([
				'success' => true, // Still success, but with a message
				'data' => [
					'cover_name' => $cover->name,
					'cover_type_name' => 'N/A (Not Set)',
					'cover_image_url' => $coverImageUrl,
					'templates' => [],
				],
				'message' => 'Cover does not have a cover type assigned. Cannot list templates.'
			]);
		}

		$assignableTemplates = Template::where('cover_type_id', $cover->cover_type_id)
			->orderBy('name')
			->get(['id', 'name', 'cover_image_path']); // Use new field name

		$assignedTemplateIds = $cover->templates()->pluck('template_id')->toArray();

		$templatesData = $assignableTemplates->map(function ($template) use ($assignedTemplateIds) {
			return [
				'id' => $template->id,
				'name' => $template->name,
				'is_assigned' => in_array($template->id, $assignedTemplateIds),
				'thumbnail_url' => $this->imageUploadService->getUrl($template->cover_image_path), // Use new field name
			];
		});

		return response()->json([
			'success' => true,
			'data' => [
				'cover_name' => $cover->name,
				'cover_type_name' => $cover->coverType->type_name ?? 'N/A',
				'cover_image_url' => $coverImageUrl,
				'templates' => $templatesData,
			]
		]);
	}

	public function updateCoverTemplateAssignments(Request $request, Cover $cover)
	{
		$validator = Validator::make($request->all(), [
			'template_ids' => 'nullable|array',
			'template_ids.*' => 'integer|exists:templates,id',
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Invalid input.', 'errors' => $validator->errors()], 422);
		}
		$templateIds = $request->input('template_ids', []);

		if (!empty($templateIds) && $cover->cover_type_id) {
			$validTemplatesCount = Template::where('cover_type_id', $cover->cover_type_id)
				->whereIn('id', $templateIds)
				->count();
			if ($validTemplatesCount !== count($templateIds)) {
				return response()->json(['success' => false, 'message' => 'One or more selected templates do not match the cover\'s type or are invalid.'], 400);
			}
		} elseif (!empty($templateIds) && !$cover->cover_type_id) {
			return response()->json(['success' => false, 'message' => 'Cannot assign templates to a cover without a cover type.'], 400);
		}


		try {
			$cover->templates()->sync($templateIds);
			return response()->json(['success' => true, 'message' => 'Template assignments updated successfully.']);
		} catch (\Exception $e) {
			Log::error("Error updating template assignments for Cover ID {$cover->id}: " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'Failed to update assignments: ' . $e->getMessage()], 500);
		}
	}

	public function updateTextPlacements(Request $request, string $itemType, int $id)
	{
		$modelInstance = $this->getModelInstance($itemType);
		if (!$modelInstance || !in_array($itemType, ['covers', 'templates'])) {
			return response()->json(['success' => false, 'message' => 'Invalid item type for text placements.'], 400);
		}
		$item = $modelInstance->find($id);
		if (!$item) {
			return response()->json(['success' => false, 'message' => ucfirst(Str::singular($itemType)) . ' not found.'], 404);
		}

		$validator = Validator::make($request->all(), [
			'text_placements' => 'nullable|array',
			'text_placements.*' => ['nullable', 'string', Rule::in([
				'top-light', 'top-dark', 'middle-light', 'middle-dark',
				'bottom-light', 'bottom-dark', 'left-light', 'left-dark',
				'right-light', 'right-dark'
			])],
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
		}

		try {
			$item->text_placements = $request->input('text_placements', []); // Already an array from JS
			$item->save();
			return response()->json(['success' => true, 'message' => 'Text placements updated successfully.']);
		} catch (\Exception $e) {
			Log::error("Update text placements error ({$itemType} ID {$id}): " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()], 500);
		}
	}

	private function getInversePlacement(string $placement): ?string
	{
		if (!preg_match('/^(top|middle|bottom|left|right)-(light|dark)$/', $placement)) {
			return null;
		}
		if (Str::endsWith($placement, '-light')) {
			return Str::replaceLast('-light', '-dark', $placement);
		} elseif (Str::endsWith($placement, '-dark')) {
			return Str::replaceLast('-dark', '-light', $placement);
		}
		return null;
	}

	public function aiEvaluateTemplateFit(Request $request, Cover $cover, Template $template)
	{
		// Use cover's main image or its thumbnail for the base
		$coverBaseImagePath = $cover->cover_path ?? $cover->cover_thumbnail_path;
		if (!$coverBaseImagePath) {
			return response()->json(['success' => false, 'message' => 'Cover image not found.'], 404);
		}

		// Use template's cover_image_path as the overlay
		if (!$template->cover_image_path) {
			return response()->json(['success' => false, 'message' => 'Template image (overlay image) not found.'], 404);
		}

		if (!Storage::disk('public')->exists($coverBaseImagePath) || !Storage::disk('public')->exists($template->cover_image_path)) {
			return response()->json(['success' => false, 'message' => 'One or more image files not found on server.'], 404);
		}

		try {
			$coverImageContent = Storage::disk('public')->get($coverBaseImagePath);
			$templateOverlayImageContent = Storage::disk('public')->get($template->cover_image_path);

			$baseImage = InterventionImageFacade::read($coverImageContent);
			$overlayImage = InterventionImageFacade::read($templateOverlayImageContent);

			$baseImageWidth = $baseImage->width();
			$baseImageHeight = $baseImage->height();
			$targetOverlayWidth = (int)round($baseImageWidth * 0.95);
			$overlayImage->scale(width: $targetOverlayWidth);
			$marginTop = (int)round($baseImageHeight * 0.03);
			$marginLeft = (int)round($baseImageWidth * 0.03);
			$baseImage->place($overlayImage, 'top-left', $marginLeft, $marginTop);

			Storage::makeDirectory('public/temp', 0755, true);
			// $tempPath = storage_path('app/public/temp/composite_image.png'); // Not used after this
			// $baseImage->save($tempPath);

			$encodedImage = $baseImage->toPng();
			$base64CompositeImage = base64_encode((string)$encodedImage);
			$mimeType = 'image/png';

			$prompt = "Analyze the following image, which is a book cover with a text template overlaid. The underlying image should show: '" . $cover->caption . "' Evaluate based on MANDATORY criteria: 1) Is the title and author text in the template completely legible and easy to read? 2) Is the key visual element from the caption visible and NOT obscured by the text overlay. Respond with the single word 'YES'. Otherwise Respond with only 'NO'. Don't add any explanation only respond with 'YES' or 'NO'.";
			$aiResponse = $this->openAiService->generateMetadataFromImageBase64($prompt, $base64CompositeImage, $mimeType, 30);

			if (isset($aiResponse['error'])) {
				Log::error("AI Template Fit Error for Cover ID {$cover->id}, Template ID {$template->id}: " . $aiResponse['error']);
				return response()->json(['success' => false, 'message' => "AI Error: " . $aiResponse['error']], 500);
			}

			$decisionString = trim(strtoupper($aiResponse['content'] ?? ''));
			$shouldAssign = str_contains($decisionString, 'YES');

			Log::info("AI Template Fit Evaluation: Cover {$cover->id}, Template {$template->id}. BaseW:{$baseImageWidth}, OverlayTargetW:{$targetOverlayWidth}, MarginT:{$marginTop}, MarginL:{$marginLeft}. AI Raw: '{$aiResponse['content']}'. Parsed Decision: " . ($shouldAssign ? 'YES' : 'NO'));

			return response()->json(['success' => true, 'data' => ['should_assign' => $shouldAssign]]);

		} catch (\Exception $e) {
			Log::error("Error in aiEvaluateTemplateFit (Cover ID {$cover->id}, Template ID {$template->id}): " . $e->getMessage() . "\n" . $e->getTraceAsString());
			return response()->json(['success' => false, 'message' => 'Failed to evaluate template fit: ' . $e->getMessage()], 500);
		}
	}

	public function getCoversWithoutTemplates(Request $request)
	{
		try {
			$covers = Cover::whereDoesntHave('templates')
				->orderBy('name')
				->get(['id', 'name']);
			return response()->json(['success' => true, 'data' => ['covers' => $covers]]);
		} catch (\Exception $e) {
			Log::error("Error fetching covers without templates: " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'Error fetching covers: ' . $e->getMessage()], 500);
		}
	}

	public function removeCoverTemplateAssignment(Request $request, Cover $cover, Template $template)
	{
		try {
			$detachedCount = $cover->templates()->detach($template->id);
			if ($detachedCount > 0) {
				return response()->json(['success' => true, 'message' => 'Template style removed from cover successfully.']);
			} else {
				return response()->json(['success' => true, 'message' => 'Template style was not associated with this cover or already removed.']);
			}
		} catch (\Exception $e) {
			Log::error("Error removing template assignment for Cover ID {$cover->id}, Template ID {$template->id}: " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'An error occurred while removing the template style.'], 500);
		}
	}

	public function coverTemplateManagementIndex(Request $request)
	{
		$covers = Cover::with(['templates' => function ($query) {
			$query->orderBy('name');
		}, 'coverType'])
			->orderBy('id', 'desc')
			->paginate(20);

		foreach ($covers as $cover) {
			// Try to generate mockup URL (using new field names if available, or existing logic)
			// This logic might need to prioritize mockup_2d_path or mockup_3d_path if they exist
			$mockupPathToUse = $cover->mockup_2d_path ?? $cover->mockup_3d_path;

			if (!$mockupPathToUse && $cover->cover_path) { // Fallback to old logic if specific mockups not set
				$mockupPathAttempt = $cover->cover_path;
				$mockupPathAttempt = preg_replace('/\.jpg$|\.jpeg$|\.png$|\.gif$/i', '-front-mockup.png', $mockupPathAttempt);
				// Adjust base path for mockups if they are in a different root folder
				// Example: 'uploads/covers/main/originals/' to 'uploads/cover-mockups/'
				// This part is highly dependent on your actual storage structure for generated mockups
				$mockupPathAttempt = str_replace('covers/main/originals/', 'cover-mockups/', $mockupPathAttempt); // Example adjustment

				if (Storage::disk('public')->exists($mockupPathAttempt)) {
					$mockupPathToUse = $mockupPathAttempt;
				}
			}

			foreach ($cover->templates as $template) {
				if ($template->cover_image_path) { // Use new field name
					$template->thumbnail_url = $this->imageUploadService->getUrl($template->cover_image_path);
				} else {
					$template->thumbnail_url = asset('images/placeholder-template-thumbnail.png');
				}
			}
		}
		return view('admin.cover-template-management.index', compact('covers'));
	}

	public function uploadCoverZip(Request $request)
	{
		ini_set('max_execution_time', 900);
		$processLocal = filter_var($request->input('process_local_temp_folder'), FILTER_VALIDATE_BOOLEAN);

		$rules = [
			'default_cover_type_id' => 'nullable|integer|exists:cover_types,id',
			'process_local_temp_folder' => 'nullable|boolean',
		];

		if (!$processLocal) {
			$rules['cover_zip_file'] = 'required|file|mimes:zip|max:102400'; // Max 100MB
		} else {
			// If processing local, zip file is not required, but can still be validated if provided (though JS hides it)
			$rules['cover_zip_file'] = 'nullable|file|mimes:zip|max:102400';
		}

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
		}

		$zipFile = $request->file('cover_zip_file');
		$defaultCoverTypeId = $request->input('default_cover_type_id') ?: 1; // Default to 1 if not provided

		$tempExtractDir = null; // For UUID based temp dir if ZIP is used
		$realTempExtractPath = null;
		$cleanupTempDir = false; // Flag to control cleanup of UUID temp dir

		$results = ['created_count' => 0, 'updated_count' => 0, 'error_count' => 0, 'details' => []];

		try {
			if ($processLocal) {
				$localTempCoversPath = 'temp_covers'; // Relative to storage/app/
				if (!Storage::disk('local')->exists($localTempCoversPath)) {
					if (!Storage::disk('local')->makeDirectory($localTempCoversPath)) {
						Log::error("Failed to create local temp_covers directory: " . $localTempCoversPath);
						return response()->json(['success' => false, 'message' => 'Failed to create local temp_covers directory. Check permissions.'], 500);
					}
					// Directory created, but it will be empty.
					$results['details'][] = ['name' => 'Local temp_covers', 'status' => 'info', 'reason' => 'Directory was created and is empty. No files to process.'];
				}
				$realTempExtractPath = Storage::disk('local')->path($localTempCoversPath);
				// No ZIP opening or extraction needed for local processing
				// No cleanup of this specific directory ($cleanupTempDir remains false)
			} else {
				// Existing ZIP file logic
				if (!$zipFile) { // Should be caught by validation, but as a safeguard
					return response()->json(['success' => false, 'message' => 'ZIP file is required.'], 422);
				}
				$tempExtractDir = 'temp_zip_uploads/' . Str::uuid();
				Storage::disk('local')->makeDirectory($tempExtractDir);
				$realTempExtractPath = Storage::disk('local')->path($tempExtractDir);
				$cleanupTempDir = true; // Mark this UUID directory for cleanup

				$zip = new ZipArchive;
				if ($zip->open($zipFile->getRealPath()) === TRUE) {
					$zip->extractTo($realTempExtractPath);
					$zip->close();
				} else {
					// Error handling for failed ZIP open is already here, cleanup will be handled in finally
					return response()->json(['success' => false, 'message' => 'Failed to open ZIP file.'], 500);
				}
			}

			if (!is_dir($realTempExtractPath)) {
				Log::error("uploadCoverZip: realTempExtractPath is not a directory: " . $realTempExtractPath);
				return response()->json(['success' => false, 'message' => 'Internal error: Source directory for covers not found.'], 500);
			}

			$extractedFiles = File::allFiles($realTempExtractPath);

			if (empty($extractedFiles)) {
				$sourceName = $processLocal ? 'Local temp_covers directory' : ($zipFile ? $zipFile->getClientOriginalName() : 'ZIP file');
				$reason = $processLocal ? 'is empty or contains no processable files.' : ($zipFile ? 'was empty or contained no processable files.' : 'yielded no files.');
				$results['details'][] = ['name' => $sourceName, 'status' => 'info', 'reason' => $reason];
			} else {
				$groupedCovers = [];
				foreach ($extractedFiles as $file) {
					$fileName = $file->getFilename();
					$coverNamePart = null;
					$fileType = null;
					$fileExt = null;

					if (preg_match('/^(.*?)-front-mockup\.png$/i', $fileName, $matches)) {
						$coverNamePart = $matches[1];
						$fileType = 'mockup2d';
					} elseif (preg_match('/^(.*?)-3d-mockup\.png$/i', $fileName, $matches)) {
						$coverNamePart = $matches[1];
						$fileType = 'mockup3d';
					} elseif (preg_match('/^(.*?)-full-cover\.(jpg|jpeg|png|gif)$/i', $fileName, $matches)) {
						$coverNamePart = $matches[1];
						$fileType = 'full_cover';
						$fileExt = strtolower($matches[2]);
					} elseif (preg_match('/^(.*?)\.(jpg|jpeg|png|gif)$/i', $fileName, $matches)) {
						// This must be last to avoid matching mockups/full_covers incorrectly if they also have .jpg etc.
						$coverNamePart = $matches[1];
						$fileType = 'main';
						$fileExt = strtolower($matches[2]);
					}

					if ($coverNamePart && $fileType) {
						if (!isset($groupedCovers[$coverNamePart])) {
							$groupedCovers[$coverNamePart] = [
								'main' => null, 'mockup2d' => null, 'mockup3d' => null, 'full_cover' => null,
								'main_ext' => null, 'full_cover_ext' => null, 'files' => []
							];
						}
						$groupedCovers[$coverNamePart]['files'][$fileType] = $file->getRealPath();
						if ($fileType === 'main') $groupedCovers[$coverNamePart]['main_ext'] = $fileExt;
						if ($fileType === 'full_cover') $groupedCovers[$coverNamePart]['full_cover_ext'] = $fileExt;
					}
				}

				foreach ($groupedCovers as $baseName => $coverFiles) {
					if (empty($coverFiles['files']['main'])) {
						$results['error_count']++;
						$results['details'][] = ['name' => $baseName, 'status' => 'error', 'reason' => "Main image (e.g., {$baseName}.jpg) missing."];
						continue;
					}

					$mainImageFilenameFromZip = $baseName . '.' . $coverFiles['main_ext'];
					$existingCover = Cover::all()->first(function ($c) use ($mainImageFilenameFromZip) {
						return $c->cover_path && basename($c->cover_path) === $mainImageFilenameFromZip;
					});

					$data = [];
					$isNew = !$existingCover;

					if ($isNew) {
						$data['name'] = Str::title(str_replace(['-', '_'], ' ', $baseName));
						$data['cover_type_id'] = $defaultCoverTypeId;
						$data['keywords'] = [];
						$data['categories'] = [];
						$data['caption'] = null;
						$data['text_placements'] = [];
					} else {
						$data['name'] = $existingCover->name;
						$data['cover_type_id'] = $existingCover->cover_type_id; // Keep existing type on update
					}

					try {
						// Main Image
						$mainFile = new SymfonyUploadedFile($coverFiles['files']['main'], basename($coverFiles['files']['main']), mime_content_type($coverFiles['files']['main']), null, true);
						$mainPaths = $this->imageUploadService->uploadImageWithThumbnail($mainFile, 'covers_main', $existingCover->cover_path ?? null, $existingCover->cover_thumbnail_path ?? null);
						$data['cover_path'] = $mainPaths['original_path'];
						$data['cover_thumbnail_path'] = $mainPaths['thumbnail_path'];

						// 2D Mockup
						if (!empty($coverFiles['files']['mockup2d'])) {
							$mockup2dFile = new SymfonyUploadedFile($coverFiles['files']['mockup2d'], basename($coverFiles['files']['mockup2d']), 'image/png', null, true);
							$mockup2dPaths = $this->imageUploadService->uploadImageWithThumbnail($mockup2dFile, 'covers_mockup_2d', $existingCover->mockup_2d_path ?? null);
							$data['mockup_2d_path'] = $mockup2dPaths['original_path'];
						} elseif ($existingCover && $existingCover->mockup_2d_path) {
							$data['mockup_2d_path'] = $existingCover->mockup_2d_path;
						}

						// 3D Mockup
						if (!empty($coverFiles['files']['mockup3d'])) {
							$mockup3dFile = new SymfonyUploadedFile($coverFiles['files']['mockup3d'], basename($coverFiles['files']['mockup3d']), 'image/png', null, true);
							$mockup3dPaths = $this->imageUploadService->uploadImageWithThumbnail($mockup3dFile, 'covers_mockup_3d', $existingCover->mockup_3d_path ?? null);
							$data['mockup_3d_path'] = $mockup3dPaths['original_path'];
						} elseif ($existingCover && $existingCover->mockup_3d_path) {
							$data['mockup_3d_path'] = $existingCover->mockup_3d_path;
						}

						// Full Cover
						if (!empty($coverFiles['files']['full_cover'])) {
							$fullCoverFile = new SymfonyUploadedFile($coverFiles['files']['full_cover'], basename($coverFiles['files']['full_cover']), mime_content_type($coverFiles['files']['full_cover']), null, true);
							$fullCoverPaths = $this->imageUploadService->uploadImageWithThumbnail($fullCoverFile, 'covers_full_cover', $existingCover->full_cover_path ?? null, $existingCover->full_cover_thumbnail_path ?? null);
							$data['full_cover_path'] = $fullCoverPaths['original_path'];
							$data['full_cover_thumbnail_path'] = $fullCoverPaths['thumbnail_path'];
						} elseif ($existingCover && $existingCover->full_cover_path) {
							$data['full_cover_path'] = $existingCover->full_cover_path;
							$data['full_cover_thumbnail_path'] = $existingCover->full_cover_thumbnail_path;
						}

						if ($isNew) {
							$cover = Cover::create($data);
							$results['created_count']++;
							$results['details'][] = ['name' => $data['name'], 'status' => 'created', 'id' => $cover->id];
						} else {
							$existingCover->update($data);
							$results['updated_count']++;
							$results['details'][] = ['name' => $data['name'], 'status' => 'updated', 'id' => $existingCover->id];
						}
					} catch (\Exception $e) {
						Log::error("Error processing cover '{$baseName}' from source: " . $e->getMessage());
						$results['error_count']++;
						$results['details'][] = ['name' => $baseName, 'status' => 'error', 'reason' => Str::limit($e->getMessage(), 100)];
					}
				}
			}
		} catch (\Exception $e) {
			Log::error("Error processing cover upload: " . $e->getMessage() . "\n" . $e->getTraceAsString());
			return response()->json(['success' => false, 'message' => 'An error occurred during processing: ' . $e->getMessage()], 500);
		} finally {
			if ($cleanupTempDir && $tempExtractDir && Storage::disk('local')->exists($tempExtractDir)) {
				Storage::disk('local')->deleteDirectory($tempExtractDir);
			}
		}

		$finalMessage = "Processing complete. ";
		if ($results['created_count'] > 0) $finalMessage .= "{$results['created_count']} created. ";
		if ($results['updated_count'] > 0) $finalMessage .= "{$results['updated_count']} updated. ";
		if ($results['error_count'] > 0) $finalMessage .= "{$results['error_count']} errors. ";
		if (empty($results['details'])) { // If no details were added (e.g. completely empty source)
			$finalMessage .= "No files were processed.";
		}

		return response()->json(['success' => true, 'message' => trim($finalMessage), 'data' => $results]);
	}

	public function updateTemplateJson(Request $request, Template $template) {
		$validator = Validator::make($request->all(), [
			'json_type' => ['required', Rule::in(['front', 'full'])],
			'json_data' => 'required|array',
			'json_data.canvas' => 'required|array',
			'json_data.canvas.width' => 'required|numeric|min:1',
			'json_data.canvas.height' => 'required|numeric|min:1',
			'json_data.layers' => 'nullable|array',
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Invalid data.', 'errors' => $validator->errors()], 422);
		}

		$jsonType = $request->input('json_type');
		$jsonData = $request->input('json_data'); // This will be an array from JSON.parse

		try {
			if ($jsonType === 'front') {
				$template->json_content = $jsonData;
			} elseif ($jsonType === 'full') {
				$template->full_cover_json_content = $jsonData;
			} else {
				return response()->json(['success' => false, 'message' => 'Invalid JSON type specified.'], 400);
			}

			$template->save();
			return response()->json(['success' => true, 'message' => 'Template JSON updated successfully.']);

		} catch (\Exception $e) {
			Log::error("Error updating template JSON for template ID {$template->id}: " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'Server error while updating template JSON.'], 500);
		}
	}
}
