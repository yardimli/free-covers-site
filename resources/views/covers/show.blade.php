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
          padding: 15px; /* Padding around the main image and thumbnails block */
          border-radius: 8px;
      }
      .free_kindle_covers_book_img .cover-image-container {
          display: inline-block; /* Allows centering of main image */
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

      /* Styles for additional previews (thumbnails) */
      .cover-additional-previews {
          /* The parent .free_kindle_covers_book_img handles overall centering.
						 This div itself is text-align: center to center its inline/inline-block children. */
      }
      .cover-additional-previews .thumb-img { /* Common class for thumbnail images */
          /* Bootstrap's img-thumbnail provides base styling (padding, border, bg, radius) */
          cursor: pointer;
          object-fit: contain; /* Ensures image fits well within specified dimensions */
          vertical-align: middle; /* Aligns images nicely if they are inline or inline-block */
          height: auto; /* Default, will be overridden by aspect ratio from width */
      }
      .cover-additional-previews .thumb-img:hover {
          border-color: #0d6efd; /* Bootstrap primary color for hover - !important might be needed if BS specificity is higher */
      }

      .cover-additional-previews .full-cover-thumb-img {
          width: 180px;
          /* height: auto; is implicit or inherited */
      }
      .cover-additional-previews .mockup-3d-thumb-img {
          width: 160px;
          /* height: auto; is implicit or inherited */
      }

      /* Styles for the image preview modal */
      #imagePreviewModal .modal-content {
          background: transparent;
          border: none;
          box-shadow: none;
      }
      #imagePreviewModal .modal-body {
          padding: 0;
          position: relative; /* For positioning the close button */
      }
      #imagePreviewModal #modalImage {
          max-height: 90vh; /* Max height to fit viewport */
          max-width: 100%;   /* Max width to fit modal dialog */
          display: block;   /* To remove extra space below image */
          margin: auto;     /* Center image if it's smaller than modal-body */
      }
      #imagePreviewModal .btn-close-modal {
          position: absolute;
          top: 10px;
          right: 10px;
          background-color: rgba(255, 255, 255, 0.8);
          border-radius: 50%;
          padding: 0.5rem;
          z-index: 1056; /* Ensure it's above the image */
          border: none;
      }
      #imagePreviewModal .btn-close-modal:focus {
          box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.5);
      }
	
	</style>
@endpush

