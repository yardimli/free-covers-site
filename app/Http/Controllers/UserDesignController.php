<?php

	namespace App\Http\Controllers;

	use App\Models\UserDesign;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Validator;
	use App\Services\ImageUploadService;

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

			$imagePath = null; // Initialize to null

			try {
				$user = Auth::user();
				$jsonData = json_decode($request->input('json_data'), true);

				if ($request->hasFile('preview_image_file')) {
					// Ensure 'user_design_previews' is configured in config/image_upload.php
					// This config should specify not to create a separate thumbnail, or we use original_path.
					$paths = $this->imageUploadService->uploadImageWithThumbnail(
						$request->file('preview_image_file'),
						'user_design_previews'
					);
					$imagePath = $paths['original_path'];
				}

				if (!$imagePath) {
					return response()->json(['success' => false, 'message' => 'Preview image upload failed.'], 500);
				}

				$userDesign = UserDesign::create([
					'user_id' => $user->id,
					'name' => $request->input('name'),
					'json_data' => $jsonData,
					'preview_image_path' => $imagePath,
				]);

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
				Log::error("Error saving user design: " . $e->getMessage() . "\n" . $e->getTraceAsString());
				if (!empty($imagePath)) { // Check if imagePath was set
					// Attempt to delete the uploaded image if DB save failed
					// Check if $userDesign was instantiated and if it exists in DB
					$designExists = isset($userDesign) && $userDesign->exists;
					if (!$designExists) {
						try {
							$this->imageUploadService->deleteImageFiles([$imagePath]);
							Log::info("Cleaned up orphaned image after failed design save: " . $imagePath);
						} catch (\Exception $deleteException) {
							Log::error("Failed to cleanup orphaned image: " . $deleteException->getMessage());
						}
					}
				}
				return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
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
				$pathsToDelete = [$userDesign->preview_image_path];
				$userDesign->delete();
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
