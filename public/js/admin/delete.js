// public/js/admin/delete.js
window.AppAdmin = window.AppAdmin || {};

AppAdmin.Delete = (function() {
	const { showAlert, escapeHtml, capitalizeFirstLetter } = AppAdmin.Utils;
	const { loadItems } = AppAdmin.Items;
	
	function handleDeleteItemClick() {
		const $button = $(this);
		const itemId = $button.data('id');
		const itemType = $button.data('type');
		
		if (confirm(`Are you sure you want to delete this ${itemType.slice(0, -1)} (ID: ${itemId})? This action cannot be undone.`)) {
			const originalButtonHtml = $button.html();
			$button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
			
			const currentScrollY = window.scrollY;
			
			$.ajax({
				url: window.adminRoutes.deleteItem,
				type: 'POST',
				data: { item_type: itemType, id: itemId }, // 'action' key not used
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showAlert(`${capitalizeFirstLetter(itemType).slice(0,-1)} deleted successfully!`, 'success');
						const params = new URLSearchParams(window.location.search);
						const page = parseInt(params.get('page'), 10) || 1;
						const search = params.get('search') || '';
						const coverTypeIdFilter = params.get('filter') || '';
						loadItems(itemType, page, search, coverTypeIdFilter, currentScrollY);
					} else {
						showAlert(`Error deleting ${itemType}: ${escapeHtml(response.message)}`, 'danger');
						$button.prop('disabled', false).html(originalButtonHtml);
					}
				},
				error: function(xhr, status, error) {
					showAlert(`AJAX Error deleting ${itemType}: ${escapeHtml(xhr.responseText || error)}`, 'danger');
					$button.prop('disabled', false).html(originalButtonHtml);
					console.error("AJAX Error:", status, error, xhr.responseText);
				}
			});
		}
	}
	
	function init() {
		$('.tab-content').on('click', '.delete-item', handleDeleteItemClick);
	}
	
	return {
		init
	};
})();
