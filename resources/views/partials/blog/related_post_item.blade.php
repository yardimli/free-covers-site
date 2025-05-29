{{-- resources/views/partials/blog/related_post_item.blade.php --}}
<div class="blog-widget">
	<a href="{{ route('blog.show', $post->slug) }}">
		@if($post->thumbnail_path)
			<img src="{{ asset('storage/' . $post->thumbnail_path) }}" alt="{{ $post->title }}" class="img-fluid rounded mb-3" style="width:100%; aspect-ratio: 16/10; object-fit: cover;">
		@elseif($post->image_path)
			<img src="{{ asset('storage/' . $post->image_path) }}" alt="{{ $post->title }}" class="img-fluid rounded mb-3" style="width:100%; aspect-ratio: 16/10; object-fit: cover;">
		@else
			<img src="{{ asset('template/assets/img/blog/blog-01.png') }}" alt="{{ $post->title }}" class="img-fluid rounded mb-3" style="width:100%; aspect-ratio: 16/10; object-fit: cover;"> {{-- Placeholder --}}
		@endif
	</a>
	<div class="blog-content">
		@if($post->category)
			<p class="blog-text dot-sep">
				<i class="icon_tag_alt"></i>
				<a href="{{ route('blog.index', ['category' => $post->category->slug]) }}">{{ $post->category->name }}</a>
			</p>
		@endif
		<a href="{{ route('blog.show', $post->slug) }}">
			<h5>{{ Str::limit($post->title, 50) }}</h5>
		</a>
		<div class="blog-sub-text">
			<i class="fa-solid fa-calendar-days"></i>
			<span>{{ $post->published_at ? $post->published_at->format('d M, Y') : '' }}</span>
		</div>
	</div>
</div>
