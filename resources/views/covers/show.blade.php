{{-- resources/views/covers/show.blade.php --}}
@extends('layouts.app')
@section('title', $cover->name ? Str::limit($cover->name, 50) . ' - Cover Details' : 'Cover Details - Free Kindle Covers')
@php
	$footerClass = '';
	// Logic for customize URLs
	$coverImagePathForDesigner = $cover->cover_path;
	$canCustomize = (bool)$coverImagePathForDesigner;
	$baseDesignerUrl = route('designer.index');
	$setupCanvasBaseUrl = route('designer.setup');

	// Kindle URL
	$kindleParams = ['w' => 1600, 'h' => 2560];
	if ($coverImagePathForDesigner) {
			$kindleParams['image_path'] = $coverImagePathForDesigner;
	}
	if ($activeTemplateForView) {
			$kindleParams['template_url'] = route('api.templates.json_data', ['template' => $activeTemplateForView->id]);
	}
	$customizeKindleUrl = $canCustomize ? $baseDesignerUrl . '?' . http_build_query($kindleParams) : '#';

	// Print URL
	$printParams = ['cover_id' => $cover->id];
	if ($activeTemplateForView) {
			$printParams['template_id'] = $activeTemplateForView->id;
	}
	$customizePrintUrl = $canCustomize ? $setupCanvasBaseUrl . '?' . http_build_query($printParams) : '#';
	$genericCustomizeButtonTitle = !$canCustomize ? 'Customization unavailable: Cover source image missing.' : '';
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

      .admin-action-button {
          font-size: 0.8rem; /* Smaller font for admin buttons */
          padding: 0.25rem 0.5rem; /* Smaller padding */
      }

      /* Styles for additional previews (thumbnails) */
      .cover-additional-previews {
          /* The parent .free_kindle_covers_book_img handles overall centering. This div itself is text-align: center to center its inline/inline-block children. */
      }

      .cover-additional-previews .thumb-img {
          /* Common class for thumbnail images */
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
          width: 180px; /* height: auto; is implicit or inherited */
      }

      .cover-additional-previews .mockup-3d-thumb-img {
          width: 160px; /* height: auto; is implicit or inherited */
      }

      /* Ensure cover-image-container used for thumbnails behaves correctly */
      .cover-additional-previews .cover-image-container {
          vertical-align: middle; /* Align with other potential inline-block elements */
      }

      .cover-additional-previews .cover-image-container .template-overlay-image {
      }

      /* Styles for the image preview modal */
      #imagePreviewModal .modal-content {
          background: transparent;
          border: none;
          box-shadow: none;
      }

      #imagePreviewModal .modal-body {
          padding: 0;
          position: relative; /* For positioning the close button AND overlay */
      }

      #imagePreviewModal #modalImage {
          max-height: 90vh; /* Max height to fit viewport */
          max-width: 100%; /* Max width to fit modal dialog */
          display: block; /* To remove extra space below image */
          margin: auto; /* Center image if it's smaller than modal-body */
          position: relative; /* Stacking context for potential direct children overlays */
          z-index: 0;
      }

      #imagePreviewModal .btn-close-modal {
          position: absolute;
          top: 10px;
          right: 10px;
          background-color: rgba(255, 255, 255, 0.8);
          border-radius: 50%;
          padding: 0.5rem;
          z-index: 1056; /* Ensure it's above the image and overlay */
          border: none;
      }

      #imagePreviewModal .btn-close-modal:focus {
          box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.5);
      }

      /* Ensure FontAwesome icons are spaced nicely in buttons */
      .btn i.fab, .btn i.fas {
          margin-right: 0.35em;
      }

      .keyword-badge-link {
          text-decoration: none;
          font-weight: 400;
      }

      .keyword-badge-link:hover {
          border-color: #0d6efd !important; /* Ensure hover stands out */
          color: #0d6efd !important;
      }

      .bj_theme_btn.favorited_btn {
          background-color: #e74c3c; /* A distinct color for favorited state */
          color: white;
          border-color: #e74c3c;
      }

      .bj_theme_btn.favorited_btn:hover {
          background-color: #c0392b;
          border-color: #c0392b;
      }

      .bj_theme_btn.favorited_btn i, .bj_theme_btn.strock_btn i.fa-heart {
          margin-right: 0.35em;
      }

      /* New Styles for Available Styles Section */
      .available-styles-section .template-list-container {
          max-height: 600px; /* Adjust as needed */
          overflow-y: auto;
		      overflow-x: hidden; /* Hide horizontal scrollbar */
          border: 1px solid #eee;
          padding: 3px; /* Add padding for the grid */
      }

      .available-styles-section .template-thumbnail-item {
          border: 2px solid transparent;
          border-radius: 6px;
          padding: 2px;
          cursor: pointer;
          transition: border-color 0.2s ease-in-out;
          text-align: center;
          margin-bottom: 0.5rem;
      }

      .available-styles-section .template-thumbnail-item:hover {
          border-color: #ccc;
      }

      .available-styles-section .template-thumbnail-item.active {
          border-color: var(--bs-primary);
      }

      .available-styles-section .template-thumbnail-image-wrapper {
          background-color: #f8f9fa; /* Light gray background */
          border-radius: 4px;
          aspect-ratio: 1600 / 2560; /* Kindle aspect ratio */
          display: flex;
          align-items: center;
          justify-content: center;
          margin-bottom: 5px;
          overflow: hidden;
      }

      .available-styles-section .template-thumbnail-image-wrapper img {
          max-width: 100%;
          max-height: 100%;
          object-fit: contain;
      }

      .available-styles-section .template-thumbnail-name {
          font-size: 0.8rem;
          color: #6c757d;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          flex-grow: 1;
          text-align: left;
          margin-right: 5px;
      }

      .template-thumbnail-item .admin-actions-inline {
          flex-shrink: 0;
      }

      .available-styles-section .preview-area .cover-mockup-image {
          max-height: 350px; /* Adjust preview size */
          object-fit: contain;
      }

      .available-styles-section .preview-area .kindle-template-overlay-image {
          position: absolute;
          top: 3% !important; /* Adjust overlay position */
          left: 3% !important;
          width: 90% !important; /* Ensure it fits well within the thumbnail container */
          height: 90% !important; /* Maintain aspect ratio and fit */
          object-fit: contain; /* Ensure the overlay image fits well */
      }

      .available-styles-section .preview-area .kindle-template-overlay-image-non-2d {
          position: absolute;
          top: 0% !important; /* Adjust overlay position */
          left: 0% !important;
          width: 100% !important; /* Ensure it fits well within the thumbnail container */
          height: 100% !important; /* Maintain aspect ratio and fit */
          object-fit: contain; /* Ensure the overlay image fits well */
      }

      .available-styles-section .preview-area .template-overlay-image {
          top: 1% !important; /* Adjust overlay position */
          left: 1% !important;
          width: 98% !important; /* Ensure it fits well within the thumbnail container */
          height: 98% !important; /* Maintain aspect ratio and fit */
          object-fit: contain; /* Ensure the overlay image fits well */
      }

      .available-styles-section .preview-area h6 {
          font-size: 0.9rem;
          color: #6c757d;
          margin-bottom: 0.25rem;
          text-align: center;
      }
	</style>
