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
use Intervention\Image\Laravel\Facades\Image;

// For logging

class DashboardController extends Controller
{
	protected ImageUploadService $imageUploadService;
	protected OpenAiService $openAiService;

	public function __construct(ImageUploadService $imageUploadService, OpenAiService $openAiService)
	{
		$this->imageUploadService = $imageUploadService;
		$this->openAiService = $openAiService;
		// $this->middleware('auth'); // Add auth middleware if needed
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
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Invalid input.', 'errors' => $validator->errors()], 422);
		}

		$itemType = $request->input('type');
		$page = $request->input('page', 1);
		$limit = $request->input('limit', config('admin_settings.items_per_page', 30));
		$search = $request->input('search');
		$coverTypeIdFilter = $request->input('cover_type_id');

		$model = $this->getModelInstance($itemType);
		if (!$model) {
			return response()->json(['success' => false, 'message' => 'Invalid item type.'], 400);
		}

		$query = $model->query();

		if ($itemType === 'covers') {
			$query->with(['coverType:id,type_name', 'templates:id,name']); // Eager load templates for covers
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

		$paginatedItems = $query->orderBy('id', 'desc')->paginate($limit, ['*'], 'page', $page);

		$items = $paginatedItems->getCollection()->map(function ($item) use ($itemType) {
			if (isset($item->thumbnail_path)) {
				$item->thumbnail_url = $this->imageUploadService->getUrl($item->thumbnail_path);
			}
			if (isset($item->image_path)) {
				$item->image_url = $this->imageUploadService->getUrl($item->image_path);
			}

			if ($itemType === 'covers') {
				$item->cover_type_name = $item->coverType->type_name ?? null;
				$item->assigned_templates_count = $item->templates->count();
				$item->assigned_templates_names = $item->templates->isNotEmpty() ? $item->templates->pluck('name')->implode(', ') : 'None';
			} elseif ($itemType === 'templates') {
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
			'name' => 'required_without:image_file,json_file|nullable|string|max:255',
			'keywords' => 'nullable|string|max:1000', // Comma-separated string from form
		];

		if ($itemType === 'covers' || $itemType === 'templates') {
			$rules['cover_type_id'] = 'nullable|integer|exists:cover_types,id';
		}

		if ($itemType === 'covers') {
			$rules['caption'] = 'nullable|string|max:500';
			$rules['categories'] = 'nullable|string|max:1000'; // Comma-separated string from form
			$rules['image_file'] = 'required|image|mimes:jpg,jpeg,png,gif|max:5120';
		} elseif ($itemType === 'elements' || $itemType === 'overlays') {
			$rules['image_file'] = 'required|image|mimes:jpg,jpeg,png,gif|max:5120';
		} elseif ($itemType === 'templates') {
			$rules['json_file'] = 'required|file|mimes:json|max:2048';
			$rules['thumbnail_file'] = 'required|image|mimes:jpg,jpeg,png,gif|max:5120';
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
			if ($itemType === 'covers' || $itemType === 'elements' || $itemType === 'overlays') {
				$imageFile = $request->file('image_file');
				if (!$request->input('name')) {
					$data['name'] = Str::title(str_replace(['-', '_'], ' ', pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)));
				}
				$paths = $this->imageUploadService->uploadImageWithThumbnail($imageFile, $itemType);
				$data['image_path'] = $paths['original_path'];
				$data['thumbnail_path'] = $paths['thumbnail_path'];

				if ($itemType === 'covers') {
					$data['caption'] = $request->input('caption');
					$data['categories'] = $request->input('categories') ? array_map('trim', explode(',', $request->input('categories'))) : [];
					$data['text_placements'] = []; // Initialize as empty array
				}
			} elseif ($itemType === 'templates') {
				$jsonFile = $request->file('json_file');
				$thumbnailFile = $request->file('thumbnail_file');
				if (!$request->input('name')) {
					$data['name'] = Str::title(str_replace(['-', '_'], ' ', pathinfo($jsonFile->getClientOriginalName(), PATHINFO_FILENAME)));
				}
				$jsonContent = file_get_contents($jsonFile->getRealPath());
				if (json_decode($jsonContent) === null && json_last_error() !== JSON_ERROR_NONE) {
					return response()->json(['success' => false, 'message' => 'Invalid JSON content: ' . json_last_error_msg()], 400);
				}
				$data['json_content'] = $jsonContent; // Store as string, model will cast if needed (or not, if it's just text)
				$paths = $this->imageUploadService->uploadImageWithThumbnail($thumbnailFile, $itemType);
				$data['thumbnail_path'] = $paths['thumbnail_path'];
				// Templates can also have text_placements, initialize if not already handled by model defaults
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

		// Convert arrays to comma-separated strings for form fields that expect it
		$item->keywords_string_for_form = is_array($item->keywords) ? implode(', ', $item->keywords) : '';

		if ($itemType === 'covers') {
			$item->categories_string_for_form = is_array($item->categories) ? implode(', ', $item->categories) : '';
			$item->text_placements_string_for_form = is_array($item->text_placements) ? implode(', ', $item->text_placements) : '';
			$item->assigned_template_ids = $item->templates->pluck('id')->toArray();
			unset($item->templates);
		} elseif ($itemType === 'templates') {
			$item->text_placements_string_for_form = is_array($item->text_placements) ? implode(', ', $item->text_placements) : '';
		}


		if (isset($item->thumbnail_path)) $item->thumbnail_url = $this->imageUploadService->getUrl($item->thumbnail_path);
		if (isset($item->image_path)) $item->image_url = $this->imageUploadService->getUrl($item->image_path);

		if ($itemType === 'covers' || $itemType === 'templates') {
			$item->cover_type_name = $item->coverType->type_name ?? null;
		}
		// text_placements itself is returned as an array due to model casting, used by the new modal

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
			'keywords' => 'nullable|string|max:1000', // Comma-separated string from form
			'image_file' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
			'thumbnail_file' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
			'json_file' => 'nullable|file|mimes:json|max:2048',
		];

		if ($itemType === 'covers' || $itemType === 'templates') {
			$rules['cover_type_id'] = 'nullable|integer|exists:cover_types,id';
		}
		if ($itemType === 'covers') {
			$rules['caption'] = 'nullable|string|max:500';
			$rules['categories'] = 'nullable|string|max:1000'; // Comma-separated string from form
			$rules['text_placements'] = 'nullable|string|max:1000'; // Comma-separated string from form
		}
		if ($itemType === 'templates') {
			$rules['text_placements'] = 'nullable|string|max:1000'; // Comma-separated string from form (if added to template main edit)
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
		} elseif (isset($item->keywords)) {
			$data['keywords'] = $item->keywords; // Keep existing if not provided
		} else {
			$data['keywords'] = []; // Default
		}


		if ($itemType === 'covers' || $itemType === 'templates') {
			$data['cover_type_id'] = $request->input('cover_type_id', $item->cover_type_id);
		}

		try {
			if ($itemType === 'covers' || $itemType === 'elements' || $itemType === 'overlays') {
				if ($request->hasFile('image_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail(
						$request->file('image_file'),
						$itemType,
						$item->image_path,
						$item->thumbnail_path
					);
					$data['image_path'] = $paths['original_path'];
					$data['thumbnail_path'] = $paths['thumbnail_path'];
				}
			}

			if ($itemType === 'covers') {
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
				if ($request->hasFile('thumbnail_file')) {
					$paths = $this->imageUploadService->uploadImageWithThumbnail(
						$request->file('thumbnail_file'),
						$itemType,
						null,
						$item->thumbnail_path
					);
					$data['thumbnail_path'] = $paths['thumbnail_path'];
				}
				if ($request->hasFile('json_file')) {
					$jsonContent = file_get_contents($request->file('json_file')->getRealPath());
					if (json_decode($jsonContent) === null && json_last_error() !== JSON_ERROR_NONE) {
						return response()->json(['success' => false, 'message' => 'Invalid JSON content for update: ' . json_last_error_msg()], 400);
					}
					$data['json_content'] = $jsonContent;
				}
				// Handle text_placements for templates if submitted from main edit form
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
			$originalPath = $item->image_path ?? null;
			$thumbnailPath = $item->thumbnail_path ?? null;
			$item->delete();
			$this->imageUploadService->deleteImageFiles($originalPath, $thumbnailPath);
			return response()->json(['success' => true, 'message' => ucfirst(Str::singular($itemType)) . ' deleted successfully.']);
		} catch (\Exception $e) {
			Log::error("Delete item error ({$itemType} ID {$id}): " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()], 500);
		}
	}

	public function generateAiMetadata(Request $request)
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
		if (!$item) return response()->json(['success' => false, 'message' => ucfirst(Str::singular($itemType)) . ' not found.'], 404);

		$imagePathForAi = null;
		if ($itemType === 'templates') {
			$imagePathForAi = $item->thumbnail_path;
		} else {
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

			$keywordsPrompt = "Generate a list of 10-15 relevant keywords for this image, suitable for search or tagging. Include single words and relevant two-word phrases. Focus on visual elements, style, and potential use case. Output only a comma-separated list.";
			$keywordsResponse = $this->openAiService->generateMetadataFromImageBase64($keywordsPrompt, $base64Image, $mimeType);
			if (isset($keywordsResponse['content'])) {
				$parsedKeywords = $this->openAiService->parseAiListResponse($keywordsResponse['content']);
				if (!empty($parsedKeywords)) $aiGeneratedData['keywords'] = $parsedKeywords;
			} elseif (isset($keywordsResponse['error'])) {
				Log::warning("AI Keywords Error for {$itemType} ID {$id}: " . $keywordsResponse['error']);
			}

			if ($itemType === 'covers') {
				$captionPrompt = "Describe this book cover image concisely for use as an alt text or short caption. Focus on the main visual elements and mood. Do not include or describe any text visible on the image. Maximum 140 characters.";
				$captionResponse = $this->openAiService->generateMetadataFromImageBase64($captionPrompt, $base64Image, $mimeType);
				if (isset($captionResponse['content'])) {
					$aiGeneratedData['caption'] = Str::limit(trim($captionResponse['content']), 250);
				} elseif (isset($captionResponse['error'])) {
					Log::warning("AI Caption Error for cover ID {$id}: " . $captionResponse['error']);
				}

				$categoriesPrompt = "Categorize this book cover image into 1-3 relevant genres from the following list: Mystery, Thriller & Suspense, Fantasy, Science Fiction, Horror, Romance, Erotica, Children's, Action & Adventure, Chick Lit, Historical Fiction, Literary Fiction, Teen & Young Adult, Royal Romance, Western, Surreal, Paranormal & Urban, Apocalyptic, Nature, Poetry, Travel, Religion & Spirituality, Business, Self-Improvement, Education, Health & Wellness, Cookbooks & Food, Environment, Politics & Society, Family & Parenting, Abstract, Medical, Fitness, Sports, Science, Music. Output only a comma-separated list of the chosen categories.";
				$categoriesResponse = $this->openAiService->generateMetadataFromImageBase64($categoriesPrompt, $base64Image, $mimeType);
				if (isset($categoriesResponse['content'])) {
					$parsedCategories = $this->openAiService->parseAiListResponse($categoriesResponse['content']);
					if (!empty($parsedCategories)) $aiGeneratedData['categories'] = $parsedCategories;
				} elseif (isset($categoriesResponse['error'])) {
					Log::warning("AI Categories Error for cover ID {$id}: " . $categoriesResponse['error']);
				}
			}

			if (empty($aiGeneratedData)) {
				return response()->json(['success' => false, 'message' => 'AI did not return any usable metadata or an error occurred. Check logs.'], 500);
			}

			$item->update($aiGeneratedData);
			return response()->json(['success' => true, 'message' => ucfirst(Str::singular($itemType)) . ' AI metadata updated successfully.']);
		} catch (\Exception $e) {
			Log::error("AI Metadata generation error ({$itemType} ID {$id}): " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'AI metadata generation failed: ' . $e->getMessage()], 500);
		}
	}

