// public/js/admin/items.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.Items = (function () {
	const {showAlert, escapeHtml, renderKeywords} = AppAdmin.Utils;
	const ITEMS_PER_PAGE = 30; // Should match config
	
	function updateUrl(itemType, page, searchQuery, coverTypeIdFilter, filterNoTemplatesState, sortBy, sortDirection) {
		const newParams = new URLSearchParams();
		newParams.set('tab', itemType);
		
		if (page && page > 1) {
			newParams.set('page', String(page));
		}
		if (searchQuery) {
			newParams.set('search', searchQuery);
		}
		if ((itemType === 'covers' || itemType === 'templates') && coverTypeIdFilter) {
			newParams.set('filter', coverTypeIdFilter);
		}
		if (itemType === 'covers' && filterNoTemplatesState) {
			newParams.set('no_templates', 'true');
		}
		// Add sort parameters only if they are not the default (id, desc)
		if (sortBy && sortBy !== 'id') {
			newParams.set('sort_by', sortBy);
		}
		if (sortDirection && sortDirection !== 'desc') {
			newParams.set('sort_dir', sortDirection);
		}
		
		const newQueryString = newParams.toString();
		const currentQueryString = window.location.search.substring(1);
		
		if (currentQueryString !== newQueryString) {
			const newUrl = newQueryString ? `${window.location.pathname}?${newQueryString}` : window.location.pathname;
			history.pushState({
				path: newUrl,
				itemType,
				page,
				searchQuery,
				coverTypeIdFilter,
				filterNoTemplatesState,
				sortBy,
				sortDirection
			}, '', newUrl);
		}
	}
	
	function loadItems(itemType, page = 1, searchQuery = '', coverTypeIdFilter = '', filterNoTemplates = false, scrollYToRestore = null, sortBy = 'id', sortDirection = 'desc') {
		const $tableBody = $(`#${itemType}Table tbody`);
		const $paginationContainer = $(`#${itemType}Pagination`);
		const $panel = $(`#${itemType}-panel`);
		
		if ($panel.length) {
			$panel.find('.search-input').val(searchQuery);
			const $filterDropdown = $panel.find('.cover-type-filter');
			if ($filterDropdown.length) {
				if ($filterDropdown.find(`option[value="${coverTypeIdFilter}"]`).length > 0) {
					$filterDropdown.val(coverTypeIdFilter);
				} else {
					$filterDropdown.val('');
				}
			}
			$panel.find('.sort-by-select').val(sortBy);
			$panel.find('.sort-direction-select').val(sortDirection);
			
			if (itemType === 'covers') {
				$('#filterNoTemplatesBtn').toggleClass('active', filterNoTemplates);
			}
		}
		
		$tableBody.html('<tr><td colspan="100%" class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>');
		$paginationContainer.empty();
		
		updateUrl(itemType, page, searchQuery, coverTypeIdFilter, filterNoTemplates, sortBy, sortDirection);
		
		let ajaxData = {
			type: itemType,
			page: page,
			limit: ITEMS_PER_PAGE,
			search: searchQuery,
			sort_by: sortBy,
			sort_direction: sortDirection
		};
		
		if (coverTypeIdFilter && (itemType === 'covers' || itemType === 'templates')) {
			ajaxData.cover_type_id = coverTypeIdFilter;
		}
		if (itemType === 'covers' && filterNoTemplates) {
			ajaxData.filter_no_templates = true;
		}
		
		$.ajax({
			url: window.adminRoutes.listItems,
			type: 'GET',
			data: ajaxData,
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					$tableBody.empty();
					const items = response.data.items;
					const pagination = response.data.pagination;
					
					if (items.length === 0) {
						let message = `No ${itemType} found.`;
						if (searchQuery) message = `No ${itemType} found matching "${escapeHtml(searchQuery)}".`;
						if (coverTypeIdFilter && (itemType === 'covers' || itemType === 'templates')) message += ` for the selected cover type.`;
						if (itemType === 'covers' && filterNoTemplates) message += ` that have no templates assigned.`;
						$tableBody.html(`<tr><td colspan="100%" class="text-center">${message}</td></tr>`);
					} else {
						items.forEach(item => {
							let rowHtml = `<tr>`;
							if (itemType === 'covers') {
								const thumbUrl = item.cover_thumbnail_url || 'images/placeholder.png';
								rowHtml += `<td><img src="${escapeHtml(thumbUrl)}" alt="${escapeHtml(item.name)}" class="thumbnail-preview" loading="lazy"></td>`;
								rowHtml += `<td style="vertical-align: top;"><span class="small">${escapeHtml(item.name)} (${item.id})<br>`;
								rowHtml += `${escapeHtml(item.cover_type_name || 'N/A')}</span></td>`;
								rowHtml += `<td style="vertical-align: top;"><span class="small">${escapeHtml(item.caption || '')}</span><br>`;
								rowHtml += `${renderKeywords(item.keywords)}</td>`;
								rowHtml += `<td style="vertical-align: top; font-size: 0.8em;">`;
								if (item.mockup_2d_url) rowHtml += `2D: <a href="${escapeHtml(item.mockup_2d_url)}" target="_blank">View</a><br>`;
								if (item.mockup_3d_url) rowHtml += `3D: <a href="${escapeHtml(item.mockup_3d_url)}" target="_blank">View</a><br>`;
								if (item.full_cover_url) rowHtml += `Full: <a href="${escapeHtml(item.full_cover_url)}" target="_blank">View</a>`;
								if (item.full_cover_thumbnail_url && !item.full_cover_url) rowHtml += `Full (Thumb): <a href="${escapeHtml(item.full_cover_thumbnail_url)}" target="_blank">View</a>`;
								rowHtml += `</td>`;
								rowHtml += `<td style="vertical-align: top;">${renderKeywords(item.text_placements)}<br>`;
								rowHtml += `${renderKeywords(item.assigned_templates_names.split(','))}<br>`;
								rowHtml += `${renderKeywords(item.categories)}</td>`;
							} else if (itemType === 'templates') {
								let fullCoverPreviewHtml = 'N/A';
								if (item.full_cover_image_thumbnail_url) {
									fullCoverPreviewHtml = `<img src="${item.full_cover_image_thumbnail_url}" alt="Full Cover Preview" class="thumbnail-preview square">`;
								}
								let keywordsHtml = renderKeywords(item.keywords);
								let textPlacementsHtml = renderKeywords(item.text_placements);
								rowHtml += ` <td><img src="${item.cover_image_url}" alt="${escapeHtml(item.name)}" class="thumbnail-preview square"></td> <td>${fullCoverPreviewHtml}</td> <td>${escapeHtml(item.name)} (${item.id})<br><small class="text-muted">${escapeHtml(item.cover_type_name || 'N/A')}</small></td> <td>${keywordsHtml}</td> <td>${textPlacementsHtml}</td> `;
							} else if (itemType === 'elements' || itemType === 'overlays') {
								const thumbUrl = item.thumbnail_url || 'images/placeholder.png';
								rowHtml += `<td><img src="${escapeHtml(thumbUrl)}" alt="${escapeHtml(item.name)}" class="thumbnail-preview square" loading="lazy"></td>`;
								rowHtml += `<td>${escapeHtml(item.name)} (${item.id})</td>`;
								rowHtml += `<td>${renderKeywords(item.keywords)}</td>`;
							}
							rowHtml += `<td class="actions-column">`;
							if (itemType === 'covers') {
								rowHtml += ` <button class="btn btn-primary btn-sm assign-templates" data-id="${item.id}" data-name="${escapeHtml(item.name)}" title="Assign Templates"> <i class="fas fa-layer-group"></i> </button>`;
							}
							if (itemType === 'templates') {
								let editFrontJsonUrl = '#';
								let editFullJsonUrl = '#';
								if (item.json_content && item.json_content.canvas && item.json_content.canvas.width && item.json_content.canvas.height) {
									const frontCanvasWidth = item.json_content.canvas.width;
									const frontCanvasHeight = item.json_content.canvas.height;
									const templateUrlFront = `${window.location.origin}/api/templates/${item.id}/json?type=front`;
									editFrontJsonUrl = `/designer?w=${frontCanvasWidth}&h=${frontCanvasHeight}&template_url=${encodeURIComponent(templateUrlFront)}&from_admin=true&template_id_to_update=${item.id}&json_type_to_update=front`;
								}
								if (item.full_cover_json_content && item.full_cover_json_content.canvas && item.full_cover_json_content.canvas.width && item.full_cover_json_content.canvas.height) {
									const fullCanvasWidth = item.full_cover_json_content.canvas.width;
									const fullCanvasHeight = item.full_cover_json_content.canvas.height;
									const fullCanvasFrontWidth = item.full_cover_json_content.canvas.frontWidth;
									const fullCanvasSpineWidth = item.full_cover_json_content.canvas.spineWidth;
									const templateUrlFull = `${window.location.origin}/api/templates/${item.id}/json?type=full`;
									editFullJsonUrl = `/designer?w=${fullCanvasWidth}&h=${fullCanvasHeight}&spine_width=${fullCanvasSpineWidth}&front_width=${fullCanvasFrontWidth}&template_url=${encodeURIComponent(templateUrlFull)}&from_admin=true&template_id_to_update=${item.id}&json_type_to_update=full`;
								}
								const editFrontJsonButton = (item.json_content && item.json_content.canvas) ? `<a href="${editFrontJsonUrl}" target="_blank" class="btn btn-outline-primary btn-sm mb-1 w-100" title="Edit Front JSON"><i class="fas fa-palette"></i> Front JSON</a>` : '';
								const editFullJsonButton = (item.full_cover_json_content && item.full_cover_json_content.canvas) ? `<a href="${editFullJsonUrl}" target="_blank" class="btn btn-outline-primary btn-sm mb-1 w-100" title="Edit Full JSON"><i class="fas fa-ruler-combined"></i> Full JSON</a>` : '';
								const generateFullJsonButton = ` <button class="btn btn-sm btn-info w-100 mb-1 generate-full-cover-btn" data-id="${item.id}" title="Generate Full Cover JSON"> <i class="fas fa-book-open"></i> Generate Full</button>`;
								const cloneButton = ` <button class="btn btn-sm btn-outline-secondary w-100 mb-1 clone-template-btn" data-id="${item.id}" title="Clone Template"> <i class="fas fa-clone"></i> Clone </button>`;
								const inverseCloneButton = ` <button class="btn btn-sm btn-outline-primary w-100 mb-1 clone-inverse-template-btn" data-id="${item.id}" title="Clone Template with Inverted Colors"> <i class="fas fa-palette"></i> Inverse Clone</button>`;
								rowHtml += `${editFrontJsonButton} ${editFullJsonButton} ${generateFullJsonButton}`;
								rowHtml += `${cloneButton} ${inverseCloneButton}`;
							}
							if (itemType === 'covers' || itemType === 'templates') {
								rowHtml += ` <button class="btn btn-sm btn-secondary edit-text-placements" data-id="${item.id}" data-type="${itemType}" data-name="${escapeHtml(item.name)}" title="Edit Text Placements"> <i class="fas fa-map-signs"></i> </button>`;
							}
							rowHtml += ` <button class="btn btn-sm btn-info generate-ai-metadata" data-id="${item.id}" data-type="${itemType}" title="Generate AI Metadata"> <i class="fas fa-wand-magic-sparkles"></i> </button>`;
							rowHtml += ` <button class="btn btn-sm btn-warning edit-item" data-id="${item.id}" data-type="${itemType}" title="Edit"> <i class="fas fa-edit"></i> </button>`;
							rowHtml += ` <button class="btn btn-sm btn-danger delete-item" data-id="${item.id}" data-type="${itemType}" title="Delete"> <i class="fas fa-trash-alt"></i> </button>`;
							rowHtml += `</td>`;
							rowHtml += `</tr>`;
							$tableBody.append(rowHtml);
						});
					}
					renderPagination(itemType, pagination, $paginationContainer);
					if (scrollYToRestore !== null) {
						requestAnimationFrame(() => {
							window.scrollTo(0, scrollYToRestore);
						});
					}
				} else {
					$tableBody.html(`<tr><td colspan="100%" class="text-center text-danger">Error loading ${itemType}: ${escapeHtml(response.message)}</td></tr>`);
					showAlert(`Error loading ${itemType}: ${escapeHtml(response.message)}`, 'danger');
				}
			},
			error: function (xhr, status, error) {
				$tableBody.html(`<tr><td colspan="100%" class="text-center text-danger">AJAX Error loading ${itemType}. Check console.</td></tr>`);
				showAlert(`AJAX Error loading ${itemType}: ${escapeHtml(xhr.responseText || error)}`, 'danger');
				console.error("AJAX Error:", status, error, xhr.responseText);
				if (scrollYToRestore !== null) {
					requestAnimationFrame(() => {
						window.scrollTo(0, scrollYToRestore);
					});
				}
			}
		});
	}
	
	function renderPagination(itemType, pagination, $paginationContainer) {
		const {totalItems, itemsPerPage, currentPage, totalPages} = pagination;
		$paginationContainer.empty();
		if (totalPages <= 1) {
			return;
		}
		
		let paginationHtml = '';
		paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"> <a class="page-link" href="#" data-page="${currentPage - 1}" data-type="${itemType}" aria-label="Previous"> <span aria-hidden="true">«</span> </a> </li>`;
		
		const maxPagesToShow = 5;
		let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
		let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
		startPage = Math.max(1, endPage - maxPagesToShow + 1);
		
		if (startPage > 1) {
			paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="1" data-type="${itemType}">1</a></li>`;
			if (startPage > 2) {
				paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
			}
		}
		
		for (let i = startPage; i <= endPage; i++) {
			paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}"> <a class="page-link" href="#" data-page="${i}" data-type="${itemType}">${i}</a> </li>`;
		}
		
		if (endPage < totalPages) {
			if (endPage < totalPages - 1) {
				paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
			}
			paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}" data-type="${itemType}">${totalPages}</a></li>`;
		}
		
		paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"> <a class="page-link" href="#" data-page="${currentPage + 1}" data-type="${itemType}" aria-label="Next"> <span aria-hidden="true">»</span> </a> </li>`;
		$paginationContainer.html(paginationHtml);
	}
	
	function init() {
		$(document).on('click', '.generate-full-cover-btn', function () {
			const templateId = $(this).data('id');
			if (!templateId) {
				AppAdmin.Utils.showAlert('Could not get template ID.', 'danger');
				return;
			}
			if (!confirm('Are you sure you want to generate/overwrite the full cover JSON for this template? This will modify its existing front cover elements and add spine/back elements.')) {
				return;
			}
			const $button = $(this);
			$button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
			$.ajax({
				url: `${window.adminRoutes.generateFullCoverJsonForTemplateBase}/${templateId}/generate-full-cover-json`,
				type: 'POST',
				headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
				success: function (response) {
					if (response.success) {
						AppAdmin.Utils.showAlert(response.message || 'Full cover JSON generated successfully!', 'success');
						const $panel = $('#templates-panel');
						const currentPage = parseInt($('#templatesPagination .active .page-link').data('page'), 10) || 1;
						const currentSearch = $panel.find('.search-input').val() || '';
						const currentFilter = $panel.find('.cover-type-filter').val() || '';
						const currentSortBy = $panel.find('.sort-by-select').val() || 'id';
						const currentSortDir = $panel.find('.sort-direction-select').val() || 'desc';
						AppAdmin.Items.loadItems('templates', currentPage, currentSearch, currentFilter, false, currentSortBy, currentSortDir);
					} else {
						AppAdmin.Utils.showAlert(response.message || 'Failed to generate full cover JSON.', 'danger');
					}
				},
				error: function (xhr) {
					const errorMsg = xhr.responseJSON?.message || xhr.responseText || 'An unknown error occurred.';
					AppAdmin.Utils.showAlert(`Error: ${errorMsg}`, 'danger');
					console.error("Generate Full Cover JSON error:", xhr);
				},
				complete: function () {
					$button.prop('disabled', false).html('<i class="fas fa-book-open"></i> Generate Full');
				}
			});
		});
		
		$(document).on('click', '.clone-template-btn', function () {
			const templateId = $(this).data('id');
			if (!templateId) {
				AppAdmin.Utils.showAlert('Could not get template ID for cloning.', 'danger');
				return;
			}
			if (!confirm('Are you sure you want to clone this template?')) {
				return;
			}
			const $button = $(this);
			const originalHtml = $button.html();
			$button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
			$.ajax({
				url: `/admin/templates/${templateId}/clone`, type: 'POST',
				headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
				success: function (response) {
					if (response.success) {
						AppAdmin.Utils.showAlert(response.message || 'Template cloned successfully!', 'success');
						const $panel = $('#templates-panel');
						const currentPage = parseInt($('#templatesPagination .active .page-link').data('page'), 10) || 1;
						const currentSearch = $panel.find('.search-input').val() || '';
						const currentFilter = $panel.find('.cover-type-filter').val() || '';
						const currentSortBy = $panel.find('.sort-by-select').val() || 'id';
						const currentSortDir = $panel.find('.sort-direction-select').val() || 'desc';
						AppAdmin.Items.loadItems('templates', currentPage, currentSearch, currentFilter, false, currentSortBy, currentSortDir, $(window).scrollTop());
					} else {
						AppAdmin.Utils.showAlert(response.message || 'Failed to clone template.', 'danger');
					}
				},
				error: function (xhr) {
					const errorMsg = xhr.responseJSON?.message || xhr.responseText || 'An unknown error occurred.';
					AppAdmin.Utils.showAlert(`Error cloning template: ${escapeHtml(errorMsg)}`, 'danger');
				},
				complete: function () {
					$button.prop('disabled', false).html(originalHtml);
				}
			});
		});
		
		$(document).on('click', '.clone-inverse-template-btn', function () {
			const templateId = $(this).data('id');
			if (!templateId) {
				AppAdmin.Utils.showAlert('Could not get template ID for inverse cloning.', 'danger');
				return;
			}
			if (!confirm('Are you sure you want to create an inverse color clone of this template?')) {
				return;
			}
			const $button = $(this);
			const originalHtml = $button.html();
			$button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');
			$.ajax({
				url: `/admin/templates/${templateId}/clone-inverse`, type: 'POST',
				headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
				success: function (response) {
					if (response.success) {
						AppAdmin.Utils.showAlert(response.message || 'Inverse template cloned successfully!', 'success');
						const $panel = $('#templates-panel');
						const currentPage = parseInt($('#templatesPagination .active .page-link').data('page'), 10) || 1;
						const currentSearch = $panel.find('.search-input').val() || '';
						const currentFilter = $panel.find('.cover-type-filter').val() || '';
						const currentSortBy = $panel.find('.sort-by-select').val() || 'id';
						const currentSortDir = $panel.find('.sort-direction-select').val() || 'desc';
						AppAdmin.Items.loadItems('templates', currentPage, currentSearch, currentFilter, false, currentSortBy, currentSortDir, $(window).scrollTop());
					} else {
						AppAdmin.Utils.showAlert(response.message || 'Failed to create inverse clone.', 'danger');
					}
				},
				error: function (xhr) {
					const errorMsg = xhr.responseJSON?.message || xhr.responseText || 'An unknown error occurred.';
					AppAdmin.Utils.showAlert(`Error creating inverse clone: ${escapeHtml(errorMsg)}`, 'danger');
				},
				complete: function () {
					$button.prop('disabled', false).html(originalHtml);
				}
			});
		});
	}
	
	return {
		loadItems: loadItems,
		ITEMS_PER_PAGE: ITEMS_PER_PAGE,
		init: init
	};
})();
