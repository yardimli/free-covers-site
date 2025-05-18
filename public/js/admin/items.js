// public/js/admin/items.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.Items = (function() {
	const { showAlert, escapeHtml, renderKeywords } = AppAdmin.Utils;
	const ITEMS_PER_PAGE = 30; // Should match config
	
	function updateUrl(itemType, page, searchQuery, coverTypeIdFilter, filterNoTemplatesState) {
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
		if (itemType === 'covers' && filterNoTemplatesState) { // Only add if true
			newParams.set('no_templates', 'true');
		}
		
		const newQueryString = newParams.toString();
		const currentQueryString = window.location.search.substring(1);
		
		// Only push a new state if the query string actually changes.
		if (currentQueryString !== newQueryString) {
			const newUrl = newQueryString ? `${window.location.pathname}?${newQueryString}` : window.location.pathname;
			history.pushState({ path: newUrl, itemType, page, searchQuery, coverTypeIdFilter, filterNoTemplatesState }, '', newUrl);
		}
	}
	
	function loadItems(itemType, page = 1, searchQuery = '', coverTypeIdFilter = '', filterNoTemplates = false, scrollYToRestore = null) {
		const $tableBody = $(`#${itemType}Table tbody`);
		const $paginationContainer = $(`#${itemType}Pagination`);
		
		// Update form fields to reflect the state being loaded
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
			if (itemType === 'covers') {
				$('#filterNoTemplatesBtn').toggleClass('active', filterNoTemplates);
			}
		}
		
		$tableBody.html('<tr><td colspan="100%" class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>');
		$paginationContainer.empty();
		
		updateUrl(itemType, page, searchQuery, coverTypeIdFilter, filterNoTemplates); // Update URL
		
		let ajaxData = {
			type: itemType,
			page: page,
			limit: ITEMS_PER_PAGE,
			search: searchQuery
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
			success: function(response) {
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
							const thumbUrl = item.thumbnail_url || 'images/placeholder.png'; // Placeholder if no thumb
							const isSquareThumb = itemType === 'elements' || itemType === 'overlays';
							
							rowHtml += `<td><img src="${escapeHtml(thumbUrl)}" alt="${escapeHtml(item.name)}" class="thumbnail-preview ${isSquareThumb ? 'square' : ''}" loading="lazy"></td>`;
							
							if (itemType === 'covers') {
								rowHtml += `<td style="vertical-align: top;"><span class="small">${escapeHtml(item.name)}<br>`;
								rowHtml += `${escapeHtml(item.cover_type_name || 'N/A')}</span></td>`;
								rowHtml += `<td style="vertical-align: top;"><span class="small">${escapeHtml(item.caption || '')}</span><br>`;
								rowHtml += `${renderKeywords(item.keywords)}</td>`;
								rowHtml += `<td style="vertical-align: top;">${renderKeywords(item.text_placements)}<br>`;
								rowHtml += `${renderKeywords(item.assigned_templates_names.split(','))}<br>`;
								rowHtml += `${renderKeywords(item.categories)}</td>`;
							} else if (itemType === 'templates') {
								rowHtml += `<td>${escapeHtml(item.name)}<br>`;
								rowHtml += `${escapeHtml(item.cover_type_name || 'N/A')}</td>`;
								rowHtml += `<td>${renderKeywords(item.keywords)}</td>`;
								rowHtml += `<td>${renderKeywords(item.text_placements)}</td>`;
							} else if (itemType === 'elements' || itemType === 'overlays') {
								rowHtml += `<td>${escapeHtml(item.name)}</td>`;
								rowHtml += `<td>${renderKeywords(item.keywords)}</td>`;
							}
							
							rowHtml += `<td class="actions-column">`;
							if (itemType === 'covers') {
								rowHtml += ` <button class="btn btn-primary btn-sm assign-templates" data-id="${item.id}" data-name="${escapeHtml(item.name)}" title="Assign Templates"> <i class="fas fa-layer-group"></i> </button>`;
							}
							if (itemType === 'templates') {
								rowHtml += ` <button class="btn btn-success btn-sm generate-similar-template" data-id="${item.id}" data-type="${itemType}" title="Generate Similar with AI"> <i class="fas fa-robot"></i> </button>`;
							}
							if (itemType === 'covers' || itemType === 'templates') {
								rowHtml += ` <button class="btn btn-secondary btn-sm edit-text-placements" data-id="${item.id}" data-type="${itemType}" data-name="${escapeHtml(item.name)}" title="Edit Text Placements"> <i class="fas fa-map-signs"></i> </button>`;
							}
							rowHtml += ` <button class="btn btn-info btn-sm generate-ai-metadata" data-id="${item.id}" data-type="${itemType}" title="Generate AI Metadata"> <i class="fas fa-wand-magic-sparkles"></i> </button>`;
							rowHtml += ` <button class="btn btn-warning btn-sm edit-item" data-id="${item.id}" data-type="${itemType}" title="Edit"> <i class="fas fa-edit"></i> </button>`;
							rowHtml += ` <button class="btn btn-danger btn-sm delete-item" data-id="${item.id}" data-type="${itemType}" title="Delete"> <i class="fas fa-trash-alt"></i> </button> </td>`;
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
	
	function renderPagination(itemType, pagination, $paginationContainer) {
		const { totalItems, itemsPerPage, currentPage, totalPages } = pagination;
		$paginationContainer.empty();
		
		if (totalPages <= 1) {
			return;
		}
		
		let paginationHtml = '';
		paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"> <a class="page-link" href="#" data-page="${currentPage - 1}" data-type="${itemType}" aria-label="Previous"> <span aria-hidden="true">«</span> </a> </li>`;
		
		const maxPagesToShow = 5;
		let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
		let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
		startPage = Math.max(1, endPage - maxPagesToShow + 1); // Adjust startPage if endPage was capped
		
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
		ITEMS_PER_PAGE
	};
})();
