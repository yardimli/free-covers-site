// public/js/admin/items.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.Items = (function() {
	const { showAlert, escapeHtml, renderKeywords } = AppAdmin.Utils;
	const { getCurrentState, setCurrentState } = AppAdmin.State;
	const ITEMS_PER_PAGE = 30; // Should match config
	
	function loadItems(itemType, page = 1, searchQuery = '', coverTypeIdFilter = '', scrollYToRestore = null) {
		const $tableBody = $(`#${itemType}Table tbody`);
		const $paginationContainer = $(`#${itemType}Pagination`);
		$tableBody.html('<tr><td colspan="100%" class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>');
		$paginationContainer.empty();
		
		setCurrentState(itemType, page, searchQuery, coverTypeIdFilter);
		
		let ajaxData = {
			type: itemType,
			page: page,
			limit: ITEMS_PER_PAGE,
			search: searchQuery
		};
		
		if (coverTypeIdFilter && (itemType === 'covers' || itemType === 'templates')) {
			ajaxData.cover_type_id = coverTypeIdFilter;
		}
		
		$.ajax({
			url: window.adminRoutes.listItems,
			type: 'GET',
			data: ajaxData,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					$tableBody.empty();
					const items = response.data.items;
					const pagination = response.data.pagination;
					
					if (items.length === 0) {
						let message = `No ${itemType} found.`;
						if (searchQuery) message = `No ${itemType} found matching "${escapeHtml(searchQuery)}".`;
						if (coverTypeIdFilter) message += ` for the selected cover type.`;
						$tableBody.html(`<tr><td colspan="100%" class="text-center">${message}</td></tr>`);
					} else {
						items.forEach(item => {
							let rowHtml = `<tr>`;
							const thumbUrl = item.thumbnail_url || 'images/placeholder.png';
							const isSquareThumb = itemType === 'elements' || itemType === 'overlays';
							rowHtml += `<td><img src="${escapeHtml(thumbUrl)}" alt="${escapeHtml(item.name)}" class="thumbnail-preview ${isSquareThumb ? 'square' : ''}" loading="lazy"></td>`;
							rowHtml += `<td>${escapeHtml(item.name)}</td>`;
							
							if (itemType === 'covers') {
								rowHtml += `<td>${escapeHtml(item.cover_type_name || 'N/A')}</td>`;
								rowHtml += `<td>${escapeHtml(item.caption || '')}</td>`;
								rowHtml += `<td>${renderKeywords(item.keywords)}</td>`;
								rowHtml += `<td>${renderKeywords(item.categories)}</td>`;
								rowHtml += `<td>${renderKeywords(item.text_placements)}</td>`;
								let assignedTemplatesText = item.assigned_templates_names || 'None';
								if (item.assigned_templates_count > 0) {
									if (item.assigned_templates_names && item.assigned_templates_names.length < 50) {
										assignedTemplatesText = escapeHtml(item.assigned_templates_names);
									} else {
										assignedTemplatesText = `<span title="${escapeHtml(item.assigned_templates_names)}">${item.assigned_templates_count} template(s)</span>`;
									}
								}
								rowHtml += `<td>${assignedTemplatesText}</td>`;
							} else if (itemType === 'templates') {
								rowHtml += `<td>${escapeHtml(item.cover_type_name || 'N/A')}</td>`;
								rowHtml += `<td>${renderKeywords(item.keywords)}</td>`;
								rowHtml += `<td>${renderKeywords(item.text_placements)}</td>`; // Added for templates
							} else if (itemType === 'elements' || itemType === 'overlays') {
								rowHtml += `<td>${renderKeywords(item.keywords)}</td>`;
							}
							
							rowHtml += `<td class="actions-column">`; // Added class for potential styling
							if (itemType === 'covers') {
								rowHtml += ` <button class="btn btn-primary btn-sm me-1 assign-templates" data-id="${item.id}" data-name="${escapeHtml(item.name)}" title="Assign Templates"> <i class="fas fa-layer-group"></i> </button>`;
								rowHtml += ` <button class="btn btn-outline-info btn-sm me-1 analyze-text-placements" data-id="${item.id}" data-type="${itemType}" title="Analyze Text Placements with AI"> <i class="fas fa-text-height"></i> </button>`;
							}
							if (itemType === 'templates') {
								rowHtml += ` <button class="btn btn-success btn-sm me-1 generate-similar-template" data-id="${item.id}" data-type="${itemType}" title="Generate Similar with AI"> <i class="fas fa-robot"></i> </button>`;
							}
							// Add Edit Text Placements button for Covers and Templates
							if (itemType === 'covers' || itemType === 'templates') {
								rowHtml += ` <button class="btn btn-secondary btn-sm me-1 edit-text-placements" data-id="${item.id}" data-type="${itemType}" data-name="${escapeHtml(item.name)}" title="Edit Text Placements"> <i class="fas fa-map-signs"></i> </button>`;
							}
							
							rowHtml += ` <button class="btn btn-info btn-sm me-1 generate-ai-metadata" data-id="${item.id}" data-type="${itemType}" title="Generate AI Metadata"> <i class="fas fa-wand-magic-sparkles"></i> </button> <button class="btn btn-warning btn-sm me-1 edit-item" data-id="${item.id}" data-type="${itemType}" title="Edit"> <i class="fas fa-edit"></i> </button> <button class="btn btn-danger btn-sm delete-item" data-id="${item.id}" data-type="${itemType}" title="Delete"> <i class="fas fa-trash-alt"></i> </button> </td>`;
							rowHtml += `</tr>`;
							$tableBody.append(rowHtml);
						});
					}
					renderPagination(itemType, pagination);
					
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
			error: function(xhr, status, error) {
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
	
	function renderPagination(itemType, pagination) {
		const { totalItems, itemsPerPage, currentPage, totalPages } = pagination;
		const $paginationContainer = $(`#${itemType}Pagination`);
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
	
	return {
		loadItems,
		renderPagination,
		ITEMS_PER_PAGE
	};
})();
