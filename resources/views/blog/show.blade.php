@extends('layouts.app')

@php $footerClass = ''; @endphp
@section('title', $post->title . ' - Free Kindle Covers Blog')

@push('styles')
	{{-- Add any specific styles for blog detail if needed --}}
	<style>
      .details-content .blog-post-content img {
          max-width: 100%;
          height: auto;
          border-radius: 0.25rem; /* Optional: if images in content should be rounded */
          margin-top: 1rem;
          margin-bottom: 1rem;
      }
      .details-content .blog-post-content ul,
      .details-content .blog-post-content ol {
          padding-left: 2rem; /* Indent lists */
          margin-bottom: 1rem;
      }
      .details-content .blog-post-content li {
          margin-bottom: 0.5rem;
      }
      .details-content .blog-post-content blockquote {
          font-size: 1.1rem;
          border-left: 4px solid #007bff; /* Example color */
          padding-left: 1rem;
          margin: 1.5rem 0;
          font-style: italic;
          color: #555;
      }
      .details-content .blog-post-content blockquote .author {
          display: block;
          text-align: right;
          font-style: normal;
          font-size: 0.9rem;
          color: #777;
          margin-top: 0.5rem;
      }
	</style>
@endpush

@section('content')
	@include('partials.blog.breadcrumb', ['pageTitle' => 'Blog Post', 'post' => $post])
	
	<!-- Blog Area -->
	<section class="blog-details-area bg-gray sec_padding">
		<div class="container">
			<div class="row">
				<div class="col-xl-8 mb-50">
					<article class="details-content round-box wow fadeIn">
						@if($post->image_path)
							<img class="rounded-5 mb-30 img-fluid" src="{{ asset('storage/' . $post->image_path) }}" alt="{{ $post->title }}">
						@endif
						
						<header class="mb-4">
							<h1 class="entry-title mb-3">{{ $post->title }}</h1>
							<div class="entry-meta text-muted">
								@if($post->category)
									<span class="me-3"><i class="icon_folder-alt"></i> <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}">{{ $post->category->name }}</a></span>
								@endif
								@if($post->published_at)
									<span><i class="fa-solid fa-calendar-days"></i> {{ $post->published_at->format('F d, Y') }}</span>
								@endif
								{{-- Add author if you have it: <span class="me-3"><i class="icon_profile"></i> Author Name</span> --}}
							</div>
						</header>
						
						{{-- Full Content --}}
						<div class="blog-post-content">
							{!! $post->content !!} {{-- Use {!! !!} if content contains HTML from a WYSIWYG editor --}}
						</div>
						{{-- The template had a blockquote and list items hardcoded within content.
								 These should ideally be part of the $post->content if they are dynamic.
								 The styles added in @push('styles') will help format these if they are in the content.
						--}}
					</article>
					
					<div class="social-content d-flex flex-lg-row flex-column gap-3 justify-content-between my-4 wow fadeInUP">
						@if($post->keywords && count($post->keywords) > 0)
							<div class="post-tags">
								<span>Tags:</span>
								@foreach($post->keywords as $index => $tag)
									<a href="{{ route('blog.index', ['tag' => Str::slug($tag)]) }}">{{ Str::title($tag) }}</a>@if(!$loop->last), @endif
								@endforeach
							</div>
						@endif
						<div class="social-item d-flex align-items-center">
							<p class="share-text me-2 mb-0">Share:</p>
							<div class="social-list d-flex justify-content-center">
								<a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('blog.show', $post->slug)) }}" target="_blank" title="Share on Facebook"><i class="fa-brands fa-facebook-f"></i></a>
								<a href="https://twitter.com/intent/tweet?url={{ urlencode(route('blog.show', $post->slug)) }}&text={{ urlencode($post->title) }}" target="_blank" title="Share on Twitter"><i class="fa-brands fa-twitter"></i></a>
								<a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(route('blog.show', $post->slug)) }}&title={{ urlencode($post->title) }}" target="_blank" title="Share on LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
								{{-- Add more share links as needed (e.g., Pinterest, WhatsApp) --}}
							</div>
						</div>
					</div>
					
					@if($relatedPosts->count() > 0)
						<div class="post-item round-box mb-30">
							<h4 class="mb-35">Related Posts</h4>
							<div class="row pb-30">
								@foreach($relatedPosts as $relatedPost)
									<div class="col-lg-6 wow fadeInRight" data-wow-delay="{{ $loop->index * 0.1 }}s">
										@include('partials.blog.related_post_item', ['post' => $relatedPost])
									</div>
								@endforeach
							</div>
						</div>
					@endif
					
					{{-- Comments - Static for now --}}
{{--					@include('partials.blog.comments_section')--}}
{{--					@include('partials.blog.comment_form')--}}
				
				</div>
				
				<div class="col-xl-4 col-lg-6 col-md-8 mx-auto mx-lg-0"> {{-- Adjusted col classes for sidebar responsiveness --}}
					@include('partials.blog.sidebar', compact('categories', 'recentPosts', 'allTags'))
				</div>
			</div>
		</div>
	</section>
	<!-- Blog Area -->
@endsection

@push('scripts')
	{{-- Add any specific scripts for blog detail if needed (e.g., for FancyBox if used for images/videos in content) --}}
@endpush