	public function generateSimilarTemplate(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'original_template_id' => 'required|integer|exists:templates,id',
			'user_prompt' => 'required|string|min:10|max:2000',
			'original_json_content' => 'required|json',
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Invalid input.', 'errors' => $validator->errors()], 422);
		}

		$originalTemplateId = $request->input('original_template_id');
		$userPromptText = $request->input('user_prompt');
		$originalJsonContent = $request->input('original_json_content');

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
		$imagePathForAi = $cover->image_path ?? $cover->thumbnail_path;
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
		$coverImageUrl = null;
		if ($cover->image_path) {
			$coverImageUrl = $this->imageUploadService->getUrl($cover->image_path);
		} elseif ($cover->thumbnail_path) {
			$coverImageUrl = $this->imageUploadService->getUrl($cover->thumbnail_path);
		}

		if (!$cover->cover_type_id) {
			return response()->json([
				'success' => true,
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
			->get(['id', 'name', 'thumbnail_path']);

		$assignedTemplateIds = $cover->templates()->pluck('template_id')->toArray();

		$templatesData = $assignableTemplates->map(function ($template) use ($assignedTemplateIds) {
			$thumbnailUrl = null;
			if ($template->thumbnail_path) {
				$thumbnailUrl = $this->imageUploadService->getUrl($template->thumbnail_path);
			}
			return [
				'id' => $template->id,
				'name' => $template->name,
				'is_assigned' => in_array($template->id, $assignedTemplateIds),
				'thumbnail_url' => $thumbnailUrl,
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
				'top-light', 'top-dark',
				'middle-light', 'middle-dark',
				'bottom-light', 'bottom-dark',
				'left-light', 'left-dark',
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
		// Validate format 'area-tone'
		if (!preg_match('/^(top|middle|bottom|left|right)-(light|dark)$/', $placement)) {
			return null;
		}

		if (Str::endsWith($placement, '-light')) {
			return Str::replaceLast('-light', '-dark', $placement);
		} elseif (Str::endsWith($placement, '-dark')) {
			return Str::replaceLast('-dark', '-light', $placement);
		}
		return null; // Should not be reached if preg_match passes and Str::endsWith works
	}

	public function aiEvaluateTemplateFit(Request $request, Cover $cover, Template $template) {
		if (!$cover->thumbnail_path) {
			return response()->json(['success' => false, 'message' => 'Cover image not found.'], 404);
		}
		if (!$template->thumbnail_path) { // This is the template's own thumbnail, which we'll use as the overlay
			return response()->json(['success' => false, 'message' => 'Template thumbnail (overlay image) not found.'], 404);
		}

		$coverImagePath = $cover->thumbnail_path; // Prefer full image for the base

		if (!Storage::disk('public')->exists($coverImagePath) || !Storage::disk('public')->exists($template->thumbnail_path)) {
			return response()->json(['success' => false, 'message' => 'One or more image files not found on server.'], 404);
		}

		try {
			$coverImageContent = Storage::disk('public')->get($coverImagePath);
			$templateOverlayImageContent = Storage::disk('public')->get($template->thumbnail_path); // This is the template's image

			// For Intervention Image 3.x
			$baseImage = Image::read($coverImageContent);
			$overlayImage = Image::read($templateOverlayImageContent);

			// Get dimensions of the base image (the cover)
			$baseImageWidth = $baseImage->width();
			$baseImageHeight = $baseImage->height();

			// Calculate the target width for the overlay (95% of the base image's width)
			$targetOverlayWidth = (int) round($baseImageWidth * 0.95);

			$overlayImage->scale(width : $targetOverlayWidth);

			// Calculate margins for placement
			// 3% top margin of the base image's height
			$marginTop = (int) round($baseImageHeight * 0.03);
			// 3% left margin of the base image's width
			$marginLeft = (int) round($baseImageWidth * 0.03);

			// Place the resized overlay onto the base image with the calculated margins.
			// The `place` method's x and y are offsets from the specified position.
			$baseImage->place($overlayImage, 'top-left', $marginLeft, $marginTop);

			//save to temp folder in storage
			// Ensure the temp directory exists
			Storage::makeDirectory('public/temp', 0755, true);
			$tempPath = storage_path('app/public/temp/composite_image.png');
			$baseImage->save($tempPath);

			// Get the composite image data as a PNG string
			$encodedImage = $baseImage->toPng();
			$base64CompositeImage = base64_encode((string) $encodedImage);
			$mimeType = 'image/png';

			// Prompt for AI

			$prompt = "Analyze the following image, which is a book cover with a text template overlaid. The underlying image should show: '". $cover->caption ."'
Evaluate based on MANDATORY criteria:
1) Is the title and author text in the template completely legible and easy to read?
2) Are ALL the key visual elements from the caption visible and NOT obscured totally by the text overlay. 
 
 Respond with the single word 'YES'. Otherwise Respond with only 'NO'. Don't add any explanation only respond with 'YES' or 'NO'.";


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
				->orderBy('name') // Process in a consistent order
				->get(['id', 'name']); // Fetch name for better logging/UI
			return response()->json(['success' => true, 'data' => ['covers' => $covers]]);
		} catch (\Exception $e) {
			Log::error("Error fetching covers without templates: " . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'Error fetching covers: ' . $e->getMessage()], 500);
		}
	}
}
