{{-- free-cover-site/resources/views/layouts/admin.blade.php --}}
	<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>Admin - {{ config('app.name', 'Laravel') }} - @yield('title', 'Dashboard')</title>
	
	<link href="{{ asset('vendors/bootstrap5.3.5/css/bootstrap.min.css') }}" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('vendors/fontawesome-free-6.7.2/css/all.min.css') }}">
	
	<!-- Quill.js CSS -->
	<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
	
	<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
	
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
	<link rel="manifest" href="{{ asset('images/site.webmanifest') }}">
	
	@stack('styles')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
	<div class="container-fluid">
		<a class="navbar-brand" href="{{ route('admin.dashboard') }}">Cover Designer Admin</a>
		<ul class="navbar-nav ms-auto mb-2 mb-lg-0">
			<li class="nav-item">
				<button id="batchGenerateMetadataBtn" class="btn btn-sm btn-outline-warning me-2" type="button">
					Batch Generate Metadata
				</button>
			</li>
			<li class="nav-item">
				<button id="batchAnalyzeTextPlacementsBtn" class="btn btn-sm btn-outline-info me-2" type="button">
					Batch Analyze Text Placements
				</button>
			</li>
			<li class="nav-item">
				<button id="autoAssignTemplatesBtn" class="btn btn-sm btn-outline-success me-2" type="button">
					Auto Assign Templates
				</button>
			</li>
			<li class="nav-item">
				<a href="{{ route('admin.covers.template-management.index') }}" class="btn btn-sm btn-outline-light me-2" target="_blank">Prune Cover/Template Pairs</a>
			</li>
			<li class="nav-item">
				<a href="{{ route('admin.blog.index') }}" class="btn btn-sm btn-outline-secondary me-2">Blog Management</a>
			</li>
			<li class="nav-item">
				<a href="{{ route('home') }}" class="btn btn-sm btn-outline-light me-2" target="_blank">View App</a>
			</li>
			@auth
				<li class="nav-item">
					<form method="POST" action="{{ route('logout') }}">
						@csrf
						<button type="submit" class="btn btn-sm btn-outline-warning">Logout</button>
					</form>
				</li>
			@endauth
		</ul>
	</div>
</nav>

<div class="container admin-container">
	<div id="alert-messages-container" class="alert-messages"></div>
	
	{{-- Batch Progress Area --}}
	<div id="batchProgressArea" class="my-3" style="display: none;">
		<h5>Batch Processing Status:</h5>
		<div class="progress" style="height: 25px;">
			<div id="batchProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
				<span id="batchProgressText"></span>
			</div>
		</div>
		<div id="batchProgressSummary" class="mt-2 small"></div>
	</div>
	
	@yield('content')
</div>

<script src="{{ asset('vendors/jquery-ui-1.14.1/external/jquery/jquery.js') }}"></script>
<script src="{{ asset('vendors/bootstrap5.3.5/js/bootstrap.bundle.min.js') }}"></script>

<!-- Quill.js JS -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>


<!-- Pass routes to JS -->
<script>
	window.adminRoutes = {
		listCoverTypes: "{{ route('admin.cover-types.list') }}",
		listItems: "{{ route('admin.items.list') }}",
		uploadItem: "{{ route('admin.items.upload') }}",
		getItemDetails: "{{ route('admin.items.details') }}",
		updateItem: "{{ route('admin.items.update') }}",
		deleteItem: "{{ route('admin.items.delete') }}",
		generateAiMetadata: "{{ route('admin.items.generate-ai-metadata') }}",
		getCoversNeedingMetadata: "{{ route('admin.covers.needing-metadata') }}",
		generateSimilarTemplate: "{{ route('admin.templates.generate-similar') }}",
		generateAiTextPlacementsBase: "{{ url('admin/covers') }}",
		getUnprocessedCovers: "{{ route('admin.covers.unprocessed-list') }}",
		listAssignableTemplatesBase: "{{ url('admin/covers') }}",
		updateCoverTemplateAssignmentsBase: "{{ url('admin/covers') }}",
		updateTextPlacementsBase: "{{ url('admin/items') }}",
		aiEvaluateTemplateFitBase: "{{ url('admin/covers') }}",
		getCoversWithoutTemplates: "{{ route('admin.covers.without-templates') }}",
		uploadCoverZip: "{{ route('admin.covers.upload-zip') }}",
		generateFullCoverJsonForTemplateBase: "{{ url('admin/templates') }}",
		
		blogCategoriesList: "{{ route('admin.blog.categories.list') }}",
		blogCategoriesStore: "{{ route('admin.blog.categories.store') }}",
		blogCategoriesUpdateBase: "{{ url('admin/blog/categories') }}",
		blogCategoriesDestroyBase: "{{ url('admin/blog/categories') }}",
		
		blogPostsList: "{{ route('admin.blog.posts.list') }}",
		blogPostsGetBase: "{{ url('admin/blog/posts') }}", // Used for constructing edit links
		blogPostsDestroyBase: "{{ url('admin/blog/posts') }}",
		generateAiBlogPost: "{{ route('admin.blog.posts.generate-ai') }}",
	};
	window.AppAdmin = window.AppAdmin || {}; // Initialize the global namespace
</script>

<!-- Modular Admin Scripts (Order Matters!) -->
<script src="{{ asset('js/admin/utils.js') }}"></script>
<script src="{{ asset('js/admin/coverTypes.js') }}"></script>
<script src="{{ asset('js/admin/items.js') }}"></script>
<script src="{{ asset('js/admin/upload.js') }}"></script>
<script src="{{ asset('js/admin/edit.js') }}"></script>
<script src="{{ asset('js/admin/delete.js') }}"></script>
<script src="{{ asset('js/admin/aiMetadata.js') }}"></script>
<script src="{{ asset('js/admin/aiSimilarTemplate.js') }}"></script>
<script src="{{ asset('js/admin/assignTemplates.js') }}"></script>
<script src="{{ asset('js/admin/textPlacements.js') }}"></script>
<script src="{{ asset('js/admin/batchCoverTextPlacement.js') }}"></script>
<script src="{{ asset('js/admin/batchAutoAssignTemplates.js') }}"></script>
<script src="{{ asset('js/admin/batchAiMetadata.js') }}"></script>
<script src="{{ asset('js/admin/uploadZip.js') }}"></script>
<script src="{{ asset('js/admin/blogManagement.js') }}"></script>
<script src="{{ asset('js/admin/updateAllTemplateImages.js') }}"></script>

<!-- Main Admin Orchestrator -->
<script src="{{ asset('js/admin.js') }}"></script>

@stack('scripts')
</body>
</html>
