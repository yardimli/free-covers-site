<?php namespace App\Services;

// No need to alias Illuminate\Http\UploadedFile if we use the Symfony one in the type hint
use Symfony\Component\HttpFoundation\File\UploadedFile as BaseUploadedFile; // Use this for type hinting
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image as InterventionImageFacade;
use Illuminate\Support\Facades\Log; // Make sure Log is imported

class ImageUploadService
{
	protected string $disk = 'public';

	/**
	 * Uploads an image and optionally its thumbnail based on configuration.
	 * @param BaseUploadedFile $file The uploaded file object (can be Illuminate or Symfony variant).
	 * @param string $uploadConfigKey Key to fetch path and dimension configs (e.g., 'covers_main', 'templates_cover_image').
	 * @param string|null $existingOriginalPath Path to an existing original image to delete.
	 * @param string|null $existingThumbnailPath Path to an existing thumbnail to delete.
	 * @return array ['original_path' => ?string, 'thumbnail_path' => ?string] (storage-relative paths)
	 * @throws \Exception
	 */
	public function uploadImageWithThumbnail(
		BaseUploadedFile $file, // Type hint to the Symfony base class
		string $uploadConfigKey,
		?string $existingOriginalPath = null,
		?string $existingThumbnailPath = null
	): array {
		$config = config('admin_settings.paths.' . $uploadConfigKey);
		if (!$config || !isset($config['originals'])) {
			throw new \Exception("Upload configuration not found or incomplete for type: {$uploadConfigKey}");
		}

		$uploadPrefix = config('admin_settings.upload_path_prefix', 'uploads');

		// Get original filename without extension and sanitize it
		$originalClientFilenameWithoutExt = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
		$sanitizedOriginalBaseFilename = $this->sanitizeFilename($originalClientFilenameWithoutExt);

		// Determine file extension
		$extension = $file->getClientOriginalExtension();
		if (empty($extension) && method_exists($file, 'extension')) { // For Illuminate\Http\UploadedFile
			$extension = $file->extension();
		}
		if (empty($extension)) { // Fallback for Symfony\Component\HttpFoundation\File\UploadedFile
			$extension = $file->guessExtension();
		}
		if (empty($extension)) { // Last resort from original name
			$extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
		}
		if (empty($extension)) {
			Log::warning("ImageUploadService: Could not determine extension for file: " . $file->getPathname() . " with original name: " . $file->getClientOriginalName() . ". Defaulting to 'jpg'.");
			$extension = 'jpg';
		}
		$extension = strtolower($extension);


		// Delete existing files first (if paths provided)
		// This is important if an update might result in a different filename due to original name change or new duplicates
		if ($existingOriginalPath && Storage::disk($this->disk)->exists($existingOriginalPath)) {
			Storage::disk($this->disk)->delete($existingOriginalPath);
		}
		if ($existingThumbnailPath && Storage::disk($this->disk)->exists($existingThumbnailPath) && $existingThumbnailPath !== $existingOriginalPath) {
			Storage::disk($this->disk)->delete($existingThumbnailPath);
		}

		// Determine unique filename for the original image
		$originalDir = rtrim($uploadPrefix . '/' . $config['originals'], '/');
		Storage::disk($this->disk)->makeDirectory($originalDir); // Ensure directory exists

		$currentNamingBase = $sanitizedOriginalBaseFilename; // e.g., "my-image"
		$counter = 0;
		$finalOriginalFilenameWithExt = $currentNamingBase . '.' . $extension; // e.g., "my-image.jpg"

		while (Storage::disk($this->disk)->exists($originalDir . '/' . $finalOriginalFilenameWithExt)) {
			$counter++;
			$currentNamingBase = $sanitizedOriginalBaseFilename . '-' . $counter; // e.g., "my-image-1"
			$finalOriginalFilenameWithExt = $currentNamingBase . '.' . $extension; // e.g., "my-image-1.jpg"
		}
		// $currentNamingBase is now the unique base (e.g., "my-image" or "my-image-1")
		// $finalOriginalFilenameWithExt is the unique full filename for the original image

		$originalPath = Storage::disk($this->disk)->putFileAs($originalDir, $file, $finalOriginalFilenameWithExt);

		if (!$originalPath) {
			throw new \Exception("Failed to store original image: {$finalOriginalFilenameWithExt} for type {$uploadConfigKey}");
		}

		$thumbnailPath = null;
		$generateThumbnail = isset($config['thumbnails']) && !empty($config['thumbnails']) &&
			isset($config['thumb_w']) && $config['thumb_w'] > 0 &&
			isset($config['thumb_h']) && $config['thumb_h'] > 0;

		if ($generateThumbnail) {
			$thumbnailDir = rtrim($uploadPrefix . '/' . $config['thumbnails'], '/');
			Storage::disk($this->disk)->makeDirectory($thumbnailDir); // Ensure directory exists

			// Use the $currentNamingBase (which might have -1, -2 etc.) for the thumbnail
			$finalThumbnailFilenameWithExt = $currentNamingBase . '-thumbnail.' . $extension; // e.g., "my-image-1-thumbnail.jpg"
			$tempThumbnailPath = Storage::disk($this->disk)->path($thumbnailDir . '/' . $finalThumbnailFilenameWithExt);

			// Intervention Image's read method can take a path, SplFileInfo, or UploadedFile.
			$img = InterventionImageFacade::read($file->getRealPath());
			$img->scaleDown(width: $config['thumb_w'], height: $config['thumb_h']);
			$quality = $config['thumb_quality'] ?? config('admin_settings.thumbnail_quality', 85);
			$img->save($tempThumbnailPath, quality: $quality);

			if (!file_exists($tempThumbnailPath)) {
				if ($originalPath) Storage::disk($this->disk)->delete($originalPath); // Clean up original if thumb fails
				throw new \Exception("Failed to create thumbnail: {$finalThumbnailFilenameWithExt} for type {$uploadConfigKey}");
			}
			$thumbnailPath = $thumbnailDir . '/' . $finalThumbnailFilenameWithExt;
		}

		return [
			'original_path' => $originalPath,
			'thumbnail_path' => $thumbnailPath,
		];
	}

