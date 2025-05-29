{{-- free-cover-site/resources/views/admin/blog/index.blade.php --}}
@extends('layouts.admin')
@section('title', 'Blog Management')

@section('content')
	<div class="container-fluid">
		<div class="d-flex justify-content-between align-items-center my-4">
			<h1>Blog Management</h1>
			<div>
				<a href="{{ route('admin.blog.posts.create') }}" class="btn btn-success me-2">
					<i class="fas fa-plus-circle"></i> Add New Post
				</a>
				{{-- AI Blog Post button removed from here, can be added to create page --}}
				<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manageBlogCategoriesModal">
					<i class="fas fa-tags"></i> Manage Categories
				</button>
			</div>
		</div>
		
		@if(session('success_message'))
			<div class="alert alert-success alert-dismissible fade show" role="alert">
				{{ session('success_message') }}
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>
		@endif
		@if(session('error_message'))
			<div class="alert alert-danger alert-dismissible fade show" role="alert">
				{{ session('error_message') }}
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>
		@endif
		
		<!-- Blog Posts List -->
		<h4>Blog Posts</h4>
		<form class="mb-3 row gx-2 gy-2 align-items-center" id="blogPostsFilterForm">
			<div class="col-md-5">
				<input type="search" class="form-control" name="search" placeholder="Search Posts (Title, Desc, Keywords)...">
			</div>
			<div class="col-md-3">
				<select class="form-select" name="category_id" id="blogPostFilterCategory">
					<option value="">All Categories</option>
					<!-- Populated by JS -->
				</select>
			</div>
			<div class="col-md-2">
				<select class="form-select" name="status">
					<option value="">All Statuses</option>
					<option value="draft">Draft</option>
					<option value="published">Published</option>
				</select>
			</div>
			<div class="col-md-2">
				<button class="btn btn-outline-secondary w-100" type="submit">Filter/Search</button>
			</div>
		</form>
		
		<div class="table-responsive">
			<table class="table table-striped" id="blogPostsTable">
				<thead>
				<tr>
					<th>Image</th>
					<th>Title</th>
					<th>Category</th>
					<th>Status</th>
					<th>Keywords</th>
					<th>Published At</th>
					<th style="width: 120px;">Actions</th>
				</tr>
				</thead>
				<tbody><!-- Populated by JS --></tbody>
			</table>
		</div>
		<nav>
			<ul class="pagination justify-content-center" id="blogPostsPagination"></ul>
		</nav>
	</div>
	
	<!-- Manage Blog Categories Modal (Remains the same) -->
	<div class="modal fade" id="manageBlogCategoriesModal" tabindex="-1" aria-labelledby="manageBlogCategoriesModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="manageBlogCategoriesModalLabel">Manage Blog Categories</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form id="blogCategoryForm" class="mb-3">
						<input type="hidden" name="category_id" id="blogCategoryId">
						<div class="input-group">
							<input type="text" class="form-control" id="blogCategoryName" name="name" placeholder="New Category Name" required>
							<button type="submit" class="btn btn-success" id="saveBlogCategoryButton">Add Category</button>
							<button type="button" class="btn btn-outline-secondary" id="cancelEditBlogCategoryButton" style="display:none;">Cancel Edit</button>
						</div>
					</form>
					<input type="text" id="searchBlogCategoryInput" class="form-control mb-2" placeholder="Search categories...">
					<div style="max-height: 300px; overflow-y: auto;">
						<ul class="list-group" id="blogCategoriesList">
							<!-- Populated by JS -->
						</ul>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>
	
	{{-- Removed Add/Edit Blog Post Modal and AI Blog Post Modal --}}
@endsection

@push('styles')
	{{-- Styles for image preview if any, but it's inline now --}}
@endpush
