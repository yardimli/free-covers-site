<?php

	namespace App\Services;

	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Str;
	use Intervention\Image\Facades\Image; // Or use ImageManager directly: use Intervention\Image\ImageManager;

	class ImageUploadService
	{
		protected string $disk = 'public'; // Assumes 'public' disk is configured for storage/app/public

		// If using Intervention Image 3.x
		// protected ImageManager $imageManager;
		// public function __construct(ImageManager $imageManager)
		// {
		//     $this->imageManager = $imageManager->driver(); // Or specific driver
		// }


		/**
		 * Uploads an image and its thumbnail.
		 * @param UploadedFile $file The uploaded file object.
		 * @param string $itemType 'covers', 'elements', 'overlays', or 'templates' (for thumbnail).
		 * @param string|null $existingOriginalPath Path to an existing original image to delete.
		 * @param string|null $existingThumbnailPath Path to an existing thumbnail to delete.
		 * @return array ['original_path' => string, 'thumbnail_path' => string] (storage-relative paths)
		 * @throws \Exception
		 */
		public function uploadImageWithThumbnail(
			UploadedFile $file,
			string $itemType,
			?string $existingOriginalPath = null,
			?string $existingThumbnailPath = null
		): array {
			$config = config('admin_settings.paths.' . $itemType);
			$uploadPrefix = config('admin_settings.upload_path_prefix');

			$originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
			$sanitizedFilename = $this->sanitizeFilename($originalFilename);
			$extension = $file->getClientOriginalExtension();

			// Delete old files if paths are provided
			if ($existingOriginalPath && Storage::disk($this->disk)->exists($existingOriginalPath)) {
				Storage::disk($this->disk)->delete($existingOriginalPath);
			}
			if ($existingThumbnailPath && Storage::disk($this->disk)->exists($existingThumbnailPath) && $existingThumbnailPath !== $existingOriginalPath) {
				Storage::disk($this->disk)->delete($existingThumbnailPath);
			}

			$uniqueNameBase = Str::slug($itemType) . '_' . time() . '_' . Str::random(5) . '_' . $sanitizedFilename;

			$originalPath = null;
			$thumbnailPath = null;

			if ($itemType === 'templates') { // Templates only have a "thumbnail" which is their main image
				$thumbnailDir = $uploadPrefix . '/' . $config['thumbnails'];
				$thumbnailName = $uniqueNameBase . '.' . $extension;
				$thumbnailPath = $file->storeAs($thumbnailDir, $thumbnailName, $this->disk);
				if (!$thumbnailPath) {
					throw new \Exception("Failed to store template thumbnail: {$thumbnailName}");
				}
				// For templates, original_path and thumbnail_path might point to the same file in the DB
				// or original_path might be null if you only store thumbnail_path.
				// Let's assume thumbnail_path is the primary one.
			} else {
				// Store Original
				$originalDir = $uploadPrefix . '/' . $config['originals'];
				$originalImageName = $uniqueNameBase . '.' . $extension;
				$originalPath = $file->storeAs($originalDir, $originalImageName, $this->disk);
				if (!$originalPath) {
					throw new \Exception("Failed to store original image: {$originalImageName}");
				}

				// Create Thumbnail
				$thumbnailDir = $uploadPrefix . '/' . $config['thumbnails'];
				if (!Storage::disk($this->disk)->exists($thumbnailDir)) {
					Storage::disk($this->disk)->makeDirectory($thumbnailDir);
				}
				$thumbnailName = 'thumb_' . $uniqueNameBase . '.' . $extension;
				$tempThumbnailPath = Storage::disk($this->disk)->path($thumbnailDir . '/' . $thumbnailName);

				// Intervention Image v2
				$img = Image::make(Storage::disk($this->disk)->path($originalPath));
				$img->resize($config['thumb_w'], $config['thumb_h'], function ($constraint) {
					$constraint->aspectRatio();
					$constraint->upsize(); // Prevent upsizing
				});
				if (strtolower($extension) === 'png') {
					$img->save($tempThumbnailPath);
				} else {
					$img->save($tempThumbnailPath, config('admin_settings.thumbnail_quality', 85));
				}

				// Intervention Image v3
				// $image = $this->imageManager->read(Storage::disk($this->disk)->path($originalPath));
				// $image->scaleDown(width: $config['thumb_w'], height: $config['thumb_h']); // Or fit()
				// if (strtolower($extension) === 'png') {
				//     $image->save($tempThumbnailPath);
				// } else {
				//     $image->save($tempThumbnailPath, quality: config('admin_settings.thumbnail_quality', 85));
				// }


				if (!file_exists($tempThumbnailPath)) {
					// Cleanup original if thumbnail fails
					if ($originalPath) Storage::disk($this->disk)->delete($originalPath);
					throw new \Exception("Failed to create thumbnail: {$thumbnailName}");
				}
				$thumbnailPath = $thumbnailDir . '/' . $thumbnailName;
			}

			return [
				'original_path' => $originalPath, // This will be null for templates if you only store thumbnail
				'thumbnail_path' => $thumbnailPath,
			];
		}

		public function deleteImageFiles(?string $originalPath, ?string $thumbnailPath): void
		{
			if ($originalPath && Storage::disk($this->disk)->exists($originalPath)) {
				Storage::disk($this->disk)->delete($originalPath);
			}
			if ($thumbnailPath && Storage::disk($this->disk)->exists($thumbnailPath) && $thumbnailPath !== $originalPath) {
				Storage::disk($this->disk)->delete($thumbnailPath);
			}
		}

		public function sanitizeFilename(string $filename): string
		{
			$filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", $filename);
			$filename = preg_replace("/\.{2,}/", ".", $filename);
			$filename = trim($filename, ".-_");
			$filename = substr($filename, 0, 200);
			return empty($filename) ? "file" : $filename;
		}

		/**
		 * Get public URL for a storage path.
		 */
		public function getUrl(?string $path): ?string
		{
			return $path ? Storage::disk($this->disk)->url($path) : null;
		}
	}
