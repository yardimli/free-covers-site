{{-- resources/views/designer/setup_canvas.blade.php --}}
@extends('layouts.app')

@section('title', $cover->name ? Str::limit($cover->name, 50) . ' - Setup Cover' : 'Cover Setup - Free Kindle Covers')

@php
	$footerClass = '';
@endphp

@push('styles')
	<style>
      .setup-container {
          max-width: 900px;
          margin: 2rem auto;
          background-color: #fff;
          padding: 2rem;
          border-radius: 8px;
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      }

      .preview-area-container {
          background-color: #e9ecef; /* Fallback if no image */
          border-radius: 0.25rem;
          padding: 10px;
          min-height: 300px; /* Ensure it has some height */
          display: flex;
          align-items: center;
          justify-content: center;
          position: relative;
      }

      #canvasPreviewAreaSetup {
          /*width: 100%;*/
          /*height: 100%;*/
          display: flex; /* Use cover to fill the area */
		      
          position: relative; /* For pseudo-element positioning */
      }

      /* Pseudo-element for the background image with opacity */
      #canvasPreviewAreaSetup.has-background-image::before {
          content: "";
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background-image: var(--preview-bg-image); /* Set by JS */
          background-repeat: no-repeat;
          background-position: center;
		      background-size: cover; /* Cover to fill the area */
          opacity: 0.75; /* Adjust opacity as desired (e.g., 0.7 for 70%) */
          z-index: 0; /* Behind the panels */
          border-radius: 0.25rem; /* Match container's border-radius if any visual effect is desired */
      }

      .preview-panel {
          box-sizing: border-box;
          border: 1px solid #6c757d; /* Default border */
          flex-shrink: 0;
          display: flex;
          align-items: center;
          justify-content: center;
          text-align: center;
          font-size: 0.9rem;
          color: #495057; /* Default text color if no background image */
          overflow: hidden;
          white-space: normal;
          padding: 5px;
          position: relative;
          z-index: 1; /* To be above the ::before pseudo-element and default background */
          background-color: rgba(248, 249, 250, 0.3); /* Default semi-transparent background for panels */
      }

      /* Styles for panels when background image is present */
      #canvasPreviewAreaSetup.has-background-image .preview-panel {
          background-color: transparent !important; /* Make panels transparent to see pseudo-element bg */
          border-color: rgba(255, 255, 255, 0.4) !important;
          color: white;
          font-weight: bold;
          /* Enhanced text-shadow for strong contrast (acting as "inverse border") */
          text-shadow:
                  -1px -1px 0 rgba(0,0,0,0.7),
                  1px -1px 0 rgba(0,0,0,0.7),
                  -1px  1px 0 rgba(0,0,0,0.7),
                  1px  1px 0 rgba(0,0,0,0.7), /* Creates a 1px dark outline */
                  -2px -2px 3px rgba(0,0,0,0.5),
                  2px -2px 3px rgba(0,0,0,0.5),
                  -2px  2px 3px rgba(0,0,0,0.5),
                  2px  2px 3px rgba(0,0,0,0.5); /* Softer, slightly larger shadow */
      }

      #previewBackSetup {
          border-right: none;
      }

      #previewSpineSetup {
          border-left: 1px dashed #6c757d; /* Default spine border */
          border-right: 1px dashed #6c757d; /* Default spine border */
          writing-mode: vertical-rl;
          text-orientation: mixed;
      }
      /* Specific border style for spine when background image is active */
      #canvasPreviewAreaSetup.has-background-image #previewSpineSetup {
          border-left: 1px dashed rgba(255, 255, 255, 0.6) !important;
          border-right: 1px dashed rgba(255, 255, 255, 0.6) !important;
      }


      #previewFrontSetup {
          border-left: none;
      }

      .preview-area-container > #previewSpineSetup:not([style*="display: none"]) + #previewFrontSetup {
          border-left: none;
      }

      .form-label-sm {
          font-size: 0.8rem;
          margin-bottom: 0.15rem;
          color: #6c757d;
      }
      .form-control-sm, .form-select-sm {
          font-size: 0.8rem;
      }
      .form-check-sm .form-check-label {
          font-size: 0.85rem;
      }
      .btn-primary {
          background-color: #0d6efd;
          border-color: #0d6efd;
      }
	</style>
@endpush

