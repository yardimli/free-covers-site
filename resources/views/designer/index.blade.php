<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>Free Kindle Covers Designer</title>
	<!-- Dependencies -->
	<link href="{{ asset('vendors/bootstrap5.3.5/css/bootstrap.min.css') }}" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('vendors/fontawesome-free-6.7.2/css/all.min.css') }}">
	<link rel="stylesheet" href="{{ asset('vendors/jquery-ui-1.14.1/jquery-ui.css') }}">
	<link rel="stylesheet" href="{{ asset('vendors/jsfontpicker/dist/jquery.fontpicker.css') }}">
	<link rel="stylesheet" href="{{ asset('css/designer-style.css') }}">
	<link rel="stylesheet" href="{{ asset('css/designer-inspector-style.css') }}">
	<link rel="stylesheet" href="{{ asset('css/designer-canvas-size-style.css') }}">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
	<link rel="manifest" href="{{ asset('images/site.webmanifest') }}">
</head>
<script>
	window.IS_ADMIN_DESIGNER_MODE = {{ $from_admin_mode ? 'true' : 'false' }};
	@if (request()->has('template_id_to_update'))
		window.TEMPLATE_ID_TO_UPDATE = {{ intval(request()->get('template_id_to_update')) }};
	@else
		window.TEMPLATE_ID_TO_UPDATE = null;
	@endif
		@if (request()->has('json_type_to_update'))
		window.JSON_TYPE_TO_UPDATE = "{{ request()->get('json_type_to_update') }}";
	@else
		window.JSON_TYPE_TO_UPDATE = null;
	@endif
