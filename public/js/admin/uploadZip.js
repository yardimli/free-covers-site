// public/js/admin/uploadZip.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.UploadZip = (function () {
	const {showAlert, escapeHtml} = AppAdmin.Utils;
	
	function handleSubmit(event) {
		event.preventDefault();
		const formData = new FormData(this); // 'this' is the form
		const $form = $(this);
		const $modal = $form.closest('.modal');
		const $submitButton = $form.find('button[type="submit"]');
		const originalButtonText = $submitButton.html();
		
		$submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading & Processing...');
		
		$.ajax({
			url: window.adminRoutes.uploadCoverZip,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					let message = response.message || 'ZIP processed successfully.';
					if (response.data) {
						message += `<br>Created: ${response.data.created_count || 0}, Updated: ${response.data.updated_count || 0}, Errors: ${response.data.error_count || 0}.`;
						if (response.data.details && response.data.details.length > 0 && Array.isArray(response.data.details)) {
							message += '<ul class="mt-2" style="font-size: 0.9em; max-height: 150px; overflow-y: auto; list-style-type: none; padding-left: 0;">';
							response.data.details.forEach(detail => {
								let statusClass = '';
								if (detail.status === 'created') statusClass = 'text-success';
								else if (detail.status === 'updated') statusClass = 'text-primary';
								else if (detail.status === 'error') statusClass = 'text-danger';
								message += `<li class="${statusClass}">${escapeHtml(detail.name)}: ${escapeHtml(detail.status)} ${detail.reason ? '(' + escapeHtml(detail.reason) + ')' : ''}</li>`;
							});
							message += '</ul>';
						}
					}
					showAlert(message, 'success'); // showAlert should handle HTML
					if ($modal.length && bootstrap.Modal.getInstance($modal[0])) {
						bootstrap.Modal.getInstance($modal[0]).hide();
					}
					
					
					if (typeof AppAdmin.Items.loadItems === 'function') {
						const currentActiveItemType = $('#adminTab button.active').data('bs-target').replace('#', '').replace('-panel', '');
						if (currentActiveItemType === 'covers') {
							AppAdmin.Items.loadItems('covers', 1, '', '', $('#filterNoTemplatesBtn').hasClass('active'));
						}
					}
				} else {
					let errorMsg = 'Error: ' + escapeHtml(response.message || 'Unknown error during ZIP upload.');
					if (response.errors) {
						errorMsg += '<ul>';
						$.each(response.errors, function (field, messages) {
							messages.forEach(function (msg) {
								errorMsg += `<li>${escapeHtml(field)}: ${escapeHtml(msg)}</li>`;
							});
						});
						errorMsg += '</ul>';
					}
					showAlert(errorMsg, 'danger');
				}
			},
			error: function (xhr, status, error) {
				let errorMsg = 'AJAX Error: ' + escapeHtml(xhr.responseText || error);
				try {
					const errResponse = JSON.parse(xhr.responseText);
					if (errResponse.message) {
						errorMsg = 'Error: ' + escapeHtml(errResponse.message);
						if (errResponse.errors) {
							errorMsg += '<ul>';
							$.each(errResponse.errors, function (field, messages) {
								messages.forEach(function (msg) {
									errorMsg += `<li>${escapeHtml(field)}: ${escapeHtml(msg)}</li>`;
								});
							});
							errorMsg += '</ul>';
						}
					}
				} catch (e) { /* ignore parsing error, use raw responseText */
				}
				showAlert(errorMsg, 'danger'); // showAlert should handle HTML
			},
			complete: function () {
				$submitButton.prop('disabled', false).html(originalButtonText);
				$form[0].reset();
				const $coverTypeDropdown = $form.find('#zipDefaultCoverTypeId');
				if ($coverTypeDropdown.length > 0 && $coverTypeDropdown.find('option:first').length > 0) {
					$coverTypeDropdown.val($coverTypeDropdown.find('option:first').val());
				}
			}
		});
	}
	
	function init() {
		$('#uploadCoverZipForm').on('submit', handleSubmit);
		
		// Ensure the modal is fully reset when hidden, especially file input
		const zipModalElement = document.getElementById('uploadCoverZipModal');
		const $zipFileInput = $('#coverZipFile');
		const $processLocalCheckbox = $('#processLocalTempFolder');
		
		if ($processLocalCheckbox.length && $zipFileInput.length) {
			// Initial state based on checkbox (it defaults to unchecked)
			if ($processLocalCheckbox.is(':checked')) {
				$zipFileInput.prop('required', false).closest('.mb-3').hide();
			} else {
				$zipFileInput.prop('required', true).closest('.mb-3').show();
			}
			
			$processLocalCheckbox.on('change', function () {
				if ($(this).is(':checked')) {
					$zipFileInput.prop('required', false).closest('.mb-3').slideUp();
					$zipFileInput.val(''); // Clear any selected file
				} else {
					$zipFileInput.prop('required', true).closest('.mb-3').slideDown();
				}
			});
		}
		
		if (zipModalElement) {
			zipModalElement.addEventListener('hidden.bs.modal', function () {
				$('#uploadCoverZipForm')[0].reset();
				const $coverTypeDropdown = $('#zipDefaultCoverTypeId');
				if ($coverTypeDropdown.length > 0 && $coverTypeDropdown.find('option:first').length > 0) {
					$coverTypeDropdown.val($coverTypeDropdown.find('option:first').val());
				}
				
				// Reset checkbox and file input visibility/requirement
				if ($processLocalCheckbox.length && $zipFileInput.length) {
					$processLocalCheckbox.prop('checked', false);
					$zipFileInput.prop('required', true).closest('.mb-3').show();
					$zipFileInput.val('');
				}
			});
		}
	}
	
	return {init};
})();
