<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>Admin - {{ config('app.name', 'Laravel') }}</title>
	
	<link href="{{ asset('vendors/bootstrap5.3.5/css/bootstrap.min.css') }}" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('vendors/fontawesome-free-6.7.2/css/all.min.css') }}">
	<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
	
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
	<link rel="manifest" href="{{ asset('images/site.webmanifest') }}">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
	<div class="container-fluid">
		<a class="navbar-brand" href="{{ route('admin.dashboard') }}">Cover Designer Admin</a>
		<ul class="navbar-nav ms-auto mb-2 mb-lg-0">
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
	@yield('content')
</div>

<script src="{{ asset('vendors/jquery-ui-1.14.1/external/jquery/jquery.js') }}"></script>
<script src="{{ asset('vendors/bootstrap5.3.5/js/bootstrap.bundle.min.js') }}"></script>

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
		generateSimilarTemplate: "{{ route('admin.templates.generate-similar') }}",
		listAssignableTemplatesBase: "{{ url('admin/covers') }}",
		updateCoverTemplateAssignmentsBase: "{{ url('admin/covers') }}"
	};
</script>
<script src="{{ asset('js/admin.js') }}"></script>
@stack('scripts')
</body>
</html>
