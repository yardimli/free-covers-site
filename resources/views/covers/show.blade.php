{{-- resources/views/covers/show.blade.php --}}
@extends('layouts.app')

@section('title', $cover->name ? Str::limit($cover->name, 50) . ' - Cover Details' : 'Cover Details - Free Kindle Covers')

@php
	$footerClass = '';
@endphp

@push('styles')
	{{-- Add page-specific styles if needed --}}
	<style>
      .cover-image-container .cover-mockup-image {
          max-height: 550px; /* Adjust as needed for main image */
          object-fit: contain;
          width: auto; /* Ensure aspect ratio is maintained */
      }
      .free_kindle_covers_book_img {
          width: 400px;
          text-align: center; /* Center the image if it's smaller than container */
          padding: 15px;
          border-radius: 8px;
      }
      .free_kindle_covers_book_img .cover-image-container {
          display: inline-block; /* Allows centering */
      }
      .free_kindle_covers_book_details .price {
          font-size: 1.8rem;
          font-weight: bold;
          color: var(--bs-primary); /* Or your theme's primary color */
      }
      .product_details_section_key {
          font-weight: 600;
          min-width: 120px;
          display: inline-block;
      }
      .badge.bg-light {
          border: 1px solid #eee;
      }
      /* Styles for cover variations in description tab */
      .cover-variations-grid .cover-image-container {
          padding: 5px;
      }
      .admin-action-button {
          font-size: 0.8rem; /* Smaller font for admin buttons */
          padding: 0.25rem 0.5rem; /* Smaller padding */
      }
	</style>
@endpush

