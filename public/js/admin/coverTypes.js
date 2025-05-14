// public/js/admin/coverTypes.js
window.AppAdmin = window.AppAdmin || {};

AppAdmin.CoverTypes = (function() {
	const { showAlert, escapeHtml } = AppAdmin.Utils;
	let allCoverTypes = [];
	
	function fetchCoverTypes() {
		return $.ajax({
			url: window.adminRoutes.listCoverTypes,
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data.cover_types) {
					allCoverTypes = response.data.cover_types;
					populateAllCoverTypeDropdowns();
				} else {
					showAlert('Error fetching cover types: ' + escapeHtml(response.message), 'danger');
				}
			},
			error: function(xhr, status, error) {
				showAlert('Failed to connect to server to fetch cover types: ' + escapeHtml(xhr.responseText || error), 'danger');
			}
		});
	}
	
	function populateAllCoverTypeDropdowns() {
		$('.admin-cover-type-dropdown').each(function() {
			const $dropdown = $(this);
			const firstOptionValue = $dropdown.find('option:first-child').val();
			const firstOptionText = $dropdown.find('option:first-child').text();
			const currentValue = $dropdown.val(); // Preserve current selection if any
			
			$dropdown.empty();
			$dropdown.append(`<option value="${escapeHtml(firstOptionValue)}">${escapeHtml(firstOptionText)}</option>`);
			allCoverTypes.forEach(function(type) {
				$dropdown.append(`<option value="${escapeHtml(type.id)}">${escapeHtml(type.type_name)}</option>`);
			});
			$dropdown.val(currentValue); // Re-apply selection
		});
	}
	
	function getAllCoverTypes() {
		return allCoverTypes;
	}
	
	return {
		fetchCoverTypes,
		populateAllCoverTypeDropdowns,
		getAllCoverTypes
	};
})();