@endpush
@section('content')
	@include('partials.cover_breadcrumb', ['cover' => $cover])
	
	<section class="product_details_area sec_padding" data-bg-color="#f5f5f5">
		<div class="container">
			<div class="row gy-xl-0 gy-3">
				<div class="col-xl-9">
					<div class="bj_book_single me-xl-3">
						<div class="free_kindle_covers_book_img">
							{{-- This container is 400px wide and text-align: center --}}
							<div class="cover-image-container">
								<img class="img-fluid cover-mockup-image"
								     src="{{ asset('storage/' . $cover->mockup_2d_path ) }}"
								     alt="{{ $cover->name ?: 'Cover Image' }}">
								{{-- Use active_template_overlay_url --}}
								@if($cover->active_template_overlay_url)
									<img src="{{ $cover->active_template_overlay_url }}" alt="Template Overlay"
									     class="{{ $cover->has_real_2d ? 'template-overlay-image' : 'template-overlay-image-non-2d' }}"/>
								@endif
							</div>
							{{-- Additional Previews (Thumbnails) --}}
							<div class="cover-additional-previews text-center mt-2">
								@if($cover->full_cover_thumbnail_path && $cover->full_cover_path)
									<a href="#" class="cover-image-container me-2" style="display: inline-block;"
									   data-bs-toggle="modal" data-bs-target="#imagePreviewModal"
									   data-image-src="{{ asset('storage/' . $cover->full_cover_path) }}"
									   @if(isset($activeTemplateFullCoverOverlayUrl)) data-overlay-src="{{ $activeTemplateFullCoverOverlayUrl }}"
									   @endif
									   title="View Full Cover">
										<img src="{{ asset('storage/' . $cover->full_cover_thumbnail_path) }}"
										     alt="Full Cover Thumbnail" class="img-thumbnail thumb-img full-cover-thumb-img">
										@if(isset($activeTemplateFullCoverOverlayUrl))
											<img src="{{ $activeTemplateFullCoverOverlayUrl }}"
											     alt="Template Full Cover Overlay" class="template-overlay-image"
											     style="top:10; left:10; width:93% !important; height:93% !important; object-fit: contain;">
										@endif
									</a>
								@endif
								@if($cover->mockup_3d_path)
									<a href="#" data-bs-toggle="modal" data-bs-target="#imagePreviewModal"
									   data-image-src="{{ asset('storage/' . $cover->mockup_3d_path) }}"
									   title="View 3D Mockup">
										<img src="{{ asset('storage/' . $cover->mockup_3d_path) }}"
										     alt="3D Mockup Thumbnail" class="img-thumbnail thumb-img mockup-3d-thumb-img">
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
											<a
												href="{{ route('shop.index', ['category' => Str::title($category)]) }}">{{ Str::title($category) }}</a>{{ !$loop->last ? ',' : '' }}
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
									<li><span>Type:</span>{{ $cover->coverType->type_name ?? '' }}</li>
								@endif
								<li><span>Template:</span>{{ $activeTemplateForView->name ?? 'Default' }}</li>
							</ul>
							<div class="d-flex flex-wrap mt-4">
								<a href="{{ $customizeKindleUrl }}"
								   class="bj_theme_btn {{ !$canCustomize ? 'disabled' : '' }}"
								   title="{{ $genericCustomizeButtonTitle ?: 'Customize Kindle Cover'}}" target="_blank">
									<i class="fab fa-amazon"></i> Customize Kindle
								</a>
								<a href="{{ $customizePrintUrl }}"
								   class="bj_theme_btn strock_btn {{ !$canCustomize ? 'disabled' : '' }}"
								   title="{{ $genericCustomizeButtonTitle ?: 'Customize Print Cover'}}" target="_blank">
									<i class="fas fa-print"></i> Customize Print
								</a>
								@auth
									<button id="favoriteButton"
									        class="bj_theme_btn mt-2 {{ $isFavorited ? 'favorited_btn' : 'strock_btn' }}"
									        data-cover-id="{{ $cover->id }}"
									        data-template-id="{{ $activeTemplateForView ? $activeTemplateForView->id : '' }}"
									        data-is-favorited="{{ $isFavorited ? 'true' : 'false' }}"
									        title="{{ $isFavorited ? 'Remove from Favorites' : 'Add to Favorites' }}">
										<i class="fas {{ $isFavorited ? 'fa-heart-broken' : 'fa-heart' }}"></i>
										<span class="button-text">{{ $isFavorited ? 'Favorited' : 'Favorite' }}</span>
									</button>
								@else
									<a href="{{ route('login') }}" class="bj_theme_btn strock_btn mt-2"
									   title="Login to add to favorites">
										<i class="fas fa-heart"></i> Favorite
									</a>
								@endauth
							</div>
							@auth
								@if(Auth::user()->isAdmin() && $activeTemplateForView && $cover->templates->contains($activeTemplateForView->id))
									<div class="mt-2"
									     id="main-active-style-remove-form-container-{{ $activeTemplateForView->id }}">
										<form method="POST"
										      action="{{ route('admin.covers.templates.remove-assignment', ['cover' => $cover->id, 'template' => $activeTemplateForView->id]) }}"
										      class="remove-template-assignment-form"
										      data-cover-id="{{ $cover->id }}"
										      data-template-id="{{ $activeTemplateForView->id }}">
											@csrf
											<button type="submit" class="btn btn-sm btn-outline-danger admin-action-button">
												<i class="fas fa-trash-alt"></i> Remove This Assigned Style
												({{ Str::limit($activeTemplateForView->name ?? '', 20) }})
											</button>
										</form>
									</div>
								@endif
							@endauth
						</div>
					</div>
					<div class="bj_book_single_tab_area me-xl-3 mt-5">
						{{-- New Available Styles Section --}}
						@if($allCompatibleTemplates->isNotEmpty())
							<div class="available-styles-section mt-4">
								<div class="row">
									{{-- Left Column: Template Thumbnails --}}
									<div class="col-md-5">
										<h5 class="content_header mb-3">Available Styles</h5>
										<div class="template-list-container">
											<div class="row g-2">
												@foreach($allCompatibleTemplates as $templateStyle)
													@php $isActuallyAssigned = $cover->templates->contains($templateStyle->id); @endphp
													<div class="col-6">
														<div
															class="template-thumbnail-item {{ ($activeTemplateForView && $activeTemplateForView->id == $templateStyle->id) ? 'active' : '' }}"
															data-template-id="{{ $templateStyle->id }}"
															data-template-name="{{ $templateStyle->name }}"
															data-front-overlay-url="{{ $templateStyle->cover_image_path ? asset('storage/' . $templateStyle->cover_image_path) : '' }}"
															data-full-overlay-url="{{ $templateStyle->full_cover_image_path ? asset('storage/' . $templateStyle->full_cover_image_path) : '' }}"
															title="{{ $templateStyle->name }}">
															
															<div class="template-thumbnail-image-wrapper">
																@if($templateStyle->cover_image_path)
																	<img src="{{ asset('storage/' . $templateStyle->cover_image_path) }}"
																	     alt="{{ $templateStyle->name }}">
																@else
																	<i class="fas fa-image fa-2x text-muted"></i>
																@endif
															</div>
															<div class="d-flex justify-content-between align-items-center">
{{--																<div class="template-thumbnail-name"--}}
{{--																     title="{{ $templateStyle->name }}">{{ $templateStyle->name }}</div>--}}
																@auth
																	@if(Auth::user()->isAdmin() && $isActuallyAssigned)
																		<div class="admin-actions-inline">
																			<form method="POST"
																			      action="{{ route('admin.covers.templates.remove-assignment', ['cover' => $cover->id, 'template' => $templateStyle->id]) }}"
																			      class="remove-template-assignment-form d-inline"
																			      data-cover-id="{{ $cover->id }}"
																			      data-template-id="{{ $templateStyle->id }}">
																				@csrf
																				<button type="submit"
																				        class="btn btn-sm btn-outline-danger admin-action-button py-0 px-1"
																				        title="Remove this assigned style">
																					<i class="fas fa-trash-alt"></i>
																				</button>
																			</form>
																		</div>
																	@endif
																@endauth
															</div>
														</div>
													</div>
												@endforeach
											</div>
										</div>
									</div>
									{{-- Right Column: Preview Area --}}
									<div class="col-md-7">
										<div class="preview-area text-center">
											<div>
												<h6>Kindle Preview</h6>
												<div class="cover-image-container">
													<img id="stylePreviewKindleBase"
													     src="{{ asset('storage/' . $cover->mockup_2d_path) }}"
													     alt="Kindle Preview Base" class="cover-mockup-image">
													<img id="stylePreviewKindleOverlay"
													     src="{{ $activeTemplateForView && $activeTemplateForView->cover_image_path ? asset('storage/' . $activeTemplateForView->cover_image_path) : '' }}"
													     alt="Kindle Template Overlay"
													     class="{{ $cover->has_real_2d ? 'kindle-template-overlay-image' : 'kindle-template-overlay-image-non-2d' }}"
													     style="{{ !($activeTemplateForView && $activeTemplateForView->cover_image_path) ? 'display:none;' : '' }}">
												</div>
											</div>
											@if($cover->full_cover_path)
												<div class="mt-3">
													<h6>Full Cover Preview</h6>
													<div class="cover-image-container">
														<img id="stylePreviewFullCoverBase"
														     src="{{ asset('storage/' . $cover->full_cover_path) }}"
														     alt="Full Cover Preview Base" class="cover-mockup-image">
														<img id="stylePreviewFullCoverOverlay"
														     src="{{ $activeTemplateForView && $activeTemplateForView->full_cover_image_path ? asset('storage/' . $activeTemplateForView->full_cover_image_path) : '' }}"
														     alt="Full Cover Template Overlay"
														     class="template-overlay-image"
														     style="{{ !($activeTemplateForView && $activeTemplateForView->full_cover_image_path) ? 'display:none;' : '' }}">
													</div>
												</div>
											@endif
											<button id="useThisStyleButton"
											        class="btn btn-primary mt-3" {{ !$activeTemplateForView ? 'disabled' : '' }}>
												<i class="fas fa-check-circle"></i> Use This Style
											</button>
										</div>
									</div>
								</div>
							</div>
						@else
							<div class="available-styles-section mt-4">
								<h5 class="content_header mb-3">Available Styles</h5>
								<p>No styles (templates) found for this cover type ({{ $cover->coverType->type_name ?? 'Unknown Type' }}
									).</p>
								@if ($cover->templates->isNotEmpty() && Auth::user() && Auth::user()->isAdmin())
									<p class="mt-2 text-muted small">However, this cover has the following templates
										assigned (possibly of a different type):</p>
									<ul class="list-unstyled">
										@foreach($cover->templates as $assignedTemplate)
											<li class="d-flex justify-content-between align-items-center mb-1">
												<span>{{ $assignedTemplate->name }} (ID: {{ $assignedTemplate->id }}, Type ID: {{ $assignedTemplate->cover_type_id }})</span>
												<form method="POST"
												      action="{{ route('admin.covers.templates.remove-assignment', ['cover' => $cover->id, 'template' => $assignedTemplate->id]) }}"
												      class="remove-template-assignment-form d-inline"
												      data-cover-id="{{ $cover->id }}"
												      data-template-id="{{ $assignedTemplate->id }}">
													@csrf
													<button type="submit"
													        class="btn btn-xs btn-outline-danger admin-action-button py-0 px-1"
													        title="Remove this assigned style">
														<i class="fas fa-trash-alt"></i>
													</button>
												</form>
											</li>
										@endforeach
									</ul>
								@endif
							</div>
						@endif
						@if(!$cover->caption && (!$cover->text_placements || empty(array_filter($cover->text_placements))) && $allCompatibleTemplates->isEmpty() && $cover->templates->isEmpty())
							<p>No specific details available for this cover.</p>
						@endif
					</div>
				</div>
				<div class="col-xl-3">
					<div class="product_sidbar p-4"
					     style="background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
						<div class="price_head">Price: <span class="price"
						                                     style="font-size: 1.5rem; color: var(--bs-primary);">Free</span>
						</div>
						<hr>
						{{-- Keywords Displayed Here --}}
						@if(!empty($keywordData))
							<h6 class="mt-3 mb-2">Keywords</h6>
							<div class="mb-3">
								@foreach($keywordData as $kwData)
									@if ($kwData['count']>1)
										<a href="{{ route('shop.index', ['keyword' => $kwData['displayName']]) }}"
										   class="badge bg-light text-dark me-1 mb-1 p-2 keyword-badge-link"
										   title="Find covers with keyword: {{ $kwData['displayName'] }}">
											{{ $kwData['displayName'] }} ({{ $kwData['count'] }})
										</a>
									@else
										<span class="badge bg-light text-dark me-1 mb-1 p-2 keyword-badge-link"
										      title="Keyword: {{ $kwData['displayName'] }}">
                                            {{ $kwData['displayName'] }}
                                        </span>
									@endif
								@endforeach
							</div>
						@endif
						<ul class="list-unstyled">
							<li class="mb-2 d-flex align-items-center">
								<img src="{{ asset('template/assets/img/arrow.png') }}" alt=""
								     style="width:16px; margin-right: 8px;"> Instant Download
							</li>
							<li class="mb-2 d-flex align-items-center">
								<img src="{{ asset('template/assets/img/arrow.png') }}" alt=""
								     style="width:16px; margin-right: 8px;"> High-Resolution
							</li>
							<li class="mb-2 d-flex align-items-center">
								<img src="{{ asset('template/assets/img/arrow.png') }}" alt=""
								     style="width:16px; margin-right: 8px;"> Customizable
							</li>
						</ul>
						<h3 class="mt-3">Available</h3>
						<div class="d-flex flex-column gap-2 mt-3">
							@php
								$sidebarKindleButtonText = $activeTemplateForView ? Str::limit($activeTemplateForView->name ?? '', 10) : 'Kindle';
								$sidebarPrintButtonText = $activeTemplateForView ? Str::limit($activeTemplateForView->name ?? '', 10) : 'Print';
							@endphp
							<a href="{{ $customizeKindleUrl }}"
							   class="bj_theme_btn {{ !$canCustomize ? 'disabled' : '' }}"
							   title="{{ $genericCustomizeButtonTitle ?: 'Customize Kindle: ' . $sidebarKindleButtonText }}"
							   target="_blank">
								<i class="fab fa-amazon"></i> Kindle {{ $sidebarKindleButtonText }}
							</a>
							<a href="{{ $customizePrintUrl }}"
							   class="bj_theme_btn strock_btn {{ !$canCustomize ? 'disabled' : '' }}"
							   title="{{ $genericCustomizeButtonTitle ?: 'Customize Print: ' . $sidebarPrintButtonText }}"
							   target="_blank">
								<i class="fas fa-print"></i> Print {{ $sidebarPrintButtonText }}
							</a>
						</div>
					</div>
					<div class="product_details_sidebar mt-4 p-4"
					     style="background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
						<a class="details_header d-flex justify-content-between align-items-center"
						   data-bs-toggle="collapse" href="#product_details_collapse" role="button" aria-expanded="true"
						   aria-controls="product_details_collapse">
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
												<a href="{{ route('shop.index', ['category' => Str::title($category)]) }}"
												   class="fw-normal">{{ Str::title($category) }}</a>
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
					<button type="button" class="btn-close btn-close-modal" data-bs-dismiss="modal"
					        aria-label="Close"></button>
					<img id="modalImage" src="" alt="Cover Preview" class="img-fluid">
					{{-- Overlay will be added here by JavaScript if applicable --}}
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
			
			// Admin: Remove Template Assignment Form
			document.querySelectorAll('.remove-template-assignment-form').forEach(form => {
				form.addEventListener('submit', function (event) {
					event.preventDefault();
					const actionUrl = this.getAttribute('action');
					const templateIdToRemove = parseInt(this.dataset.templateId);
					const csrfToken = this.querySelector('input[name="_token"]').value;
					const currentForm = this;
					
					fetch(actionUrl, {
						method: 'POST',
						headers: {
							'X-CSRF-TOKEN': csrfToken,
							'Accept': 'application/json',
						},
					})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								showToast('Success', data.message, 'bg-success');
								const activeTemplateIdOnPage = {{ $activeTemplateForView ? $activeTemplateForView->id : 'null' }};
								
								// If the removed template was the main active one for the page, redirect
								if (activeTemplateIdOnPage && templateIdToRemove === activeTemplateIdOnPage) {
									// Also remove its "remove" button from the main details if it exists
									const mainFormContainer = document.getElementById(`main-active-style-remove-form-container-${templateIdToRemove}`);
									if (mainFormContainer) mainFormContainer.remove();
									window.location.href = "{{ route('covers.show', ['cover' => $cover->id]) }}";
									return;
								}
								
								// For the list item in "Available Styles"
								const listItem = document.querySelector(`.template-thumbnail-item[data-template-id="${templateIdToRemove}"]`);
								if (listItem) {
									// Remove the entire thumbnail item's column
									listItem.closest('.col-6').remove();
								}
								
								// If the template just unassigned was the one being PREVIEWED in the "Available Styles" section
								if (selectedTemplateIdForPreview && templateIdToRemove === selectedTemplateIdForPreview) {
									// Reset the preview to the main page's active template's style (if any)
									const mainPageActiveListItem = document.querySelector(`.template-thumbnail-item[data-template-id="${activeTemplateIdOnPage}"]`);
									if (mainPageActiveListItem) {
										mainPageActiveListItem.click(); // Simulate click to reset selection and preview
									} else {
										// If no main page active template, or it's not in the list, clear previews
										updateStylePreviews('', '');
										selectedTemplateIdForPreview = null;
										if (useThisStyleButton) useThisStyleButton.disabled = true;
										document.querySelectorAll('.available-styles-section .template-thumbnail-item').forEach(i => i.classList.remove('active'));
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
			var modalImageEl = document.getElementById('modalImage');
			var currentModalOverlayImage = null;
			
			if (imagePreviewModalEl && modalImageEl) {
				imagePreviewModalEl.addEventListener('show.bs.modal', function (event) {
					var button = event.relatedTarget;
					var imageSrc = button.getAttribute('data-image-src');
					var overlaySrc = button.getAttribute('data-overlay-src');
					
					if (currentModalOverlayImage) {
						currentModalOverlayImage.remove();
						currentModalOverlayImage = null;
					}
					
					modalImageEl.setAttribute('src', '');
					modalImageEl.onload = null;
					
					if (imageSrc) {
						modalImageEl.onload = function () {
							requestAnimationFrame(function () {
								if (currentModalOverlayImage) {
									currentModalOverlayImage.remove();
									currentModalOverlayImage = null;
								}
								if (overlaySrc) {
									if (modalImageEl.offsetWidth > 0 && modalImageEl.offsetHeight > 0) {
										var overlayImg = document.createElement('img');
										overlayImg.src = overlaySrc;
										overlayImg.alt = "Template Overlay";
										overlayImg.style.position = 'absolute';
										overlayImg.style.top = modalImageEl.offsetTop + 'px';
										overlayImg.style.left = modalImageEl.offsetLeft + 'px';
										overlayImg.style.width = modalImageEl.offsetWidth + 'px';
										overlayImg.style.height = modalImageEl.offsetHeight + 'px';
										overlayImg.style.objectFit = 'contain';
										overlayImg.style.pointerEvents = 'none';
										overlayImg.style.zIndex = '1';
										modalImageEl.parentNode.appendChild(overlayImg);
										currentModalOverlayImage = overlayImg;
									} else {
										console.warn("Modal image dimensions are still zero after load and rAF.");
									}
								}
							});
						};
						modalImageEl.setAttribute('src', imageSrc);
					} else {
						modalImageEl.setAttribute('src', '');
					}
				});
				
				imagePreviewModalEl.addEventListener('hidden.bs.modal', function () {
					if (modalImageEl) {
						modalImageEl.setAttribute('src', '');
						modalImageEl.onload = null;
					}
					if (currentModalOverlayImage) {
						currentModalOverlayImage.remove();
						currentModalOverlayImage = null;
					}
				});
			}
			
			// Favorite Button Logic
			const favoriteButton = document.getElementById('favoriteButton');
			if (favoriteButton) {
				favoriteButton.addEventListener('click', function () {
					const coverId = this.dataset.coverId;
					// IMPORTANT: For favoriting, we use the template that is *currently active on the main page*,
					// not necessarily the one selected in the "Available Styles" preview.
					const templateIdForFavorite = {{ $activeTemplateForView ? $activeTemplateForView->id : 'null' }};
					let isFavorited = this.dataset.isFavorited === 'true';
					const csrfToken = document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}';
					const url = isFavorited ? '{{ route("favorites.destroy") }}' : '{{ route("favorites.store") }}';
					const method = isFavorited ? 'DELETE' : 'POST';
					
					this.disabled = true;
					const originalIconClass = this.querySelector('i').className;
					this.querySelector('i').className = 'fas fa-spinner fa-spin';
					
					fetch(url, {
						method: method,
						headers: {
							'X-CSRF-TOKEN': csrfToken,
							'Accept': 'application/json',
							'Content-Type': 'application/json'
						},
						body: JSON.stringify({
							cover_id: coverId,
							template_id: templateIdForFavorite // Use the main page's active template
						})
					})
						.then(response => {
							this.disabled = false;
							this.querySelector('i').className = originalIconClass;
							if (!response.ok) {
								return response.json().then(err => {
									throw err;
								});
							}
							return response.json();
						})
						.then(data => {
							if (data.success) {
								showToast('Success', data.message, 'bg-success');
								isFavorited = data.is_favorited;
								this.dataset.isFavorited = isFavorited ? 'true' : 'false';
								const icon = this.querySelector('i');
								const text = this.querySelector('.button-text');
								if (isFavorited) {
									this.classList.remove('strock_btn');
									this.classList.add('favorited_btn');
									icon.className = 'fas fa-heart-broken';
									text.textContent = 'Favorited';
									this.title = 'Remove from Favorites';
								} else {
									this.classList.remove('favorited_btn');
									this.classList.add('strock_btn');
									icon.className = 'fas fa-heart';
									text.textContent = 'Favorite';
									this.title = 'Add to Favorites';
								}
							} else {
								showToast('Error', data.message || 'Could not update favorite status.', 'bg-danger');
							}
						})
						.catch(error => {
							this.disabled = false;
							this.querySelector('i').className = originalIconClass;
							console.error('Error:', error);
							showToast('Error', error.message || 'An unexpected error occurred.', 'bg-danger');
						});
				});
			}
			
			// New Available Styles Section Logic
			const templateListItems = document.querySelectorAll('.available-styles-section .template-thumbnail-item');
			const kindlePreviewOverlay = document.getElementById('stylePreviewKindleOverlay');
			const fullCoverPreviewOverlay = document.getElementById('stylePreviewFullCoverOverlay');
			const useThisStyleButton = document.getElementById('useThisStyleButton');
			let selectedTemplateIdForPreview = {{ $activeTemplateForView ? $activeTemplateForView->id : 'null' }};
			
			function updateStylePreviews(frontOverlayUrl, fullOverlayUrl) {
				if (kindlePreviewOverlay) {
					if (frontOverlayUrl) {
						kindlePreviewOverlay.src = frontOverlayUrl;
						kindlePreviewOverlay.style.display = 'block';
					} else {
						kindlePreviewOverlay.src = ''; // Clear src if no overlay
						kindlePreviewOverlay.style.display = 'none';
					}
				}
				if (fullCoverPreviewOverlay) {
					if (fullOverlayUrl) {
						fullCoverPreviewOverlay.src = fullOverlayUrl;
						fullCoverPreviewOverlay.style.display = 'block';
					} else {
						fullCoverPreviewOverlay.src = ''; // Clear src if no overlay
						fullCoverPreviewOverlay.style.display = 'none';
					}
				}
			}
			
			templateListItems.forEach(item => {
				item.addEventListener('click', function () {
					// Prevent form submission if admin button is inside and clicked
					if (event.target.closest('.remove-template-assignment-form')) {
						return;
					}
					
					templateListItems.forEach(i => i.classList.remove('active'));
					this.classList.add('active');
					
					selectedTemplateIdForPreview = parseInt(this.dataset.templateId); // Ensure it's a number
					const frontOverlayUrl = this.dataset.frontOverlayUrl;
					const fullOverlayUrl = this.dataset.fullOverlayUrl;
					
					updateStylePreviews(frontOverlayUrl, fullOverlayUrl);
					
					if (useThisStyleButton) {
						useThisStyleButton.disabled = false;
					}
				});
			});
			
			if (useThisStyleButton) {
				// Initial state of the button based on whether a template is active for the page
				useThisStyleButton.disabled = !selectedTemplateIdForPreview;
				
				useThisStyleButton.addEventListener('click', function () {
					if (selectedTemplateIdForPreview) {
						const coverId = {{ $cover->id }};
						const baseUrl = "{{ route('covers.show', ['cover' => $cover->id, 'template' => 'TEMPLATE_ID_PLACEHOLDER']) }}";
						const finalUrl = baseUrl.replace('TEMPLATE_ID_PLACEHOLDER', selectedTemplateIdForPreview);
						window.location.href = finalUrl;
					}
				});
			}
		});
	</script>
@endpush