</script>
<body>
<div class="app-container d-flex flex-column vh-100">
	<!-- Top Toolbar (Simplified) -->
	<nav class="navbar navbar-expand-sm navbar-dark bg-dark top-toolbar" style="padding: 0px 0px;">
		<div class="container-fluid">
			<div class="navbar-brand mb-0 h2"><img src="{{ asset('template/assets/img/home/logo-dark.png') }}"
			                                        style="height: 34px;"> <span class="navbar-brand mb-0 h2" id="project-name"></span></div>
		</div>
	</nav>
	
	<!-- Hidden input for loading designs -->
	<input type="file" id="loadDesignInput" accept=".json" style="display: none;">
	
	<!-- Main Content Area -->
	<div class="d-flex flex-grow-1 overflow-hidden main-content position-relative">
		<!-- Icon Bar (Fixed Width) -->
		<ul class="nav nav-pills flex-column text-center sidebar-nav flex-shrink-0">
			@if($from_admin_mode)
				<li class="nav-item" id="coversPanelLink"><a class="nav-link" href="#" data-panel-target="#coversPanel"
				                                             title="Covers"><i class="fas fa-image fa-lg"></i></a></li>
				<li class="nav-item" id="templatesPanelLink"><a class="nav-link" href="#" data-panel-target="#templatesPanel"
				                                                title="Templates"><i class="fas fa-th-large fa-lg"></i></a></li>
			@endif
			<li class="nav-item"><a class="nav-link" href="#" data-panel-target="#elementsPanel" title="Elements"><i
						class="fas fa-shapes fa-lg"></i></a></li>
			<li class="nav-item"><a class="nav-link" href="#" data-panel-target="#overlaysPanel" title="Overlays"><i
						class="fas fa-clone fa-lg"></i></a></li>
			<li class="nav-item"><a class="nav-link" href="#" data-panel-target="#uploadPanel" title="Upload"><i
						class="fas fa-upload fa-lg"></i></a></li>
			<li class="nav-item"><a class="nav-link" href="#" data-panel-target="#layersPanel" title="Layers"><i
						class="fas fa-layer-group fa-lg"></i></a></li>
			<li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#backgroundSettingsModal"
			                        title="Canvas Background"><i class="fas fa-palette fa-lg"></i></a></li>
			<hr class="mx-2" style="border-top: 1px solid #495057;">
			@if($from_admin_mode)
				<li class="nav-item" id="loadDesignPanelLink"><a class="nav-link" href="#" id="loadDesignIconBtn"
				                                                 title="Load Design (.json)"><i
							class="fas fa-folder-open fa-lg"></i></a></li>
				<li class="nav-item" id="saveDesignPanelLink"><a class="nav-link" href="#" id="saveDesign"
				                                                 title="Save Design (.json)"><i
							class="fas fa-save fa-lg"></i></a></li>
				@if(request()->has('template_id_to_update'))
					<li class="nav-item" id="updateTemplateInDbLink"><a class="nav-link" href="#" id="updateTemplateInDbBtn"
					                                                    title="Update Template in Database"><i
								class="fas fa-database fa-lg text-warning"></i></a></li>
				@endif
			@else
				{{-- Regular user save design button --}}
				@auth
					<li class="nav-item" id="saveUserDesignLink"><a class="nav-link" href="#" id="saveUserDesignBtn"
					                                                title="Save My Design"><i
								class="fas fa-cloud-upload-alt fa-lg"></i></a></li>
				@endauth
			@endif
			<li class="nav-item"><a class="nav-link" href="#" id="undoBtn" title="Undo"><i class="fas fa-undo fa-lg"></i></a>
			</li>
			<li class="nav-item"><a class="nav-link" href="#" id="redoBtn" title="Redo"><i class="fas fa-redo fa-lg"></i></a>
			</li>
			<li class="nav-item"><a class="nav-link" href="#" id="downloadBtn" title="Download Image (PNG)"><i
						class="fas fa-download fa-lg"></i></a></li>
		</ul>
		
		<!-- Sliding Panels Container (Absolute Position) -->
		<div id="sidebar-panels-container" class="closed">
			<!-- Covers Panel -->
			<div id="coversPanel" class="sidebar-panel">
				<div class="panel-content-wrapper">
					<div class="panel-header">
						<input type="search" id="coverSearch" class="form-control form-control-sm" placeholder="Search covers..."
						       style="flex-grow: 1;">
					</div>
					<div id="coverList" class="row item-grid panel-scrollable-content"><p>Loading covers...</p></div>
				</div>
			</div>
			<!-- Templates Panel -->
			<div id="templatesPanel" class="sidebar-panel">
				<div class="panel-content-wrapper">
					<div class="panel-header">
						<input type="search" id="templateSearch" class="form-control form-control-sm"
						       placeholder="Search templates..." style="flex-grow: 1;">
					</div>
					<div id="templateList" class="row item-grid panel-scrollable-content"><p>Loading templates...</p></div>
				</div>
			</div>
			<!-- Elements Panel -->
			<div id="elementsPanel" class="sidebar-panel">
				<div class="panel-content-wrapper">
					<div class="panel-header">
						<input type="search" id="elementSearch" class="form-control form-control-sm"
						       placeholder="Search elements...">
					</div>
					<div id="elementList" class="row item-grid panel-scrollable-content"><p>Loading elements...</p></div>
				</div>
			</div>
			<!-- Overlays Panel -->
			<div id="overlaysPanel" class="sidebar-panel">
				<div class="panel-content-wrapper">
					<div class="panel-header">
						<input type="search" id="overlaySearch" class="form-control form-control-sm"
						       placeholder="Search overlays...">
					</div>
					<div id="overlayList" class="row item-grid panel-scrollable-content"><p>Loading overlays...</p></div>
				</div>
			</div>
			<!-- Upload Panel -->
			<div id="uploadPanel" class="sidebar-panel">
				<div class="panel-content-wrapper">
					<div class="panel-header">Upload Image</div>
					<div class="panel-scrollable-content p-2">
						<input type="file" id="imageUploadInput" class="form-control form-control-sm mb-2" accept="image/*">
						<div id="uploadPreview" class="mt-2 text-center mb-2" style="min-height: 50px;"></div>
						<button id="addImageFromUpload" class="btn btn-primary btn-sm w-100" disabled>Add to Canvas</button>
					</div>
				</div>
			</div>
			<!-- Layers Panel -->
			<div id="layersPanel" class="sidebar-panel">
				<div class="panel-content-wrapper">
					<div class="panel-header">Layers</div>
					<div class="panel-scrollable-content">
						<ul id="layerList" class="list-group list-group-flush">
							<li class="list-group-item text-muted">No layers yet.</li>
						</ul>
					</div>
				</div>
			</div>
		</div> <!-- End Sliding Panels Container -->
		
		<!-- Canvas Area (Takes remaining space) -->
		<div id="canvas-area" class="bg-secondary overflow-auto position-relative flex-grow-1">
			<div id="canvas-wrapper" class="position-relative">
				<div id="canvas" class="shadow position-relative">
					<!-- Canvas elements added here by JS -->
				</div>
			</div>
			<!-- Zoom Controls -->
			<div id="zoom-controls" class="position-fixed rounded shadow-sm p-1 m-2 bg-dark d-flex align-items-center"
			     style="bottom: 10px; left: 50%; transform: translateX(-50%); z-index: 1060;">
				<button id="zoom-out" class="btn btn-sm me-1" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
				<div class="dropup mx-1">
					<button class="btn btn-sm dropdown-toggle zoom-percentage-display" type="button" id="zoom-percentage-toggle"
					        data-bs-toggle="dropdown" aria-expanded="false">
						100%
					</button>
					<ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="zoom-percentage-toggle" id="zoom-options-menu">
						<li><a class="dropdown-item zoom-option" href="#" data-zoom="0.25">25%</a></li>
						<li><a class="dropdown-item zoom-option" href="#" data-zoom="0.5">50%</a></li>
						<li><a class="dropdown-item zoom-option" href="#" data-zoom="0.75">75%</a></li>
						<li><a class="dropdown-item zoom-option" href="#" data-zoom="1.0">100%</a></li>
						<li><a class="dropdown-item zoom-option" href="#" data-zoom="1.5">150%</a></li>
						<li><a class="dropdown-item zoom-option" href="#" data-zoom="2.0">200%</a></li>
						<li><a class="dropdown-item zoom-option" href="#" data-zoom="3.0">300%</a></li>
						<li>
							<hr class="dropdown-divider">
						</li>
						<li><a class="dropdown-item zoom-option" href="#" data-zoom="fit">Fit</a></li>
					</ul>
				</div>
				<button id="zoom-in" class="btn btn-sm ms-1" title="Zoom In"><i class="fas fa-search-plus"></i></button>
			</div>
		</div> <!-- End Canvas Area -->
		
		<!-- Inspector Panel (Remains on the right) -->
		@include('designer.partials.inspectorPanel')
	
	</div> <!-- End Main Content Area -->
