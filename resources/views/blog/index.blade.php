@extends('layouts.app')

@php $footerClass = ''; @endphp
@section('title', $pageTitle . ' - Free Kindle Covers Blog')

@section('content')
	@include('partials.blog.breadcrumb', ['pageTitle' => $pageTitle])
	
	<!-- Blog Area -->
	<section class="blog-area bg-gray sec_padding">
		<div class="container">
			<div class="row">
				<div class="col-xl-8">
					@if($posts->count() > 0)
						@foreach($posts as $post)
							{{-- The template structure has col-md-12 for each item, then special items.
									 This loop will create a standard list of posts.
									 Special items like blockquotes or link-only posts would need different handling/post types.
							--}}
							<div class="col-md-12">
								@include('partials.blog.post_item_list', ['post' => $post])
							</div>
						@endforeach
						
						<!-- Page Link -->
						@if ($posts->hasPages())
							<div class="col-md-12">
								<nav aria-label="Blog navigation" class="mt-4">
									{{-- Use a custom pagination view if default Bootstrap 5 is not suitable --}}
									{{-- To match template exactly, create resources/views/vendor/pagination/custom-blog.blade.php --}}
									{{-- For now, using Laravel's default which is Bootstrap 5 compatible --}}
									{{ $posts->links() }}
								</nav>
							</div>
						@endif
						<!-- /Page Link -->
					@else
						<div class="col-md-12">
							<div class="alert alert-info">No blog posts found matching your criteria.</div>
						</div>
					@endif
				</div>
				
				<!--  Sidebar -->
				<div class="col-xl-4">
					@include('partials.blog.sidebar', compact('categories', 'recentPosts', 'allTags'))
				</div>
				<!-- /sidebar -->
			</div>
		</div>
	</section>
	<!-- Blog Area -->
@endsection
