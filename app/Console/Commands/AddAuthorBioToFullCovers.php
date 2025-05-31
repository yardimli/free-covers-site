<?php

	namespace App\Console\Commands;

	use App\Models\Template;
	use Illuminate\Console\Command;
	use Illuminate\Support\Facades\Log;

	class AddAuthorBioToFullCovers extends Command
	{
		/**
		 * The name and signature of the console command.
		 *
		 * @var string
		 */
		protected $signature = 'templates:add-author-bio';

		/**
		 * The console command description.
		 *
		 * @var string
		 */
		protected $description = 'Adds author picture and biography sections to full cover templates, if not already present.';

		const AUTHOR_PIC_ID = 'author-picture-id';
		const AUTHOR_BIO_ID = 'author-bio-id'; // Corrected typo from 'auhtor-bio-id' in example
		const AUTHOR_PLACEHOLDER_IMAGE_PATH = '/storage/elements/person-placeholder.png'; // Ensure this image exists in public/storage/elements

		// Default placeholder bio text
		const AUTHOR_PLACEHOLDER_BIO_CONTENT = "[Author Name] is an accomplished writer whose work has captivated readers across multiple genres. With a background in [relevant field], they bring both literary skill and real-world expertise to their storytelling.\n\nTheir previous works have garnered critical acclaim and a devoted readership. When not writing, [Author Name] enjoys [hobby/interest] and resides in [location] with [family/pets].";

		/**
		 * Execute the console command.
		 *
		 * @return int
		 */
		public function handle()
		{
			$this->info('Starting to add author bio sections to full cover templates...');
			$updatedCount = 0;
			$skippedExistingCount = 0;
			$skippedInvalidJsonCount = 0;
			$errorCount = 0;
			$notFoundBackTextCount = 0;

			Template::chunk(100, function ($templates) use (
				&$updatedCount,
				&$skippedExistingCount,
				&$skippedInvalidJsonCount,
				&$errorCount,
				&$notFoundBackTextCount
			) {
				foreach ($templates as $template) {
					try {
						$fullCoverJson = $template->full_cover_json_content;

						if (!is_array($fullCoverJson) || empty($fullCoverJson) || !isset($fullCoverJson['layers']) || !is_array($fullCoverJson['layers'])) {
							$this->warn("Skipping Template ID: {$template->id} - full_cover_json_content is empty, not an array, or layers key is missing/invalid.");
							$skippedInvalidJsonCount++;
							continue;
						}

						// Check if author bio elements already exist
						$bioExists = false;
						foreach ($fullCoverJson['layers'] as $layer) {
							if (isset($layer['id']) && ($layer['id'] === self::AUTHOR_PIC_ID || $layer['id'] === self::AUTHOR_BIO_ID)) {
								$bioExists = true;
								break;
							}
						}

						if ($bioExists) {
							$this->comment("Skipping Template ID: {$template->id} - Author bio section already exists.");
							$skippedExistingCount++;
							continue;
						}

						// Find the main back cover text layer
						$backTextLayer = null;
						// Priority 1: Layer with id 'back-text' (often the main lorem ipsum block)
						foreach ($fullCoverJson['layers'] as $layer) {
							if (isset($layer['id']) && $layer['id'] === 'back-text' && isset($layer['type']) && $layer['type'] === 'text') {
								$backTextLayer = $layer;
								break;
							}
						}

						// Priority 2 (Fallback): If 'back-text' id not found, find the text layer with definition 'back_cover_text'
						// on the back panel that has the largest height.
						if (!$backTextLayer) {
							$candidateBackTextLayer = null;
							$maxHeight = -1;
							$backPanelWidth = $fullCoverJson['canvas']['backWidth'] ?? 0;

							if ($backPanelWidth > 0) { // Only search if back panel has defined width
								foreach ($fullCoverJson['layers'] as $layer) {
									if (isset($layer['type']) && $layer['type'] === 'text' &&
										isset($layer['definition']) && $layer['definition'] === 'back_cover_text' &&
										isset($layer['x']) && ($layer['x'] < $backPanelWidth) && // Basic check: layer starts within back panel
										isset($layer['height']) && $layer['height'] > $maxHeight) {

										$candidateBackTextLayer = $layer;
										$maxHeight = $layer['height'];
									}
								}
							}
							if ($candidateBackTextLayer) {
								$backTextLayer = $candidateBackTextLayer;
							}
						}

						if (!$backTextLayer) {
							$this->warn("Template ID: {$template->id} - Could not find a suitable back cover text layer to anchor author bio.");
							$notFoundBackTextCount++;
							continue;
						}

						// Ensure essential properties for positioning and styling exist in backTextLayer
						$backTextLayerY = $backTextLayer['y'] ?? 0;
						$backTextLayerHeight = $backTextLayer['height'] ?? 200; // Default height if missing
						$backTextLayerFontSize = $backTextLayer['fontSize'] ?? 20;
						$backTextLayerFontFamily = $backTextLayer['fontFamily'] ?? 'Arial';
						$backTextLayerFill = $backTextLayer['fill'] ?? 'rgba(0,0,0,1)';

						$backPanelWidth = $fullCoverJson['canvas']['backWidth'] ?? 1540; // Default if not set, from GenerateFullCoverTemplates
						$generalPadding = 50; // General padding for layout elements
						$yPositionForBioSection = $backTextLayerY + $backTextLayerHeight + $generalPadding;

						// --- Define Author Picture Layer ---
						$picX = 100; // Fixed X for picture from left edge of back panel
						$picWidth = round($backPanelWidth * 0.3); // Picture takes ~30% of back panel width
						$picWidth = max(150, $picWidth); // Minimum width for picture
						$picHeight = round($picWidth * 1.1); // Aspect ratio for picture (slightly taller than wide)
						$picHeight = max(165, $picHeight); // Minimum height for picture

						$authorPictureLayer = [
							"id" => self::AUTHOR_PIC_ID, "name" => "Author Picture", "type" => "image",
							"layerSubType" => "element", "opacity" => 1, "visible" => true, "locked" => false,
							"x" => $picX, "y" => $yPositionForBioSection,
							"width" => $picWidth, "height" => $picHeight,
							"zIndex" => 6, "rotation" => 0, "scale" => 100,
							"definition" => "back_cover_image", "content" => self::AUTHOR_PLACEHOLDER_IMAGE_PATH,
							// Default visual properties for an image layer (some might not be directly applicable but good to have)
							"fontSize" => 24, "fontFamily" => "Arial", "fontStyle" => "normal", "fontWeight" => "normal",
							"textDecoration" => "none", "fill" => "rgba(0,0,0,1)", "align" => "left", "vAlign" => "center",
							"lineHeight" => 1.3, "letterSpacing" => 0, "textPadding" => 0,
							"shadowEnabled" => false, "shadowBlur" => 5, "shadowOffsetX" => 2, "shadowOffsetY" => 2, "shadowColor" => "rgba(0,0,0,0.5)",
							"strokeWidth" => 0, "stroke" => "rgba(0,0,0,1)",
							"backgroundEnabled" => false, "backgroundColor" => "rgba(255,255,255,1)", "backgroundOpacity" => 1, "backgroundCornerRadius" => 0,
							"filters" => [ "brightness" => 100, "contrast" => 100, "saturation" => 100, "grayscale" => 0, "sepia" => 0, "hueRotate" => 0, "blur" => 0 ],
							"blendMode" => "normal"
						];

						// --- Define Author Bio Text Layer ---
						$bioTextX = $picX + $picWidth + $generalPadding; // Position bio text to the right of picture with padding
						// Calculate width for bio text, ensuring it doesn't go off the back panel
						$bioTextWidth = $backPanelWidth - $bioTextX - $generalPadding; // Space remaining after pic, its left padding, inter-padding, and a right padding
						$bioTextWidth = max(200, $bioTextWidth); // Minimum width for bio text

						// Make bio text height slightly taller than picture, similar to example proportions (e.g., 610 for bio, 520 for pic)
						$bioTextHeight = round($picHeight * 1.17);
						$bioTextHeight = max(200, $bioTextHeight); // Minimum height for bio text

						$authorBioTextLayer = [
							"id" => self::AUTHOR_BIO_ID, "name" => "Author Bio", "type" => "text",
							"layerSubType" => null, "opacity" => 1, "visible" => true, "locked" => false,
							"x" => $bioTextX, "y" => $yPositionForBioSection, // Align top with picture
							"width" => $bioTextWidth, "height" => $bioTextHeight,
							"zIndex" => 4, "rotation" => 0, "scale" => 100,
							"definition" => "back_cover_text", "content" => self::AUTHOR_PLACEHOLDER_BIO_CONTENT,
							// Inherit font and style from the main back text layer
							"fontSize" => $backTextLayerFontSize,
							"fontFamily" => $backTextLayerFontFamily,
							"fontStyle" => $backTextLayer['fontStyle'] ?? 'normal',
							"fontWeight" => $backTextLayer['fontWeight'] ?? 'normal',
							"textDecoration" => $backTextLayer['textDecoration'] ?? '',
							"fill" => $backTextLayerFill,
							"align" => "left", "vAlign" => "top", // Typical for bio text
							"lineHeight" => $backTextLayer['lineHeight'] ?? 1.3,
							"letterSpacing" => $backTextLayer['letterSpacing'] ?? 0,
							"textPadding" => $backTextLayer['textPadding'] ?? 15,
							// Inherit shadow, stroke, background properties from backTextLayer or use defaults
							"shadowEnabled" => $backTextLayer['shadowEnabled'] ?? false,
							"shadowBlur" => $backTextLayer['shadowBlur'] ?? 5,
							"shadowOffsetX" => $backTextLayer['shadowOffsetX'] ?? 2,
							"shadowOffsetY" => $backTextLayer['shadowOffsetY'] ?? 2,
							"shadowColor" => $backTextLayer['shadowColor'] ?? "rgba(0,0,0,0.5)",
							"shadowOpacity" => $backTextLayer['shadowOpacity'] ?? 0.5,
							"strokeWidth" => $backTextLayer['strokeWidth'] ?? 0,
							"stroke" => $backTextLayer['stroke'] ?? "rgba(0,0,0,1)",
							"backgroundEnabled" => $backTextLayer['backgroundEnabled'] ?? false,
							"backgroundColor" => $backTextLayer['backgroundColor'] ?? "rgba(255,255,255,1)",
							"backgroundOpacity" => $backTextLayer['backgroundOpacity'] ?? 1,
							"backgroundCornerRadius" => $backTextLayer['backgroundCornerRadius'] ?? 0,
							"backgroundPadding" => $backTextLayer['backgroundPadding'] ?? 0,
							"filters" => $backTextLayer['filters'] ?? [ "brightness" => 100, "contrast" => 100, "saturation" => 100, "grayscale" => 0, "sepia" => 0, "hueRotate" => 0, "blur" => 0 ],
							"blendMode" => $backTextLayer['blendMode'] ?? "normal",
						];

						// Add new layers to the JSON
						$fullCoverJson['layers'][] = $authorPictureLayer;
						$fullCoverJson['layers'][] = $authorBioTextLayer;

						$template->full_cover_json_content = $fullCoverJson;
						$template->save();

						$updatedCount++;
						$this->line("Added author bio section to Template ID: {$template->id}");

					} catch (\Exception $e) {
						$this->error("Error processing Template ID: {$template->id} - " . $e->getMessage());
						Log::error("Error adding author bio for Template ID {$template->id}: " . $e->getMessage(), [
							'exception_class' => get_class($e),
							'file' => $e->getFile(),
							'line' => $e->getLine(),
							'trace' => $e->getTraceAsString() // More detailed trace for debugging
						]);
						$errorCount++;
					}
				}
			});

			$this->info("-----------------------------------------");
			$this->info("Author bio section addition complete.");
			$this->info("Successfully updated: {$updatedCount} templates.");
			$this->info("Skipped (already had bio): {$skippedExistingCount} templates.");
			$this->info("Skipped (invalid JSON or missing layers key): {$skippedInvalidJsonCount} templates.");
			$this->info("Skipped (suitable back text layer not found): {$notFoundBackTextCount} templates.");
			$this->info("Errors encountered: {$errorCount} templates (check logs for details).");
			$this->info("-----------------------------------------");

			return Command::SUCCESS;
		}
	}