@section('content')
	@include('partials.cover_breadcrumb', [
			'cover' => $cover,
			'showCanvasSetup' => true
	])
	
	<div class="container setup-container">
		<div class="text-center mb-4">
			<h3 class="mt-3">Setup Your Print Cover Canvas for {{$cover->name ?? ''}}</h3>
		</div>
		
		<div class="row">
			<!-- Controls Column -->
			<div class="col-md-7">
				<form id="canvasSizeFormSetup">
					<!-- Unit Selection -->
					<div class="mb-3">
						<label class="form-label form-label-sm">Units:</label>
						<div class="form-check form-check-inline form-check-sm">
							<input class="form-check-input" type="radio" name="canvasUnit" id="unitInches" value="inches" checked>
							<label class="form-check-label" for="unitInches">Inches</label>
						</div>
						<div class="form-check form-check-inline form-check-sm">
							<input class="form-check-input" type="radio" name="canvasUnit" id="unitMillimeters" value="mm">
							<label class="form-check-label" for="unitMillimeters">Millimeters</label>
						</div>
					</div>
					
					<!-- Preset Size Selection -->
					<div class="mb-3">
						<label class="form-label form-label-sm">Preset Size (Front Cover)</label>
						<div id="canvasSizePresetGroup"></div> <!-- Populated by JS -->
						<div class="invalid-feedback" id="presetError" style="display: none;">Please select a preset size.</div>
					</div>
					
					<!-- Custom Size Inputs -->
					<div id="customSizeControls" class="mb-3" style="display: none;">
						<div class="row g-2">
							<div class="col-md-6">
								<label for="customWidthInput" class="form-label form-label-sm">Custom Width (<span id="customWidthUnit">inches</span>)</label>
								<input type="number" class="form-control form-control-sm" id="customWidthInput" value="6" min="0.1" step="0.01">
								<div class="invalid-feedback" id="customWidthError" style="display: none;">Enter a valid width.</div>
							</div>
							<div class="col-md-6">
								<label for="customHeightInput" class="form-label form-label-sm">Custom Height (<span id="customHeightUnit">inches</span>)</label>
								<input type="number" class="form-control form-control-sm" id="customHeightInput" value="9" min="0.1" step="0.01">
								<div class="invalid-feedback" id="customHeightError" style="display: none;">Enter a valid height.</div>
							</div>
						</div>
					</div>
					
					<!-- Spine and Back Cover Checkbox (Hidden and always active) -->
					<div class="form-check form-check-sm mb-3" id="addSpineAndBackContainer" style="display: none;">
						<input class="form-check-input" type="checkbox" value="" id="addSpineAndBackCheckbox" checked>
						<label class="form-check-label" for="addSpineAndBackCheckbox">Add Spine & Back Cover</label>
					</div>
					
					<!-- Spine Controls (Always shown as checkbox is hidden and active, if a preset is selected) -->
					<div id="spineControls" class="mb-3" style="display: none;">
						<div class="mb-2">
							<label class="form-label form-label-sm">Spine Width Method:</label><br>
							<div class="form-check form-check-inline form-check-sm">
								<input class="form-check-input" type="radio" name="spineInputMethod" id="spineMethodPixels" value="pixels">
								<label class="form-check-label" for="spineMethodPixels">Enter Pixels</label>
							</div>
							<div class="form-check form-check-inline form-check-sm">
								<input class="form-check-input" type="radio" name="spineInputMethod" id="spineMethodCalculate" value="calculate" checked>
								<label class="form-check-label" for="spineMethodCalculate">Calculate from Pages</label>
							</div>
						</div>
						
						<div id="spinePixelInputContainer" class="mb-2" style="display: none;">
							<label for="spineWidthInput" class="form-label form-label-sm">Spine Width (pixels)</label>
							<input type="number" class="form-control form-control-sm" id="spineWidthInput" value="200" min="1" step="1" max="1000">
							<div class="invalid-feedback" id="spineWidthError" style="display: none;">Please enter a valid positive number.</div>
						</div>
						
						<div id="spineCalculateInputContainer" class="mb-2">
							<div class="row g-2">
								<div class="col-md-6">
									<label for="pageCountInput" class="form-label form-label-sm">Page Count</label>
									<input type="number" class="form-control form-control-sm" id="pageCountInput" value="200" min="1" step="1" max="1000">
									<div class="invalid-feedback" id="pageCountError" style="display: none;">Enter valid page count.</div>
								</div>
								<div class="col-md-6">
									<label for="paperTypeSelect" class="form-label form-label-sm">Paper Type</label>
									<select class="form-select form-select-sm" id="paperTypeSelect">
										<option value="bw">White</option>
										<option value="cream">Cream</option>
									</select>
								</div>
							</div>
							<div class="form-text text-muted small mt-1" id="calculatedSpineInfo" style="display: none;"></div>
							<div class="invalid-feedback" id="spineCalculationError" style="display: none;">Could not calculate. Check options.</div>
						</div>
					</div>
				</form>
			</div>
			
			<!-- Preview Column -->
			<div class="col-md-5 d-flex flex-column align-items-center">
				<label class="form-label form-label-sm mb-1">Preview:</label>
				<label class="form-label form-label-sm mb-1">(This is an approximate preview)</label>
				<div class="preview-area-container w-100" id="canvasPreviewContainerSetup">
					<div id="canvasPreviewAreaSetup" class="d-flex">
						<div id="previewBackSetup" class="preview-panel">Back</div>
						<div id="previewSpineSetup" class="preview-panel">Spine</div>
						<div id="previewFrontSetup" class="preview-panel">Front</div>
					</div>
				</div>
			</div>
		</div>
		
		<div class="mt-4 text-end">
			<button type="button" class="bj_theme_btn" id="proceedToDesignerBtn">Start Customizing</button>
		</div>
	</div>
@endsection

@push('scripts')
	<script id="pageNumberData" type="application/json">{!! $page_numbers_json_for_modal !!}</script>
	<script>
		// Pass data from PHP to JavaScript
		const COVER_IMAGE_URL_PREVIEW = "{{ $coverImageUrlForPreview ?? '' }}";
		const TEMPLATE_OVERLAY_URL_PREVIEW = "{{ $templateOverlayUrlForPreview ?? '' }}";
		
		const ORIGINAL_COVER_IMAGE_PATH_DESIGNER = "{{ $originalCoverImagePathForDesigner ?? '' }}"; // Front Slice
		const FULL_COVER_IMAGE_PATH_DESIGNER = "{{ $fullCoverImagePathForDesigner ?? '' }}";       // Full Cover
		
		const TEMPLATE_JSON_URL_DESIGNER = "{{ $templateJsonUrlForDesigner ?? '' }}";
		const FULL_COVER_THUMBNAIL_URL_PREVIEW = "{{ $fullCoverThumbnailUrlForPreview ?? '' }}";
		const DEFAULT_PRESET_VALUE_SETUP_PAGE = "{{ $default_preset_value_for_setup_page ?? '1840x2775' }}";
	</script>
	<script src="{{ asset('js/designer/CanvasSizeSetupPage.js') }}"></script>
@endpush
