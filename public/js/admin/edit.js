// public/js/admin/edit.js
window.AppAdmin = window.AppAdmin || {};

AppAdmin.Edit = (function() {
	const { showAlert, escapeHtml, capitalizeFirstLetter } = AppAdmin.Utils;
	const { getCurrentState } = AppAdmin.State;
	const { loadItems } = AppAdmin.Items;
	const { populateAllCoverTypeDropdowns } = AppAdmin.CoverTypes;
	
	
	let $editModal, editModal, $editForm;
	
	function handleEditItemClick() {
		const $button = $(this);
		const itemId = $button.data('id');
		const itemType = $button.data('type');
		
		$editModal.find('.modal-title').text(`Loading ${capitalizeFirstLetter(itemType).slice(0,-1)}...`);
		$editForm[0].reset();
		$('#editCurrentImagePreview').empty().hide();
		$('#editCurrentThumbnailPreview').empty().hide();
		$('#editCurrentJsonInfo').hide();
		$('.edit-field').hide();
		// Ensure cover type dropdown is populated before trying to set its value
		populateAllCoverTypeDropdowns();
		
		
		$.ajax({
			url: window.adminRoutes.getItemDetails,
			type: 'GET',
			data: { item_type: itemType, id: itemId }, // 'action' key not used by backend
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					const item = response.data;
					$editModal.find('.modal-title').text(`Edit ${capitalizeFirstLetter(itemType).slice(0,-1)}: ${escapeHtml(item.name)}`);
					$('#editItemId').val(item.id);
					$('#editItemType').val(itemType);
					$('#editItemName').val(item.name);
					
					$(`.edit-field-${itemType}`).show();
					
					if (itemType === 'covers') {
						$('#editItemCaption').val(item.caption || '');
						$('#editItemKeywords').val(item.keywords || '');
						$('#editItemCategories').val(item.categories || '');
						$('#editItemTextPlacements').val(item.text_placements || '');
						$('#editItemCoverType').val(item.cover_type_id || '');
						if(item.image_url) {
							$('#editCurrentImagePreview').html(`<p class="mb-1">Current Image:</p><img src="${escapeHtml(item.image_url)}" alt="Current Preview">`).show();
						}
					} else if (itemType === 'elements' || itemType === 'overlays') {
						$('#editItemKeywords').val(item.keywords || '');
						if(item.image_url) {
							$('#editCurrentImagePreview').html(`<p class="mb-1">Current Image:</p><img src="${escapeHtml(item.image_url)}" alt="Current Preview">`).show();
						}
					} else if (itemType === 'templates') {
						$('#editItemKeywords').val(item.keywords || '');
						$('#editItemCoverType').val(item.cover_type_id || '');
						if(item.thumbnail_url) {
							$('#editCurrentThumbnailPreview').html(`<p class="mb-1">Current Thumbnail:</p><img src="${escapeHtml(item.thumbnail_url)}" alt="Current Thumbnail">`).show();
						}
						$('#editCurrentJsonInfo').text('Current JSON data is loaded. Upload a new file to replace it.').show();
					}
					editModal.show();
				} else {
					showAlert(`Error fetching details for ${itemType} ID ${itemId}: ${escapeHtml(response.message)}`, 'danger');
				}
			},
			error: function(xhr, status, error) {
				showAlert(`AJAX Error fetching details: ${escapeHtml(xhr.responseText || error)}`, 'danger');
				console.error("AJAX Error:", status, error, xhr.responseText);
			}
		});
	}
	
	function handleEditFormSubmit(event) {
		event.preventDefault();
		const formData = new FormData(this);
		// formData.append('action', 'update_item'); // Not needed, route implies action
		const itemType = $('#editItemType').val();
		const $submitButton = $('#saveEditButton');
		const originalButtonText = $submitButton.html();
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
					const state = getCurrentState(itemType);
					loadItems(itemType, state.page, state.search, state.coverTypeId);
				} else {
					showAlert(`Error updating ${itemType}: ${escapeHtml(response.message)}`, 'danger');
				}
			},
			error: function(xhr, status, error) {
				showAlert(`AJAX Error updating ${itemType}: ${escapeHtml(xhr.responseText || error)}`, 'danger');
				console.error("AJAX Error:", status, error, xhr.responseText);
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
				$('#editItemImageFile').val('');
				$('#editItemThumbnailFile').val('');
				$('#editItemJsonFile').val('');
				$('#editCurrentImagePreview').empty().hide();
				$('#editCurrentThumbnailPreview').empty().hide();
				$('#editItemCoverType').val('');
			});
		}
	}
	
	return {
		init
	};
})();
