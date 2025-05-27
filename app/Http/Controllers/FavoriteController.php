<?php

	namespace App\Http\Controllers;

	use App\Models\Favorite;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Database\QueryException;

	class FavoriteController extends Controller
	{
		public function store(Request $request)
		{
			$request->validate([
				'cover_id' => 'required|exists:covers,id',
				'template_id' => 'nullable|exists:templates,id',
			]);

			$user = Auth::user();
			$coverId = $request->input('cover_id');
			$templateId = $request->input('template_id') ?: null; // Ensure actual null if empty

			try {
				$favorite = Favorite::firstOrCreate(
					[
						'user_id' => $user->id,
						'cover_id' => $coverId,
						'template_id' => $templateId,
					]
				);

				return response()->json([
					'success' => true,
					'message' => 'Added to favorites!',
					'favorite_id' => $favorite->id,
					'is_favorited' => true,
				]);
			} catch (QueryException $e) {
				// Handle potential unique constraint violation if firstOrCreate fails (should be rare)
				return response()->json(['success' => false, 'message' => 'Could not add to favorites. It might already be favorited or an error occurred.'], 409);
			}
		}

		public function destroy(Request $request)
		{
			// This method is for removing based on cover_id and template_id (used by cover show page)
			$request->validate([
				'cover_id' => 'required|exists:covers,id',
				'template_id' => 'nullable|exists:templates,id',
			]);

			$user = Auth::user();
			$coverId = $request->input('cover_id');
			$templateId = $request->input('template_id') ?: null;

			$deleted = Favorite::where('user_id', $user->id)
				->where('cover_id', $coverId)
				->where('template_id', $templateId)
				->delete();

			if ($deleted) {
				return response()->json(['success' => true, 'message' => 'Removed from favorites!', 'is_favorited' => false]);
			}

			return response()->json(['success' => false, 'message' => 'Favorite not found or already removed.'], 404);
		}

		public function destroyById(Favorite $favorite)
		{
			// This method is for removing by Favorite ID (used by dashboard)
			if ($favorite->user_id !== Auth::id()) {
				return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
			}

			$favorite->delete();
			return response()->json(['success' => true, 'message' => 'Removed from favorites!']);
		}
	}
