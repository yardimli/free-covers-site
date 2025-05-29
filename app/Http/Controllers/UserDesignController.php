<?php

	namespace App\Http\Controllers;

	use App\Models\UserDesign;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Validator;
	use App\Services\ImageUploadService;
	use Illuminate\Support\Str; // Added Str

	class UserDesignController extends Controller
	{
		protected ImageUploadService $imageUploadService;

		public function __construct(ImageUploadService $imageUploadService)
		{
			$this->imageUploadService = $imageUploadService;
		}

		public function store(Request $request)
		{
			$validator = Validator::make($request->all(), [
				'name' => 'required|string|max:255',
				'json_data' => 'required|json',
				'preview_image_file' => 'required|image|mimes:png,jpeg|max:10240', // Max 10MB
			]);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
			}

			$user = Auth::user();
			$jsonData = json_decode($request->input('json_data'), true);

			// Create UserDesign record first to get an ID
			$userDesign = new UserDesign([
				'user_id' => $user->id,
				'name' => $request->input('name'),
				'json_data' => $jsonData,
				// preview_image_path will be set after successful upload
			]);
			$userDesign->save(); // $userDesign->id is now available

			$uploadedFilePaths = null; // To store paths from ImageUploadService

			try {
				if ($request->hasFile('preview_image_file')) {
					$file = $request->file('preview_image_file');

					// Sanitize design name for filename part
					$designNameForFile = Str::slug($request->input('name'));
					if (empty($designNameForFile)) {
						$designNameForFile = 'design'; // Fallback name
					}
					$designNameForFile = substr($designNameForFile, 0, 100); // Limit length

					// Construct the base filename: user_id_design_id_name
					$finalFilenameBase = $user->id . '_' . $userDesign->id . '_' . $designNameForFile;

					$uploadedFilePaths = $this->imageUploadService->uploadImageWithThumbnail(
						$file,
						'user_design_previews',  // Config key from admin_settings.php
						null,                    // existingOriginalPath
						null,                    // existingThumbnailPath
						(string) $user->id,      // customSubdirectory (user_id)
						$finalFilenameBase       // customFilenameBase
					);

					// Consistent with current behavior, store the original path as the preview.
					// If you want to store the thumbnail path instead:
					// $pathForDatabase = $uploadedFilePaths['thumbnail_path'] ?? $uploadedFilePaths['original_path'];
					$pathForDatabase = $uploadedFilePaths['original_path'];

					if (!$pathForDatabase) {
						// This should ideally not happen if original_path is always returned
						throw new \Exception("Image upload service did not return a valid path for the preview image.");
					}
					$userDesign->preview_image_path = $pathForDatabase;
					$userDesign->save(); // Update record with the image path
				} else {
					// This case should be caught by validator, but as a defensive measure:
					throw new \Exception("Preview image file is required but was not provided in the request.");
				}

				return response()->json([
					'success' => true,
					'message' => 'Design saved successfully!',
					'data' => [
						'id' => $userDesign->id,
						'name' => $userDesign->name,
						'preview_image_url' => $this->imageUploadService->getUrl($userDesign->preview_image_path),
					]
				]);

			} catch (\Exception $e) {
				Log::error("Error during image processing/saving for user design ID {$userDesign->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());

				// Attempt to delete any files that might have been uploaded by the service
				if (is_array($uploadedFilePaths) && (!empty($uploadedFilePaths['original_path']) || !empty($uploadedFilePaths['thumbnail_path']))) {
					try {
						$this->imageUploadService->deleteImageFiles(array_filter([
							$uploadedFilePaths['original_path'] ?? null,
							$uploadedFilePaths['thumbnail_path'] ?? null,
						]));
						Log::info("Cleaned up uploaded image(s) for user design ID {$userDesign->id} after error.");
					} catch (\Exception $deleteException) {
						Log::error("Failed to cleanup image(s) for user design ID {$userDesign->id}: " . $deleteException->getMessage());
					}
				}

				// Delete the UserDesign record as the process was incomplete
				// Check if it still exists (it should, as it was saved before try block)
				if ($userDesign && UserDesign::find($userDesign->id)) { // Re-fetch to confirm existence before delete
					$userDesign->delete();
					Log::info("Deleted UserDesign record ID {$userDesign->id} due to failed image processing step.");
				}

				return response()->json(['success' => false, 'message' => 'An error occurred while saving the design: ' . $e->getMessage()], 500);
			}
		}

		public function getJsonData(UserDesign $userDesign)
		{
			if (Auth::id() !== $userDesign->user_id && !Auth::user()->isAdmin()) { // Allow admin access if needed
				return response()->json(['error' => 'Forbidden'], 403);
			}
			return response()->json($userDesign->json_data);
		}

		public function destroy(UserDesign $userDesign)
		{
			if (Auth::id() !== $userDesign->user_id && !Auth::user()->isAdmin()) {
				return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
			}

			try {
				$pathsToDelete = [];
				$originalPath = $userDesign->preview_image_path;

				if ($originalPath) {
					$pathsToDelete[] = $originalPath;

					// Attempt to derive and add the corresponding thumbnail path for deletion
					// This logic assumes 'user_design_previews' config and naming convention
					// Original: admin_uploads/user_designs/previews/USER_ID/USERID_DESIGNID_NAME.EXT
					// Thumbnail: admin_uploads/user_designs/thumbnails/USER_ID/USERID_DESIGNID_NAME-thumbnail.EXT

					// Check if the original path seems to conform to the expected structure
					if (strpos($originalPath, 'user_designs/previews/') !== false) {
						$thumbnailPath = str_replace('/previews/', '/thumbnails/', $originalPath);
						$filename = pathinfo($thumbnailPath, PATHINFO_FILENAME);
						$extension = pathinfo($thumbnailPath, PATHINFO_EXTENSION);
						// Insert -thumbnail before the extension
						$thumbnailPath = dirname($thumbnailPath) . '/' . $filename . '-thumbnail.' . $extension;

						// Only add if it's different from original and potentially exists
						if ($thumbnailPath !== $originalPath && Storage::disk('public')->exists($thumbnailPath)) {
							$pathsToDelete[] = $thumbnailPath;
						}
					}
				}

				$userDesign->delete(); // Delete the database record

				if (!empty(array_filter($pathsToDelete))) {
					$this->imageUploadService->deleteImageFiles(array_filter($pathsToDelete));
				}

				return response()->json(['success' => true, 'message' => 'Design deleted successfully.']);
			} catch (\Exception $e) {
				Log::error("Error deleting user design ID {$userDesign->id}: " . $e->getMessage());
				return response()->json(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()], 500);
			}
		}
	}
