// public/js/admin/aiMetadata.js
window.AppAdmin = window.AppAdmin || {};

AppAdmin.AiMetadata = (function() {
	const { showAlert, escapeHtml, capitalizeFirstLetter } = AppAdmin.Utils;
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
					const params = new URLSearchParams(window.location.search);
					const page = parseInt(params.get('page'), 10) || 1;
					const search = params.get('search') || '';
					const coverTypeIdFilter = params.get('filter') || '';
					loadItems(itemType, page, search, coverTypeIdFilter);
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
	
	
	function init() {
		$('.tab-content').on('click', '.generate-ai-metadata', handleGenerateAiMetadataClick)
	}
	
	return {
		init
	};
})();
