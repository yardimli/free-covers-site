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

	class AdminTemplateCloneController extends Controller
	{
		protected ImageUploadService $imageUploadService;

		public function __construct(ImageUploadService $imageUploadService)
		{
			$this->imageUploadService = $imageUploadService;
		}

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
