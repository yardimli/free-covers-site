// public/js/admin/upload.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.Upload = (function() {
	const { showAlert, escapeHtml, capitalizeFirstLetter } = AppAdmin.Utils;
	const { loadItems } = AppAdmin.Items;
	
	function handleUploadFormSubmit(event) {
		event.preventDefault();
		const $form = $(this);
		const itemType = $form.find('input[name="item_type"]').val();
		const $submitButton = $form.find('button[type="submit"]');
		const originalButtonText = $submitButton.html();
		const $modal = $form.closest('.modal'); // Get the modal instance
		
		$submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
		
		const formData = new FormData(this); // 'this' is the form element
		
		$.ajax({
			url: window.adminRoutes.uploadItem,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAlert(`${capitalizeFirstLetter(itemType).slice(0, -1)} uploaded successfully!`, 'success');
					$form[0].reset();
					if ($modal.length) {
						bootstrap.Modal.getInstance($modal[0])?.hide();
					}
					
					// Reload items for the current tab
					const currentActiveItemType = $('#adminTab button.active').data('bs-target').replace('#', '').replace('-panel', '');
					if (currentActiveItemType === itemType) {
						const params = new URLSearchParams(window.location.search);
						// const page = parseInt(params.get('page'), 10) || 1; // Go to page 1 after upload
						// const search = params.get('search') || '';
						// const coverTypeIdFilter = params.get('filter') || '';
						loadItems(itemType, 1, '', ''); // Load page 1, no search/filter
					}
				} else {
					let errorMsg = `Error uploading ${itemType}: ${escapeHtml(response.message)}`;
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
				showAlert(`AJAX Error uploading ${itemType}: ${escapeHtml(xhr.responseText || error)}`, 'danger');
				console.error("AJAX Error:", status, error, xhr.responseText);
			},
			complete: function() {
				$submitButton.prop('disabled', false).html(originalButtonText);
			}
		});
	}
	
	function init() {
		// Target forms by ID, assuming they are unique now
		$('#uploadCoverForm').on('submit', handleUploadFormSubmit);
		$('#uploadTemplateForm').on('submit', handleUploadFormSubmit);
		$('#uploadElementForm').on('submit', handleUploadFormSubmit);
		$('#uploadOverlayForm').on('submit', handleUploadFormSubmit);
	}
	
	return {
		init
	};
})();
