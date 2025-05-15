// public/js/admin/autoAssignTemplates.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.AutoAssignTemplates = (function() {
	const { showAlert, escapeHtml } = AppAdmin.Utils; // Assuming escapeHtml might be needed later
	let $modal, modalInstance, $startButton, $progressArea, $progressBar, $progressText, $assignmentModeRadios;
	
	function init() {
		$modal = $('#autoAssignTemplatesModal');
		if (!$modal.length) {
			console.warn('AutoAssignTemplatesModal not found.');
			return;
		}
		
		modalInstance = new bootstrap.Modal($modal[0]);
		$startButton = $('#startAutoAssignButton');
		$progressArea = $('#autoAssignProgressArea');
		$progressBar = $('#autoAssignProgressBar');
		$progressText = $('#autoAssignProgressText');
		$assignmentModeRadios = $modal.find('input[name="assignmentMode"]');
		
		const $autoAssignBtn = $('#autoAssignTemplatesBtn');
		if (!$autoAssignBtn.length) {
			console.warn('autoAssignTemplatesBtn not found.');
			return;
		}
		
		$autoAssignBtn.on('click', function() {
			// Reset modal state
			$progressArea.hide();
			$progressBar.css('width', '0%').removeClass('bg-success bg-danger').text('0%');
			$progressText.html(''); // Use html for potential <br>
			$startButton.prop('disabled', false).html('Start Auto-Assignment');
			$assignmentModeRadios.filter('[value="append"]').prop('checked', true); // Default to append
			modalInstance.show();
		});
		
		if ($startButton.length) {
			$startButton.on('click', handleStartAutoAssignment);
		} else {
			console.warn('startAutoAssignButton not found in modal.');
		}
	}
	
	function handleStartAutoAssignment() {
		const assignmentMode = $assignmentModeRadios.filter(':checked').val();
		if (!assignmentMode) {
			showAlert('Please select an assignment mode (Append or Replace).', 'warning');
			return;
		}
		
		$startButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
		$progressArea.show();
		$progressText.text('Starting process... Please wait. This might take a while.');
		$progressBar.css('width', '50%').removeClass('bg-success bg-danger').text('Processing...'); // Indeterminate progress
		
		$.ajax({
			url: window.adminRoutes.autoAssignTemplates,
			type: 'POST',
			data: {
				assignment_mode: assignmentMode,
				_token: $('meta[name="csrf-token"]').attr('content')
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					$progressBar.css('width', '100%').addClass('bg-success').text('Completed');
					let summaryHtml = `<strong>Process Completed:</strong><br>
                        Covers Scanned: ${escapeHtml(response.data.covers_scanned || 0)}<br>
                        Covers Processed (had type & placements): ${escapeHtml(response.data.covers_processed || 0)}<br>
                        Covers Skipped (no type/placements): ${escapeHtml(response.data.skipped_no_type_or_placements || 0)}<br>
                        Covers With New/Updated Assignments: ${escapeHtml(response.data.covers_with_new_assignments || 0)}<br>
                        Total New Templates Links Created: ${escapeHtml(response.data.total_templates_assigned || 0)}`;
					$progressText.html(summaryHtml);
					showAlert(response.message || 'Auto-assignment process completed successfully!', 'success');
					
					// Reload 'covers' tab items if it's active
					if ($('#covers-tab').hasClass('active') && window.AppAdmin && window.AppAdmin.Items && window.AppAdmin.Items.loadItems) {
						const params = new URLSearchParams(window.location.search);
						const page = parseInt(params.get('page'), 10) || 1;
						const search = params.get('search') || '';
						const coverTypeIdFilter = params.get('filter') || '';
						window.AppAdmin.Items.loadItems('covers', page, search, coverTypeIdFilter);
					}
				} else {
					$progressBar.css('width', '100%').addClass('bg-danger').text('Error');
					$progressText.text('Error: ' + escapeHtml(response.message || 'An unknown error occurred.'));
					showAlert('Auto-assignment failed: ' + escapeHtml(response.message || 'Unknown error'), 'danger');
				}
			},
			error: function(xhr, status, error) {
				$progressBar.css('width', '100%').addClass('bg-danger').text('AJAX Error');
				$progressText.text('AJAX Error: ' + escapeHtml(xhr.responseText || error));
				showAlert('AJAX Error during auto-assignment: ' + escapeHtml(xhr.responseText || error), 'danger');
				console.error("AJAX Error (Auto Assign Templates):", status, error, xhr.responseText);
			},
			complete: function() {
				$startButton.prop('disabled', false).html('Start Auto-Assignment');
			}
		});
	}
	
	return {
		init
	};
})();
