<?php

	namespace App\Console\Commands;

	use App\Models\Template;
	use Illuminate\Console\Command;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;

	class UpdateTemplateJsonContent extends Command
	{
		/**
		 * The name and signature of the console command.
		 *
		 * @var string
		 */
		protected $signature = 'templates:update-json-content';

		/**
		 * The console command description.
		 *
		 * @var string
		 */
		protected $description = 'Updates the json_content of all templates with new properties and version 1.3';

		/**
		 * Execute the console command.
		 *
		 * @return int
		 */
		public function handle()
		{
			$this->info('Starting update of template json_content...');

			// Define default/additional layer properties (based on second example)
			$defaultLayerProperties = [
				'name' => null,
				'layerSubType' => null,
				'rotation' => 0,
				'scale' => 100,
				'definition' => 'cover_text',
				'vAlign' => 'center',
				'filters' => [
					'brightness' => 100,
					'contrast' => 100,
					'saturation' => 100,
					'grayscale' => 0,
					'sepia' => 0,
					'hueRotate' => 0,
					'blur' => 0
				],
				'blendMode' => 'normal',
				'shadowOpacity' => 1, // Default if not present, won't overwrite existing
				'zIndex' => 1 // Default zIndex (camelCase)
			];

			$updatedCount = 0;
			$skippedCount = 0;
			$errorCount = 0;

			// Process in chunks to avoid memory issues with large datasets
			// Wrap in a transaction if you want all-or-nothing,
			// or process individually if partial success is acceptable.
			// For a one-time script, individual processing with logging might be better
			// to identify problematic records.

			DB::transaction(function () use (&$updatedCount, &$skippedCount, &$errorCount, $defaultLayerProperties) {
				Template::chunk(100, function ($templates) use (&$updatedCount, &$skippedCount, &$errorCount, $defaultLayerProperties) {
					foreach ($templates as $template) {
						try {
							$jsonContent = $template->json_content; // Already an array due to $casts

							if (!is_array($jsonContent) || empty($jsonContent)) {
								$this->warn("Skipping Template ID: {$template->id} - json_content is empty or not an array.");
								$skippedCount++;
								continue;
							}

							$coverTextCounter = 1; // Initialize counter for cover text layers

							// 1. Update top-level version
							$jsonContent['version'] = "1.3";

							// 2. Update canvas properties
							if (!isset($jsonContent['canvas']) || !is_array($jsonContent['canvas'])) {
								$jsonContent['canvas'] = []; // Ensure canvas key exists
							}
							$jsonContent['canvas']['width'] = ($jsonContent['canvas']['width'] ?? 0) > 0 ? $jsonContent['canvas']['width'] : 1540;
							$jsonContent['canvas']['height'] = ($jsonContent['canvas']['height'] ?? 0) > 0 ? $jsonContent['canvas']['height'] : 2475;
							$jsonContent['canvas']['frontWidth'] = ($jsonContent['canvas']['frontWidth'] ?? 0) > 0 ? $jsonContent['canvas']['frontWidth'] : 1540;
							$jsonContent['canvas']['spineWidth'] = ($jsonContent['canvas']['spineWidth'] ?? 0) > 0 ? $jsonContent['canvas']['spineWidth'] : 0;
							$jsonContent['canvas']['backWidth'] = ($jsonContent['canvas']['backWidth'] ?? 0) > 0 ? $jsonContent['canvas']['backWidth'] : 0;

							// 3. Loop over layers and add/update properties
							if (isset($jsonContent['layers']) && is_array($jsonContent['layers'])) {
								foreach ($jsonContent['layers'] as &$layer) { // Use reference '&'
									if (!is_array($layer)) { // Skip if a layer is not an array
										$this->warn("Skipping malformed layer in Template ID: {$template->id}. Layer data: " . json_encode($layer));
										continue;
									}

									//set layer id to text dash counter for text layers
									if (isset($layer['type']) && $layer['type'] === 'text') {
										$layer['id'] = 'cover-text-' . $coverTextCounter++;
									}

									//set name to first 15 characters of the layer content if it doesn't exist
									if (!isset($layer['name']) || empty($layer['name'])) {
										$layer['name'] = substr($layer['content'] ?? '', 0, 15);
									}


									//round x and y, width and height to integer values
									if (isset($layer['x']) && is_numeric($layer['x'])) {
										$layer['x'] = round($layer['x']);
									}
									if (isset($layer['y']) && is_numeric($layer['y'])) {
										$layer['y'] = round($layer['y']);
									}
									if (isset($layer['width']) && is_numeric($layer['width'])) {
										$layer['width'] = round($layer['width']);
									}
									if (isset($layer['height']) && is_numeric($layer['height'])) {
										$layer['height'] = round($layer['height']);
									}

									//round shadowOpacity, lineHeight to two decimal places
									if (isset($layer['shadowOpacity']) && is_numeric($layer['shadowOpacity'])) {
										$layer['shadowOpacity'] = round($layer['shadowOpacity'], 2);
									}
									if (isset($layer['lineHeight']) && is_numeric($layer['lineHeight'])) {
										$layer['lineHeight'] = round($layer['lineHeight'], 2);
									}

									// Handle zIndex vs z-index:
									if (isset($layer['z-index']) && !isset($layer['zIndex'])) {
										$layer['zIndex'] = $layer['z-index'];
									}
									// Remove z-index if it exists to avoid confusion
									if (isset($layer['z-index'])) {
										unset($layer['z-index']);
									}
									// Ensure zIndex is present, even if z-index wasn't
									if (!isset($layer['zIndex'])) {
										$layer['zIndex'] = $defaultLayerProperties['zIndex'];
									}

									// Add default/additional properties if they don't exist
									foreach ($defaultLayerProperties as $key => $defaultValue) {
										if (!array_key_exists($key, $layer)) {
											$layer[$key] = $defaultValue;
										}
									}
								}
								unset($layer); // Unset reference
							} else {
								// If no layers array, create an empty one or log
								$jsonContent['layers'] = [];
								$this->comment("Template ID: {$template->id} had no 'layers' array. Initialized as empty.");
							}

//							echo "Json content: " . json_encode($jsonContent, JSON_PRETTY_PRINT) . "\n";

							$template->json_content = $jsonContent;
							$template->save();
							$updatedCount++;
							$this->line("Updated Template ID: {$template->id}");

						} catch (\Exception $e) {
							$this->error("Error processing Template ID: {$template->id} - " . $e->getMessage());
							Log::error("Error updating template json_content for ID {$template->id}: " . $e->getMessage(), ['exception' => $e]);
							$errorCount++;
							// If not in a transaction, this record is skipped, and loop continues.
							// If in a transaction, this will cause a rollback unless caught and handled.
							// For this script, we'll let the transaction handle it if an exception bubbles up.
							// However, by catching it here, we can log and count errors.
							// To ensure the transaction rolls back on any error, re-throw or throw a new specific exception.
							// throw $e; // Re-throw to ensure transaction rollback
						}
					}
				});

				if ($errorCount > 0) {
					// If we caught errors and want the transaction to fail, we must throw an exception here.
					// Otherwise, the transaction will commit successfully processed records.
					// For a script like this, it's often better to log errors and let successful ones commit.
					// If strict all-or-nothing is required, uncomment the throw $e inside the catch block.
					$this->warn("Transaction completed with {$errorCount} errors. These records were not updated if an exception was re-thrown.");
				}

			}); // End DB::transaction

			$this->info("-----------------------------------------");
			$this->info("Template json_content update complete.");
			$this->info("Successfully updated: {$updatedCount} templates.");
			$this->info("Skipped (empty/invalid initial JSON): {$skippedCount} templates.");
			$this->info("Errors encountered: {$errorCount} templates (check logs).");
			$this->info("-----------------------------------------");

			return Command::SUCCESS;
		}
	}
