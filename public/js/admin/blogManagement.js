// public/js/admin/blogManagement.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.BlogManagement = (function () {
	const {showAlert, escapeHtml, renderKeywords} = AppAdmin.Utils;
	let manageBlogCategoriesModal;
	let $blogCategoryForm;
	
	function init() {
		const manageCatModalEl = document.getElementById('manageBlogCategoriesModal');
		if (manageCatModalEl) manageBlogCategoriesModal = new bootstrap.Modal(manageCatModalEl);
		
		$blogCategoryForm = $('#blogCategoryForm');
		
		if ($('#blogPostsTable').length > 0) { // On blog index page
			loadBlogCategories(); // For filter dropdown
			loadBlogPosts();
		}
		
		if ($blogCategoryForm.length) {
			$blogCategoryForm.on('submit', handleSaveBlogCategory);
		}
		
		const blogPostsFilterForm = document.getElementById('blogPostsFilterForm');
		if (blogPostsFilterForm) {
			$(blogPostsFilterForm).on('submit', function (e) {
				e.preventDefault();
				loadBlogPosts(1);
			});
		}
		
		const searchBlogCategoryInput = document.getElementById('searchBlogCategoryInput');
		if (searchBlogCategoryInput) {
			$(searchBlogCategoryInput).on('keyup', function () {
				loadBlogCategories($('#manageBlogCategoriesModal'));
			});
		}
		
		const blogCategoriesList = document.getElementById('blogCategoriesList');
		if (blogCategoriesList) {
			$(blogCategoriesList).on('click', '.edit-category-btn', handleEditCategoryClick);
			$(blogCategoriesList).on('click', '.delete-category-btn', handleDeleteCategoryClick);
		}
		
		const cancelEditBlogCategoryButton = document.getElementById('cancelEditBlogCategoryButton');
		if (cancelEditBlogCategoryButton) {
			$(cancelEditBlogCategoryButton).on('click', resetCategoryForm);
		}
		
		if (manageCatModalEl) {
			manageCatModalEl.addEventListener('hidden.bs.modal', () => {
				resetCategoryForm();
				const searchInput = document.getElementById('searchBlogCategoryInput');
				if (searchInput) $(searchInput).val('');
			});
		}
		
		const blogPostsTableBody = document.querySelector('#blogPostsTable tbody');
		if (blogPostsTableBody) {
			$(blogPostsTableBody).on('click', '.delete-post-btn', handleDeletePostClick);
		}
		
		const blogPostsPagination = document.getElementById('blogPostsPagination');
		if (blogPostsPagination) {
			$(blogPostsPagination).on('click', '.page-link', function (e) {
				e.preventDefault();
				const $link = $(this);
				if ($link.parent().hasClass('disabled') || $link.parent().hasClass('active')) return;
				const page = parseInt($link.data('page'), 10);
				loadBlogPosts(page);
			});
		}
	}
	
	// --- Blog Category Functions --- (Largely Unchanged)
	function loadBlogCategories($modalInstance = null) {
		const searchInputVal = $modalInstance
			? $modalInstance.find('#searchBlogCategoryInput').val()
			: (document.getElementById('searchBlogCategoryInput') ? document.getElementById('searchBlogCategoryInput').value : '');
		
		$.ajax({
			url: window.adminRoutes.blogCategoriesList,
			type: 'GET',
			data: {search: searchInputVal},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					const categories = response.data;
					const $categoryDropdowns = $('#blogPostFilterCategory'); // Only filter dropdown now from this central JS
					
					$categoryDropdowns.each(function () {
						const $dropdown = $(this);
						if (!$dropdown.length) return;
						const currentVal = $dropdown.val();
						$dropdown.find('option:not(:first-child)').remove(); // Keep "All Categories"
						categories.forEach(cat => {
							$dropdown.append(`<option value="${cat.id}">${escapeHtml(cat.name)}</option>`);
						});
						if (currentVal && $dropdown.find(`option[value="${currentVal}"]`).length > 0) {
							$dropdown.val(currentVal);
						}
					});
					
					const $categoriesList = $('#blogCategoriesList'); // For the manage categories modal
					if ($categoriesList.length > 0 && (!$modalInstance || ($modalInstance.length && $modalInstance.attr('id') === 'manageBlogCategoriesModal'))) {
						$categoriesList.empty();
						if (categories.length > 0) {
							categories.forEach(cat => {
								$categoriesList.append(`
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        ${escapeHtml(cat.name)}
                                        <span>
                                            <button class="btn btn-sm btn-outline-warning edit-category-btn me-1" data-id="${cat.id}" data-name="${escapeHtml(cat.name)}"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger delete-category-btn" data-id="${cat.id}"><i class="fas fa-trash"></i></button>
                                        </span>
                                    </li>
                                `);
							});
						} else {
							$categoriesList.append('<li class="list-group-item text-muted">No categories found.</li>');
						}
					}
				} else {
					showAlert('Error loading categories: ' + escapeHtml(response.message), 'danger');
				}
			},
			error: function (xhr) {
				showAlert('AJAX Error loading categories: ' + escapeHtml(xhr.responseText), 'danger');
			}
		});
	}
	
	function handleSaveBlogCategory(event) {
		event.preventDefault();
		const categoryId = $('#blogCategoryId').val();
		const categoryName = $('#blogCategoryName').val();
		const url = categoryId ? `${window.adminRoutes.blogCategoriesUpdateBase}/${categoryId}` : window.adminRoutes.blogCategoriesStore;
		const method = categoryId ? 'PUT' : 'POST';
		const $button = $('#saveBlogCategoryButton');
		const originalButtonText = $button.html();
		$button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
		
		$.ajax({
			url: url,
			type: method,
			data: {name: categoryName, _token: $('meta[name="csrf-token"]').attr('content')},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showAlert(response.message, 'success');
					resetCategoryForm();
					const manageCategoriesModalEl = document.getElementById('manageBlogCategoriesModal');
					if (manageCategoriesModalEl) {
						loadBlogCategories($(manageCategoriesModalEl));
					}
					loadBlogCategories(); // Reload dropdowns everywhere
				} else {
					let errorMsg = response.message || 'Validation failed.';
					if (response.errors) {
						errorMsg += '<ul>';
						$.each(response.errors, (key, value) => {
							errorMsg += `<li>${escapeHtml(value[0])}</li>`;
						});
						errorMsg += '</ul>';
					}
					showAlert(errorMsg, 'danger');
				}
			},
			error: function (xhr) {
				showAlert('AJAX Error saving category: ' + escapeHtml(xhr.responseText), 'danger');
			},
			complete: function () {
				$button.prop('disabled', false).html(originalButtonText);
			}
		});
	}
	
	function resetCategoryForm() {
		if ($blogCategoryForm.length) $blogCategoryForm[0].reset();
		$('#blogCategoryId').val('');
		$('#saveBlogCategoryButton').text('Add Category');
		$('#cancelEditBlogCategoryButton').hide();
	}
	
	function handleEditCategoryClick() {
		const categoryId = $(this).data('id');
		const categoryName = $(this).data('name');
		$('#blogCategoryId').val(categoryId);
		$('#blogCategoryName').val(categoryName);
		$('#saveBlogCategoryButton').text('Update Category');
		$('#cancelEditBlogCategoryButton').show();
	}
	
	function handleDeleteCategoryClick() {
		const categoryId = $(this).data('id');
		if (!confirm('Are you sure you want to delete this category? This cannot be undone.')) return;
		
		$.ajax({
			url: `${window.adminRoutes.blogCategoriesDestroyBase}/${categoryId}`,
			type: 'DELETE',
			data: {_token: $('meta[name="csrf-token"]').attr('content')},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showAlert(response.message, 'success');
					const manageCategoriesModalEl = document.getElementById('manageBlogCategoriesModal');
					if (manageCategoriesModalEl) {
						loadBlogCategories($(manageCategoriesModalEl));
					}
					loadBlogCategories();
				} else {
					showAlert('Error deleting category: ' + escapeHtml(response.message), 'danger');
				}
			},
			error: function (xhr) {
				showAlert('AJAX Error deleting category: ' + escapeHtml(xhr.responseText), 'danger');
			}
		});
	}
	
	// --- Blog Post Functions ---
	function loadBlogPosts(page = 1) {
		const $tableBody = $('#blogPostsTable tbody');
		const $paginationContainer = $('#blogPostsPagination');
		if (!$tableBody.length) return;
		
		const filterData = {
			page: page,
			search: $('#blogPostsFilterForm input[name="search"]').val(),
			category_id: $('#blogPostsFilterForm select[name="category_id"]').val(),
			status: $('#blogPostsFilterForm select[name="status"]').val(),
		};
		
		$tableBody.html('<tr><td colspan="7" class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading posts...</td></tr>');
		if ($paginationContainer.length) $paginationContainer.empty();
		
		$.ajax({
			url: window.adminRoutes.blogPostsList,
			type: 'GET',
			data: filterData,
			dataType: 'json',
			success: function (response) {
				$tableBody.empty();
				if (response.success && response.data.items.length > 0) {
					response.data.items.forEach(post => {
						const placeholderImg = '/images/placeholder.png';
						const finalImageUrl = post.thumbnail_url || post.image_url || placeholderImg;
						const keywordsHtml = post.keywords_string ? renderKeywords(post.keywords_string.split(',')) : 'N/A';
						const publishedAt = post.published_at ? new Date(post.published_at).toLocaleDateString() : 'N/A';
						const statusBadge = post.status === 'published'
							? `<span class="badge bg-success">${escapeHtml(post.status)}</span>`
							: `<span class="badge bg-secondary">${escapeHtml(post.status)}</span>`;
						
						const editUrl = `${window.adminRoutes.blogPostsGetBase}/${post.id}/edit`;
						
						$tableBody.append(`
                            <tr>
                                <td><img src="${escapeHtml(finalImageUrl)}" alt="${escapeHtml(post.title)}" class="thumbnail-preview" style="max-width:100px; max-height:60px; object-fit:cover;"></td>
                                <td>${escapeHtml(post.title)}</td>
                                <td>${escapeHtml(post.category_name)}</td>
                                <td>${statusBadge}</td>
                                <td>${keywordsHtml}</td>
                                <td>${publishedAt}</td>
                                <td>
                                    <a href="${editUrl}" class="btn btn-sm btn-outline-warning me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button class="btn btn-sm btn-outline-danger delete-post-btn" data-id="${post.id}" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        `);
					});
					if ($paginationContainer.length) renderBlogPagination(response.data.pagination, $paginationContainer);
				} else if (response.success) {
					$tableBody.html('<tr><td colspan="7" class="text-center">No blog posts found.</td></tr>');
				} else {
					$tableBody.html('<tr><td colspan="7" class="text-center text-danger">Error loading posts.</td></tr>');
					showAlert('Error loading posts: ' + escapeHtml(response.message), 'danger');
				}
			},
			error: function (xhr) {
				$tableBody.html('<tr><td colspan="7" class="text-center text-danger">AJAX Error loading posts.</td></tr>');
				showAlert('AJAX Error loading posts: ' + escapeHtml(xhr.responseText), 'danger');
			}
		});
	}
	
	function renderBlogPagination(pagination, $container) {
		const {currentPage, totalPages} = pagination;
		if (totalPages <= 1) {
			$container.empty();
			return;
		}
		let paginationHtml = '';
		paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage - 1}">«</a></li>`;
		let startPage = Math.max(1, currentPage - 2);
		let endPage = Math.min(totalPages, currentPage + 2);
		if (startPage > 1) paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>${startPage > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}`;
		for (let i = startPage; i <= endPage; i++) {
			paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
		}
		if (endPage < totalPages) paginationHtml += `${endPage < totalPages - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
		paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage + 1}">»</a></li>`;
		$container.html(paginationHtml);
	}
	
	function handleDeletePostClick() {
		const postId = $(this).data('id');
		if (!confirm('Are you sure you want to delete this blog post? This cannot be undone.')) return;
		
		$.ajax({
			url: `${window.adminRoutes.blogPostsDestroyBase}/${postId}`,
			type: 'DELETE',
			data: {_token: $('meta[name="csrf-token"]').attr('content')},
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					showAlert(response.message, 'success');
					loadBlogPosts();
				} else {
					showAlert('Error deleting post: ' + escapeHtml(response.message), 'danger');
				}
			},
			error: function (xhr) {
				showAlert('AJAX Error deleting post: ' + escapeHtml(xhr.responseText), 'danger');
			}
		});
	}
	
	return {
		init
	};
})();