	/**
	 * Deletes multiple image files.
	 * @param array $paths An array of storage-relative paths to delete.
	 */
	public function deleteImageFiles(array $paths): void
	{
		foreach ($paths as $path) {
			if ($path && Storage::disk($this->disk)->exists($path)) {
				Storage::disk($this->disk)->delete($path);
			}
		}
	}

	public function sanitizeFilename(string $filename): string
	{
		// Remove potentially problematic characters but keep dots, underscores, hyphens
		$filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", $filename);
		// Prevent multiple dots next to each other
		$filename = preg_replace("/\.{2,}/", ".", $filename);
		// Trim leading/trailing dots, hyphens, underscores
		$filename = trim($filename, ".-_");
		// Limit length
		$filename = substr($filename, 0, 200); // Max length for the base name part
		return empty($filename) ? "file" : Str::lower($filename); // Ensure lowercase for consistency
	}

	/**
	 * Get public URL for a storage path.
	 */
	public function getUrl(?string $path): ?string
	{
		return $path ? Storage::disk($this->disk)->url($path) : null;
	}

	/**
	 * Stores an uploaded file (e.g., JSON) to a specified path.
	 * This method retains its original unique naming strategy.
	 * @param BaseUploadedFile $file
	 * @param string $uploadConfigKey Key to fetch path configs (e.g., 'templates_main_json')
	 * @param string|null $existingPath
	 * @return string The storage-relative path.
	 * @throws \Exception
	 */
	public function storeUploadedFile(BaseUploadedFile $file, string $uploadConfigKey, ?string $existingPath = null): string
	{
		$config = config('admin_settings.paths.' . $uploadConfigKey);
		if (!$config || !isset($config['path'])) {
			throw new \Exception("Upload configuration not found or 'path' missing for type: {$uploadConfigKey}");
		}

		$uploadPrefix = config('admin_settings.upload_path_prefix', 'uploads');
		$originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
		$sanitizedFilename = $this->sanitizeFilename($originalFilename); // Uses the same sanitization

		$extension = $file->getClientOriginalExtension();
		if (empty($extension) && method_exists($file, 'extension')) {
			$extension = $file->extension();
		}
		if (empty($extension)) {
			$extension = $file->guessExtension();
		}
		if (empty($extension)) {
			$extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
		}
		if (empty($extension)) {
			Log::warning("storeUploadedFile: Could not determine extension for file: " . $file->getPathname() . " with original name: " . $file->getClientOriginalName() . ". Defaulting to 'dat'.");
			$extension = 'dat';
		}
		$extension = strtolower($extension);

		if ($existingPath && Storage::disk($this->disk)->exists($existingPath)) {
			Storage::disk($this->disk)->delete($existingPath);
		}

		// Retain unique naming for non-image files for now, as request was specific to images/thumbnails
		$uniqueNameBase = Str::slug(str_replace('_', '-', $uploadConfigKey)) . '_' . time() . '_' . Str::random(5) . '_' . $sanitizedFilename;
		$targetDir = rtrim($uploadPrefix . '/' . $config['path'], '/');
		Storage::disk($this->disk)->makeDirectory($targetDir); // Ensure directory exists

		$targetName = $uniqueNameBase . '.' . $extension;

		$storedPath = Storage::disk($this->disk)->putFileAs($targetDir, $file, $targetName);

		if (!$storedPath) {
			throw new \Exception("Failed to store file: {$targetName} for type {$uploadConfigKey}");
		}
		return $storedPath;
	}
}
