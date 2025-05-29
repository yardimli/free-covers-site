{{-- resources/views/partials/blog/post_item_list.blade.php --}}
<div class="blog-widget box-blog wow fadeInUp mb-40"> {{-- Increased mb for spacing --}}
	@if($post->image_path)
		<a href="{{ route('blog.show', $post->slug) }}">
			<img class="featured-img img-fluid" src="{{ asset('storage/' . $post->image_path) }}" alt="{{ $post->title }}" style="width:100%; height:auto; max-height: 400px; object-fit: cover;">
		</a>
		{{-- Add video icon logic here if your BlogPost model has a video_url field
		@if($post->video_url)
		<div class="video_post">
				<img class="featured-img" src="{{ asset('storage/' . $post->image_path) }}" alt="{{ $post->title }}">
				<a class="popup-youtube video_icon" data-fancybox href="{{ $post->video_url }}"><i class="fa-solid fa-play"></i></a>
		</div>
		@endif
		--}}
	@endif
	<div class="blog-content">
		@if($post->category)
			<p class="blog-text dot-sep">
				<i class="icon_tag_alt"></i>
				<a href="{{ route('blog.index', ['category' => $post->category->slug]) }}">{{ $post->category->name }}</a>
				{{-- <span class="sep">videos</span> --}} {{-- This was static in template, remove or make dynamic --}}
			</p>
		@endif
		<a href="{{ route('blog.show', $post->slug) }}" class="title">
			<h4>{{ $post->title }}</h4>
		</a>
		<div class="blog-sub-text">
			<p class="mb-20">{{ $post->short_description ?: Str::limit(strip_tags($post->content), 200) }}</p>
			{{-- Author details can be added if available --}}
			{{-- <img src="{{ asset('template/assets/img/blog/face-04.png') }}" alt="Author">
			<a href="#">Author Name</a> --}}
			<i class="fa-solid fa-calendar-days"></i>
			<span>{{ $post->published_at ? $post->published_at->format('d F, Y') : 'Date not set' }}</span>
		</div>
	</div>
</div>
