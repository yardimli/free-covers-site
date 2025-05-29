<?php

	namespace App\Http\Controllers\Admin;

	use App\Http\Controllers\Controller;
	use App\Models\BlogCategory;
	use App\Models\BlogPost;
	use App\Services\ImageUploadService;
	use App\Services\OpenAiService;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Validator;
	use Illuminate\Support\Str;
	use Illuminate\Validation\Rule;

	class BlogManagementController extends Controller
	{
		protected ImageUploadService $imageUploadService;
		protected OpenAiService $openAiService;

		public function __construct(ImageUploadService $imageUploadService, OpenAiService $openAiService)
		{
			$this->imageUploadService = $imageUploadService;
			$this->openAiService = $openAiService;
		}

		public function index()
		{
			// This view will show messages if redirected from store/update
			return view('admin.blog.index');
		}

		// --- Blog Category Methods --- (Remain Unchanged)
		public function listCategories(Request $request)
		{
			try {
				$search = $request->input('search');
				$query = BlogCategory::orderBy('name');
				if ($search) {
					$query->where('name', 'LIKE', "%{$search}%");
				}
				$categories = $query->get();
				return response()->json(['success' => true, 'data' => $categories]);
			} catch (\Exception $e) {
				Log::error("Error fetching blog categories: " . $e->getMessage());
				return response()->json(['success' => false, 'message' => 'Error fetching categories.'], 500);
			}
		}

		public function storeCategory(Request $request)
		{
			$validator = Validator::make($request->all(), [
				'name' => 'required|string|max:255|unique:blog_categories,name',
			]);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
			}

			try {
				$category = BlogCategory::create([
					'name' => $request->input('name'),
					'slug' => Str::slug($request->input('name')),
				]);
				return response()->json(['success' => true, 'message' => 'Category created successfully.', 'data' => $category]);
			} catch (\Exception $e) {
				Log::error("Error creating blog category: " . $e->getMessage());
				return response()->json(['success' => false, 'message' => 'Error creating category.'], 500);
			}
		}

		public function updateCategory(Request $request, BlogCategory $category)
		{
			$validator = Validator::make($request->all(), [
				'name' => ['required', 'string', 'max:255', Rule::unique('blog_categories')->ignore($category->id)],
			]);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
			}

			try {
				$category->update([
					'name' => $request->input('name'),
					'slug' => Str::slug($request->input('name')),
				]);
				return response()->json(['success' => true, 'message' => 'Category updated successfully.', 'data' => $category]);
			} catch (\Exception $e) {
				Log::error("Error updating blog category {$category->id}: " . $e->getMessage());
				return response()->json(['success' => false, 'message' => 'Error updating category.'], 500);
			}
		}

		public function destroyCategory(BlogCategory $category)
		{
			try {
				if ($category->posts()->count() > 0) {
					return response()->json(['success' => false, 'message' => 'Cannot delete category with associated posts.'], 400);
				}
				$category->delete();
				return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
			} catch (\Exception $e) {
				Log::error("Error deleting blog category {$category->id}: " . $e->getMessage());
				return response()->json(['success' => false, 'message' => 'Error deleting category.'], 500);
			}
		}

		// --- Blog Post Methods ---

		public function listPosts(Request $request) // Remains for AJAX table on index page
		{
			$validator = Validator::make($request->all(), [
				'page' => 'integer|min:1',
				'limit' => 'integer|min:1',
				'search' => 'nullable|string|max:255',
				'category_id' => 'nullable|integer|exists:blog_categories,id',
				'status' => 'nullable|string|in:draft,published',
			]);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'message' => 'Invalid input.', 'errors' => $validator->errors()], 422);
			}

			$page = $request->input('page', 1);
			$limit = $request->input('limit', config('admin_settings.items_per_page', 15));
			$search = $request->input('search');
			$categoryId = $request->input('category_id');
			$status = $request->input('status');

			$query = BlogPost::with('category:id,name')->orderBy('created_at', 'desc');

			if ($search) {
				$query->where(function ($q) use ($search) {
					$q->where('title', 'LIKE', "%{$search}%")
						->orWhere('short_description', 'LIKE', "%{$search}%")
						->orWhereJsonContains('keywords', $search);
				});
			}
			if ($categoryId) {
				$query->where('blog_category_id', $categoryId);
			}
			if ($status) {
				$query->where('status', $status);
			}

			$paginatedItems = $query->paginate($limit, ['*'], 'page', $page);
			$items = $paginatedItems->getCollection()->map(function ($post) {
				$post->image_url = $this->imageUploadService->getUrl($post->image_path);
				$post->thumbnail_url = $this->imageUploadService->getUrl($post->thumbnail_path);
				$post->category_name = $post->category->name ?? 'N/A';
				$post->keywords_string = is_array($post->keywords) ? implode(', ', $post->keywords) : '';
				return $post;
			});

			return response()->json([
				'success' => true,
				'data' => [
					'items' => $items,
					'pagination' => [
						'totalItems' => $paginatedItems->total(),
						'itemsPerPage' => $paginatedItems->perPage(),
						'currentPage' => $paginatedItems->currentPage(),
						'totalPages' => $paginatedItems->lastPage(),
					],
				]
			]);
		}

		private function preparePostData(Request $request, ?BlogPost $post = null): array
		{
			$data = $request->only(['title', 'blog_category_id', 'short_description', 'content', 'status']);
			$data['slug'] = Str::slug($request->input('title'));
			$originalSlug = $data['slug'];
			$counter = 1;
			while (BlogPost::where('slug', $data['slug'])->where('id', '!=', $post->id ?? 0)->exists()) {
				$data['slug'] = $originalSlug . '-' . $counter++;
			}

			$data['keywords'] = $request->input('keywords') ? array_map('trim', explode(',', $request->input('keywords'))) : [];

			if ($request->input('status') === 'published' && (!$post || $post->status !== 'published' || !$post->published_at)) {
				$data['published_at'] = now();
			} elseif ($post && $post->status === 'published' && $request->input('status') === 'draft') {
				$data['published_at'] = null;
			} elseif ($post && $post->status === 'published' && $request->input('status') === 'published') {
				// If already published and status remains published, keep existing published_at
				$data['published_at'] = $post->published_at;
			}


			if ($request->hasFile('image_file')) {
				$paths = $this->imageUploadService->uploadImageWithThumbnail(
					$request->file('image_file'),
					'blog_post_images',
					$post->image_path ?? null,
					$post->thumbnail_path ?? null
				);
				$data['image_path'] = $paths['original_path'];
				$data['thumbnail_path'] = $paths['thumbnail_path'];
			}
			return $data;
		}

		/**
		 * Show the form for creating a new blog post.
		 */
		public function create()
		{
			$categories = BlogCategory::orderBy('name')->get();
			return view('admin.blog.posts.create', compact('categories'));
		}

		public function storePost(Request $request)
		{
			$validator = Validator::make($request->all(), [
				'title' => 'required|string|max:255',
				'blog_category_id' => 'required|integer|exists:blog_categories,id',
				'short_description' => 'nullable|string|max:500',
				'content' => 'required|string',
				'keywords' => 'nullable|string|max:1000',
				'image_file' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120', // 5MB
				'status' => ['required', Rule::in(['draft', 'published'])],
			]);

			if ($validator->fails()) {
				return redirect()->route('admin.blog.posts.create')
					->withErrors($validator)
					->withInput();
			}

			try {
				$data = $this->preparePostData($request);
				BlogPost::create($data);
				return redirect()->route('admin.blog.index')->with('success_message', 'Blog post created successfully.');
			} catch (\Exception $e) {
				Log::error("Error creating blog post: " . $e->getMessage() . "\n" . $e->getTraceAsString());
				return redirect()->route('admin.blog.posts.create')
					->with('error_message', 'Error creating post: ' . $e->getMessage())
					->withInput();
			}
		}

		/**
		 * Show the form for editing the specified blog post.
		 */
		public function edit(BlogPost $post)
		{
			$categories = BlogCategory::orderBy('name')->get();
			$post->keywords_string = is_array($post->keywords) ? implode(', ', $post->keywords) : '';
			return view('admin.blog.posts.edit', compact('post', 'categories'));
		}

		public function updatePost(Request $request, BlogPost $post)
		{
			$validator = Validator::make($request->all(), [
				'title' => 'required|string|max:255',
				'blog_category_id' => 'required|integer|exists:blog_categories,id',
				'short_description' => 'nullable|string|max:500',
				'content' => 'required|string',
				'keywords' => 'nullable|string|max:1000',
				'image_file' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
				'status' => ['required', Rule::in(['draft', 'published'])],
			]);

			if ($validator->fails()) {
				return redirect()->route('admin.blog.posts.edit', $post->id)
					->withErrors($validator)
					->withInput();
			}

			try {
				$data = $this->preparePostData($request, $post);
				$post->update($data);
				return redirect()->route('admin.blog.index')->with('success_message', 'Blog post updated successfully.');
				// Alternative: redirect back to edit page
				// return redirect()->route('admin.blog.posts.edit', $post->id)->with('success_message', 'Blog post updated successfully.');
			} catch (\Exception $e) {
				Log::error("Error updating blog post {$post->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
				return redirect()->route('admin.blog.posts.edit', $post->id)
					->with('error_message', 'Error updating post: ' . $e->getMessage())
					->withInput();
			}
		}

		public function destroyPost(BlogPost $post) // Remains AJAX
		{
			try {
				$pathsToDelete = $post->getAllImagePaths();
				$post->delete();
				if (!empty($pathsToDelete)) {
					$this->imageUploadService->deleteImageFiles(array_filter($pathsToDelete));
				}
				return response()->json(['success' => true, 'message' => 'Blog post deleted successfully.']);
			} catch (\Exception $e) {
				Log::error("Error deleting blog post {$post->id}: " . $e->getMessage());
				return response()->json(['success' => false, 'message' => 'Error deleting post.'], 500);
			}
		}

		// --- AI Blog Post Generation --- (Endpoint remains, UI trigger needs to be on create/edit page)
		public function generateAiBlogPost(Request $request)
		{
			$validator = Validator::make($request->all(), [
				'topic' => 'required|string|min:10|max:500',
				'blog_category_id' => 'nullable|integer|exists:blog_categories,id',
			]);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
			}

			$topic = $request->input('topic');
			$categoryId = $request->input('blog_category_id');
			$categoryName = $categoryId ? BlogCategory::find($categoryId)?->name : null;

			$systemMessage = "You are an expert blog writer and SEO specialist. Generate a blog post in JSON format. The output MUST be ONLY the raw JSON content, without any surrounding text, explanations, or markdown ```json ... ``` tags. Ensure all text is engaging and well-written.";
			$userPrompt = "Topic: \"{$topic}\"\n";
			if ($categoryName) {
				$userPrompt .= "Suggested Category: \"{$categoryName}\"\n";
			}
			$userPrompt .= "Generate a blog post with the following JSON structure:\n";
			$userPrompt .= <<<JSONSTRUCTURE
{
    "title": "string (compelling and SEO-friendly, around 50-70 characters)",
    "slug_suggestion": "string (lowercase, hyphenated version of the title, AI generated)",
    "short_description": "string (concise summary, around 150-160 characters, suitable for a meta description)",
    "content": "string (HTML formatted blog content, at least 300-500 words, with headings like <h2>, <h3>, paragraphs <p>, and possibly lists <ul><li>. Make it engaging and informative. Do not use <h1>.)",
    "keywords": ["array", "of", "5-10", "relevant", "keywords", "for", "SEO"]
}
JSONSTRUCTURE;
			$userPrompt .= "\nEnsure the content is original, well-structured, and provides value to the reader.";
			if ($categoryName) {
				$userPrompt .= " The content should align with the category: \"{$categoryName}\".";
			}

			$messages = [
				["role" => "system", "content" => $systemMessage],
				["role" => "user", "content" => $userPrompt]
			];

			try {
				$responseFormat = (str_contains(config('admin_settings.openai_text_model'), 'gpt-4') || str_contains(config('admin_settings.openai_text_model'), '1106')) ? ['type' => 'json_object'] : null;
				$aiResponse = $this->openAiService->generateText($messages, 0.7, 2000, $responseFormat);

				if (isset($aiResponse['error'])) {
					Log::error("AI Blog Post Error for topic '{$topic}': " . $aiResponse['error']);
					return response()->json(['success' => false, 'message' => "AI Error: " . $aiResponse['error']], 500);
				}

				$generatedJsonString = $aiResponse['content'];
				if (!$responseFormat && preg_match('/```json\s*([\s\S]*?)\s*```/', $generatedJsonString, $matches)) {
					$generatedJsonString = $matches[1];
				}
				$generatedJsonString = trim($generatedJsonString);
				$decodedJson = json_decode($generatedJsonString, true);

				if (json_last_error() !== JSON_ERROR_NONE) {
					Log::error("AI Blog Post: Invalid JSON response for topic '{$topic}'. Error: " . json_last_error_msg() . ". Raw: " . $aiResponse['content']);
					return response()->json(['success' => false, 'message' => 'AI returned invalid JSON: ' . json_last_error_msg() . ". Raw AI output: " . Str::limit($aiResponse['content'], 200) . "..."], 500);
				}

				$expectedKeys = ['title', 'slug_suggestion', 'short_description', 'content', 'keywords'];
				foreach ($expectedKeys as $key) {
					if (!array_key_exists($key, $decodedJson)) {
						Log::error("AI Blog Post: Missing key '{$key}' in JSON response for topic '{$topic}'.");
						return response()->json(['success' => false, 'message' => "AI returned JSON missing the '{$key}' field."], 500);
					}
				}
				if (!is_array($decodedJson['keywords'])) {
					$decodedJson['keywords'] = $decodedJson['keywords'] ? array_map('trim', explode(',', (string)$decodedJson['keywords'])) : [];
				}

				return response()->json([
					'success' => true,
					'message' => 'AI-generated blog post content ready.',
					'data' => $decodedJson
				]);

			} catch (\Exception $e) {
				Log::error("AI Blog Post generation error for topic '{$topic}': " . $e->getMessage() . "\n" . $e->getTraceAsString());
				return response()->json(['success' => false, 'message' => 'AI blog post generation failed: ' . $e->getMessage()], 500);
			}
		}
	}
