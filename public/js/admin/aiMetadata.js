// public/js/admin/aiMetadata.js
window.AppAdmin = window.AppAdmin || {};

AppAdmin.AiMetadata = (function() {
	const { showAlert, escapeHtml, capitalizeFirstLetter } = AppAdmin.Utils;
	const { getCurrentState } = AppAdmin.State;
	const { loadItems } = AppAdmin.Items;
	
	function handleGenerateAiMetadataClick() {
		const $button = $(this);
		const itemId = $button.data('id');
		const itemType = $button.data('type');
		
		if (!confirm(`Are you sure you want to generate AI metadata for this ${itemType.slice(0, -1)} (ID: ${itemId})? This may overwrite existing caption, keywords, and categories with AI-generated values.`)) {
			return;
		}
		
		const originalButtonHtml = $button.html();
		$button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> AI...');
		
		$.ajax({
			url: window.adminRoutes.generateAiMetadata,
			type: 'POST',
			data: { item_type: itemType, id: itemId }, // 'action' key not used
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAlert(`${capitalizeFirstLetter(itemType).slice(0,-1)} AI metadata generated/updated successfully!`, 'success');
					const state = getCurrentState(itemType);
					loadItems(itemType, state.page, state.search, state.coverTypeId);
				} else {
					showAlert(`Error generating AI metadata for ${itemType}: ${escapeHtml(response.message)}`, 'danger');
				}
			},
			error: function(xhr, status, error) {
				showAlert(`AJAX Error generating AI metadata for ${itemType}: ${escapeHtml(xhr.responseText || error)}`, 'danger');
				console.error("AJAX Error (AI Metadata):", status, error, xhr.responseText);
			},
			complete: function() {
				$button.prop('disabled', false).html(originalButtonHtml);
			}
		});
	}
	
	function handleAnalyzeTextPlacementsClick() {
		const $button = $(this);
		const itemId = $button.data('id');
		const itemType = $button.data('type'); // Should be 'covers'
		
		if (itemType !== 'covers') {
			showAlert('This action is only available for covers.', 'warning');
			return;
		}
		
		if (!confirm(`Are you sure you want to use AI to analyze text placements for Cover ID ${itemId}? This may overwrite existing text placement data.`)) {
			return;
		}
		
		const originalButtonHtml = $button.html();
		$button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Analyzing...');
		
		// Construct the URL correctly using the base path from adminRoutes
		const url = window.adminRoutes.generateAiTextPlacementsBase + '/' + itemId + '/generate-ai-text-placements';
		
		$.ajax({
			url: url,
			type: 'POST',
			// data: { _token: $('meta[name="csrf-token"]').attr('content') }, // CSRF handled globally
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAlert(response.message || 'Text placements analyzed successfully!', 'success');
					const state = getCurrentState(itemType);
					loadItems(itemType, state.page, state.search, state.coverTypeId);
				} else {
					showAlert(`Error analyzing text placements: ${escapeHtml(response.message)}`, 'danger');
				}
			},
			error: function(xhr, status, error) {
				showAlert(`AJAX Error analyzing text placements: ${escapeHtml(xhr.responseText || error)}`, 'danger');
				console.error("AJAX Error (Text Placements):", status, error, xhr.responseText);
			},
			complete: function() {
				$button.prop('disabled', false).html(originalButtonHtml);
			}
		});
	}
	
	function init() {
		$('.tab-content').on('click', '.generate-ai-metadata', handleGenerateAiMetadataClick)
		$('.tab-content').on('click', '.analyze-text-placements', handleAnalyzeTextPlacementsClick); ;
	}
	
	return {
		init
	};
})();
