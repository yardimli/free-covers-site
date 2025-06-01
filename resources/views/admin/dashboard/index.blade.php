@extends('layouts.admin')

@section('content')
	<ul class="nav nav-tabs" id="adminTab" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link active" id="covers-tab" data-bs-toggle="tab" data-bs-target="#covers-panel" type="button"
			        role="tab" aria-controls="covers-panel" aria-selected="true">Covers
			</button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates-panel" type="button"
			        role="tab" aria-controls="templates-panel" aria-selected="false">Templates
			</button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="elements-tab" data-bs-toggle="tab" data-bs-target="#elements-panel" type="button"
			        role="tab" aria-controls="elements-panel" aria-selected="false">Elements
			</button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="overlays-tab" data-bs-toggle="tab" data-bs-target="#overlays-panel" type="button"
			        role="tab" aria-controls="overlays-panel" aria-selected="false">Overlays
			</button>
		</li>
	</ul>
	
	<div class="tab-content" id="adminTabContent">
		<!-- Covers Panel -->
		<div class="tab-pane fade show active" id="covers-panel" role="tabpanel" aria-labelledby="covers-tab">
			<h3>Manage Covers</h3>
			<button type="button" class="btn btn-sm mb-2 btn-info" data-bs-toggle="modal" data-bs-target="#uploadCoverZipModal">
				<i class="fas fa-file-archive"></i> Upload Covers ZIP
			</button>
			<button type="button" class="btn btn-sm mb-2 btn-success" data-bs-toggle="modal" data-bs-target="#addCoverModal">
				<i class="fas fa-plus-circle"></i> Add New Cover
			</button>
			
			<!-- Search and Filter Form -->
			<form class="mb-3 search-form row g-3 align-items-center" data-type="covers">
				<div class="col-md-4 col-lg-4">
					<div class="input-group">
						<input type="search" class="form-control search-input"
						       placeholder="Search Covers (Name, Caption, Keywords, Categories)..." aria-label="Search Covers">
						<button class="btn btn-outline-secondary" type="submit">Search</button>
					</div>
				</div>
				<div class="col-md-2 col-lg-2">
					<select class="form-select cover-type-filter admin-cover-type-dropdown" data-type="covers"
					        aria-label="Filter by Cover Type">
						<option value="">All Cover Types</option>
						<!-- Populated by JS -->
					</select>
				</div>
				<div class="col-md-2 col-lg-2">
					<select class="form-select sort-by-select" aria-label="Sort by">
						<option value="id" selected>Sort by ID</option>
						<option value="name">Sort by Name</option>
					</select>
				</div>
				<div class="col-md-2 col-lg-2">
					<select class="form-select sort-direction-select" aria-label="Sort direction">
						<option value="desc" selected>Descending</option>
						<option value="asc">Ascending</option>
					</select>
				</div>
				<div class="col-md-2 col-lg-2">
					<button class="btn btn-outline-info w-100" type="button" id="filterNoTemplatesBtn"
					        title="Filter covers with no templates assigned">
						<i class="fas fa-filter"></i> No Templates
					</button>
				</div>
			</form>
			<div class="table-responsive">
				<table class="table table-striped item-table" id="coversTable">
					<thead>
					<tr>
						<th>Preview</th>
						<th style="min-width: 150px;">Name/Type</th>
						<th>Details</th>
						<th style="min-width: 200px;">Files</th>
						<th style="min-width: 250px;">Placements/Templates/Categories</th>
						<th style="width: 135px;">Actions</th>
					</tr>
					</thead>
					<tbody><!-- Populated by JS --></tbody>
				</table>
			</div>
			<nav aria-label="Covers pagination">
				<ul class="pagination justify-content-center" id="coversPagination"></ul>
			</nav>
		</div>
		
		<!-- Templates Panel -->
		<div class="tab-pane fade" id="templates-panel" role="tabpanel" aria-labelledby="templates-tab">
			<h3>Manage Templates</h3>
			<button id="refreshAllVisibleTemplatePreviewsBtn" class="btn btn-sm btn-outline-info mb-2" style="display: none;" title="Refresh previews for all currently visible templates. This will open and close tabs.">
				<i class="fas fa-sync-alt"></i> Refresh All Previews
			</button>
			<button type="button" class="btn btn-sm mb-2 btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
				<i class="fas fa-plus-circle"></i> Add New Template
			</button>
			
			<!-- Search and Filter Form -->
			<form class="mb-3 search-form row g-3 align-items-center" data-type="templates">
				<div class="col-md-5">
					<div class="input-group">
						<input type="search" class="form-control search-input"
						       placeholder="Search Templates (Name, Keywords)..." aria-label="Search Templates">
						<button class="btn btn-outline-secondary" type="submit">Search</button>
					</div>
				</div>
				<div class="col-md-3">
					<select class="form-select cover-type-filter admin-cover-type-dropdown" data-type="templates"
					        aria-label="Filter by Cover Type">
						<option value="">All Cover Types</option>
						<!-- Populated by JS -->
					</select>
				</div>
				<div class="col-md-2">
					<select class="form-select sort-by-select" aria-label="Sort by">
						<option value="id" selected>Sort by ID</option>
						<option value="name">Sort by Name</option>
					</select>
				</div>
				<div class="col-md-2">
					<select class="form-select sort-direction-select" aria-label="Sort direction">
						<option value="desc" selected>Descending</option>
						<option value="asc">Ascending</option>
					</select>
				</div>
			</form>
			<div class="table-responsive">
				<table class="table table-striped item-table" id="templatesTable">
					<thead>
					<tr>
						<th style="width: 150px;">Cover Image</th>
						<th style="width: 180px;">Full Cover Preview</th>
						<th style="width: 200px;">Name/Cover Type</th>
						<th>Keywords</th>
						<th>Text Placements</th>
						<th style="width: 175px;">Actions</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
			<nav aria-label="Templates pagination">
				<ul class="pagination justify-content-center" id="templatesPagination"></ul>
			</nav>
		</div>
		
		<!-- Elements Panel -->
		<div class="tab-pane fade" id="elements-panel" role="tabpanel" aria-labelledby="elements-tab">
			<h3>Manage Elements</h3>
			{{-- Upload form for elements can be a modal too if desired, or kept simple if single file --}}
			<div class="upload-form mb-4">
				<h4>Upload New Element</h4>
				<form id="uploadElementForm" enctype="multipart/form-data">
					<input type="hidden" name="item_type" value="elements">
					<div class="mb-3">
						<label for="elementName" class="form-label">Name</label>
						<input type="text" class="form-control" id="elementName" name="name" required>
					</div>
					<div class="mb-3">
						<label for="elementImage" class="form-label">Element Image (PNG, JPG, GIF)</label>
						<input type="file" class="form-control" id="elementImage" name="image_file"
						       accept="image/png, image/jpeg, image/gif" required>
					</div>
					<div class="mb-3">
						<label for="elementKeywords" class="form-label">Keywords (comma-separated)</label>
						<input type="text" class="form-control" id="elementKeywords" name="keywords">
					</div>
					<button type="submit" class="btn btn-primary">Upload Element</button>
				</form>
			</div>
			<h4>Existing Elements</h4>
			<form class="mb-3 search-form row g-3 align-items-center" data-type="elements">
				<div class="col-md-8">
					<div class="input-group">
						<input type="search" class="form-control search-input"
						       placeholder="Search Elements (Name, Keywords)..." aria-label="Search Elements">
						<button class="btn btn-outline-secondary" type="submit">Search</button>
					</div>
				</div>
				<div class="col-md-2">
					<select class="form-select sort-by-select" aria-label="Sort by">
						<option value="id" selected>Sort by ID</option>
						<option value="name">Sort by Name</option>
					</select>
				</div>
				<div class="col-md-2">
					<select class="form-select sort-direction-select" aria-label="Sort direction">
						<option value="desc" selected>Descending</option>
						<option value="asc">Ascending</option>
					</select>
				</div>
			</form>
			<div class="table-responsive">
				<table class="table table-striped item-table" id="elementsTable">
					<thead>
					<tr>
						<th>Preview</th>
						<th>Name</th>
						<th>Keywords</th>
						<th style="width: 135px;">Actions</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
			<nav>
				<ul class="pagination justify-content-center" id="elementsPagination"></ul>
			</nav>
		</div>
		
		<!-- Overlays Panel -->
		<div class="tab-pane fade" id="overlays-panel" role="tabpanel" aria-labelledby="overlays-tab">
			<h3>Manage Overlays</h3>
			{{-- Upload form for overlays can be a modal too if desired, or kept simple if single file --}}
			<div class="upload-form mb-4">
				<h4>Upload New Overlay</h4>
				<form id="uploadOverlayForm" enctype="multipart/form-data">
					<input type="hidden" name="item_type" value="overlays">
					<div class="mb-3">
						<label for="overlayName" class="form-label">Name</label>
						<input type="text" class="form-control" id="overlayName" name="name" required>
					</div>
					<div class="mb-3">
						<label for="overlayImage" class="form-label">Overlay Image (PNG, JPG, GIF)</label>
						<input type="file" class="form-control" id="overlayImage" name="image_file"
						       accept="image/png, image/jpeg, image/gif" required>
					</div>
					<div class="mb-3">
						<label for="overlayKeywords" class="form-label">Keywords (comma-separated)</label>
						<input type="text" class="form-control" id="overlayKeywords" name="keywords">
					</div>
					<button type="submit" class="btn btn-primary">Upload Overlay</button>
				</form>
			</div>
			<h4>Existing Overlays</h4>
			<form class="mb-3 search-form row g-3 align-items-center" data-type="overlays">
				<div class="col-md-8">
					<div class="input-group">
						<input type="search" class="form-control search-input"
						       placeholder="Search Overlays (Name, Keywords)..." aria-label="Search Overlays">
						<button class="btn btn-outline-secondary" type="submit">Search</button>
					</div>
				</div>
				<div class="col-md-2">
					<select class="form-select sort-by-select" aria-label="Sort by">
						<option value="id" selected>Sort by ID</option>
						<option value="name">Sort by Name</option>
					</select>
				</div>
				<div class="col-md-2">
					<select class="form-select sort-direction-select" aria-label="Sort direction">
						<option value="desc" selected>Descending</option>
						<option value="asc">Ascending</option>
					</select>
				</div>
			</form>
			<div class="table-responsive">
				<table class="table table-striped item-table" id="overlaysTable">
					<thead>
					<tr>
						<th>Preview</th>
						<th>Name</th>
						<th>Keywords</th>
						<th style="width: 135px;">Actions</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
			<nav>
				<ul class="pagination justify-content-center" id="overlaysPagination"></ul>
			</nav>
		</div>
	</div> <!-- /tab-content -->
	
	@include('admin.partials.admin-modals')
@endsection
