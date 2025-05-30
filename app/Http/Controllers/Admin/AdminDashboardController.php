<?php

	namespace App\Http\Controllers\Admin;

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
						$jsonContentString = file_get_contents($request->file('json_file')->getRealPath());
						$decodedJson = json_decode($jsonContentString, true); // Decode to associative array
						if ($decodedJson === null && json_last_error() !== JSON_ERROR_NONE) {
							return response()->json(['success' => false, 'message' => 'Invalid JSON content in json_file: ' . json_last_error_msg()], 400);
						}
						$data['json_content'] = $decodedJson; // Assign the PHP array
					}

					if ($request->hasFile('full_cover_image_file')) {
						$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('full_cover_image_file'), 'templates_full_cover_image');
						$data['full_cover_image_path'] = $paths['original_path'];
						$data['full_cover_image_thumbnail_path'] = $paths['thumbnail_path'];
					}

					if ($request->hasFile('full_cover_json_file')) {
						$jsonContentString = file_get_contents($request->file('full_cover_json_file')->getRealPath());
						$decodedJson = json_decode($jsonContentString, true); // Decode to associative array
						if ($decodedJson === null && json_last_error() !== JSON_ERROR_NONE) {
							return response()->json(['success' => false, 'message' => 'Invalid JSON content in full_cover_json_file: ' . json_last_error_msg()], 400);
						}
						$data['full_cover_json_content'] = $decodedJson; // Assign the PHP array
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
						$jsonContentString = file_get_contents($request->file('json_file')->getRealPath());
						$decodedJson = json_decode($jsonContentString, true); // Decode to associative array
						if ($decodedJson === null && json_last_error() !== JSON_ERROR_NONE) {
							return response()->json(['success' => false, 'message' => 'Invalid JSON content for update in json_file: ' . json_last_error_msg()], 400);
						}
						$data['json_content'] = $decodedJson; // Assign the PHP array
					}

					if ($request->hasFile('full_cover_image_file')) {
						$paths = $this->imageUploadService->uploadImageWithThumbnail($request->file('full_cover_image_file'), 'templates_full_cover_image', $item->full_cover_image_path, $item->full_cover_image_thumbnail_path);
						$data['full_cover_image_path'] = $paths['original_path'];
						$data['full_cover_image_thumbnail_path'] = $paths['thumbnail_path'];
					}

					if ($request->hasFile('full_cover_json_file')) {
						$jsonContentString = file_get_contents($request->file('full_cover_json_file')->getRealPath());
						$decodedJson = json_decode($jsonContentString, true); // Decode to associative array
						if ($decodedJson === null && json_last_error() !== JSON_ERROR_NONE) {
							return response()->json(['success' => false, 'message' => 'Invalid JSON content for update in full_cover_json_file: ' . json_last_error_msg()], 400);
						}
						$data['full_cover_json_content'] = $decodedJson; // Assign the PHP array
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

		//cloneing
		public function cloneTemplate(Request $request, Template $template)
		{
			return $this->performClone($template, false);
		}

		public function cloneInverseTemplate(Request $request, Template $template)
		{
			return $this->performClone($template, true);
		}

		private function performClone(Template $originalTemplate, bool $invertColors)
		{
			try {
				$clonedData = $originalTemplate->toArray(); // Get attributes as array

				// Unset ID and timestamps for a new record
				unset($clonedData['id']);
				unset($clonedData['created_at']);
				unset($clonedData['updated_at']);

				$clonedData['name'] = $originalTemplate->name . ($invertColors ? ' (Inverse Clone)' : ' (Clone)');

				// Handle image paths - copy files and get new paths
				$baseNameForFiles = Str::slug($clonedData['name']) . '-' . time();

				if ($originalTemplate->cover_image_path) {
					$newCoverImagePaths = $this->copyStorageFile(
						$this->imageUploadService,
						$originalTemplate->cover_image_path,
						'templates_cover_image',
						$baseNameForFiles . '-cover'
					);
					$clonedData['cover_image_path'] = $newCoverImagePaths['original_path'] ?? null;
				} else {
					$clonedData['cover_image_path'] = null;
				}

				if ($originalTemplate->full_cover_image_path) {
					$newFullCoverImagePaths = $this->copyStorageFile(
						$this->imageUploadService,
						$originalTemplate->full_cover_image_path,
						'templates_full_cover_image', // This config key implies thumbnail generation
						$baseNameForFiles . '-full'
					);
					$clonedData['full_cover_image_path'] = $newFullCoverImagePaths['original_path'] ?? null;
					$clonedData['full_cover_image_thumbnail_path'] = $newFullCoverImagePaths['thumbnail_path'] ?? null;
				} else {
					$clonedData['full_cover_image_path'] = null;
					$clonedData['full_cover_image_thumbnail_path'] = null;
				}

				// Handle JSON content
				if ($invertColors) {
					if (is_array($clonedData['json_content'])) {
						// Make a deep copy for modification if it's an array
						$jsonContentCopy = json_decode(json_encode($clonedData['json_content']), true);
						$clonedData['json_content'] = $this->traverseAndInvertColors($jsonContentCopy);
					}
					if (is_array($clonedData['full_cover_json_content'])) {
						// Make a deep copy for modification
						$fullJsonContentCopy = json_decode(json_encode($clonedData['full_cover_json_content']), true);
						$clonedData['full_cover_json_content'] = $this->traverseAndInvertColors($fullJsonContentCopy);
					}
				}
				// If not inverting, JSON is already copied from toArray()

				$clonedTemplate = Template::create($clonedData);

				return response()->json(['success' => true, 'message' => 'Template cloned successfully. ID: ' . $clonedTemplate->id]);

			} catch (\Exception $e) {
				Log::error("Error cloning template ID {$originalTemplate->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
				return response()->json(['success' => false, 'message' => 'Failed to clone template: ' . $e->getMessage()], 500);
			}
		}

		private function copyStorageFile(ImageUploadService $imageUploadService, ?string $sourcePath, string $uploadConfigKey, ?string $customFilenameBase = null): ?array
		{
			if (!$sourcePath || !Storage::disk('public')->exists($sourcePath)) {
				Log::warning("Source file not found for copy: {$sourcePath}");
				return null;
			}

			$fileContent = Storage::disk('public')->get($sourcePath);
			$tempFilePath = tempnam(sys_get_temp_dir(), 'clone_img_');
			if ($tempFilePath === false) {
				Log::error("Failed to create temporary file for cloning.");
				return null;
			}
			file_put_contents($tempFilePath, $fileContent);

			$originalFilename = basename($sourcePath);
			// Guess mime type, fallback if Storage facade doesn't provide it or it's generic
			$mimeType = Storage::disk('public')->mimeType($sourcePath) ?: mime_content_type($tempFilePath) ?: 'application/octet-stream';


			$uploadedFile = new SymfonyUploadedFile(
				$tempFilePath,
				$originalFilename,
				$mimeType,
				null,
				true // Mark as test mode to prevent move after upload
			);

			try {
				$newPaths = $imageUploadService->uploadImageWithThumbnail(
					$uploadedFile,
					$uploadConfigKey,
					null,
					null,
					null,
					$customFilenameBase
				);
			} catch (\Exception $e) {
				Log::error("Error in copyStorageFile using ImageUploadService for {$sourcePath} with config {$uploadConfigKey}: " . $e->getMessage());
				$newPaths = null;
			} finally {
				if (file_exists($tempFilePath)) {
					unlink($tempFilePath);
				}
			}
			return $newPaths;
		}


		private function getNamedColorMapForInversion(): array
		{
			return [
				// Basic colors
				'black' => '#ffffff',
				'white' => '#000000',
				'red' => '#00ffff',
				'lime' => '#ff00ff',
				'blue' => '#ffff00',
				'yellow' => '#0000ff',
				'cyan' => '#ff0000',
				'magenta' => '#00ff00',
				'silver' => '#404040',
				'gray' => '#808080',
				'grey' => '#808080',
				'maroon' => '#80ffff',
				'olive' => '#8080ff',
				'green' => '#ff80ff',
				'purple' => '#80ff80',
				'teal' => '#ff8080',
				'navy' => '#ffff80',
				'fuchsia' => '#00ff00',
				'aqua' => '#ff0000',

				// Extended colors
				'aliceblue' => '#0e0800',
				'antiquewhite' => '#0a1400',
				'aquamarine' => '#804d40',
				'azure' => '#000008',
				'beige' => '#0a140a',
				'bisque' => '#1c2700',
				'blanchedalmond' => '#142a00',
				'blueviolet' => '#4d2ade',
				'brown' => '#5f9f9f',
				'burlywood' => '#2741a5',
				'cadetblue' => '#a05f60',
				'chartreuse' => '#8000ff',
				'chocolate' => '#961e69',
				'coral' => '#0050af',
				'cornflowerblue' => '#9b7236',
				'cornsilk' => '#071800',
				'crimson' => '#dc1478',
				'darkblue' => '#ffff8b',
				'darkcyan' => '#ff8b8b',
				'darkgoldenrod' => '#4756b8',
				'darkgray' => '#545454',
				'darkgrey' => '#545454',
				'darkgreen' => '#ff6400',
				'darkkhaki' => '#4b4376',
				'darkmagenta' => '#8b8b00',
				'darkolivegreen' => '#aa9932',
				'darkorange' => '#0073ff',
				'darkorchid' => '#669932',
				'darkred' => '#8bffff',
				'darksalmon' => '#066996',
				'darkseagreen' => '#734f71',
				'darkslateblue' => '#b89248',
				'darkslategray' => '#d4d4b2',
				'darkslategrey' => '#d4d4b2',
				'darkturquoise' => '#ce0019',
				'darkviolet' => '#9400d3',
				'deeppink' => '#00eb14',
				'deepskyblue' => '#ff4000',
				'dimgray' => '#969696',
				'dimgrey' => '#969696',
				'dodgerblue' => '#e71e00',
				'firebrick' => '#b22222',
				'floralwhite' => '#050005',
				'forestgreen' => '#dd8b22',
				'gainsboro' => '#232323',
				'ghostwhite' => '#070700',
				'gold' => '#0028ff',
				'goldenrod' => '#255adb',
				'greenyellow' => '#5029ff',
				'honeydew' => '#000508',
				'hotpink' => '#40b5b4',
				'indianred' => '#5a92cd',
				'indigo' => '#b2ff4b',
				'ivory' => '#000005',
				'khaki' => '#2d1e90',
				'lavender' => '#191905',
				'lavenderblush' => '#050507',
				'lawngreen' => '#8400fc',
				'lemonchiffon' => '#031900',
				'lightblue' => '#502d2d',
				'lightcoral' => '#0e8080',
				'lightcyan' => '#1f0000',
				'lightgoldenrodyellow' => '#0a0a15',
				'lightgray' => '#333333',
				'lightgrey' => '#333333',
				'lightgreen' => '#100e10',
				'lightpink' => '#0e8681',
				'lightsalmon' => '#05a070',
				'lightseagreen' => '#df9b7f',
				'lightskyblue' => '#820f1e',
				'lightslategray' => '#889977',
				'lightslategrey' => '#889977',
				'lightsteelblue' => '#4f4e76',
				'lightyellow' => '#000005',
				'limegreen' => '#cd32cd',
				'linen' => '#0a0a05',
				'mediumaquamarine' => '#99cc33',
				'mediumblue' => '#ffff00',
				'mediumorchid' => '#475574',
				'mediumpurple' => '#705f48',
				'mediumseagreen' => '#c1b371',
				'mediumslateblue' => '#8b6868',
				'mediumspringgreen' => '#ff9a00',
				'mediumturquoise' => '#b8d148',
				'mediumvioletred' => '#148a15',
				'midnightblue' => '#e4e470',
				'mintcream' => '#0a0505',
				'mistyrose' => '#051e1a',
				'moccasin' => '#1e2ab5',
				'navajowhite' => '#2335ad',
				'oldlace' => '#030208',
				'olivedrab' => '#982296',
				'orange' => '#0065ff',
				'orangered' => '#0045ff',
				'orchid' => '#25469b',
				'palegoldenrod' => '#1e1ba8',
				'palegreen' => '#061506',
				'paleturquoise' => '#500e1e',
				'palevioletred' => '#247093',
				'papayawhip' => '#0d1405',
				'peachpuff' => '#2a40b5',
				'peru' => '#3f7f80',
				'pink' => '#0a4040',
				'plum' => '#225020',
				'powderblue' => '#4f1e19',
				'rosybrown' => '#476e47',
				'royalblue' => '#be9741',
				'saddlebrown' => '#721b8b',
				'salmon' => '#0a8072',
				'sandybrown' => '#0b5ca4',
				'seagreen' => '#d19b57',
				'seashell' => '#0a0508',
				'sienna' => '#5ed2a0',
				'skyblue' => '#821e0f',
				'slateblue' => '#955a2d',
				'slategray' => '#8f8f70',
				'slategrey' => '#8f8f70',
				'snow' => '#050505',
				'springgreen' => '#ff7f00',
				'steelblue' => '#b97346',
				'tan' => '#331fd2',
				'thistle' => '#272728',
				'tomato' => '#0c6347',
				'turquoise' => '#bf20e0',
				'violet' => '#1d1dee',
				'wheat' => '#0a2245',
				'whitesmoke' => '#0a0a0a',
				'yellowgreen' => '#6532cd'
			];
		}

		private function invertColor(string $color): string
		{
			$colorInput = trim($color); // Keep original case for named colors if map is case-sensitive
			$colorLower = strtolower($colorInput);

			// Handle hex: #RRGGBB or #RGB
			if (preg_match('/^#([a-f0-9]{3})$/i', $colorLower, $matches)) { // #RGB
				$r = hexdec($matches[1][0] . $matches[1][0]);
				$g = hexdec($matches[1][1] . $matches[1][1]);
				$b = hexdec($matches[1][2] . $matches[1][2]);
				return sprintf("#%02x%02x%02x", 255 - $r, 255 - $g, 255 - $b);
			} elseif (preg_match('/^#([a-f0-9]{6})$/i', $colorLower, $matches)) { // #RRGGBB
				$r = hexdec(substr($matches[1], 0, 2));
				$g = hexdec(substr($matches[1], 2, 2));
				$b = hexdec(substr($matches[1], 4, 2));
				return sprintf("#%02x%02x%02x", 255 - $r, 255 - $g, 255 - $b);
			}

			// Handle rgb(r,g,b)
			if (preg_match('/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/i', $colorLower, $matches)) {
				$r = min(255, max(0, (int)$matches[1]));
				$g = min(255, max(0, (int)$matches[2]));
				$b = min(255, max(0, (int)$matches[3]));
				return sprintf("rgb(%d, %d, %d)", 255 - $r, 255 - $g, 255 - $b);
			}

			// Handle rgba(r,g,b,a) - keep alpha
			if (preg_match('/^rgba\((\d+),\s*(\d+),\s*(\d+),\s*([\d\.]+)\)$/i', $colorLower, $matches)) {
				$r = min(255, max(0, (int)$matches[1]));
				$g = min(255, max(0, (int)$matches[2]));
				$b = min(255, max(0, (int)$matches[3]));
				$a = $matches[4]; // Keep alpha as is
				return sprintf("rgba(%d, %d, %d, %s)", 255 - $r, 255 - $g, 255 - $b, $a);
			}

			$namedColors = $this->getNamedColorMapForInversion();
			if (isset($namedColors[$colorLower])) {
				return $namedColors[$colorLower];
			}

			return $colorInput; // Return original if not recognized
		}

		private function &traverseAndInvertColors(array &$data): array
		{
			$colorKeys = ['fill', 'stroke', 'color', 'shadowColor', 'backgroundColor', 'borderColor', 'outlineColor', 'textStrokeColor', 'stopColor']; // Common keys that hold color values

			foreach ($data as $key => &$value) {
				if (is_array($value)) {
					$this->traverseAndInvertColors($value);
				} elseif (is_string($value)) {
					$potentialColor = false;
					if (in_array($key, $colorKeys, true)) { // If key is a known color key
						$potentialColor = true;
					} elseif (preg_match('/^(#([a-f0-9]{3}|[a-f0-9]{6})|rgba?\([\d\s,.]+\))$/i', $value)) { // If value looks like hex/rgb(a)
						$potentialColor = true;
					} elseif (array_key_exists(strtolower($value), $this->getNamedColorMapForInversion())) { // If value is a known named color
						$potentialColor = true;
					}


					if ($potentialColor) {
						$inverted = $this->invertColor($value);
						if ($inverted !== $value) {
							// Log::debug("Inverting color: '{$value}' to '{$inverted}' for key '{$key}'");
							$value = $inverted;
						}
					}
				}
			}
			return $data;
		}

	}
