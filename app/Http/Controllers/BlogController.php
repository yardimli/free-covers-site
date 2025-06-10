<?php

	namespace App\Http\Controllers;

	use App\Models\BlogPost;
	use App\Models\BlogCategory;
	use Illuminate\Http\Request;
	use Illuminate\Support\Str;
	use Artesaos\SEOTools\Facades\SEOTools;

	class BlogController extends Controller
	{
		private function getSidebarData(Request $request = null)
		{
			$categories = BlogCategory::withCount(['posts' => function ($query) {
				$query->where('status', 'published')->where('published_at', '<=', now());
			}])->having('posts_count', '>', 0)->orderBy('name')->get();

			$recentPosts = BlogPost::where('status', 'published')
				->where('published_at', '<=', now())
				->orderBy('published_at', 'desc')
				->take(4) // As per template example
				->get();

			$allTags = BlogPost::where('status', 'published')
				->where('published_at', '<=', now())
				->whereNotNull('keywords')
				->pluck('keywords')
				->flatMap(function ($keywords) {
					return is_array($keywords) ? $keywords : [];
				})
				->map(function ($keyword) {
					return Str::title(trim($keyword));
				})
				->filter()
				->unique()
				->sort()
				->values()
				->take(10); // Limit tags displayed as per template example

			return compact('categories', 'recentPosts', 'allTags');
		}

		public function index(Request $request)
		{
			$query = BlogPost::with('category')
				->where('status', 'published')
				->where('published_at', '<=', now());

			if ($request->filled('search')) {
				$searchTerm = $request->search;
				$query->where(function ($q) use ($searchTerm) {
					$q->where('title', 'LIKE', "%{$searchTerm}%")
						->orWhere('content', 'LIKE', "%{$searchTerm}%")
						->orWhere('short_description', 'LIKE', "%{$searchTerm}%");
				});
			}

			if ($request->filled('category')) {
				$categorySlug = $request->category;
				$query->whereHas('category', function ($q) use ($categorySlug) {
					$q->where('slug', $categorySlug);
				});
			}

			if ($request->filled('tag')) {
				$tagSlug = $request->tag;
				$tagName = Str::title(str_replace('-', ' ', $tagSlug));
				// Search for the tag in various cases to be more robust
				$query->where(function ($q) use ($tagName) {
					$q->whereJsonContains('keywords', $tagName)
						->orWhereJsonContains('keywords', strtolower($tagName))
						->orWhereJsonContains('keywords', strtoupper($tagName))
						->orWhereJsonContains('keywords', Str::ucfirst(strtolower($tagName))); // Handle cases like "Travel" vs "travel"
				});
			}

			$posts = $query->orderBy('published_at', 'desc')
				->paginate(5) // Number of posts per page
				->withQueryString(); // Appends current query parameters to pagination links

			$sidebarData = $this->getSidebarData($request);

			$pageTitle = 'Our Blog';
			if ($request->filled('category')) {
				$category = BlogCategory::where('slug', $request->category)->first();
				if ($category) {
					$pageTitle = 'Category: ' . $category->name;
				}
			} elseif ($request->filled('tag')) {
				$pageTitle = 'Tag: ' . Str::title(str_replace('-', ' ', $request->tag));
			}

			SEOTools::setTitle($pageTitle);
			SEOTools::setDescription('The official blog for Free Kindle Covers. Find tips, tutorials, and announcements about our free book cover design tools.');
			SEOTools::setCanonical(route('blog.index'));
			SEOTools::opengraph()->setUrl(route('blog.index'));

			return view('blog.index', array_merge(compact('posts', 'pageTitle'), $sidebarData));
		}

		public function show($slug)
		{
			$post = BlogPost::with('category')
				->where('slug', $slug)
				->where('status', 'published')
				->where('published_at', '<=', now())
				->firstOrFail();

			$pageTitle = $post->title;
			$pageDescription = Str::limit($post->short_description, 155);

			SEOTools::setTitle($pageTitle);
			SEOTools::setDescription($pageDescription);
			SEOTools::setCanonical(route('blog.show', $post->slug));

			// OpenGraph & Twitter Cards for Articles
			SEOTools::opengraph()->setUrl(route('blog.show', $post->slug));
			SEOTools::opengraph()->addProperty('type', 'article');
			SEOTools::opengraph()->setTitle($pageTitle);
			SEOTools::opengraph()->setDescription($pageDescription);
			SEOTools::opengraph()->addProperty('article:published_time', $post->published_at->toIso8601String());
			SEOTools::opengraph()->addProperty('article:modified_time', $post->updated_at->toIso8601String());
			if ($post->category) {
				SEOTools::opengraph()->addProperty('article:section', $post->category->name);
			}
			if ($post->keywords && is_array($post->keywords)) {
				foreach ($post->keywords as $tag) {
					SEOTools::opengraph()->addProperty('article:tag', $tag);
				}
			}

			if ($post->image_path) {
				SEOTools::opengraph()->addImage(asset('storage/' . $post->image_path));
				SEOTools::twitter()->setImage(asset('storage/' . $post->image_path));
			}

			// Structured Data (JSON-LD) for Blog Posts
			SEOTools::jsonLd()->setType('BlogPosting');
			SEOTools::jsonLd()->setTitle($pageTitle);
			SEOTools::jsonLd()->setDescription($pageDescription);
			SEOTools::jsonLd()->setUrl(route('blog.show', $post->slug));
			SEOTools::jsonLd()->addValue('datePublished', $post->published_at->toIso8601String());
			SEOTools::jsonLd()->addValue('dateModified', $post->updated_at->toIso8601String());
			if ($post->image_path) {
				SEOTools::jsonLd()->setImage(asset('storage/' . $post->image_path));
			}
			SEOTools::jsonLd()->addValue('author', [
				'@type' => 'Person',
				'name' => 'FreeKindleCovers Team' // Or add an author relationship to your post model
			]);
			SEOTools::jsonLd()->addValue('publisher', [
				'@type' => 'Organization',
				'name' => 'Free Kindle Covers',
				'logo' => [
					'@type' => 'ImageObject',
					'url' => asset('images/favicon-32x32.png') // Or a proper logo image
				]
			]);


			$relatedPosts = BlogPost::where('status', 'published')
				->where('published_at', '<=', now())
				->where('blog_category_id', $post->blog_category_id)
				->where('id', '!=', $post->id)
				->orderBy('published_at', 'desc')
				->take(2) // Number of related posts
				->get();

			$sidebarData = $this->getSidebarData();

			return view('blog.show', array_merge(compact('post', 'relatedPosts'), $sidebarData));
		}
	}
