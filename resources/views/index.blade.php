@extends('layouts.app')

@php $footerClass = ''; // No longer need top padding on footer @endphp

@section('title', 'Free Kindle Covers - Your Next Premium Book For Free')

@push('styles')
	<style>
      .tag-cloud-container .badge {
          font-size: 0.8rem;
          padding: 0.4em 0.8em;
          margin: 0.2rem;
          transition: all 0.2s ease-in-out;
          border: 1px solid #dee2e6;
          font-weight: 500;
      }
      .tag-cloud-container .badge:hover {
          background-color: var(--bs-primary) !important;
          color: white !important;
          border-color: var(--bs-primary) !important;
          transform: translateY(-2px);
          text-decoration: none;
      }
      .tag-cloud-container a.badge {
          text-decoration: none;
      }
      .home-search-section {
          padding-top: 30px;
          padding-bottom: 60px;
      }
      /* Styles for the cover grid items, to ensure consistency */
      .projects_item {
          margin-bottom: 30px;
      }
      .best_product_item {
          position: relative;
          border-radius: 10px;
          overflow: hidden;
          background: #fff;
          box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
          transition: all 0.3s ease-in-out;
      }
      .best_product_item:hover {
          transform: translateY(-5px);
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      }
      .best_product_item .img {
          display: block;
          position: relative;
      }
      .best_product_item .img img {
          width: 100%;
      }
      .bj_new_pr_content {
          padding: 20px;
          min-height: 100px;
      }
      .bj_new_pr_title {
          font-size: 1rem;
          margin-bottom: 5px;
      }
      .writer_name {
          font-size: 0.85rem;
          color: #6c757d;
          height: 2.5em; /* to align boxes */
          overflow: hidden;
      }
	</style>
@endpush

@section('content')
	@include('partials.banner')
	
	<!-- Search and Covers Section -->
	<section class="home-search-section" data-bg-color="#f5f5f5">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-lg-10">
					<!-- Search Form -->
					<form role="search" method="get" class="pr_search_form pr_search_form_two input-group mb-4" action="{{ route('shop.index') }}">
						<input type="text" name="s" class="form-control search-field" placeholder="Search for covers by keyword, title, or genre...">
						<button type="submit"><i class="ti-search"></i></button>
					</form>
					
					<!-- Tag Cloud -->
					@if(isset($availableCategories) && !empty($availableCategories))
						<div class="tag-cloud-container text-center mb-2 wow fadeInUp">
							@foreach($availableCategories as $categoryName => $count)
								<a href="{{ route('shop.index', ['category' => $categoryName]) }}" class="badge bg-light text-dark">
									{{ $categoryName }}
								</a>
							@endforeach
						</div>
					@endif
				</div>
			</div>
			
			<!-- Covers Grid -->
			@if($covers->isNotEmpty())
				<div class="row mt-2">
					@foreach($covers as $cover)
						<div class="col-lg-3 col-md-4 col-sm-6 projects_item">
							<div class="best_product_item best_product_item_two shop_product">
								<a href="{{ route('covers.show', ['cover' => $cover->id, 'template' => $cover->random_template_overlay_id]) }}" class="cover-image-container">
									<div class="img">
										<img src="{{ asset('storage/' . $cover->mockup_2d_path ) }}" alt="{{ $cover->name }}" class="cover-mockup-image img-fluid">
										@if($cover->random_template_overlay_url)
											<img src="{{ $cover->random_template_overlay_url }}" alt="Template Overlay" class="{{ $cover->has_real_2d ? 'template-overlay-image' : 'template-overlay-image-non-2d' }}" />
										@endif
									</div>
								</a>
								<div class="bj_new_pr_content">
									<a href="{{ route('covers.show', ['cover' => $cover->id, 'template' => $cover->random_template_overlay_id]) }}">
										<h5 class="bj_new_pr_title" style="margin-bottom:0px;">#{{ $cover->id }} {{ $cover->name ? Str::limit($cover->name, 25) : 'Untitled' }}</h5>
									</a>
									<div class="writer_name">{{ $cover->caption ? Str::limit($cover->caption, 40) : 'No caption' }}</div>
								</div>
							</div>
						</div>
					@endforeach
				</div>
				<div class="text-center mt-5">
					<a href="{{ route('shop.index') }}" class="bj_theme_btn">View All Covers</a>
				</div>
			@else
				<div class="text-center p-5">
					<p>No covers found. Please check back later!</p>
				</div>
			@endif
		
		</div>
	</section>
@endsection

@push('scripts')
	{{-- No specific JS needed for this new static page layout --}}
@endpush