@section('content')
	{{-- Toast Notification Container (can be part of layout if used globally) --}}
	<div class="toast-container position-fixed p-3 top-0 end-0" style="z-index: 1090;">
		<div id="actionToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
			<div class="toast-header">
				<strong class="me-auto">Notification</strong>
				<small>just now</small>
				<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
			<div class="toast-body">
				Action performed! (This is a demo)
			</div>
		</div>
	</div>
	
	@include('partials.cover_breadcrumb', ['cover' => $cover])
	
	<section class="product_details_area sec_padding" data-bg-color="#f5f5f5">
		<div class="container">
			<div class="row gy-xl-0 gy-3">
				<div class="col-xl-9">
					<div class="bj_book_single me-xl-3">
						<div class="free_kindle_covers_book_img">
							<div class="cover-image-container">
								<img class="img-fluid cover-mockup-image" src="{{ $cover->mockup_url }}" alt="{{ $cover->name ?: 'Cover Image' }}">
								@if($cover->random_template_overlay_url)
									<img src="{{ $cover->random_template_overlay_url }}" alt="Template Overlay" class="template-overlay-image"/>
								@endif
							</div>
						</div>
						<div class="bj_book_details">
							<h2>{{ $cover->name ?: 'Untitled Cover' }}</h2>
							<ul class="list-unstyled book_meta">
								@if($cover->categories && !empty(array_filter($cover->categories)))
									<li>Category:
										@foreach(array_filter($cover->categories) as $category)
											<a href="{{ route('shop.index', ['category' => Str::title($category)]) }}">{{ Str::title($category) }}</a>{{ !$loop->last ? ',' : '' }}
										@endforeach
									</li>
								@endif
								<li>ID: #{{ $cover->id }}</li>
							</ul>
							<div class="price my-3">Free</div>
							@if($cover->caption)
								<p>{{ $cover->caption }}</p>
							@endif
							<ul class="product_meta list-unstyled">
								<li><span>Published:</span>{{ $cover->created_at->format('F j, Y') }}</li>
								@if($cover->coverType)
									<li><span>Type:</span>{{ $cover->coverType->type_name }}</li>
								@endif
							</ul>
							<div class="d-flex mt-4">
								<a href="#" class="bj_theme_btn me-2 p-3" data-name="{{ $cover->name }}" data-price="0" data-img="{{ $cover->mockup_url }}" data-mrp="0"><i class="icon_pencil-edit"></i> Customize This Cover</a>
								{{-- <a href="#" class="bj_theme_btn strock_btn"><i class="icon_download"></i> Download (Placeholder)</a> --}}
							</div>
							{{-- Admin: Remove button for the main displayed template style --}}
							@auth
								@if(Auth::user()->isAdmin() && $randomTemplateForView)
									<div class="mt-2">
										<form method="POST" action="{{ route('admin.covers.templates.remove-assignment', ['cover' => $cover->id, 'template' => $randomTemplateForView->id]) }}">
											@csrf
											<button type="submit" class="btn btn-sm btn-outline-danger admin-action-button">
												<i class="fas fa-trash-alt"></i> Remove This Style ({{ Str::limit($randomTemplateForView->name, 20) }})
											</button>
										</form>
									</div>
								@endif
							@endauth
						</div>
					</div>
					
					<div class="bj_book_single_tab_area me-xl-3 mt-5">
						{{-- Cover Variations Section --}}
						@if(!empty($coverVariations))
							<div class="mt-4 cover-variations-grid">
								<h5 class="content_header mb-3">Available Styles</h5>
								<div class="row">
									@foreach($coverVariations as $variation)
										<div class="col-lg-3 col-md-6 col-sm-6 mb-4">
											<div class="cover-image-container text-center">
												<img class="img-fluid cover-mockup-image" src="{{ $variation['mockup_url'] }}" alt="{{ $cover->name ?: 'Cover' }} - Style with {{ $variation['template_name'] }}">
												@if($variation['template_overlay_url'])
													<img src="{{ $variation['template_overlay_url'] }}" alt="{{ $variation['template_name'] }} Overlay" class="template-overlay-image"/>
												@endif
											</div>
											{{-- Optional: display template name below image --}}
											{{-- <p class="text-center small mt-1 fst-italic">{{ $variation['template_name'] }}</p> --}}
											
											{{-- Admin: Remove button for this specific variation/template style --}}
											@auth
												@if(Auth::user()->isAdmin())
													<div class="text-center mt-2">
														<form method="POST" action="{{ route('admin.covers.templates.remove-assignment', ['cover' => $cover->id, 'template' => $variation['template_id']]) }}">
															@csrf
															<button type="submit" class="btn btn-sm btn-outline-danger admin-action-button">
																<i class="fas fa-trash-alt"></i> Remove Style
															</button>
														</form>
													</div>
												@endif
											@endauth
										</div>
									@endforeach
								</div>
							</div>
						@endif
						
						@if(!$cover->caption && (!$cover->text_placements || empty(array_filter($cover->text_placements))) && empty($coverVariations))
							<p>No specific details available for this cover.</p>
						@endif
					</div>
				</div>
				
				<div class="col-xl-3">
					<div class="product_sidbar p-4" style="background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
						<div class="price_head">Price: <span class="price" style="font-size: 1.5rem; color: var(--bs-primary);">Free</span></div>
						<hr>
						{{-- Keywords Displayed Here --}}
						@if($cover->keywords && !empty(array_filter($cover->keywords)))
							<h6 class="mt-3 mb-2">Keywords</h6>
							<div class="mb-3">
								@foreach(array_filter($cover->keywords) as $keyword)
									<span class="badge bg-light text-dark me-1 mb-1 p-2">{{ Str::title($keyword) }}</span>
								@endforeach
							</div>
							{{-- Removed HR as one is above, and "Available" section follows --}}
						@endif
						<ul class="list-unstyled">
							<li class="mb-2 d-flex align-items-center">
								<img src="{{ asset('template/assets/img/arrow.png') }}" alt="" style="width:16px; margin-right: 8px;">
								Instant Download
							</li>
							<li class="mb-2 d-flex align-items-center">
								<img src="{{ asset('template/assets/img/arrow.png') }}" alt="" style="width:16px; margin-right: 8px;">
								High-Resolution
							</li>
							<li class="mb-2 d-flex align-items-center">
								<img src="{{ asset('template/assets/img/arrow.png') }}" alt="" style="width:16px; margin-right: 8px;">
								Customizable
							</li>
						</ul>
						<h3 class="mt-3">Available</h3>
						<div class="d-flex flex-column gap-3 mt-3">
							<a href="#" class="bj_theme_btn" data-name="{{ $cover->name }}" data-price="0" data-img="{{ $cover->mockup_url }}" data-mrp="0">
								<i class="icon_pencil-edit"></i>Customize This Cover</a>
						</div>
					</div>
					
					<div class="product_details_sidebar mt-4 p-4" style="background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
						<a class="details_header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#product_details_collapse" role="button" aria-expanded="true" aria-controls="product_details_collapse">
							<h6 class="mb-0">More Details</h6>
							<i class="fa-solid fa-chevron-down"></i>
						</a>
						<div class="collapse show mt-3" id="product_details_collapse">
							<div class="product_details_section_wrap">
								<div class="product_details_section_content mb-2">
									<span class="product_details_section_key">Published:</span>
									<span class="product_details_section_value">{{ $cover->created_at->format('M d, Y') }}</span>
								</div>
								<div class="product_details_section_content mb-2">
									<span class="product_details_section_key">Cover ID:</span>
									<span class="product_details_section_value">#{{ $cover->id }}</span>
								</div>
								@if($cover->coverType)
									<div class="product_details_section_content mb-2">
										<span class="product_details_section_key">Type:</span>
										<span class="product_details_section_value">{{ $cover->coverType->type_name }}</span>
									</div>
								@endif
								@if($cover->categories && !empty(array_filter($cover->categories)))
									<div class="product_details_section_content mb-2">
										<span class="product_details_section_key">Categories:</span>
										<div class="product_details_section_value">
											@foreach(array_filter($cover->categories) as $category)
												<a href="{{ route('shop.index', ['category' => Str::title($category)]) }}" class="fw-normal">{{ Str::title($category) }}</a>
											@endforeach
										</div>
									</div>
								@endif
								{{-- Keywords Section Removed from "More Details" --}}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	
	@include('partials.related_covers', ['relatedCovers' => $relatedCovers])

