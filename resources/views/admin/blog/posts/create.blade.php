{{-- free-cover-site/resources/views/admin/blog/posts/create.blade.php --}}
@extends('layouts.admin')
@section('title', 'Create Blog Post')

@section('content')
	<div class="container-fluid">
		<div class="d-flex justify-content-between align-items-center my-4">
			<h1>Create New Blog Post</h1>
			<a href="{{ route('admin.blog.index') }}" class="btn btn-secondary">Back to List</a>
		</div>
		
		@if(session('error_message'))
			<div class="alert alert-danger">{{ session('error_message') }}</div>
		@endif
		
		@if ($errors->any())
			<div class="alert alert-danger">
				<ul class="mb-0">
					@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		@endif
		
		<form action="{{ route('admin.blog.posts.store') }}" method="POST" enctype="multipart/form-data"
		      id="createBlogPostForm">
			@csrf
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-9">
							<div class="mb-3">
								<label for="title" class="form-label">Title <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" required>
							</div>
							
							<div class="mb-3">
								<label for="content_editor_container" class="form-label">Content <span
										class="text-danger">*</span></label>
								<input type="hidden" name="content" id="content_input">
								<div id="content_editor_container" style="height: 400px; border: 1px solid #ced4da;">
									{{-- Quill editor will be initialized here --}}
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="mb-3">
								<button type="button" class="btn btn-info w-100 mb-3" id="aiGenerateContentButton">
									<i class="fas fa-robot"></i> Generate with AI
								</button>
							</div>
							<div class="mb-3">
								<label for="blog_category_id" class="form-label">Category <span class="text-danger">*</span></label>
								<select class="form-select" id="blog_category_id" name="blog_category_id" required>
									<option value="">Select Category</option>
									@foreach($categories as $category)
										<option
											value="{{ $category->id }}" {{ old('blog_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
									@endforeach
								</select>
							</div>
							<div class="mb-3">
								<label for="short_description" class="form-label">Short Description</label>
								<textarea class="form-control" id="short_description" name="short_description"
								          rows="3">{{ old('short_description') }}</textarea>
							</div>
							<div class="mb-3">
								<label for="keywords" class="form-label">Keywords (comma-separated)</label>
								<input type="text" class="form-control" id="keywords" name="keywords" value="{{ old('keywords') }}">
							</div>
							<div class="mb-3">
								<label for="image_file" class="form-label">Featured Image</label>
								<input type="file" class="form-control" id="image_file" name="image_file" accept="image/*">
								<img id="image_file_preview" src="#" alt="Image Preview" class="img-fluid mt-2"
								     style="display:none; max-height: 150px; border:1px solid #ddd; object-fit: contain;">
							</div>
							<div class="mb-3">
								<label for="status" class="form-label">Status <span class="text-danger">*</span></label>
								<select class="form-select" id="status" name="status" required>
									<option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
									<option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Published</option>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="card-footer text-end">
					<a href="{{ route('admin.blog.index') }}" class="btn btn-secondary">Cancel</a>
					<button type="submit" class="btn btn-primary" id="savePostButton">Save Post</button>
				</div>
			</div>
		</form>
	</div>
	
	<!-- AI Topic Modal -->
	<div class="modal fade" id="aiTopicModal" tabindex="-1" aria-labelledby="aiTopicModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="aiTopicModalLabel">Generate Content with AI</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label for="aiTopicInput" class="form-label">Enter Blog Post Topic:</label>
						<textarea class="form-control" id="aiTopicInput" rows="3"
						          placeholder="e.g., The future of AI in graphic design"></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="submitAiTopicButton">Generate</button>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const oldContent = {!! json_encode(old('content')) !!} || '';
			var quill = new Quill('#content_editor_container', {
				theme: 'snow',
				modules: {
					toolbar: [
						[{'header': [1, 2, 3, 4, 5, 6, false]}],
						['bold', 'italic', 'underline', 'strike'],
						[{'list': 'ordered'}, {'list': 'bullet'}],
						[{'indent': '-1'}, {'indent': '+1'}], // outdent/indent
						[{'align': []}], // text alignment
						['link'], // link
						// ['image'], // image button, requires custom handler
						['clean'] // remove formatting
					]
				}
			});
			
			if (oldContent) {
				quill.clipboard.dangerouslyPasteHTML(oldContent);
			}
			
			const createBlogPostForm = document.getElementById('createBlogPostForm');
			if (createBlogPostForm) {
				createBlogPostForm.addEventListener('submit', function () {
					document.getElementById('content_input').value = quill.root.innerHTML;
				});
			}
			
			const imageInput = document.getElementById('image_file');
			const imagePreview = document.getElementById('image_file_preview');
			if (imageInput) {
				imageInput.addEventListener('change', function (event) {
					const file = event.target.files[0];
					const reader = new FileReader();
					reader.onloadend = function () {
						imagePreview.src = reader.result;
						imagePreview.style.display = 'block';
					}
					if (file) {
						reader.readAsDataURL(file);
					} else {
						imagePreview.src = "#";
						imagePreview.style.display = 'none';
					}
				});
			}
			
			// AI Content Generation
			const aiGenerateButton = document.getElementById('aiGenerateContentButton');
			const aiTopicModalEl = document.getElementById('aiTopicModal');
			const aiTopicModal = aiTopicModalEl ? new bootstrap.Modal(aiTopicModalEl) : null;
			const aiTopicInput = document.getElementById('aiTopicInput');
			const submitAiTopicButton = document.getElementById('submitAiTopicButton');
			
			if (aiGenerateButton && aiTopicModal) {
				aiGenerateButton.addEventListener('click', function () {
					aiTopicModal.show();
				});
			}
			
			if (submitAiTopicButton && aiTopicInput) {
				submitAiTopicButton.addEventListener('click', function () {
					const topic = aiTopicInput.value.trim();
					const categoryId = document.getElementById('blog_category_id').value;
					
					if (!topic) {
						AppAdmin.Utils.showAlert('Please enter a topic.', 'warning');
						return;
					}
					
					const originalButtonText = submitAiTopicButton.innerHTML;
					submitAiTopicButton.disabled = true;
					submitAiTopicButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
					
					$.ajax({
						url: window.adminRoutes.generateAiBlogPost,
						type: 'POST',
						data: {
							topic: topic,
							blog_category_id: categoryId,
							_token: $('meta[name="csrf-token"]').attr('content')
						},
						dataType: 'json',
						success: function (response) {
							if (response.success && response.data) {
								AppAdmin.Utils.showAlert('AI content generated!', 'success');
								document.getElementById('title').value = response.data.title || '';
								document.getElementById('short_description').value = response.data.short_description || '';
								
								quill.clipboard.dangerouslyPasteHTML(response.data.content || '');
								
								document.getElementById('keywords').value = Array.isArray(response.data.keywords) ? response.data.keywords.join(', ') : '';
								aiTopicModal.hide();
								aiTopicInput.value = ''; // Clear topic input
							} else {
								AppAdmin.Utils.showAlert('Error generating AI post: ' + (response.message || 'Unknown error'), 'danger');
							}
						},
						error: function (xhr) {
							AppAdmin.Utils.showAlert('AJAX Error generating AI post: ' + (xhr.responseText || 'Request failed'), 'danger');
						},
						complete: function () {
							submitAiTopicButton.disabled = false;
							submitAiTopicButton.innerHTML = originalButtonText;
						}
					});
				});
			}
		});
	</script>
@endpush
