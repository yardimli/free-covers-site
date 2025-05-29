{{-- free-cover-site/resources/views/admin/blog/posts/edit.blade.php --}}
@extends('layouts.admin')
@section('title', 'Edit Blog Post')

@section('content')
	<div class="container-fluid">
		<div class="d-flex justify-content-between align-items-center my-4">
			<h1>Edit Blog Post: <small class="text-muted">{{ Str::limit($post->title, 50) }}</small></h1>
			<a href="{{ route('admin.blog.index') }}" class="btn btn-secondary">Back to List</a>
		</div>
		
		@if(session('success_message'))
			<div class="alert alert-success">{{ session('success_message') }}</div>
		@endif
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
		
		<form action="{{ route('admin.blog.posts.update', $post->id) }}" method="POST" enctype="multipart/form-data" id="editBlogPostForm">
			@csrf
			@method('POST') {{-- Laravel handles PUT/PATCH via _method field with POST --}}
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-9">
							<div class="mb-3">
								<label for="title" class="form-label">Title <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="title" name="title" value="{{ old('title', $post->title) }}" required>
							</div>
							
							<div class="mb-3">
								<label for="content_editor_container" class="form-label">Content <span class="text-danger">*</span></label>
								<input type="hidden" name="content" id="content_input">
								<div id="content_editor_container" style="height: 400px; border: 1px solid #ced4da;">
									{{-- Quill editor will be initialized here --}}
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="mb-3">
								<label for="blog_category_id" class="form-label">Category <span class="text-danger">*</span></label>
								<select class="form-select" id="blog_category_id" name="blog_category_id" required>
									<option value="">Select Category</option>
									@foreach($categories as $category)
										<option value="{{ $category->id }}" {{ old('blog_category_id', $post->blog_category_id) == $category->id ? 'selected' : '' }}>
											{{ $category->name }}
										</option>
									@endforeach
								</select>
							</div>
							<div class="mb-3">
								<label for="short_description" class="form-label">Short Description</label>
								<textarea class="form-control" id="short_description" name="short_description" rows="3">{{ old('short_description', $post->short_description) }}</textarea>
							</div>
							<div class="mb-3">
								<label for="keywords" class="form-label">Keywords (comma-separated)</label>
								<input type="text" class="form-control" id="keywords" name="keywords" value="{{ old('keywords', $post->keywords_string) }}">
							</div>
							<div class="mb-3">
								<label for="image_file" class="form-label">Replace Featured Image</label>
								<input type="file" class="form-control" id="image_file" name="image_file" accept="image/*">
								@if($post->image_path)
									<div class="mt-2">
										<small>Current Image:</small><br>
										<img src="{{ Storage::disk('public')->url($post->image_path) }}" alt="Current Image" style="max-height: 100px; border:1px solid #ddd; object-fit: contain;" class="mb-1">
									</div>
								@endif
								<img id="image_file_preview" src="#" alt="New Image Preview" class="img-fluid mt-1" style="display:none; max-height: 150px; border:1px solid #ddd; object-fit: contain;">
							</div>
							<div class="mb-3">
								<label for="status" class="form-label">Status <span class="text-danger">*</span></label>
								<select class="form-select" id="status" name="status" required>
									<option value="draft" {{ old('status', $post->status) == 'draft' ? 'selected' : '' }}>Draft</option>
									<option value="published" {{ old('status', $post->status) == 'published' ? 'selected' : '' }}>Published</option>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="card-footer text-end">
					<a href="{{ route('admin.blog.index') }}" class="btn btn-secondary">Cancel</a>
					<button type="submit" class="btn btn-primary" id="updatePostButton">Update Post</button>
				</div>
			</div>
		</form>
	</div>
@endsection

@push('scripts')
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const initialPostContent = {!! json_encode(old('content', $post->content)) !!} || '';
			var quill = new Quill('#content_editor_container', {
				theme: 'snow',
				modules: {
					toolbar: [
						[{ 'header': [1, 2, 3, 4, 5, 6, false] }],
						['bold', 'italic', 'underline', 'strike'],
						[{ 'list': 'ordered'}, { 'list': 'bullet' }],
						[{ 'indent': '-1'}, { 'indent': '+1' }],
						[{ 'align': [] }],
						['link'],
						// ['image'],
						['clean']
					]
				}
			});
			
			if (initialPostContent) {
				quill.clipboard.dangerouslyPasteHTML(initialPostContent);
			}
			
			const editBlogPostForm = document.getElementById('editBlogPostForm');
			if (editBlogPostForm) {
				editBlogPostForm.addEventListener('submit', function() {
					document.getElementById('content_input').value = quill.root.innerHTML;
				});
			}
			
			const imageInput = document.getElementById('image_file');
			const imagePreview = document.getElementById('image_file_preview');
			if (imageInput) {
				imageInput.addEventListener('change', function(event) {
					const file = event.target.files[0];
					const reader = new FileReader();
					reader.onloadend = function() {
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
		});
	</script>
@endpush
