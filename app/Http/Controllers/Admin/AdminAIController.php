<?php

	namespace App\Http\Controllers\Admin;

	use App\Http\Controllers\Controller;
	use App\Models\Cover;
	use App\Models\Element;
	use App\Models\Overlay;
	use App\Models\Template;
	use App\Services\ImageUploadService;
	use App\Services\OpenAiService;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Validator;
	use Illuminate\Support\Str;
	use Illuminate\Validation\Rule;
	use Intervention\Image\Laravel\Facades\Image as InterventionImageFacade;

	class AdminAIController extends Controller
	{

		protected ImageUploadService $imageUploadService;
		protected OpenAiService $openAiService;

		// Properties and methods from GenerateFullCoverTemplates command
		protected $authorNames = ['Morgan', 'Casey', 'Peyton', 'Emerson', 'Jordan', 'Parker', 'Avery', 'Rowan', 'Taylor', 'Alexa'];

		public function __construct(ImageUploadService $imageUploadService, OpenAiService $openAiService)
		{
			$this->imageUploadService = $imageUploadService;
			$this->openAiService = $openAiService;
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

		public function getCoversNeedingMetadata(Request $request)
		{
			try {
				// Fetch all covers to ensure every name can be checked for the 3-word rule.
				// This is the most reliable way to catch all covers needing an update.
				$allCovers = Cover::query()
					->orderBy('id')
					->get(['id', 'name', 'caption', 'keywords', 'categories']);

				$coversNeedingUpdate = $allCovers->map(function ($cover) {
					$fields_to_generate = [];

					// Check name: must be exactly 3 words.
					// We trim the name, explode by space, and filter empty values
					// to correctly handle multiple spaces between words or leading/trailing spaces.
					$trimmedName = trim($cover->name ?? '');
					$words = $trimmedName === '' ? [] : array_filter(explode(' ', $trimmedName));
					$nameWordCount = count($words);

					if ($nameWordCount !== 3) {
						$fields_to_generate[] = 'name';
					}

					// Check caption: generate only if it's empty.
					if (empty(trim($cover->caption ?? ''))) {
						$fields_to_generate[] = 'caption';
					}

					// Check keywords: generate only if the array is empty.
					// The Cover model casts the 'keywords' JSON column to an array.
					if (empty($cover->keywords)) {
						$fields_to_generate[] = 'keywords';
					}

					// Check categories: generate only if the array is empty.
					// The Cover model casts the 'categories' JSON column to an array.
					if (empty($cover->categories)) {
						$fields_to_generate[] = 'categories';
					}

					// If any fields were identified for generation, return the cover info.
					// Otherwise, return null, and it will be filtered out.
					if (!empty($fields_to_generate)) {
						return [
							'id' => $cover->id,
							'current_name' => $cover->name, // For logging/display by JS
							'fields_to_generate' => $fields_to_generate
						];
					}

					return null;
				})
					->filter() // Removes any null entries where no updates were needed.
					->values(); // Reset collection keys for a clean JSON array.

				return response()->json(['success' => true, 'data' => ['covers' => $coversNeedingUpdate]]);
			} catch (\Exception $e) {
				Log::error("Error fetching covers needing metadata: " . $e->getMessage() . "\n" . $e->getTraceAsString());
				return response()->json(['success' => false, 'message' => 'Error fetching covers: ' . $e->getMessage()], 500);
			}
		}

		public function generateAiMetadata(Request $request)
		{
			$validator = ValiFdator::make($request->all(), [
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

		public function updateTemplateJson(Request $request, Template $template)
		{
			$baseRules = [
				'json_type' => ['required', Rule::in(['front', 'full'])],
				'json_data' => 'required|string', // Will be a JSON string
				'updated_image_file' => 'nullable|image|mimes:png|max:5120', // Max 5MB PNG
			];

			$validator = Validator::make($request->all(), $baseRules);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
			}

			$jsonType = $request->input('json_type');
			$jsonDataString = $request->input('json_data');
			$jsonData = json_decode($jsonDataString, true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				return response()->json(['success' => false, 'message' => 'Invalid JSON data provided: ' . json_last_error_msg()], 422);
			}

			// Further validate the structure of the decoded JSON
			$jsonStructureValidator = Validator::make($jsonData, [
				'canvas' => 'required|array',
				'canvas.width' => 'required|numeric|min:1',
				'canvas.height' => 'required|numeric|min:1',
				'layers' => 'nullable|array',
			]);

			if ($jsonStructureValidator->fails()) {
				return response()->json(['success' => false, 'message' => 'Invalid JSON data structure.', 'errors' => $jsonStructureValidator->errors()], 422);
			}

			try {
				$updateData = [];

				// Handle image upload if a new file is provided
				if ($request->hasFile('updated_image_file')) {
					$file = $request->file('updated_image_file');
					Log::info("Updating template {$template->id} ({$jsonType}) with new image file: " . $file->getClientOriginalName());

					if ($jsonType === 'front') {
						// This is for the 'cover_image_path' which is the main preview for the template editor
						$paths = $this->imageUploadService->uploadImageWithThumbnail(
							$file,
							'templates_cover_image', // Config key for template's main cover image
							$template->cover_image_path // Old original path to delete
						// Assuming 'templates_cover_image' config might not generate a separate thumbnail,
						// or if it does, the Template model doesn't have a dedicated field for it.
						);
						$updateData['cover_image_path'] = $paths['original_path'];
						Log::info("Updated cover_image_path for template {$template->id} to: " . $paths['original_path']);

					} elseif ($jsonType === 'full') {
						// This is for the 'full_cover_image_path' and its thumbnail
						$paths = $this->imageUploadService->uploadImageWithThumbnail(
							$file,
							'templates_full_cover_image', // Config key for template's full cover image
							$template->full_cover_image_path, // Old original path
							$template->full_cover_image_thumbnail_path // Old thumbnail path
						);
						$updateData['full_cover_image_path'] = $paths['original_path'];
						$updateData['full_cover_image_thumbnail_path'] = $paths['thumbnail_path'];
						Log::info("Updated full_cover_image_path for template {$template->id} to: " . $paths['original_path']);
						Log::info("Updated full_cover_image_thumbnail_path for template {$template->id} to: " . $paths['thumbnail_path']);
					}
				}

				// Update JSON content
				if ($jsonType === 'front') {
					$updateData['json_content'] = $jsonData;
				} elseif ($jsonType === 'full') {
					$updateData['full_cover_json_content'] = $jsonData;
				} else {
					// Should be caught by initial validation, but as a safeguard
					return response()->json(['success' => false, 'message' => 'Invalid JSON type specified.'], 400);
				}

				if (!empty($updateData)) {
					$template->update($updateData);
					Log::info("Template {$template->id} ({$jsonType}) updated successfully with data: " . json_encode(array_keys($updateData)));
				} else {
					Log::info("No data to update for template {$template->id} ({$jsonType}). JSON content might be identical and no new image provided.");
					// Still return success if no actual change was needed but process was valid
				}

				return response()->json(['success' => true, 'message' => 'Template JSON and preview updated successfully.']);

			} catch (\Exception $e) {
				Log::error("Error updating template JSON for template ID {$template->id} ({$jsonType}): " . $e->getMessage() . "\n" . $e->getTraceAsString());
				return response()->json(['success' => false, 'message' => 'Server error while updating template JSON: ' . $e->getMessage()], 500);
			}
		}

		protected function generateLoremIpsumForFullCover()
		{
			$paragraphs = [
				"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.",
				"Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
				"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo."
			];
			return implode("\n\n", $paragraphs);
		}

		public function generateFullCoverJsonForTemplate(Request $request, Template $template)
		{
			try {
				$jsonContent = $template->json_content;

				if (!is_array($jsonContent) || empty($jsonContent)) {
					Log::warning("Skipping Template ID: {$template->id} - json_content is empty or not an array for full cover generation.");
					return response()->json(['success' => false, 'message' => 'Template json_content is empty or not an array.'], 400);
				}

				// Check if it's a front-only template (spineWidth and backWidth must exist and be 0)
				if (!isset($jsonContent['canvas']) ||
					!isset($jsonContent['canvas']['backWidth']) ||
					!isset($jsonContent['canvas']['spineWidth']) ||
					$jsonContent['canvas']['backWidth'] != 0 ||
					$jsonContent['canvas']['spineWidth'] != 0) {
					Log::info("Skipping Template ID: {$template->id} - Not a front-only template suitable for full cover generation. Spine/back widths are not both zero, or canvas data missing.");
					return response()->json(['success' => false, 'message' => 'Template is not a front-only template (spine/back widths must both be explicitly zero, or canvas data is missing).'], 400);
				}

				$fullCoverJson = $jsonContent;

				$spineWidth = 300; // Default spine width
				$frontWidth = $fullCoverJson['canvas']['frontWidth'] ?? ($fullCoverJson['canvas']['width'] ?? 1540);

				$backWidth = $frontWidth;

				$fullCoverJson['canvas']['spineWidth'] = $spineWidth;
				$fullCoverJson['canvas']['backWidth'] = $backWidth;
				$fullCoverJson['canvas']['width'] = $frontWidth + $spineWidth + $backWidth;

				$offset = $backWidth + $spineWidth;

				$authorLayer = null;
				$titleLayer = null;
				$smallestTextLayer = null;
				$smallestFontSize = PHP_INT_MAX;

				if (isset($fullCoverJson['layers']) && is_array($fullCoverJson['layers'])) {
					foreach ($fullCoverJson['layers'] as $layer) {
						if (!isset($layer['type']) || $layer['type'] !== 'text') {
							continue;
						}
						$content = $layer['content'] ?? '';
						foreach ($this->authorNames as $authorName) {
							if (stripos($content, $authorName) !== false) {
								$authorLayer = $layer;
								break;
							}
						}
						$fontSize = $layer['fontSize'] ?? 16;
						if ($fontSize < $smallestFontSize) {
							$smallestFontSize = $fontSize;
							$smallestTextLayer = $layer;
						}
					}

					$largestFontSize = 0;
					foreach ($fullCoverJson['layers'] as $layer) {
						if (!isset($layer['type']) || $layer['type'] !== 'text') {
							continue;
						}
						$fontSize = $layer['fontSize'] ?? 16;
						$content = $layer['content'] ?? '';
						if ($authorLayer && isset($authorLayer['content']) && $content === $authorLayer['content']) {
							continue;
						}
						if ($fontSize > $largestFontSize) {
							$largestFontSize = $fontSize;
							$titleLayer = $layer;
						}
					}

					foreach ($fullCoverJson['layers'] as &$layer) {
						if (isset($layer['x'])) {
							$layer['x'] = (is_numeric($layer['x']) ? floatval($layer['x']) : 0) + $offset;
						}
					}
					unset($layer);

					if ($authorLayer && $titleLayer) {
						$spineAuthor = $authorLayer;
						$spineAuthor['id'] = 'spine-author-' . Str::random(4);
						$spineAuthor['name'] = 'Spine Author';
						$spineAuthor['x'] = $backWidth - 300;
						$spineAuthor['y'] = 400;
						$spineAuthor['align'] = 'left';
						$spineAuthor['rotation'] = 90;
						$spineAuthor['fontSize'] = max(50, ($authorLayer['fontSize'] ?? 100) * 0.5);
						$spineAuthor['width'] = 1200;
						$spineAuthor['height'] = 200;
						$spineAuthor['content'] = str_replace("\n", " ", $spineAuthor['content'] ?? '');
						$spineAuthor['definition'] = 'spine_text';

						$spineTitle = $titleLayer;
						$spineTitle['id'] = 'spine-title-' . Str::random(4);
						$spineTitle['name'] = 'Spine Title';
						$spineTitle['x'] = $backWidth - 300;
						$spineTitle['y'] = 1400;
						$spineTitle['align'] = 'left';
						$spineTitle['rotation'] = 90;
						$spineTitle['fontSize'] = max(50, ($titleLayer['fontSize'] ?? 100) * 0.4);
						$spineTitle['width'] = 1200;
						$spineTitle['height'] = 200;
						$spineTitle['content'] = str_replace("\n", " ", $spineTitle['content'] ?? '');
						$spineTitle['definition'] = 'spine_text';

						$fullCoverJson['layers'][] = $spineAuthor;
						$fullCoverJson['layers'][] = $spineTitle;

						$backTitle = $titleLayer;
						$backTitle['id'] = 'back-title-' . Str::random(4);
						$backTitle['name'] = 'Back Title';
						$backTitle['x'] = 100;
						$backTitle['y'] = 100;
						$backTitle['align'] = 'left';
						$backTitle['vAlign'] = 'top';
						$backTitle['rotation'] = 0;
						$backTitle['width'] = 1400;
						$backTitle['height'] = round(($titleLayer['height'] ?? 50) * 0.6);
						$backTitle['fontSize'] = ($titleLayer['fontSize'] ?? 24) * 0.6;
						$backTitle['content'] = str_replace("\n", " ", $backTitle['content'] ?? '');
						$backTitle['definition'] = 'back_cover_text';

						$backAuthor = $authorLayer;
						$backAuthor['id'] = 'back-author-' . Str::random(4);
						$backAuthor['name'] = 'Back Author';
						$backAuthor['x'] = 100;
						$backAuthor['y'] = 100 + ($backTitle['height'] ?? 30) + 50;
						$backAuthor['align'] = 'left';
						$backAuthor['vAlign'] = 'top';
						$backAuthor['rotation'] = 0;
						$backAuthor['width'] = 1400;
						$backAuthor['height'] = round(($authorLayer['height'] ?? 30) * 0.6);
						$backAuthor['fontSize'] = ($authorLayer['fontSize'] ?? 16) * 0.6;
						$backAuthor['content'] = str_replace("\n", " ", $backAuthor['content'] ?? '');
						$backAuthor['definition'] = 'back_cover_text';

						$backText = $smallestTextLayer ?: $authorLayer; // Fallback
						$backText['id'] = 'back-text-' . Str::random(4);
						$backText['name'] = 'Back Cover Text';
						$backText['x'] = 100;
						$backText['y'] = ($backAuthor['y'] ?? 180) + ($backAuthor['height'] ?? 18) + 50;
						$backText['align'] = 'left';
						$backText['rotation'] = 0;
						$backText['width'] = 1400;
						$backText['height'] = 800;
						$backText['content'] = $this->generateLoremIpsumForFullCover();
						$backText['fontSize'] = (($smallestTextLayer['fontSize'] ?? ($authorLayer['fontSize'] ?? 16))) * 1; // Keep original smallest size or default
						$backText['definition'] = 'back_cover_text';
						$backText['align'] = 'left';
						$backText['vAlign'] = 'top';

						$fullCoverJson['layers'][] = $backTitle;
						$fullCoverJson['layers'][] = $backAuthor;
						$fullCoverJson['layers'][] = $backText;
					} else {
						Log::warning("Template ID: {$template->id} - Could not identify author and/or title layers for full cover generation. Spine/back elements not added.");
					}
				}

				$template->full_cover_json_content = $fullCoverJson;
				$template->save();

				Log::info("Generated full cover JSON for Template ID: {$template->id}");
				return response()->json(['success' => true, 'message' => 'Full cover JSON generated successfully.']);

			} catch (\Exception $e) {
				Log::error("Error generating full cover JSON for Template ID: {$template->id} - " . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
				return response()->json(['success' => false, 'message' => 'Server error generating full cover JSON: ' . $e->getMessage()], 500);
			}
		}

	}
