// public/js/admin/edit.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.Edit = (function() {
	const { showAlert, escapeHtml, capitalizeFirstLetter } = AppAdmin.Utils;
	const { loadItems } = AppAdmin.Items;
	const { populateAllCoverTypeDropdowns } = AppAdmin.CoverTypes;
	let $editModal, editModal, $editForm;
	
	function displayPreview(containerId, imageUrl, label) {
		const $container = $(`#${containerId}`);
		if (imageUrl) {
			$container.html(`<p class="mb-1 small text-muted">${label}:</p><img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(label)} Preview">`).show();
		} else {
			$container.empty().hide();
		}
	}
	
	function handleEditItemClick() {
		const $button = $(this);
		const itemId = $button.data('id');
		const itemType = $button.data('type');
		
		$editModal.find('.modal-title').text(`Loading ${capitalizeFirstLetter(itemType).slice(0,-1)}...`);
		$editForm[0].reset();
		
		// Clear all previews
		$('.preview-container').empty().hide();
		$('#editTemplateJsonInfo').hide().text('');
		$('#editTemplateFullCoverJsonInfo').hide().text('');
		
		$('.edit-field').hide(); // Hide all conditional fields first
		populateAllCoverTypeDropdowns(); // Ensure cover type dropdown is populated
		
		$.ajax({
			url: window.adminRoutes.getItemDetails,
			type: 'GET',
			data: { item_type: itemType, id: itemId },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					const item = response.data;
					$editModal.find('.modal-title').text(`Edit ${capitalizeFirstLetter(itemType).slice(0,-1)}: ${escapeHtml(item.name)}`);
					$('#editItemId').val(item.id);
					$('#editItemType').val(itemType);
					$('#editItemName').val(item.name);
					
					$(`.edit-field-${itemType}`).show();
					$('#editItemKeywords').val(item.keywords_string_for_form || '');
					
					if (itemType === 'covers') {
						$('#editItemCaption').val(item.caption || '');
						$('#editItemCategories').val(item.categories_string_for_form || '');
						$('#editItemTextPlacements').val(item.text_placements_string_for_form || '');
						$('#editItemCoverType').val(item.cover_type_id || '');
						
						displayPreview('editCoverMainImagePreview', item.cover_thumbnail_url || item.cover_url, 'Current Main Image');
						displayPreview('editCoverMockup2DPreview', item.mockup_2d_url, 'Current 2D Mockup');
						displayPreview('editCoverMockup3DPreview', item.mockup_3d_url, 'Current 3D Mockup');
						displayPreview('editCoverFullCoverPreview', item.full_cover_thumbnail_url || item.full_cover_url, 'Current Full Cover');
						
					} else if (itemType === 'templates') {
						$('#editItemCoverType').val(item.cover_type_id || '');
						$('#editTemplateTextPlacements').val(item.text_placements_string_for_form || '');
						
						displayPreview('editTemplateCoverImagePreview', item.cover_image_url, 'Current Cover Image');
						displayPreview('editTemplateFullCoverImagePreview', item.full_cover_image_thumbnail_url || item.full_cover_image_url, 'Current Full Cover Image');
						
						if (item.json_content) { // Check if json_content exists
							$('#editTemplateJsonInfo').text('Current JSON data is loaded. Upload a new file to replace it.').show();
						}
						if (item.full_cover_json_content) {
							$('#editTemplateFullCoverJsonInfo').text('Current Full Cover JSON data is loaded. Upload a new file to replace it.').show();
						}
						
					} else if (itemType === 'elements' || itemType === 'overlays') {
						// Assuming old field names for these, adjust if they also get refactored
						displayPreview('editCurrentImagePreview', item.thumbnail_url || item.image_url, 'Current Image');
					}
					editModal.show();
				} else {
					showAlert(`Error fetching details for ${itemType} ID ${itemId}: ${escapeHtml(response.message)}`, 'danger');
				}
			},
			error: function(xhr, status, error) {
				showAlert(`AJAX Error fetching details: ${escapeHtml(xhr.responseText || error)}`, 'danger');
			}
		});
	}
	
	function handleEditFormSubmit(event) {
		event.preventDefault();
		const formData = new FormData(this);
		const itemType = $('#editItemType').val();
		const $submitButton = $('#saveEditButton');
		const originalButtonText = $submitButton.html();
		const currentScrollY = window.scrollY;
		
		$submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
		
		$.ajax({
			url: window.adminRoutes.updateItem,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAlert(`${capitalizeFirstLetter(itemType).slice(0,-1)} updated successfully!`, 'success');
					editModal.hide();
					const params = new URLSearchParams(window.location.search);
					const currentPage = parseInt(params.get('page'), 10) || 1;
					const currentSearch = params.get('search') || '';
					const currentFilter =  params.get('filter') || '';
					const noTemplatesFilter = itemType === 'covers' && $('#filterNoTemplatesBtn').hasClass('active');
					const currentSortBy = params.get('sort_by') || 'id';
					const currentSortDir = params.get('sort_dir') || 'desc';
					loadItems(itemType, currentPage, currentSearch, currentFilter, noTemplatesFilter, currentScrollY, currentSortBy, currentSortDir);
				} else {
					let errorMsg = `Error updating ${itemType}: ${escapeHtml(response.message)}`;
					if (response.errors) {
						errorMsg += '<ul>';
						$.each(response.errors, function(field, messages) {
							messages.forEach(function(message) {
								errorMsg += `<li>${escapeHtml(message)}</li>`;
							});
						});
						errorMsg += '</ul>';
					}
					showAlert(errorMsg, 'danger');
				}
			},
			error: function(xhr, status, error) {
				showAlert(`AJAX Error updating ${itemType}: ${escapeHtml(xhr.responseText || error)}`, 'danger');
			},
			complete: function() {
				$submitButton.prop('disabled', false).html(originalButtonText);
			}
		});
	}
	
	function init() {
		$editModal = $('#editItemModal');
		$editForm = $('#editItemForm');
		if ($editModal.length) {
			editModal = new bootstrap.Modal($editModal[0]);
		}
		
		$('.tab-content').on('click', '.edit-item', handleEditItemClick);
		if ($editForm.length) {
			$editForm.on('submit', handleEditFormSubmit);
		}
		
		if ($editModal.length) {
			$editModal.on('hidden.bs.modal', function () {
				// Clear all file inputs in the form
				$editForm.find('input[type="file"]').val('');
				// Clear previews
				$('.preview-container').empty().hide();
				$('#editTemplateJsonInfo').hide().text('');
				$('#editTemplateFullCoverJsonInfo').hide().text('');
				// Reset select
				$('#editItemCoverType').val('');
				// Hide all conditional fields again
				$('.edit-field').hide();
				$editForm[0].reset(); // General reset for other fields
			});
		}
	}
	
	return {
		init
	};
})();
