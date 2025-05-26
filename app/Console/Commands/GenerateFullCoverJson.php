<?php

	namespace App\Console\Commands;

	use App\Models\Template;
	use Illuminate\Console\Command;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;

	class GenerateFullCoverJson extends Command
	{
		protected $signature = 'templates:generate-full-cover-json
                            {--force : Overwrite existing full_cover_json_content}
                            {--id= : Process only a specific template ID}';

		protected $description = 'Generates full cover json_content (front, spine, back) for templates where backWidth and spineWidth are 0.';

		const SPINE_WIDTH = 300;
		const AUTHOR_NAMES = ['Morgan', 'Casey', 'Peyton', 'Emerson', 'Jordan', 'Parker', 'Avery', 'Rowan', 'Taylor'];
		const BACK_COVER_TEXT_X = 100;
		const BACK_COVER_TITLE_Y = 100;
		const BACK_COVER_AUTHOR_Y = 300;
		const BACK_COVER_LOREM_Y_START_OFFSET = 50; // Below author
		const BACK_COVER_LOREM_WIDTH = 1400; // As per requirement, but this is very wide for a typical back cover section.
		// Original frontWidth is 1540. If backWidth is also 1540, this is almost full width.

		// Default layer properties (subset, others will be cloned)
		protected array $defaultLayerProperties = [
			'layerSubType' => null,
			'rotation' => 0,
			'scale' => 100,
			'definition' => 'cover_text', // Or 'spine_text', 'back_text'
			'vAlign' => 'center',
			'filters' => [
				'brightness' => 100, 'contrast' => 100, 'saturation' => 100,
				'grayscale' => 0, 'sepia' => 0, 'hueRotate' => 0, 'blur' => 0
			],
			'blendMode' => 'normal',
			'shadowOpacity' => 1,
			'zIndex' => 10, // Start new elements with a reasonable zIndex
			// Properties like 'fontFamily', 'fontSize', 'color', 'textAlign' will be cloned
		];


		public function handle()
		{
			$this->info('Starting generation of full cover json_content...');

			$updatedCount = 0;
			$skippedCount = 0;
			$errorCount = 0;
			$forceUpdate = $this->option('force');
			$specificId = $this->option('id');

			DB::transaction(function () use (&$updatedCount, &$skippedCount, &$errorCount, $forceUpdate, $specificId) {
				$query = Template::query();
				if ($specificId) {
					$query->where('id', $specificId);
				}

				$query->chunk(50, function ($templates) use (&$updatedCount, &$skippedCount, &$errorCount, $forceUpdate) {
					foreach ($templates as $template) {
						try {
							$originalJsonContent = $template->json_content; // Already an array due to $casts

							if (!is_array($originalJsonContent) || empty($originalJsonContent) || !isset($originalJsonContent['canvas'])) {
								$this->warn("Skipping Template ID: {$template->id} - original json_content is empty, not an array, or missing canvas.");
								$skippedCount++;
								continue;
							}

							// Check condition: backWidth and spineWidth are 0
							$canvas = $originalJsonContent['canvas'];
							$originalFrontWidth = $canvas['frontWidth'] ?? $canvas['width'] ?? 1540; // Fallback if frontWidth not set
							$originalCanvasHeight = $canvas['height'] ?? 2475; // Fallback

							if (!$specificId && (($canvas['spineWidth'] ?? 0) != 0 || ($canvas['backWidth'] ?? 0) != 0)) {
								if (!$template->full_cover_json_content || $forceUpdate) {
									$this->comment("Skipping Template ID: {$template->id} - spineWidth or backWidth is not 0. It might already be a full cover or a different format.");
								}
								$skippedCount++;
								continue;
							}

							if ($template->full_cover_json_content && !$forceUpdate && !$specificId) {
								$this->comment("Skipping Template ID: {$template->id} - full_cover_json_content already exists. Use --force to overwrite.");
								$skippedCount++;
								continue;
							}

							$this->line("Processing Template ID: {$template->id}");

							// --- Core Logic ---
							$newFullCoverJson = $this->generateFullCover($originalJsonContent, $originalFrontWidth, $originalCanvasHeight, $template->id);

							if ($newFullCoverJson === null) { // Means critical info was missing
								$errorCount++;
								continue; // Error already logged in generateFullCover
							}

							$template->full_cover_json_content = $newFullCoverJson;
							$template->save();
							$updatedCount++;
							$this->info("Successfully generated full cover for Template ID: {$template->id}");

						} catch (\Exception $e) {
							$this->error("Error processing Template ID: {$template->id} - " . $e->getMessage());
							Log::error("Error generating full cover for ID {$template->id}: " . $e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
							$errorCount++;
							// throw $e; // Re-throw to ensure transaction rollback if strictness is needed
						}
					}
				});
			});

			$this->info("-----------------------------------------");
			$this->info("Full cover generation complete.");
			$this->info("Successfully generated/updated: {$updatedCount} templates.");
			$this->info("Skipped: {$skippedCount} templates.");
			$this->info("Errors encountered: {$errorCount} templates (check logs).");
			$this->info("-----------------------------------------");

			return Command::SUCCESS;
		}

		private function generateFullCover(array $originalJsonContent, int $frontWidth, int $canvasHeight, int $templateId): ?array
		{
			$newBackWidth = $frontWidth; // As per requirement
			$newSpineWidth = self::SPINE_WIDTH;
			$totalWidth = $frontWidth + $newSpineWidth + $newBackWidth;

			$fullCoverJson = [
				'version' => $originalJsonContent['version'] ?? '1.3', // Or bump to a new version like '2.0'
				'canvas' => [
					'width' => $totalWidth,
					'height' => $canvasHeight,
					'frontWidth' => $frontWidth,
					'spineWidth' => $newSpineWidth,
					'backWidth' => $newBackWidth,
				],
				'layers' => []
			];

			$originalLayers = $originalJsonContent['layers'] ?? [];
			if (empty($originalLayers)) {
				$this->warn("Template ID: {$templateId} has no layers in original json_content. Resulting cover will be empty.");
			}

			// --- Identify Author and Title from Front Cover ---
			$authorLayerOriginal = null;
			$titleLayerOriginal = null;
			$textLayers = array_filter($originalLayers, fn($layer) => ($layer['type'] ?? '') === 'text' && !empty($layer['content']));

			if (empty($textLayers)) {
				$this->error("Template ID: {$templateId} - No text layers found on the front cover. Cannot identify author/title.");
				return null;
			}

			// Identify Author
			foreach ($textLayers as $layer) {
				foreach (self::AUTHOR_NAMES as $authorName) {
					if (stripos($layer['content'], $authorName) !== false) {
						$authorLayerOriginal = $layer;
						break 2; // Found author, break both loops
					}
				}
			}

			// Identify Title (largest font size, not author)
			$largestFontSize = 0;
			foreach ($textLayers as $layer) {
				// Skip if this layer is the identified author layer
				if ($authorLayerOriginal && $this->areLayersEffectivelySame($layer, $authorLayerOriginal)) {
					continue;
				}
				if (($layer['fontSize'] ?? 0) > $largestFontSize) {
					$largestFontSize = $layer['fontSize'];
					$titleLayerOriginal = $layer;
				}
			}

			if (!$authorLayerOriginal) {
				$this->warn("Template ID: {$templateId} - Could not identify an author layer. Spine/Back author will be missing.");
			}
			if (!$titleLayerOriginal) {
				$this->warn("Template ID: {$templateId} - Could not identify a title layer. Spine/Back title will be missing.");
				// Fallback: if only one text layer, and it wasn't author, assume it's title
				if (count($textLayers) == 1 && $textLayers[0] !== $authorLayerOriginal) {
					$titleLayerOriginal = $textLayers[0];
					$this->comment("Template ID: {$templateId} - Using the only non-author text layer as title.");
				} elseif (count($textLayers) > 1 && !$authorLayerOriginal && $textLayers[0]) {
					// If no author, pick first text layer as title as a last resort
					$titleLayerOriginal = $textLayers[0];
					$this->comment("Template ID: {$templateId} - No author, using first text layer as title fallback.");
				}
			}


			// --- 1. Shift existing front cover layers ---
			$frontCoverOffsetX = $newBackWidth + $newSpineWidth;
			$layerIdCounter = 1; // For generating new unique IDs

			foreach ($originalLayers as $layer) {
				$newLayer = $layer; // Create a copy
				$newLayer['x'] = round(($newLayer['x'] ?? 0) + $frontCoverOffsetX);
				// Ensure zIndex is present
				if (!isset($newLayer['zIndex'])) {
					$newLayer['zIndex'] = $this->defaultLayerProperties['zIndex'];
				}
				$fullCoverJson['layers'][] = $newLayer;
			}

			// --- 2. Add Spine Layers ---
			$spineCenterX = $newBackWidth + ($newSpineWidth / 2);
			$spineLayerBaseZIndex = 5; // Lower than front cover elements typically

			if ($authorLayerOriginal) {
				$spineAuthorLayer = $this->cloneAndModifyLayer($authorLayerOriginal, [
					'id' => 'spine-author-' . ($layerIdCounter++),
					'name' => 'Spine Author',
					'content' => $authorLayerOriginal['content'], // Keep original author content
					'x' => round($spineCenterX), // Will be centered by textAlign
					'y' => round($canvasHeight * 0.25), // Top quarter/third
					'rotation' => 90,
					'fontSize' => round(($authorLayerOriginal['fontSize'] ?? 20) * 0.7), // Reduce font size
					'textAlign' => 'center', // Center horizontally within its bounding box
					'vAlign' => 'middle',    // Center vertically
					'definition' => 'spine_text',
					'width' => $canvasHeight * 0.4, // Rotated width is effectively height constraint
					'height' => ($authorLayerOriginal['fontSize'] ?? 20) * 0.7 * 1.2, // Rotated height
					'zIndex' => $spineLayerBaseZIndex + 1,
				]);
				$fullCoverJson['layers'][] = $this->mergeWithDefaultLayerProperties($spineAuthorLayer);
			}

			if ($titleLayerOriginal) {
				$spineTitleLayer = $this->cloneAndModifyLayer($titleLayerOriginal, [
					'id' => 'spine-title-' . ($layerIdCounter++),
					'name' => 'Spine Title',
					'content' => $titleLayerOriginal['content'], // Keep original title content
					'x' => round($spineCenterX),
					'y' => round($canvasHeight * 0.5), // Middle
					'rotation' => 90,
					'fontSize' => round(($titleLayerOriginal['fontSize'] ?? 30) * 0.6), // Reduce font size
					'textAlign' => 'center',
					'vAlign' => 'middle',
					'definition' => 'spine_text',
					'width' => $canvasHeight * 0.45, // Rotated width
					'height' => ($titleLayerOriginal['fontSize'] ?? 30) * 0.6 * 1.2, // Rotated height
					'zIndex' => $spineLayerBaseZIndex + 2,
				]);
				$fullCoverJson['layers'][] = $this->mergeWithDefaultLayerProperties($spineTitleLayer);
			}

			// --- 3. Add Back Cover Layers ---
			$backCoverBaseZIndex = 1; // Typically lowest

			if ($titleLayerOriginal) {
				$backTitleLayer = $this->cloneAndModifyLayer($titleLayerOriginal, [
					'id' => 'back-title-' . ($layerIdCounter++),
					'name' => 'Back Cover Title',
					'content' => $titleLayerOriginal['content'],
					'x' => self::BACK_COVER_TEXT_X,
					'y' => self::BACK_COVER_TITLE_Y,
					'textAlign' => 'left',
					'definition' => 'back_text',
					'width' => $newBackWidth - (2 * self::BACK_COVER_TEXT_X), // Give some padding
					'height' => ($titleLayerOriginal['fontSize'] ?? 30) * 1.5, // Auto height essentially
					'zIndex' => $backCoverBaseZIndex + 2,
				]);
				$fullCoverJson['layers'][] = $this->mergeWithDefaultLayerProperties($backTitleLayer);
			}

			$lastBackElementY = self::BACK_COVER_TITLE_Y + (($titleLayerOriginal['fontSize'] ?? 30) * 1.5);

			if ($authorLayerOriginal) {
				$backAuthorLayer = $this->cloneAndModifyLayer($authorLayerOriginal, [
					'id' => 'back-author-' . ($layerIdCounter++),
					'name' => 'Back Cover Author',
					'content' => $authorLayerOriginal['content'],
					'x' => self::BACK_COVER_TEXT_X,
					'y' => self::BACK_COVER_AUTHOR_Y,
					'textAlign' => 'left',
					'definition' => 'back_text',
					'width' => $newBackWidth - (2 * self::BACK_COVER_TEXT_X),
					'height' => ($authorLayerOriginal['fontSize'] ?? 20) * 1.5,
					'zIndex' => $backCoverBaseZIndex + 1,
				]);
				$fullCoverJson['layers'][] = $this->mergeWithDefaultLayerProperties($backAuthorLayer);
				$lastBackElementY = self::BACK_COVER_AUTHOR_Y + (($authorLayerOriginal['fontSize'] ?? 20) * 1.5);
			}


			// Add Lorem Ipsum text
			$smallestFontTextLayer = $this->findSmallestFontTextLayer($originalLayers, $authorLayerOriginal, $titleLayerOriginal);
			$loremBaseStyleLayer = $smallestFontTextLayer ?: ($authorLayerOriginal ?: $titleLayerOriginal); // Fallback style

			if ($loremBaseStyleLayer) {
				$loremY = $lastBackElementY + self::BACK_COVER_LOREM_Y_START_OFFSET;
				$loremFontSize = $loremBaseStyleLayer['fontSize'] ?? 12;
				$loremLineHeight = $loremBaseStyleLayer['lineHeight'] ?? 1.5;
				$charsPerLine = (self::BACK_COVER_LOREM_WIDTH / ($loremFontSize * 0.6)); // Rough estimate

				$loremParagraphs = [
					"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.",
					"Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Curabitur pretium tincidunt lacus. Nulla gravida orci a odio.",
					// "Nullam varius, turpis et commodo pharetra, est eros bibendum elit, nec luctus magna felis sollicitudin mauris. Integer in mauris eu nibh euismod gravida. Duis ac tellus et risus vulputate vehicula."
				];

				foreach ($loremParagraphs as $index => $paragraph) {
					$numLines = ceil(strlen($paragraph) / $charsPerLine);
					$paragraphHeight = $numLines * $loremFontSize * $loremLineHeight;

					$loremLayer = $this->cloneAndModifyLayer($loremBaseStyleLayer, [
						'id' => 'back-lorem-' . $index . '-' . ($layerIdCounter++),
						'name' => 'Back Cover Text ' . ($index + 1),
						'content' => $paragraph,
						'x' => self::BACK_COVER_TEXT_X,
						'y' => round($loremY),
						'width' => self::BACK_COVER_LOREM_WIDTH,
						'height' => round($paragraphHeight),
						'fontSize' => $loremFontSize,
						'lineHeight' => $loremLineHeight,
						'textAlign' => 'left',
						'vAlign' => 'top',
						'definition' => 'back_text_body',
						'zIndex' => $backCoverBaseZIndex,
					]);
					$fullCoverJson['layers'][] = $this->mergeWithDefaultLayerProperties($loremLayer);
					$loremY += $paragraphHeight + ($loremFontSize * $loremLineHeight); // Add spacing
				}
			} else {
				$this->warn("Template ID: {$templateId} - Could not find a base style for Lorem Ipsum text.");
			}

			// Re-sort layers by zIndex just in case, then by original order for stability
			// This is optional, rendering usually handles zIndex.
			// usort($fullCoverJson['layers'], function ($a, $b) {
			//     $zIndexA = $a['zIndex'] ?? 0;
			//     $zIndexB = $b['zIndex'] ?? 0;
			//     if ($zIndexA == $zIndexB) {
			//         return 0; // Maintain original relative order for same zIndex
			//     }
			//     return ($zIndexA < $zIndexB) ? -1 : 1;
			// });


			return $fullCoverJson;
		}

		private function cloneAndModifyLayer(array $originalLayer, array $modifications): array
		{
			// Deep clone for safety, though simple assignment is often fine for non-object array values
			$clonedLayer = json_decode(json_encode($originalLayer), true);

			// Apply modifications. array_replace_recursive is good for nested structures if needed.
			// For simple top-level overrides, array_merge is fine.
			$modifiedLayer = array_merge($clonedLayer, $modifications);

			// Ensure essential properties from default if they were somehow missing in original
			// and not set by modifications.
			// This is more about ensuring new layers have all base props.
			// foreach ($this->defaultLayerProperties as $key => $defaultValue) {
			//     if (!array_key_exists($key, $modifiedLayer)) {
			//         $modifiedLayer[$key] = $defaultValue;
			//     }
			// }
			return $modifiedLayer;
		}

		private function mergeWithDefaultLayerProperties(array $layer): array
		{
			$defaults = $this->defaultLayerProperties;
			// For filters, we want to merge, not overwrite, if the layer has some filters already
			if (isset($layer['filters']) && is_array($layer['filters'])) {
				$defaults['filters'] = array_merge($defaults['filters'], $layer['filters']);
			}
			return array_replace_recursive($defaults, $layer); // layer values take precedence
		}


		private function findSmallestFontTextLayer(array $layers, ?array $excludeAuthor, ?array $excludeTitle): ?array
		{
			$smallestFontSize = PHP_INT_MAX;
			$smallestLayer = null;
			foreach ($layers as $layer) {
				if (($layer['type'] ?? '') !== 'text' || empty($layer['content'])) {
					continue;
				}
				if ($excludeAuthor && $this->areLayersEffectivelySame($layer, $excludeAuthor)) {
					continue;
				}
				if ($excludeTitle && $this->areLayersEffectivelySame($layer, $excludeTitle)) {
					continue;
				}

				if (($layer['fontSize'] ?? PHP_INT_MAX) < $smallestFontSize) {
					$smallestFontSize = $layer['fontSize'];
					$smallestLayer = $layer;
				}
			}
			return $smallestLayer;
		}

		private function areLayersEffectivelySame(array $layer1, array $layer2): bool
		{
			// Compare by a few key properties that would make them distinct if they were different text elements
			// e.g. content and original position. Or if they have unique IDs.
			if (isset($layer1['id']) && isset($layer2['id'])) {
				return $layer1['id'] === $layer2['id'];
			}
			// Fallback if IDs are not reliable or not present in original data for comparison
			return ($layer1['content'] ?? null) === ($layer2['content'] ?? null) &&
				($layer1['x'] ?? null) === ($layer2['x'] ?? null) &&
				($layer1['y'] ?? null) === ($layer2['y'] ?? null);
		}
	}
