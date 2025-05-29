{{-- resources/views/partials/blog/sidebar.blade.php --}}
<div class="blog-right-content mb-50 px-xl-4 wow fadeInLeft">
	<form action="{{ route('blog.index') }}" method="GET">
		<div class="input-group mb-50">
			<input type="text" name="search" id="blog_search_input" placeholder="Search Blog..." value="{{ request('search') }}" class="form-control">
			<button type="submit" class="search-icon" style="border:none; background:transparent; cursor:pointer; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); z-index: 10;"><i class="las la-search"></i></button>
		</div>
	</form>
	
	@if(isset($categories) && $categories->count() > 0)
		<h4 class="blog-title mb-30">Post Categories</h4>
		<ul class="list-unstyled category_list">
			@foreach($categories as $category)
				<li><a href="{{ route('blog.index', ['category' => $category->slug]) }}" class="{{ request('category') == $category->slug ? 'active' : '' }}">{{ $category->name }} <span>({{ $category->posts_count }})</span></a></li>
			@endforeach
		</ul>
	@endif
	
	@if(isset($recentPosts) && $recentPosts->count() > 0)
		<h4 class="blog-title mb-30">Recent News</h4>
		<div class="recent-news mb-50">
			@foreach($recentPosts as $rPost)
				<div class="news-item">
					<a href="{{ route('blog.show', $rPost->slug) }}">
						@if($rPost->thumbnail_path)
							<img src="{{ asset('storage/' . $rPost->thumbnail_path) }}" alt="{{ Str::limit($rPost->title, 20) }}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 4px;">
						@elseif($rPost->image_path)
							<img src="{{ asset('storage/' . $rPost->image_path) }}" alt="{{ Str::limit($rPost->title, 20) }}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 4px;">
						@else
							<img src="{{ asset('template/assets/img/blog/news-01.png') }}" alt="Default news image" style="width: 70px; height: 70px; object-fit: cover; border-radius: 4px;"> {{-- Placeholder --}}
						@endif
					</a>
					<div>
						<a class="news-title" href="{{ route('blog.show', $rPost->slug) }}">{{ Str::limit($rPost->title, 45) }}</a>
						<p class="news-date"><i class="fa-solid fa-calendar-days"></i> {{ $rPost->published_at ? $rPost->published_at->format('d M, Y') : '' }}</p>
					</div>
				</div>
			@endforeach
		</div>
	@endif
	
	@if(isset($allTags) && $allTags->count() > 0)
		<h4 class="blog-title mb-30">Tags</h4>
		<div class="tags-item d-flex flex-wrap mb-50">
			@foreach($allTags as $tag)
				<a class="tags-btn {{ request('tag') == Str::slug($tag) ? 'active' : '' }}" href="{{ route('blog.index', ['tag' => Str::slug($tag)]) }}">{{ $tag }}</a>
			@endforeach
		</div>
	@endif
	
</div>
