@extends('layouts.admin')

@section('content')
	<div class="d-flex justify-content-end mb-3">
		<button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#uploadCoverZipModal">
			<i class="fas fa-file-archive"></i> Upload Covers ZIP
		</button>
		<button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addCoverModal">
			<i class="fas fa-plus-circle"></i> Add New Cover
		</button>
		<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
			<i class="fas fa-plus-circle"></i> Add New Template
		</button>
	</div>
	
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
			<!-- Search and Filter Form -->
			<form class="mb-3 search-form row g-3 align-items-center" data-type="covers">
				<div class="col-md-6 col-lg-7">
					<div class="input-group">
						<input type="search" class="form-control search-input"
						       placeholder="Search Covers (Name, Caption, Keywords, Categories)..." aria-label="Search Covers">
						<button class="btn btn-outline-secondary" type="submit">Search</button>
					</div>
				</div>
				<div class="col-md-3 col-lg-3">
					<select class="form-select cover-type-filter admin-cover-type-dropdown" data-type="covers"
					        aria-label="Filter by Cover Type">
						<option value="">All Cover Types</option>
						<!-- Populated by JS -->
					</select>
				</div>
				<div class="col-md-3 col-lg-2">
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
			<!-- Search and Filter Form -->
			<form class="mb-3 search-form row g-3 align-items-center" data-type="templates">
				<div class="col-md-9">
					<div class="input-group">
						<input type="search" class="form-control search-input" placeholder="Search Templates (Name, Keywords)..."
						       aria-label="Search Templates">
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
			</form>
			<div class="table-responsive">
				<table class="table table-striped item-table" id="templatesTable">
					<thead>
					<tr>
						<th>Cover Image</th>
						<th>Full Cover Preview</th>
						<th style="width: 200px;">Name/Cover Type</th>
						<th>Keywords</th>
						<th>Text Placements</th>
						<th style="width: 135px;">Actions</th>
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
			<form class="mb-3 search-form" data-type="elements">
				<div class="input-group">
					<input type="search" class="form-control search-input" placeholder="Search Elements (Name, Keywords)..."
					       aria-label="Search Elements">
					<button class="btn btn-outline-secondary" type="submit">Search</button>
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
			<form class="mb-3 search-form" data-type="overlays">
				<div class="input-group">
					<input type="search" class="form-control search-input" placeholder="Search Overlays (Name, Keywords)..."
					       aria-label="Search Overlays">
					<button class="btn btn-outline-secondary" type="submit">Search</button>
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
	
	<!-- Add Cover Modal -->
	<div class="modal fade" id="addCoverModal" tabindex="-1" aria-labelledby="addCoverModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<form id="uploadCoverForm" enctype="multipart/form-data">
					<div class="modal-header">
						<h5 class="modal-title" id="addCoverModalLabel">Add New Cover</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<input type="hidden" name="item_type" value="covers">
						<div class="mb-3">
							<label for="coverName" class="form-label">Name</label>
							<input type="text" class="form-control" id="coverName" name="name" required>
						</div>
						<div class="mb-3">
							<label for="coverMainImage" class="form-label">Main Cover Image (PNG, JPG, GIF)</label>
							<input type="file" class="form-control" id="coverMainImage" name="main_image_file"
							       accept="image/png, image/jpeg, image/gif" required>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="coverMockup2D" class="form-label">2D Mockup (Optional)</label>
								<input type="file" class="form-control" id="coverMockup2D" name="mockup_2d_file"
								       accept="image/png, image/jpeg, image/gif">
							</div>
							<div class="col-md-6 mb-3">
								<label for="coverMockup3D" class="form-label">3D Mockup (Optional)</label>
								<input type="file" class="form-control" id="coverMockup3D" name="mockup_3d_file"
								       accept="image/png, image/jpeg, image/gif">
							</div>
						</div>
						<div class="mb-3">
							<label for="coverFullCoverImage" class="form-label">Full Cover Image (e.g., for print, Optional)</label>
							<input type="file" class="form-control" id="coverFullCoverImage" name="full_cover_file"
							       accept="image/png, image/jpeg, image/gif">
						</div>
						<div class="mb-3">
							<label for="coverCoverType" class="form-label">Cover Type</label>
							<select class="form-select admin-cover-type-dropdown" id="coverCoverType" name="cover_type_id">
								<option value="">Select Cover Type</option> <!-- Populated by JS -->
							</select>
						</div>
						<div class="mb-3">
							<label for="coverCaption" class="form-label">Caption (Optional)</label>
							<textarea class="form-control" id="coverCaption" name="caption" rows="2"></textarea>
						</div>
						<div class="mb-3">
							<label for="coverKeywords" class="form-label">Keywords (comma-separated)</label>
							<input type="text" class="form-control" id="coverKeywords" name="keywords">
						</div>
						<div class="mb-3">
							<label for="coverCategories" class="form-label">Categories (comma-separated)</label>
							<input type="text" class="form-control" id="coverCategories" name="categories">
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary">Upload Cover</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<!-- Add Template Modal -->
	<div class="modal fade" id="addTemplateModal" tabindex="-1" aria-labelledby="addTemplateModalLabel"
	     aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<form id="uploadTemplateForm" enctype="multipart/form-data">
					<div class="modal-header">
						<h5 class="modal-title" id="addTemplateModalLabel">Add New Template</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<input type="hidden" name="item_type" value="templates">
						<div class="mb-3">
							<label for="templateName" class="form-label">Name</label>
							<input type="text" class="form-control" id="templateName" name="name" required>
						</div>
						<div class="mb-3">
							<label for="templateCoverImage" class="form-label">Template Cover Image (PNG, JPG, GIF)</label>
							<input type="file" class="form-control" id="templateCoverImage" name="cover_image_file"
							       accept="image/png, image/jpeg, image/gif" required>
						</div>
						<div class="mb-3">
							<label for="templateJsonFile" class="form-label">Template JSON File (.json)</label>
							<input type="file" class="form-control" id="templateJsonFile" name="json_file" accept=".json" required>
						</div>
						<hr>
						<h6 class="mb-3">Full Cover Version (Optional)</h6>
						<div class="mb-3">
							<label for="templateFullCoverImage" class="form-label">Full Cover Image (Optional)</label>
							<input type="file" class="form-control" id="templateFullCoverImage" name="full_cover_image_file"
							       accept="image/png, image/jpeg, image/gif">
						</div>
						<div class="mb-3">
							<label for="templateFullCoverJsonFile" class="form-label">Full Cover JSON File (Optional)</label>
							<input type="file" class="form-control" id="templateFullCoverJsonFile" name="full_cover_json_file"
							       accept=".json">
						</div>
						<hr>
						<div class="mb-3">
							<label for="templateCoverType" class="form-label">Cover Type</label>
							<select class="form-select admin-cover-type-dropdown" id="templateCoverType" name="cover_type_id">
								<option value="">Select Cover Type</option> <!-- Populated by JS -->
							</select>
						</div>
						<div class="mb-3">
							<label for="templateKeywords" class="form-label">Keywords (comma-separated)</label>
							<input type="text" class="form-control" id="templateKeywords" name="keywords">
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary">Upload Template</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	
	<!-- Edit Item Modal -->
	<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<form id="editItemForm" enctype="multipart/form-data">
					<div class="modal-header">
						<h5 class="modal-title" id="editItemModalLabel">Edit Item</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<input type="hidden" name="id" id="editItemId">
						<input type="hidden" name="item_type" id="editItemType">
						<div class="mb-3">
							<label for="editItemName" class="form-label">Name</label>
							<input type="text" class="form-control" id="editItemName" name="name" required>
						</div>
						
						<!-- Cover Type Dropdown (for Covers and Templates) -->
						<div class="mb-3 edit-field edit-field-covers edit-field-templates">
							<label for="editItemCoverType" class="form-label">Cover Type</label>
							<select class="form-select admin-cover-type-dropdown" id="editItemCoverType" name="cover_type_id">
								<option value="">Select Cover Type</option> <!-- Populated by JS -->
							</select>
						</div>
						
						<!-- Fields specific to Covers -->
						<div class="edit-field edit-field-covers">
							<div class="mb-3">
								<label for="editItemCaption" class="form-label">Caption</label>
								<textarea class="form-control" id="editItemCaption" name="caption" rows="2"></textarea>
							</div>
							<div class="mb-3">
								<label for="editItemCategories" class="form-label">Categories (comma-separated)</label>
								<input type="text" class="form-control" id="editItemCategories" name="categories">
							</div>
							<div class="mb-3">
								<label for="editItemTextPlacements" class="form-label">Text Placements (e.g.,
									top-light,middle-dark)</label>
								<input type="text" class="form-control" id="editItemTextPlacements" name="text_placements">
							</div>
							<div class="mb-3">
								<label for="editCoverMainImageFile" class="form-label">Replace Main Image (Optional)</label>
								<input type="file" class="form-control" id="editCoverMainImageFile" name="main_image_file"
								       accept="image/png, image/jpeg, image/gif">
								<div id="editCoverMainImagePreview" class="mt-2 preview-container"><!-- Content via JS --></div>
							</div>
							<div class="row">
								<div class="col-md-6 mb-3">
									<label for="editCoverMockup2DFile" class="form-label">Replace 2D Mockup (Optional)</label>
									<input type="file" class="form-control" id="editCoverMockup2DFile" name="mockup_2d_file"
									       accept="image/png, image/jpeg, image/gif">
									<div id="editCoverMockup2DPreview" class="mt-2 preview-container"></div>
								</div>
								<div class="col-md-6 mb-3">
									<label for="editCoverMockup3DFile" class="form-label">Replace 3D Mockup (Optional)</label>
									<input type="file" class="form-control" id="editCoverMockup3DFile" name="mockup_3d_file"
									       accept="image/png, image/jpeg, image/gif">
									<div id="editCoverMockup3DPreview" class="mt-2 preview-container"></div>
								</div>
							</div>
							<div class="mb-3">
								<label for="editCoverFullCoverFile" class="form-label">Replace Full Cover Image (Optional)</label>
								<input type="file" class="form-control" id="editCoverFullCoverFile" name="full_cover_file"
								       accept="image/png, image/jpeg, image/gif">
								<div id="editCoverFullCoverPreview" class="mt-2 preview-container"></div>
							</div>
						</div>
						
						<!-- Fields specific to Templates -->
						<div class="edit-field edit-field-templates">
							<div class="mb-3"> <!-- Text placements for templates -->
								<label for="editTemplateTextPlacements" class="form-label">Text Placements (e.g.,
									top-light,middle-dark)</label>
								<input type="text" class="form-control" id="editTemplateTextPlacements" name="text_placements">
							</div>
							<div class="mb-3">
								<label for="editTemplateCoverImageFile" class="form-label">Replace Cover Image (Optional)</label>
								<input type="file" class="form-control" id="editTemplateCoverImageFile" name="cover_image_file"
								       accept="image/png, image/jpeg, image/gif">
								<div id="editTemplateCoverImagePreview" class="mt-2 preview-container"></div>
							</div>
							<div class="mb-3">
								<label for="editTemplateJsonFile" class="form-label">Replace JSON File (Optional)</label>
								<input type="file" class="form-control" id="editTemplateJsonFile" name="json_file" accept=".json">
								<div id="editTemplateJsonInfo" class="mt-2 small text-muted"></div>
							</div>
							<hr>
							<h6 class="mb-3">Full Cover Version (Optional)</h6>
							<div class="mb-3">
								<label for="editTemplateFullCoverImageFile" class="form-label">Replace Full Cover Image
									(Optional)</label>
								<input type="file" class="form-control" id="editTemplateFullCoverImageFile" name="full_cover_image_file"
								       accept="image/png, image/jpeg, image/gif">
								<div id="editTemplateFullCoverImagePreview" class="mt-2 preview-container"></div>
							</div>
							<div class="mb-3">
								<label for="editTemplateFullCoverJsonFile" class="form-label">Replace Full Cover JSON (Optional)</label>
								<input type="file" class="form-control" id="editTemplateFullCoverJsonFile" name="full_cover_json_file"
								       accept=".json">
								<div id="editTemplateFullCoverJsonInfo" class="mt-2 small text-muted"></div>
							</div>
						</div>
						
						<!-- Fields for Elements/Overlays (assuming simple image_file) -->
						<div class="mb-3 edit-field edit-field-elements edit-field-overlays">
							<label for="editItemImageFile" class="form-label">Replace Image (Optional)</label>
							<input type="file" class="form-control" id="editItemImageFile" name="image_file"
							       accept="image/png, image/jpeg, image/gif">
							<div id="editCurrentImagePreview" class="mt-2 preview-container"></div>
						</div>
						
						<!-- Common field: Keywords -->
						<div class="mb-3 edit-field edit-field-covers edit-field-elements edit-field-overlays edit-field-templates">
							<label for="editItemKeywords" class="form-label">Keywords (comma-separated)</label>
							<input type="text" class="form-control" id="editItemKeywords" name="keywords">
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary" id="saveEditButton">Save Changes</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	
	<!-- Generate Similar Template Modal -->
	<div class="modal fade" id="generateSimilarTemplateModal" tabindex="-1"
	     aria-labelledby="generateSimilarTemplateModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<form id="generateSimilarTemplateForm">
					<div class="modal-header">
						<h5 class="modal-title" id="generateSimilarTemplateModalLabel">Generate Similar Template with AI</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<input type="hidden" id="aiOriginalTemplateId" name="original_template_id">
						<input type="hidden" id="aiOriginalTemplateJsonContent" name="original_json_content">
						<div class="mb-3">
							<p><strong>Original Template (for reference):</strong></p>
							<pre id="aiOriginalTemplatePreview"
							     style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; white-space: pre-wrap; word-break: break-all;">Loading original template...</pre>
						</div>
						<div class="mb-3">
							<label for="aiTemplatePrompt" class="form-label">Your Prompt for AI:</label>
							<textarea class="form-control" id="aiTemplatePrompt" name="user_prompt" rows="6" required></textarea>
							<div class="form-text">Guide the AI to modify the template. E.g., "Change theme to cyberpunk, main color
								to neon pink, and add a placeholder for a subtitle."
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary" id="submitAiGenerateTemplateButton">Generate & Download File
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<!-- Assign Templates Modal -->
	<div class="modal fade" id="assignTemplatesModal" tabindex="-1" aria-labelledby="assignTemplatesModalLabel"
	     aria-hidden="true">
		<div class="modal-dialog modal-xl">
			<div class="modal-content p-0">
				<form id="assignTemplatesForm">
					<div class="modal-header py-2 px-3">
						<h5 class="modal-title" id="assignTemplatesModalLabel">Assign Templates</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body p-2">
						<input type="hidden" id="assignTemplatesCoverId" name="cover_id">
						<div class="row g-1">
							<div class="col-md-7">
								<p class="mb-0"><strong>Cover:</strong> <span id="assignTemplatesCoverName"></span></p>
								<p class="mb-1"><strong>Cover Type:</strong> <span id="assignTemplatesCoverTypeName"></span></p>
								<hr class="my-1">
								<h6 class="mb-1">Available Templates (for this cover type):</h6>
								<div id="aiChoiceProgressArea" class="my-2" style="display: none;">
									<div class="d-flex justify-content-between align-items-center mb-1">
										<h6 class="mb-0 small">AI Processing Templates:</h6>
										<span id="aiChoiceProgressText" class="small text-muted"></span>
									</div>
									<div class="progress" style="height: 10px;">
										<div id="aiChoiceProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
										     role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0"
										     aria-valuemax="100"></div>
									</div>
								</div>
								<div id="assignableTemplatesList"
								     style="max-height: 450px; overflow-y: auto; padding: 5px; padding-left: 15px; border: 1px solid #eee; border-radius: 4px;">
									<p class="text-center mb-0">Loading templates...</p>
								</div>
								<div id="noAssignableTemplatesMessage" class="alert alert-info mt-1 py-1 px-2"
								     style="display: none;"></div>
							</div>
							<div class="col-md-5">
								<h6 class="mb-1">Cover Preview:</h6>
								<div id="assignTemplatesCoverPreviewContainer">
									<img id="assignTemplatesCoverPreviewImage" src="" alt="Cover Preview">
									<img id="assignTemplatesTemplateOverlay" src="" alt="Template Overlay">
									<span id="assignTemplatesPreviewPlaceholder">No preview available</span>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer py-1 px-2">
						<button type="button" class="btn btn-info btn-sm me-auto" id="aiChooseTemplatesButton">
							<i class="fas fa-robot"></i> Use AI to Choose
						</button>
						<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary btn-sm" id="saveTemplateAssignmentsButton">Save Assignments
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<!-- Edit Text Placements Modal -->
	<div class="modal fade" id="editTextPlacementsModal" tabindex="-1" aria-labelledby="editTextPlacementsModalLabel"
	     aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<form id="editTextPlacementsForm">
					<div class="modal-header">
						<h5 class="modal-title" id="editTextPlacementsModalLabel">Edit Text Placements</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<input type="hidden" id="textPlacementsItemId" name="item_id">
						<input type="hidden" id="textPlacementsItemType" name="item_type">
						<p>For item: <strong id="textPlacementsItemName"></strong></p>
						<hr>
						@php
							$areas = ['top', 'middle', 'bottom', 'left', 'right'];
							$tones = ['light', 'dark'];
						@endphp
						@foreach ($areas as $area)
							<div class="mb-3 row align-items-center">
								<div class="col-sm-4 col-md-3">
									<div class="form-check">
										<input class="form-check-input area-checkbox" type="checkbox" value="{{ $area }}"
										       id="tp_area_{{ $area }}">
										<label class="form-check-label" for="tp_area_{{ $area }}">
											{{ ucfirst($area) }}
										</label>
									</div>
								</div>
								<div class="col-sm-8 col-md-9 tp-tone-group" id="tp_tone_group_{{ $area }}" style="display: none;">
									@foreach ($tones as $tone)
										<div class="form-check form-check-inline">
											<input class="form-check-input tone-radio" type="radio" name="tp_tone_{{ $area }}"
											       id="tp_tone_{{ $area }}_{{ $tone }}" value="{{ $tone }}" disabled>
											<label class="form-check-label" for="tp_tone_{{ $area }}_{{ $tone }}">{{ ucfirst($tone) }}</label>
										</div>
									@endforeach
								</div>
							</div>
						@endforeach
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary" id="saveTextPlacementsButton">Save Changes</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<div class="modal fade" id="uploadCoverZipModal" tabindex="-1" aria-labelledby="uploadCoverZipModalLabel"
	     aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<form id="uploadCoverZipForm" enctype="multipart/form-data">
					<div class="modal-header">
						<h5 class="modal-title" id="uploadCoverZipModalLabel">Upload Covers from ZIP</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="mb-3 form-check">
							<input type="checkbox" class="form-check-input" id="processLocalTempFolder"
							       name="process_local_temp_folder" value="1">
							<label class="form-check-label" for="processLocalTempFolder">Process files from <code>storage/app/temp_covers</code>
								(ignores ZIP file input)</label>
						</div>
						<div class="mb-3">
							<label for="coverZipFile" class="form-label">ZIP File (.zip)</label>
							<input type="file" class="form-control" id="coverZipFile" name="cover_zip_file" accept=".zip" required>
							<div class="form-text small">
								ZIP file should contain sets of images based on a common prefix <code>[covername]</code>:
								<ul>
									<li><code>[covername].jpg</code> (or .png, .gif) - Main image (Required)</li>
									<li><code>[covername]-front-mockup.png</code> - 2D Mockup (Optional)</li>
									<li><code>[covername]-3d-mockup.png</code> - 3D Mockup (Optional)</li>
									<li><code>[covername]-full-cover.jpg</code> (or .png, .gif) - Full Cover (Optional)</li>
								</ul>
								Existing covers are matched if <code>[covername].ext</code> (e.g. <code>my-book.jpg</code>) matches the
								filename part of an existing cover's main image path. Matched covers are updated. Unmatched groups
								create new covers.
							</div>
						</div>
						<div class="mb-3">
							<label for="zipDefaultCoverTypeId" class="form-label">Default Cover Type for New Covers</label>
							<select class="form-select admin-cover-type-dropdown" id="zipDefaultCoverTypeId"
							        name="default_cover_type_id">
								<option value="">Select Cover Type (defaults to ID 1)</option>
								<!-- Populated by JS (AppAdmin.CoverTypes) -->
							</select>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary" id="submitUploadCoverZipButton">Upload & Process ZIP</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<style>
      .preview-container img {
          max-width: 100%;
          max-height: 120px;
          border: 1px solid #ddd;
          margin-top: 5px;
          object-fit: contain;
      }
	</style>
@endsection