@endsection

@push('scripts')
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// Ensure parallax and other template JS runs
			if (typeof $ !== 'undefined') {
				if ($(".banner_animation_03").length > 0 && typeof $.fn.parallax === 'function') {
					$(".banner_animation_03").css({"opacity": 1}).parallax({scalarX: 7.0, scalarY: 10.0});
				}
				if (typeof WOW === 'function' && $("body").data("scroll-animation") === true) {
					new WOW({}).init();
				}
				var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
				tooltipTriggerList.map(function (tooltipTriggerEl) {
					return new bootstrap.Tooltip(tooltipTriggerEl);
				});
			}
			
			// Toggle chevron icon for collapsible sidebar details
			var productDetailsCollapse = document.getElementById('product_details_collapse');
			if (productDetailsCollapse) {
				var chevronIcon = document.querySelector('a[href="#product_details_collapse"] .fa-solid');
				productDetailsCollapse.addEventListener('show.bs.collapse', function () {
					if (chevronIcon) chevronIcon.classList.replace('fa-chevron-down', 'fa-chevron-up');
				});
				productDetailsCollapse.addEventListener('hide.bs.collapse', function () {
					if (chevronIcon) chevronIcon.classList.replace('fa-chevron-up', 'fa-chevron-down');
				});
			}
			
			// Display session messages (success, error, info) as toasts
			@if(session('success'))
			showToast('Success', '{{ session('success') }}', 'bg-success');
			@endif
			@if(session('error'))
			showToast('Error', '{{ session('error') }}', 'bg-danger');
			@endif
			@if(session('info'))
			showToast('Info', '{{ session('info') }}', 'bg-info');
			@endif
			
			function showToast(title, message, bgClass) {
				var toastEl = document.getElementById('actionToast');
				if (toastEl) {
					var toastHeader = toastEl.querySelector('.toast-header');
					var toastBody = toastEl.querySelector('.toast-body');
					
					toastHeader.querySelector('strong').textContent = title;
					// Remove existing bg classes from header
					toastHeader.className = 'toast-header'; // Reset
					if(bgClass) {
						//toastHeader.classList.add(bgClass); // Optional: color header
						//toastHeader.classList.add('text-white'); // Optional: white text on colored header
					}
					
					toastBody.textContent = message;
					
					var toast = new bootstrap.Toast(toastEl);
					toast.show();
				}
			}
		});
	</script>
@endpush
