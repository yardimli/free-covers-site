// public/js/admin/textPlacements.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.TextPlacements = (function() {
	const { showAlert, escapeHtml } = AppAdmin.Utils;
	const { getCurrentState } = AppAdmin.State;
	const { loadItems } = AppAdmin.Items;
	
	let $modal, modalInstance, $form;
	let $itemNameEl, $itemIdEl, $itemTypeEl; // Renamed for clarity (element)
	const areas = ['top', 'middle', 'bottom', 'left', 'right'];
	
	function init() {
		$modal = $('#editTextPlacementsModal');
		if (!$modal.length) return;
		
		modalInstance = new bootstrap.Modal($modal[0]);
		$form = $('#editTextPlacementsForm');
		$itemNameEl = $('#textPlacementsItemName');
		$itemIdEl = $('#textPlacementsItemId');
		$itemTypeEl = $('#textPlacementsItemType');
		
		$('.tab-content').on('click', '.edit-text-placements', handleOpenModalClick);
		$form.on('submit', handleFormSubmit);
		
		$modal.on('change', '.area-checkbox', function() {
			const area = $(this).val();
			const $toneGroup = $(`#tp_tone_group_${area}`);
			const $radios = $toneGroup.find('.tone-radio');
			
			if ($(this).is(':checked')) {
				$toneGroup.show();
				$radios.prop('disabled', false);
				if (!$radios.filter(':checked').length) { // Default to 'light' if nothing selected
					$radios.filter('[value="light"]').prop('checked', true);
				}
			} else {
				$toneGroup.hide();
				$radios.prop('disabled', true).prop('checked', false);
			}
		});
		
		$modal.on('hidden.bs.modal', resetModal);
	}
	
	function resetModal() {
		$form[0].reset(); // Resets all form inputs including hidden ones
		areas.forEach(area => {
			$(`#tp_area_${area}`).prop('checked', false); // Ensure checkbox is visually reset
			const $toneGroup = $(`#tp_tone_group_${area}`);
			$toneGroup.hide().find('.tone-radio').prop('disabled', true).prop('checked', false);
		});
		$itemNameEl.text('');
		// $itemIdEl.val(''); // These are reset by $form[0].reset()
		// $itemTypeEl.val('');
		$('#editTextPlacementsModalLabel').text('Edit Text Placements'); // Reset title
	}
	
	function handleOpenModalClick() {
		const itemIdVal = $(this).data('id');
		const itemTypeVal = $(this).data('type');
		const itemNameVal = $(this).data('name');
		
		resetModal(); // Reset before populating
		
		$itemIdEl.val(itemIdVal);
		$itemTypeEl.val(itemTypeVal);
		$itemNameEl.text(escapeHtml(itemNameVal));
		
		$('#editTextPlacementsModalLabel').text(`Edit Text Placements for ${escapeHtml(itemNameVal)}`);
		
		$.ajax({
			url: window.adminRoutes.getItemDetails,
			type: 'GET',
			data: { item_type: itemTypeVal, id: itemIdVal },
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data && response.data.text_placements) {
					const currentPlacements = response.data.text_placements; // This is an array
					if (Array.isArray(currentPlacements)) {
						currentPlacements.forEach(placementStr => {
							const parts = placementStr.split('-');
							if (parts.length === 2) {
								const area = parts[0];
								const tone = parts[1];
								if (areas.includes(area)) {
									$(`#tp_area_${area}`).prop('checked', true).trigger('change'); // Trigger change to show/enable radios
									$(`#tp_tone_${area}_${tone}`).prop('checked', true);
								}
							}
						});
					}
				}
				modalInstance.show();
			},
			error: function(xhr) {
				showAlert(`Error fetching item details: ${escapeHtml(xhr.responseText || 'Unknown error')}`, 'danger');
				modalInstance.show(); // Show modal anyway so user isn't stuck
			}
		});
	}
	
	function handleFormSubmit(event) {
		event.preventDefault();
		const currentItemId = $itemIdEl.val();
		const currentItemType = $itemTypeEl.val();
		const $submitButton = $('#saveTextPlacementsButton');
		const originalButtonText = $submitButton.html();
		
		let newPlacements = [];
		areas.forEach(area => {
			if ($(`#tp_area_${area}`).is(':checked')) {
				const selectedTone = $(`input[name="tp_tone_${area}"]:checked`).val();
				if (selectedTone) { // Ensure a tone is selected
					newPlacements.push(`${area}-${selectedTone}`);
				} else {
					// This case should ideally not happen if a default is set when checkbox is checked
					console.warn(`Area ${area} was checked but no tone selected.`);
				}
			}
		});
		
		$submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
		
		// Construct URL: admin/items/{item_type}/{id}/update-text-placements
		const updateUrl = `${window.adminRoutes.updateTextPlacementsBase}/${currentItemType}/${currentItemId}/update-text-placements`;
		
		const currentScrollY = window.scrollY;
		
		$.ajax({
			url: updateUrl,
			type: 'POST',
			data: {
				text_placements: newPlacements,
				// CSRF token handled globally
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAlert(response.message || 'Text placements updated successfully!', 'success');
					modalInstance.hide();
					const state = getCurrentState(currentItemType);
					loadItems(currentItemType, state.page, state.search, state.coverTypeId, currentScrollY);
				} else {
					let errorMsg = `Error: ${escapeHtml(response.message || 'Unknown error')}`;
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
			error: function(xhr) {
				showAlert(`AJAX Error: ${escapeHtml(xhr.responseText || 'Failed to update text placements')}`, 'danger');
			},
			complete: function() {
				$submitButton.prop('disabled', false).html(originalButtonText);
			}
		});
	}
	
	return {
		init
	};
})();