</div> <!-- End App Container -->

<!-- Export Overlay -->
<div id="export-overlay" style="display: none;">
	<div class="export-spinner-content">
		<div class="spinner-border text-light" role="status">
			<span class="visually-hidden">Loading...</span>
		</div>
		<p class="mt-2 text-light" id="loading-overlay-message">Processing...</p>
	</div>
</div>

<!-- Canvas Size Modal -->
@include('designer.partials.canvasSizeModal')
@include('designer.partials.backgroundSettingsModal')

<!-- Overlay Confirmation Modal -->
<div class="modal fade" id="overlayConfirmModal" tabindex="-1" aria-labelledby="overlayConfirmModalLabel"
     aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="overlayConfirmModalLabel">Add Overlay</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				An overlay layer already exists. Would you like to replace the existing overlay(s) or add this as an additional
				one?
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-outline-primary" id="addOverlayAsNewBtn">Add as New</button>
				<button type="button" class="btn btn-primary" id="replaceOverlayBtn">Replace Existing</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="saveDesignNameModal" tabindex="-1" aria-labelledby="saveDesignNameModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="saveDesignNameModalLabel">Save Your Design</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="saveDesignNameForm" novalidate>
					<div class="mb-3">
						<label for="designNameInput" class="form-label">Design Name</label>
						<input type="text" class="form-control" id="designNameInput" required minlength="1" maxlength="255">
						<div class="invalid-feedback">
							Please enter a name for your design (1-255 characters).
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="confirmSaveDesignBtn">Save Design</button>
			</div>
		</div>
	</div>
</div>

<!-- Save Design Result Modal -->
<div class="modal fade" id="saveDesignResultModal" tabindex="-1" aria-labelledby="saveDesignResultModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="saveDesignResultModalLabel">Save Result</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="saveDesignResultMessage"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>


<!-- Embed data -->
<script id="coverTypesData" type="application/json">{!! $cover_types_json !!}</script>
<script id="templateData" type="application/json">{!! $templates_json !!}</script>
<script id="coverData" type="application/json">{!! $covers_json !!}</script>
<script id="elementData" type="application/json">{!! $elements_json !!}</script>
<script id="overlayData" type="application/json">{!! $overlays_json !!}</script>

<!-- Scripts -->
<script src="{{ asset('vendors/bootstrap5.3.5/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendors/jquery-ui-1.14.1/external/jquery/jquery.js') }}"></script>
<script src="{{ asset('vendors/jquery-ui-1.14.1/jquery-ui.min.js') }}"></script>
<script src="{{ asset('vendors/jsfontpicker/dist/jquery.fontpicker.min.js') }}"></script>
<script type="module" src="{{ asset('vendors/modern-screenshot.js') }}"></script>
<script src="{{ asset('vendors/tinycolor-min.js') }}"></script>
<script src="{{ asset('vendors/moveable.min.js') }}"></script>

<script src="{{ asset('js/designer/LayerManager.js') }}"></script>
<script src="{{ asset('js/designer/HistoryManager.js') }}"></script>
<script src="{{ asset('js/designer/CanvasManager.js') }}"></script>
<script src="{{ asset('js/designer/InspectorPanel.js') }}"></script>
<script src="{{ asset('js/designer/SidebarItemManager.js') }}"></script>
<script src="{{ asset('js/designer/CanvasSizeModal.js') }}"></script>
<script src="{{ asset('js/designer/App.js') }}"></script>
</body>
</html>
