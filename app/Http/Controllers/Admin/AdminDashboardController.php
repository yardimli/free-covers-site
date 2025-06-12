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
use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image as InterventionImageFacade;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

// Renamed to avoid conflict

class AdminDashboardController extends Controller
{
	protected ImageUploadService $imageUploadService;

	public function __construct(ImageUploadService $imageUploadService)
	{
		$this->imageUploadService = $imageUploadService;
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
			'elements' => new Element(),
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
			'sort_by' => ['nullable', 'string', Rule::in(['id', 'name'])],
			'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
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
		$sortBy = $request->input('sort_by', 'id');
		$sortDirection = $request->input('sort_direction', 'desc');

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

		$query->orderBy($sortBy, $sortDirection);
		if ($sortBy !== 'id') {
			$query->orderBy('id', 'desc');
		}

		$paginatedItems = $query->paginate($limit, ['*'], 'page', $page);

		$items = $paginatedItems->getCollection()->map(function ($item) use ($itemType) {
			if (isset($item->image_path)) {
				$item->image_url = $this->imageUploadService->getUrl($item->image_path);
			}
			if (isset($item->thumbnail_path)) {
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
			//'name' => 'required|string|max:255', // Name is now optional, derived if empty
			'keywords' => 'nullable|string|max:1000',
		];

		if ($itemType === 'covers' || $itemType === 'templates') {
			$rules['cover_type_id'] = 'nullable|integer|exists:cover_types,id';
		}

		if ($itemType === 'covers') {
			$rules['caption'] = 'nullable|string|max:500';
			$rules['categories'] = 'nullable|string|max:1000';
			$rules['main_image_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['mockup_2d_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['mockup_3d_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['full_cover_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
		} elseif ($itemType === 'elements' || $itemType === 'overlays') {
			$rules['image_file'] = 'required|image|mimes:jpg,jpeg,png,gif|max:5120';
		} elseif ($itemType === 'templates') {
			$rules['cover_image_file'] = 'required|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['json_file'] = 'required|file|mimes:json|max:2048';
			$rules['full_cover_image_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['full_cover_json_file'] = 'nullable|file|mimes:json|max:2048';
		}

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
		}

		if ($itemType === 'covers' && !$request->hasFile('main_image_file') && !$request->hasFile('full_cover_file')) {
			return response()->json([
				'success' => false,
				'message' => 'Validation failed.',
				'errors' => ['main_image_file' => ['Either a Main Cover Image or a Full Cover Image is required.']]
			], 422);
		}

		$model = $this->getModelInstance($itemType);
		if (!$model) return response()->json(['success' => false, 'message' => 'Invalid item type.'], 400);

		$data = [
			'name' => $request->input('name') ?? '',
			'keywords' => $request->input('keywords') ? array_map('trim', explode(',', $request->input('keywords'))) : [],
		];

		if ($itemType === 'covers' || $itemType === 'templates') {
			$data['cover_type_id'] = $request->input('cover_type_id');
		}

		$tempCroppedFilePathForCleanup = null;

		try {
			if ($itemType === 'covers') {
				$mainImageFile = $request->file('main_image_file');
				$fullCoverFileFromRequest = $request->file('full_cover_file');

				if ($mainImageFile) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($mainImageFile, 'covers_main');
					$data['cover_path'] = $paths['original_path'];
					$data['cover_thumbnail_path'] = $paths['thumbnail_path'];
					if (empty($data['name'])) {
						$data['name'] = pathinfo($mainImageFile->getClientOriginalName(), PATHINFO_FILENAME);
						$data['name'] = str_replace(['-', '_'], ' ', $data['name']);
					}
				} elseif ($fullCoverFileFromRequest) {
					if (empty($data['name'])) {
						$data['name'] = pathinfo($fullCoverFileFromRequest->getClientOriginalName(), PATHINFO_FILENAME);
						$data['name'] = str_replace(['-', '_'], ' ', $data['name']);
					}
					try {
						$image = InterventionImageFacade::read($fullCoverFileFromRequest->getRealPath());
						$cropWidth = 2100;
						$cropHeight = 3360;
						if ($image->width() < $cropWidth || $image->height() < $cropHeight) {
							Log::warning("Full cover image for '{$data['name']}' is smaller than {$cropWidth}x{$cropHeight}. Cropping may not be as expected. Image dimensions: {$image->width()}x{$image->height()}");
						}
						$offsetX = max(0, $image->width() - $cropWidth);
						$offsetY = 0;
						$image->crop($cropWidth, $cropHeight, $offsetX, $offsetY);

						$tempFilename = 'cropped_main_' . Str::random(10) . '.' . $fullCoverFileFromRequest->getClientOriginalExtension();
						$tempStorageDir = storage_path('app/temp');
						File::ensureDirectoryExists($tempStorageDir);
						$tempCroppedFilePathForCleanup = $tempStorageDir . '/' . $tempFilename;
						$image->save($tempCroppedFilePathForCleanup);

						$tempCroppedSymfonyFile = new SymfonyUploadedFile(
							$tempCroppedFilePathForCleanup,
							$tempFilename,
							mime_content_type($tempCroppedFilePathForCleanup),
							null,
							true
						);
						$paths = $this->imageUploadService->uploadImageWithThumbnail($tempCroppedSymfonyFile, 'covers_main');
						$data['cover_path'] = $paths['original_path'];
						$data['cover_thumbnail_path'] = $paths['thumbnail_path'];
					} catch (\Exception $cropException) {
						Log::error("Error cropping full cover '{$fullCoverFileFromRequest->getClientOriginalName()}' for main image: " . $cropException->getMessage());
						return response()->json(['success' => false, 'message' => 'Error processing full cover image to create main cover: ' . $cropException->getMessage()], 500);
					}
				}

				if ($request->hasFile('mockup_2d_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('mockup_2d_file'), 'covers_mockup_2d');
					$data['mockup_2d_path'] = $paths['original_path'];
				} else {
					if (isset($data['cover_path'])) {
						$data['mockup_2d_path'] = $data['cover_path'];
					}
				}

				if ($request->hasFile('mockup_3d_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('mockup_3d_file'), 'covers_mockup_3d');
					$data['mockup_3d_path'] = $paths['original_path'];
				} else {
					if (isset($data['cover_path'])) {
						$data['mockup_3d_path'] = $data['cover_path'];
					}
				}

				if ($fullCoverFileFromRequest) { // Process the original full cover file if it was provided
					$paths = $this->imageUploadService->uploadImageWithThumbnail($fullCoverFileFromRequest, 'covers_full_cover');
					$data['full_cover_path'] = $paths['original_path'];
					$data['full_cover_thumbnail_path'] = $paths['thumbnail_path'];
				}

				$data['caption'] = $request->input('caption');
				$data['categories'] = $request->input('categories') ? array_map('trim', explode(',', $request->input('categories'))) : [];
				$data['text_placements'] = [];

			} elseif ($itemType === 'elements' || $itemType === 'overlays') {
				if ($request->hasFile('image_file')) {
					$imageFile = $request->file('image_file');
					$uploadConfigKey = $itemType . '_main';
					$paths = $this->imageUploadService->uploadImageWithThumbnail($imageFile, $uploadConfigKey);
					$data['image_path'] = $paths['original_path'];
					$data['thumbnail_path'] = $paths['thumbnail_path'];
					if (empty($data['name'])) {
						$data['name'] = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
						$data['name'] = str_replace(['-', '_'], ' ', $data['name']);
					}
				}
			} elseif ($itemType === 'templates') {
				if (empty($data['name'])) {
					$data['name'] = 'template_' . Str::random(10); // Default name if not provided
				}
				if ($request->hasFile('cover_image_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('cover_image_file'), 'templates_cover_image');
					$data['cover_image_path'] = $paths['original_path'];
				}
				if ($request->hasFile('json_file')) {
					$jsonContentString = file_get_contents($request->file('json_file')->getRealPath());
					$decodedJson = json_decode($jsonContentString, true);
					if ($decodedJson === null && json_last_error() !== JSON_ERROR_NONE) {
						return response()->json(['success' => false, 'message' => 'Invalid JSON content in json_file: ' . json_last_error_msg()], 400);
					}
					$data['json_content'] = $decodedJson;
				}
				if ($request->hasFile('full_cover_image_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('full_cover_image_file'), 'templates_full_cover_image');
					$data['full_cover_image_path'] = $paths['original_path'];
					$data['full_cover_image_thumbnail_path'] = $paths['thumbnail_path'];
				}
				if ($request->hasFile('full_cover_json_file')) {
					$jsonContentString = file_get_contents($request->file('full_cover_json_file')->getRealPath());
					$decodedJson = json_decode($jsonContentString, true);
					if ($decodedJson === null && json_last_error() !== JSON_ERROR_NONE) {
						return response()->json(['success' => false, 'message' => 'Invalid JSON content in full_cover_json_file: ' . json_last_error_msg()], 400);
					}
					$data['full_cover_json_content'] = $decodedJson;
				}
				if (!isset($data['text_placements'])) {
					$data['text_placements'] = [];
				}
			}

			$item = $model->create($data);

			if ($tempCroppedFilePathForCleanup && File::exists($tempCroppedFilePathForCleanup)) {
				File::delete($tempCroppedFilePathForCleanup);
			}

			return response()->json(['success' => true, 'message' => ucfirst(Str::singular($itemType)) . ' uploaded successfully.', 'data' => ['id' => $item->id]]);

		} catch (\Exception $e) {
			if ($tempCroppedFilePathForCleanup && File::exists($tempCroppedFilePathForCleanup)) {
				File::delete($tempCroppedFilePathForCleanup);
			}
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
		} elseif ($itemType === 'elements' || $itemType === 'overlays') {
			if (isset($item->image_path)) {
				$item->image_url = $this->imageUploadService->getUrl($item->image_path);
			}
			if (isset($item->thumbnail_path)) {
				$item->thumbnail_url = $this->imageUploadService->getUrl($item->thumbnail_path);
			}
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

		if ($itemType === 'covers') {
			$rules['caption'] = 'nullable|string|max:500';
			$rules['categories'] = 'nullable|string|max:1000';
			$rules['text_placements'] = 'nullable|string|max:1000';
			$rules['main_image_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['mockup_2d_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['mockup_3d_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
			$rules['full_cover_file'] = 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120';
		} elseif ($itemType === 'templates') {
			$rules['text_placements'] = 'nullable|string|max:1000';
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
					$jsonContentString = file_get_contents($request->file('json_file')->getRealPath());
					$decodedJson = json_decode($jsonContentString, true);
					if ($decodedJson === null && json_last_error() !== JSON_ERROR_NONE) {
						return response()->json(['success' => false, 'message' => 'Invalid JSON content for update in json_file: ' . json_last_error_msg()], 400);
					}
					$data['json_content'] = $decodedJson;
				}
				if ($request->hasFile('full_cover_image_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('full_cover_image_file'), 'templates_full_cover_image', $item->full_cover_image_path, $item->full_cover_image_thumbnail_path);
					$data['full_cover_image_path'] = $paths['original_path'];
					$data['full_cover_image_thumbnail_path'] = $paths['thumbnail_path'];
				}
				if ($request->hasFile('full_cover_json_file')) {
					$jsonContentString = file_get_contents($request->file('full_cover_json_file')->getRealPath());
					$decodedJson = json_decode($jsonContentString, true);
					if ($decodedJson === null && json_last_error() !== JSON_ERROR_NONE) {
						return response()->json(['success' => false, 'message' => 'Invalid JSON content for update in full_cover_json_file: ' . json_last_error_msg()], 400);
					}
					$data['full_cover_json_content'] = $decodedJson;
				}
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
			} elseif (isset($item->image_path) && isset($item->thumbnail_path)) {
				$pathsToDelete[] = $item->image_path;
				$pathsToDelete[] = $item->thumbnail_path;
			}

			$item->delete();

			if (!empty($pathsToDelete)) {
				$this->imageUploadService->deleteImageFiles(array_filter($pathsToDelete));
			}
			return response()->json(['success' => true, 'message' => ucfirst(Str::singular($itemType)) . ' deleted successfully.']);
		} catch (\Exception $e) {
			Log::error("Delete item error ({$itemType} ID {$id}): " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()], 500);
		}
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
			$rules['cover_zip_file'] = 'nullable|file|mimes:zip|max:102400';
		}

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
		}

		$zipFile = $request->file('cover_zip_file');
		$defaultCoverTypeId = $request->input('default_cover_type_id') ?: 1;

		$tempExtractDir = null;
		$realTempExtractPath = null;
		$cleanupTempDir = false;
		$results = ['created_count' => 0, 'updated_count' => 0, 'error_count' => 0, 'details' => []];

		$tempCropBaseDir = storage_path('app/temp_zip_crops'); // For cropped main images from full_cover
		File::ensureDirectoryExists($tempCropBaseDir);

		try {
			if ($processLocal) {
				$localTempCoversPath = 'temp_covers';
				if (!Storage::disk('local')->exists($localTempCoversPath)) {
					Storage::disk('local')->makeDirectory($localTempCoversPath);
					$results['details'][] = ['name' => 'Local temp_covers', 'status' => 'info', 'reason' => 'Directory was created and is empty. No files to process.'];
				}
				$realTempExtractPath = Storage::disk('local')->path($localTempCoversPath);
			} else {
				if (!$zipFile) {
					return response()->json(['success' => false, 'message' => 'ZIP file is required.'], 422);
				}
				$tempExtractDir = 'temp_zip_uploads/' . Str::uuid();
				Storage::disk('local')->makeDirectory($tempExtractDir);
				$realTempExtractPath = Storage::disk('local')->path($tempExtractDir);
				$cleanupTempDir = true;

				$zip = new ZipArchive;
				if ($zip->open($zipFile->getRealPath()) === TRUE) {
					$zip->extractTo($realTempExtractPath);
					$zip->close();
				} else {
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
					$hasReal2D = false;
					$hasReal3D = false;

					if (preg_match('/^(.*?)-front-mockup\.png$/i', $fileName, $matches)) {
						$coverNamePart = $matches[1];
						$fileType = 'mockup2d';
						$hasReal2D = true;
					} elseif (preg_match('/^(.*?)-3d-mockup\.png$/i', $fileName, $matches)) {
						$coverNamePart = $matches[1];
						$fileType = 'mockup3d';
						$hasReal3D = true;
					} elseif (preg_match('/^(.*?)-full-cover\.(jpg|jpeg|png|gif)$/i', $fileName, $matches)) {
						$coverNamePart = $matches[1];
						$fileType = 'full_cover';
						$fileExt = strtolower($matches[2]);
					} elseif (preg_match('/^(.*?)\.(jpg|jpeg|png|gif)$/i', $fileName, $matches)) {
						$coverNamePart = $matches[1];
						$fileType = 'main';
						$fileExt = strtolower($matches[2]);
					}

					if ($coverNamePart && $fileType) {
						if (!isset($groupedCovers[$coverNamePart])) {
							$groupedCovers[$coverNamePart] = ['main' => null, 'mockup2d' => null, 'mockup3d' => null, 'full_cover' => null, 'main_ext' => null, 'full_cover_ext' => null, 'files' => []];
							$groupedCovers[$coverNamePart]['has_real_2d'] = $hasReal2D;
							$groupedCovers[$coverNamePart]['has_real_3d'] = $hasReal3D;
						}
						$groupedCovers[$coverNamePart]['files'][$fileType] = $file->getRealPath();
						if ($fileType === 'main') $groupedCovers[$coverNamePart]['main_ext'] = $fileExt;
						if ($fileType === 'full_cover') $groupedCovers[$coverNamePart]['full_cover_ext'] = $fileExt;
					}
				}

				foreach ($groupedCovers as $baseName => $coverFiles) {
					$tempCroppedFilePathForCleanupThisItem = null;
					try {
						// Validation: Must have either main or full_cover
						if (empty($coverFiles['files']['main']) && empty($coverFiles['files']['full_cover'])) {
							$results['error_count']++;
							$results['details'][] = ['name' => $baseName, 'status' => 'error', 'reason' => "Main image (e.g., {$baseName}.jpg) or Full Cover (e.g., {$baseName}-full-cover.jpg) missing."];
							continue;
						}

						$existingCover = null;
						$mainImageFileForSymfony = null;
						$mainImageOriginalNameForSymfony = null;

						if (!empty($coverFiles['files']['main'])) {
							$mainFileRealPath = $coverFiles['files']['main'];
							$mainImageOriginalNameForSymfony = basename($mainFileRealPath);
							$mainImageFilenameFromZip = $baseName . '.' . $coverFiles['main_ext'];

							// More efficient lookup than Cover::all()->first()
							// This assumes cover_path stores filenames like '.../filename.ext'
							// and ImageUploadService doesn't drastically change the name from $mainImageFilenameFromZip
							// or that $mainImageFilenameFromZip is a unique identifier stored elsewhere.
							// For robustness, matching by a unique 'name' field derived from $baseName might be better
							// if $mainImageFilenameFromZip isn't reliably the basename of cover_path.
							// $existingCover = Cover::where('name', Str::title(str_replace(['-', '_'], ' ', $baseName)))->first();
							// Sticking to the original logic's spirit (matching by filename):
							$existingCover = Cover::whereRaw('SUBSTRING_INDEX(cover_path, "/", -1) = ?', [$mainImageFilenameFromZip])->first();
							// If the above Raw query is problematic for some DBs, an alternative (less precise):
							// $existingCover = Cover::where('cover_path', 'LIKE', '%' . $mainImageFilenameFromZip)->first();


							$mainImageFileForSymfony = new SymfonyUploadedFile($mainFileRealPath, $mainImageOriginalNameForSymfony, mime_content_type($mainFileRealPath), null, true);
						} elseif (!empty($coverFiles['files']['full_cover'])) {
							// Main image not present, derive from full_cover. $existingCover remains null.
							$fullCoverRealPath = $coverFiles['files']['full_cover'];
							$fullCoverOriginalName = basename($fullCoverRealPath);
							$fullCoverExtension = $coverFiles['full_cover_ext'] ?? pathinfo($fullCoverOriginalName, PATHINFO_EXTENSION);

							try {
								$image = InterventionImageFacade::read($fullCoverRealPath);
								$cropWidth = 2100;
								$cropHeight = 3360;
								if ($image->width() < $cropWidth || $image->height() < $cropHeight) {
									Log::warning("ZIP Upload: Full cover for '{$baseName}' is smaller than {$cropWidth}x{$cropHeight}. Cropping may not be as expected. Image dimensions: {$image->width()}x{$image->height()}");
								}
								$offsetX = max(0, $image->width() - $cropWidth);
								$offsetY = 0;
								$image->crop($cropWidth, $cropHeight, $offsetX, $offsetY);

								$tempFilename = 'cropped_main_zip_' . Str::random(10) . '.' . $fullCoverExtension;
								$tempCroppedFilePathForCleanupThisItem = $tempCropBaseDir . '/' . $tempFilename;
								$image->save($tempCroppedFilePathForCleanupThisItem);
								$mainImageOriginalNameForSymfony = $baseName . '.' . $fullCoverExtension; // Construct a name
								$mainImageFileForSymfony = new SymfonyUploadedFile($tempCroppedFilePathForCleanupThisItem, $mainImageOriginalNameForSymfony, mime_content_type($tempCroppedFilePathForCleanupThisItem), null, true);
							} catch (\Exception $cropException) {
								Log::error("Error cropping full cover '{$fullCoverOriginalName}' from ZIP for '{$baseName}': " . $cropException->getMessage());
								$results['error_count']++;
								$results['details'][] = ['name' => $baseName, 'status' => 'error', 'reason' => 'Error creating main image from full cover: ' . Str::limit($cropException->getMessage(), 70)];
								if ($tempCroppedFilePathForCleanupThisItem && File::exists($tempCroppedFilePathForCleanupThisItem)) {
									File::delete($tempCroppedFilePathForCleanupThisItem);
								}
								continue;
							}
						}

						if (!$mainImageFileForSymfony) { // Should not happen if validation above is correct
							$results['error_count']++;
							$results['details'][] = ['name' => $baseName, 'status' => 'error', 'reason' => "Failed to prepare main image file."];
							if ($tempCroppedFilePathForCleanupThisItem && File::exists($tempCroppedFilePathForCleanupThisItem)) {
								File::delete($tempCroppedFilePathForCleanupThisItem);
							}
							continue;
						}

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
							$data['cover_type_id'] = $existingCover->cover_type_id;
							$data['caption'] = $existingCover->caption;
							$data['keywords'] = $existingCover->keywords;
							$data['categories'] = $existingCover->categories;
							$data['text_placements'] = $existingCover->text_placements;
						}

						// Main Image
						$mainPaths = $this->imageUploadService->uploadImageWithThumbnail($mainImageFileForSymfony, 'covers_main', $existingCover->cover_path ?? null, $existingCover->cover_thumbnail_path ?? null);
						$data['cover_path'] = $mainPaths['original_path'];
						$data['cover_thumbnail_path'] = $mainPaths['thumbnail_path'];

						// 2D Mockup
						if (!empty($coverFiles['files']['mockup2d'])) {
							$mockup2dFile = new SymfonyUploadedFile($coverFiles['files']['mockup2d'], basename($coverFiles['files']['mockup2d']), 'image/png', null, true);
							$mockup2dPaths = $this->imageUploadService->uploadImageWithThumbnail($mockup2dFile, 'covers_mockup_2d', $existingCover->mockup_2d_path ?? null);
							$data['mockup_2d_path'] = $mockup2dPaths['original_path'];
						} else { // Default to main cover_path if not in zip
							$data['mockup_2d_path'] = $data['cover_path'];
						}

						// 3D Mockup
						if (!empty($coverFiles['files']['mockup3d'])) {
							$mockup3dFile = new SymfonyUploadedFile($coverFiles['files']['mockup3d'], basename($coverFiles['files']['mockup3d']), 'image/png', null, true);
							$mockup3dPaths = $this->imageUploadService->uploadImageWithThumbnail($mockup3dFile, 'covers_mockup_3d', $existingCover->mockup_3d_path ?? null);
							$data['mockup_3d_path'] = $mockup3dPaths['original_path'];
						} else { // Default to main cover_path if not in zip
							$data['mockup_3d_path'] = $data['cover_path'];
						}

						// Full Cover
						if (!empty($coverFiles['files']['full_cover'])) {
							$fullCoverFile = new SymfonyUploadedFile($coverFiles['files']['full_cover'], basename($coverFiles['files']['full_cover']), mime_content_type($coverFiles['files']['full_cover']), null, true);
							$fullCoverPaths = $this->imageUploadService->uploadImageWithThumbnail($fullCoverFile, 'covers_full_cover', $existingCover->full_cover_path ?? null, $existingCover->full_cover_thumbnail_path ?? null);
							$data['full_cover_path'] = $fullCoverPaths['original_path'];
							$data['full_cover_thumbnail_path'] = $fullCoverPaths['thumbnail_path'];
						} elseif ($existingCover) { // Not in zip, but updating existing: preserve existing full cover
							$data['full_cover_path'] = $existingCover->full_cover_path;
							$data['full_cover_thumbnail_path'] = $existingCover->full_cover_thumbnail_path;
						} else { // Not in zip, new item: set to null
							$data['full_cover_path'] = null;
							$data['full_cover_thumbnail_path'] = null;
						}
						$data['has_real_2d'] = $coverFiles['has_real_2d'] ?? false;
						$data['has_real_3d'] = $coverFiles['has_real_3d'] ?? false;

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
						Log::error("Error processing cover '{$baseName}' from ZIP: " . $e->getMessage() . "\n" . $e->getTraceAsString());
						$results['error_count']++;
						$results['details'][] = ['name' => $baseName, 'status' => 'error', 'reason' => Str::limit($e->getMessage(), 100)];
					} finally {
						if ($tempCroppedFilePathForCleanupThisItem && File::exists($tempCroppedFilePathForCleanupThisItem)) {
							File::delete($tempCroppedFilePathForCleanupThisItem);
						}
					}
				}
			}
		} catch (\Exception $e) {
			Log::error("Error processing cover ZIP upload: " . $e->getMessage() . "\n" . $e->getTraceAsString());
			return response()->json(['success' => false, 'message' => 'An error occurred during processing: ' . $e->getMessage()], 500);
		} finally {
			if ($cleanupTempDir && $tempExtractDir && Storage::disk('local')->exists($tempExtractDir)) {
				Storage::disk('local')->deleteDirectory($tempExtractDir);
			}
			// Optionally, clean up $tempCropBaseDir if it's meant to be empty after each run,
			// but individual file cleanup within the loop is generally safer.
			// If $tempCropBaseDir can grow large, a periodic cleanup job might be better.
		}

		$finalMessage = "Processing complete. ";
		if ($results['created_count'] > 0) $finalMessage .= "{$results['created_count']} created. ";
		if ($results['updated_count'] > 0) $finalMessage .= "{$results['updated_count']} updated. ";
		if ($results['error_count'] > 0) $finalMessage .= "{$results['error_count']} errors. ";
		if (empty($results['details']) && $results['created_count'] === 0 && $results['updated_count'] === 0 && $results['error_count'] === 0) {
			$finalMessage .= "No files were processed from the source.";
		}

		return response()->json(['success' => true, 'message' => trim($finalMessage), 'data' => $results]);
	}
}