@section('content')
	@include('partials.cover_breadcrumb', ['cover' => $cover])
	
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
	
	<section class="product_details_area sec_padding" data-bg-color="#f5f5f5">
		<div class="container">
			<div class="row gy-xl-0 gy-3">
				<div class="col-xl-9">
					<div class="bj_book_single me-xl-3">
						<div class="free_kindle_covers_book_img"> {{-- This container is 400px wide and text-align: center --}}
							<div class="cover-image-container">
								<img class="img-fluid cover-mockup-image"
								     src="{{ asset('storage/' . $cover->mockup_2d_path ) }}"
								     alt="{{ $cover->name ?: 'Cover Image' }}">
								{{-- Use active_template_overlay_url --}}
								@if($cover->active_template_overlay_url)
									<img src="{{ $cover->active_template_overlay_url }}" alt="Template Overlay"
									     class="template-overlay-image"/>
								@endif
							</div>
							
							{{-- Additional Previews (Thumbnails) --}}
							<div class="cover-additional-previews text-center">
								@if($cover->full_cover_thumbnail_path && $cover->full_cover_path)
									<a href="#" data-bs-toggle="modal" data-bs-target="#imagePreviewModal" data-image-src="{{ asset('storage/' . $cover->full_cover_path) }}" title="View Full Cover">
										<img src="{{ asset('storage/' . $cover->full_cover_thumbnail_path) }}" alt="Full Cover Thumbnail" class="img-thumbnail thumb-img full-cover-thumb-img me-2">
									</a>
								@endif
								@if($cover->mockup_3d_path)
									<a href="#" data-bs-toggle="modal" data-bs-target="#imagePreviewModal" data-image-src="{{ asset('storage/' . $cover->mockup_3d_path) }}" title="View 3D Mockup">
										{{-- Using mockup_3d_path directly as thumbnail image. If it's large, a dedicated 3D thumbnail field would be better. --}}
										<img src="{{ asset('storage/' . $cover->mockup_3d_path) }}" alt="3D Mockup Thumbnail" class="img-thumbnail thumb-img mockup-3d-thumb-img">
									</a>
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
								@php
									$customizeUrl = '#'; // Default
									$coverImagePathForDesigner = $cover->cover_path;
									$queryParams = ['cover_id' => $cover->id];
									if ($activeTemplateForView) { // Use activeTemplateForView
											$queryParams['template_id'] = $activeTemplateForView->id;
									}
									if ($coverImagePathForDesigner) {
											$customizeUrl = route('designer.setup') . '?' . http_build_query($queryParams);
									}
									$customizeButtonTitle = !$coverImagePathForDesigner ? 'Customization unavailable: Cover source image missing.' : ($activeTemplateForView ? 'Customize This Cover with ' . Str::limit($activeTemplateForView->name, 20) : 'Customize This Cover');
								@endphp
								<a href="{{ $coverImagePathForDesigner ? $customizeUrl : '#' }}"
								   class="bj_theme_btn me-2 p-3 {{ !$coverImagePathForDesigner ? 'disabled' : '' }}"
								   title="{{ $customizeButtonTitle }}">
									<i class="icon_pencil-edit"></i>
									{{ $activeTemplateForView ? 'Customize Style: ' . Str::limit($activeTemplateForView->name, 20) : 'Customize This Cover' }}
								</a>
							</div>
							@auth
								@if(Auth::user()->isAdmin() && $activeTemplateForView)
									<div class="mt-2" id="main-active-style-remove-form-container-{{ $activeTemplateForView->id }}"> {{-- Added ID --}}
										<form method="POST" action="{{ route('admin.covers.templates.remove-assignment', ['cover' => $cover->id, 'template' => $activeTemplateForView->id]) }}"
										      class="remove-template-assignment-form" {{-- Added class --}}
										      data-cover-id="{{ $cover->id }}" data-template-id="{{ $activeTemplateForView->id }}">
											@csrf
											<button type="submit" class="btn btn-sm btn-outline-danger admin-action-button">
												<i class="fas fa-trash-alt"></i> Remove This Style ({{ Str::limit($activeTemplateForView->name, 20) }})
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
										@php
											$isCurrentActiveStyle = ($activeTemplateForView && $activeTemplateForView->id == $variation['template_id']);
										@endphp
										<div class="col-lg-3 col-md-4 col-sm-6 mb-4" id="variation-card-{{ $variation['template_id'] }}">
											<div class="cover-image-container text-center">
												<img class="img-fluid cover-mockup-image"
												     src="{{ asset('storage/' . $cover->mockup_2d_path) }}"
												     alt="{{ $cover->name ?: 'Cover' }} - Style with {{ $variation['template_name'] }}">
												@if($variation['template_overlay_url'])
													<img src="{{ $variation['template_overlay_url'] }}"
													     alt="{{ $variation['template_name'] }} Overlay"
													     class="template-overlay-image"/>
												@endif
											</div>
											<div class="text-center mt-2">
												@if($isCurrentActiveStyle)
													<button class="btn btn-sm btn-success mb-1 d-block w-100 disabled" title="Currently viewing with this style: {{ $variation['template_name'] }}">
														<i class="fas fa-check-circle"></i> Current Style
													</button>
												@else
													<a href="{{ route('covers.show', ['cover' => $cover->id, 'template' => $variation['template_id']]) }}"
													   class="btn btn-sm btn-outline-primary mb-1 d-block w-100"
													   title="View with style: {{ $variation['template_name'] }}">
														<i class="fas fa-eye"></i> View with this Style
													</a>
												@endif
												@auth
													@if(Auth::user()->isAdmin())
														<form method="POST" action="{{ route('admin.covers.templates.remove-assignment', ['cover' => $cover->id, 'template' => $variation['template_id']]) }}"
														      class="d-block mt-1 remove-template-assignment-form" {{-- Added class --}}
														      data-cover-id="{{ $cover->id }}" data-template-id="{{ $variation['template_id'] }}">
															@csrf
															<button type="submit" class="btn btn-sm btn-outline-danger admin-action-button w-100">
																<i class="fas fa-trash-alt"></i> Remove Style
															</button>
														</form>
													@endif
												@endauth
											</div>
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
							{{-- Sidebar Customize Button - also uses $activeTemplateForView logic --}}
							<a href="{{ $coverImagePathForDesigner ? $customizeUrl : '#' }}"
							   class="bj_theme_btn {{ !$coverImagePathForDesigner ? 'disabled' : '' }}"
							   title="{{ $customizeButtonTitle }}"
							   data-name="{{ $cover->name }}"
							   data-price="0"
							   data-img="{{ asset('storage/' . $cover->mockup_2d_path ) }}"
							   data-mrp="0">
								<i class="icon_pencil-edit"></i>
								{{ $activeTemplateForView ? 'Customize Style: ' . Str::limit($activeTemplateForView->name, 15) : 'Customize This Cover' }}
							</a>
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
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	
	<!-- Image Preview Modal -->
	<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="btn-close btn-close-modal" data-bs-dismiss="modal" aria-label="Close"></button>
					<img id="modalImage" src="" alt="Cover Preview" class="img-fluid">
				</div>
			</div>
		</div>
	</div>
	
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
					// if(bgClass) { // Optional: color header
					//toastHeader.classList.add(bgClass);
					//toastHeader.classList.add('text-white'); // Optional: white text on colored header
					// }
					toastBody.textContent = message;
					var toast = new bootstrap.Toast(toastEl);
					toast.show();
				}
			}
			
			document.querySelectorAll('.remove-template-assignment-form').forEach(form => {
				form.addEventListener('submit', function (event) {
					event.preventDefault(); // Prevent default form submission
					const actionUrl = this.getAttribute('action');
					const templateId = this.dataset.templateId;
					const csrfToken = this.querySelector('input[name="_token"]').value;
					
					// if (!confirm('Are you sure you want to remove this style from the cover?')) {
					// return;
					// }
					
					fetch(actionUrl, {
						method: 'POST',
						headers: {
							'X-CSRF-TOKEN': csrfToken,
							'Accept': 'application/json',
							// 'Content-Type': 'application/json' // Not strictly needed if body is empty
						},
						// body: JSON.stringify({}) // No body needed as IDs are in URL
					})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								showToast('Success', data.message, 'bg-success');
								const activeTemplateIdOnPage = {{ $activeTemplateForView ? $activeTemplateForView->id : 'null' }};
								
								// If the removed template was the main active one, redirect to the cover page without template params
								// This will allow the backend to pick a new random one or show none.
								if (activeTemplateIdOnPage && parseInt(templateId) === activeTemplateIdOnPage) {
									// Remove the main form container itself
									const mainFormContainer = document.getElementById(`main-active-style-remove-form-container-${templateId}`);
									if (mainFormContainer) mainFormContainer.remove();
									// Redirect to the base cover URL to refresh the main view
									window.location.href = "{{ route('covers.show', ['cover' => $cover->id]) }}";
									return; // Stop further JS execution as page will reload/redirect
								}
								
								// Remove the specific variation card from the DOM
								const variationCard = document.getElementById(`variation-card-${templateId}`);
								if (variationCard) {
									variationCard.remove();
								}
								
								// Check if the variations grid is now empty
								const variationsGridRow = document.querySelector('.cover-variations-grid .row');
								if (variationsGridRow && variationsGridRow.children.length === 0) {
									const variationsContainer = document.querySelector('.cover-variations-grid');
									if (variationsContainer) {
										// Replace content with a message
										variationsContainer.innerHTML = '<h5 class="content_header mb-3">Available Styles</h5><p>No styles currently assigned to this cover.</p>';
									}
								}
							} else {
								showToast('Error', data.message || 'Could not remove style.', 'bg-danger');
							}
						})
						.catch(error => {
							console.error('Error:', error);
							showToast('Error', 'An unexpected error occurred while removing the style.', 'bg-danger');
						});
				});
			});
			
			// Image Preview Modal Logic
			var imagePreviewModalEl = document.getElementById('imagePreviewModal');
			if (imagePreviewModalEl) {
				var modalImageEl = document.getElementById('modalImage');
				
				imagePreviewModalEl.addEventListener('show.bs.modal', function (event) {
					var button = event.relatedTarget; // Anchor tag that triggered the modal
					var imageSrc = button.getAttribute('data-image-src');
					if (modalImageEl && imageSrc) {
						modalImageEl.setAttribute('src', imageSrc);
					}
				});
				
				// Optional: Clear image src when modal is hidden to prevent brief display of old image
				// and to free up memory if the image is large.
				imagePreviewModalEl.addEventListener('hidden.bs.modal', function () {
					if (modalImageEl) {
						modalImageEl.setAttribute('src', '');
					}
				});
			}
		});
	</script>
@endpush
