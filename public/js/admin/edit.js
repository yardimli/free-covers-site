// public/js/admin/edit.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.Edit = (function() {
	const { showAlert, escapeHtml, capitalizeFirstLetter } = AppAdmin.Utils;
	const { getCurrentState } = AppAdmin.State;
	const { loadItems } = AppAdmin.Items;
	const { populateAllCoverTypeDropdowns } = AppAdmin.CoverTypes; // Ensure this is available
	
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
		$('.edit-field').hide(); // Hide all conditional fields first
		
		// Ensure cover type dropdown is populated before trying to set its value
		// This might be better done once on page load and then just referenced
		populateAllCoverTypeDropdowns();
		
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
					
					// Show relevant sections based on itemType
					$(`.edit-field-${itemType}`).show();
					
					// Populate common fields that might exist as comma-separated strings in forms
					$('#editItemKeywords').val(item.keywords_string_for_form || '');
					
					
					if (itemType === 'covers') {
						$('#editItemCaption').val(item.caption || '');
						$('#editItemCategories').val(item.categories_string_for_form || '');
						$('#editItemTextPlacements').val(item.text_placements_string_for_form || '');
						$('#editItemCoverType').val(item.cover_type_id || '');
						if(item.image_url) {
							$('#editCurrentImagePreview').html(`<p class="mb-1">Current Image:</p><img src="${escapeHtml(item.image_url)}" alt="Current Preview">`).show();
						}
					} else if (itemType === 'elements' || itemType === 'overlays') {
						// Keywords already handled above
						if(item.image_url) {
							$('#editCurrentImagePreview').html(`<p class="mb-1">Current Image:</p><img src="${escapeHtml(item.image_url)}" alt="Current Preview">`).show();
						}
					} else if (itemType === 'templates') {
						// Keywords already handled above
						$('#editItemCoverType').val(item.cover_type_id || '');
						$('#editItemTextPlacements').val(item.text_placements_string_for_form || ''); // For templates main edit form
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
					const state = getCurrentState(itemType);
					loadItems(itemType, state.page, state.search, state.coverTypeId, currentScrollY );
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
				// Clear file inputs
				$('#editItemImageFile').val('');
				$('#editItemThumbnailFile').val('');
				$('#editItemJsonFile').val('');
				// Clear previews
				$('#editCurrentImagePreview').empty().hide();
				$('#editCurrentThumbnailPreview').empty().hide();
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
