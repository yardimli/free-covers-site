<?php

	namespace App\Services;

	use Symfony\Component\HttpFoundation\File\UploadedFile as BaseUploadedFile;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Str;
	use Intervention\Image\Laravel\Facades\Image as InterventionImageFacade;
	use Illuminate\Support\Facades\Log;

	class ImageUploadService
	{
		protected string $disk = 'public';

		/**
		 * Uploads an image and optionally its thumbnail based on configuration.
		 * @param BaseUploadedFile $file The uploaded file object.
		 * @param string $uploadConfigKey Key to fetch path and dimension configs.
		 * @param string|null $existingOriginalPath Path to an existing original image to delete.
		 * @param string|null $existingThumbnailPath Path to an existing thumbnail to delete.
		 * @param string|null $customSubdirectory Optional subdirectory to append to configured root paths.
		 * @param string|null $customFilenameBase Optional base name for the file (without extension).
		 * @return array ['original_path' => ?string, 'thumbnail_path' => ?string]
		 * @throws \Exception
		 */
		public function uploadImageWithThumbnail(
			BaseUploadedFile $file,
			string           $uploadConfigKey,
			?string          $existingOriginalPath = null,
			?string          $existingThumbnailPath = null,
			?string          $customSubdirectory = null,
			?string          $customFilenameBase = null
		): array
		{
			$config = config('admin_settings.paths.' . $uploadConfigKey);
			if (!$config || (!isset($config['originals_root']) && !isset($config['originals']))) {
				throw new \Exception("Upload configuration not found or incomplete for type: {$uploadConfigKey}");
			}

			$globalUploadPrefix = config('admin_settings.upload_path_prefix', 'uploads');
			$isUserDesignUpload = ($uploadConfigKey === 'user_design_previews');

			// Determine file extension (same as before)
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
				Log::warning("ImageUploadService: Could not determine extension for file: " . $file->getPathname() . ". Defaulting to 'jpg'.");
				$extension = 'jpg';
			}
			$extension = strtolower($extension);

			// Delete existing files (same as before)
			if ($existingOriginalPath && Storage::disk($this->disk)->exists($existingOriginalPath)) {
				Storage::disk($this->disk)->delete($existingOriginalPath);
			}
			if ($existingThumbnailPath && Storage::disk($this->disk)->exists($existingThumbnailPath) && $existingThumbnailPath !== $existingOriginalPath) {
				Storage::disk($this->disk)->delete($existingThumbnailPath);
			}

			// Determine original directory
			$originalRootPathFromConfig = $config['originals_root'] ?? $config['originals'];

			if ($isUserDesignUpload) {
				// For user designs, the root path from config is the *full* path from storage/app/public
				$originalBaseDir = rtrim($originalRootPathFromConfig, '/');
			} else {
				// For other uploads, prepend the global prefix
				$originalBaseDir = rtrim($globalUploadPrefix . '/' . $originalRootPathFromConfig, '/');
			}
			$originalDir = $originalBaseDir . ($customSubdirectory ? '/' . trim($customSubdirectory, '/') : '');
			Storage::disk($this->disk)->makeDirectory($originalDir); // Ensure directory exists

			// Determine filename base (same as before)
			$filenameBaseToUse = $customFilenameBase ?: $this->sanitizeFilename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

			// Determine unique filename for the original image (same as before)
			$currentNamingBase = $filenameBaseToUse;
			$counter = 0;
			$finalOriginalFilenameWithExt = $currentNamingBase . '.' . $extension;
			while (Storage::disk($this->disk)->exists($originalDir . '/' . $finalOriginalFilenameWithExt)) {
				$counter++;
				$currentNamingBase = $filenameBaseToUse . '-' . $counter;
				$finalOriginalFilenameWithExt = $currentNamingBase . '.' . $extension;
			}

			$originalPath = Storage::disk($this->disk)->putFileAs($originalDir, $file, $finalOriginalFilenameWithExt);
			if (!$originalPath) {
				throw new \Exception("Failed to store original image: {$finalOriginalFilenameWithExt} for type {$uploadConfigKey}");
			}

			$thumbnailPath = null;
			$generateThumbnail = (isset($config['thumbnails_root']) || isset($config['thumbnails'])) &&
				isset($config['thumb_w']) && $config['thumb_w'] > 0 &&
				isset($config['thumb_h']) && $config['thumb_h'] > 0;

			if ($generateThumbnail) {
				$thumbnailRootPathFromConfig = $config['thumbnails_root'] ?? $config['thumbnails'];

				if ($isUserDesignUpload) {
					// For user designs, the root path from config is the *full* path from storage/app/public
					$thumbnailBaseDir = rtrim($thumbnailRootPathFromConfig, '/');
				} else {
					// For other uploads, prepend the global prefix
					$thumbnailBaseDir = rtrim($globalUploadPrefix . '/' . $thumbnailRootPathFromConfig, '/');
				}
				$thumbnailDir = $thumbnailBaseDir . ($customSubdirectory ? '/' . trim($customSubdirectory, '/') : '');
				Storage::disk($this->disk)->makeDirectory($thumbnailDir); // Ensure directory exists

				$finalThumbnailFilenameWithExt = $currentNamingBase . '-thumbnail.' . $extension;
				$tempThumbnailStoragePath = $thumbnailDir . '/' . $finalThumbnailFilenameWithExt;

				$img = InterventionImageFacade::read($file->getRealPath());
				$img->scaleDown(width: $config['thumb_w'], height: $config['thumb_h']);
				$quality = $config['thumb_quality'] ?? config('admin_settings.thumbnail_quality', 85);

				$fullDiskPathForThumbnail = Storage::disk($this->disk)->path($tempThumbnailStoragePath);
				$img->save($fullDiskPathForThumbnail, quality: $quality);

				if (!file_exists($fullDiskPathForThumbnail)) {
					if ($originalPath) Storage::disk($this->disk)->delete($originalPath);
					throw new \Exception("Failed to create thumbnail: {$finalThumbnailFilenameWithExt} for type {$uploadConfigKey}");
				}
				$thumbnailPath = $tempThumbnailStoragePath;
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
			$filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", $filename);
			$filename = preg_replace("/\.{2,}/", ".", $filename);
			$filename = trim($filename, ".-_");
			$filename = substr($filename, 0, 200);
			return empty($filename) ? "file" : Str::lower($filename);
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

			// For storeUploadedFile, we assume it always uses the global prefix if one is set.
			// If specific non-prefixed paths are needed for JSONs, this method would need similar conditional logic.
			// For now, keeping it consistent with its previous behavior.
			$uploadPrefix = config('admin_settings.upload_path_prefix', 'uploads');
			$baseDirFromConfig = rtrim($uploadPrefix . '/' . $config['path'], '/');

			$originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
			$sanitizedFilename = $this->sanitizeFilename($originalFilename);

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
				Log::warning("storeUploadedFile: Could not determine extension for file: " . $file->getPathname() . ". Defaulting to 'dat'.");
				$extension = 'dat';
			}
			$extension = strtolower($extension);

			if ($existingPath && Storage::disk($this->disk)->exists($existingPath)) {
				Storage::disk($this->disk)->delete($existingPath);
			}

			$uniqueNameBase = Str::slug(str_replace('_', '-', $uploadConfigKey)) . '_' . time() . '_' . Str::random(5) . '_' . $sanitizedFilename;
			$targetDir = $baseDirFromConfig; // Use the potentially prefixed path
			Storage::disk($this->disk)->makeDirectory($targetDir);
			$targetName = $uniqueNameBase . '.' . $extension;

			$storedPath = Storage::disk($this->disk)->putFileAs($targetDir, $file, $targetName);

			if (!$storedPath) {
				throw new \Exception("Failed to store file: {$targetName} for type {$uploadConfigKey}");
			}
			return $storedPath;
		}
	}
