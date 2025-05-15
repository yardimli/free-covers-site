// public/js/admin/upload.js
window.AppAdmin = window.AppAdmin || {};

AppAdmin.Upload = (function() {
	const { showAlert, escapeHtml, deriveNameFromFilename, capitalizeFirstLetter } = AppAdmin.Utils;
	const { loadItems } = AppAdmin.Items;
	
	function handleUploadFormSubmit(event) {
		event.preventDefault();
		const $form = $(this);
		const itemType = $form.find('input[name="item_type"]').val();
		const $submitButton = $form.find('button[type="submit"]');
		const originalButtonText = $submitButton.html();
		$submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
		
		let filesToUpload = [];
		let commonFormDataFields = {};
		const formElements = this.elements;
		
		for (let i = 0; i < formElements.length; i++) {
			const element = formElements[i];
			if (element.name && element.name !== 'name' && element.type !== 'file' && element.name !== 'item_type' && element.name !== 'action') {
				if ((element.type === 'checkbox' || element.type === 'radio') && element.checked) {
					commonFormDataFields[element.name] = element.value;
				} else if (element.type !== 'checkbox' && element.type !== 'radio') {
					commonFormDataFields[element.name] = element.value;
				}
			}
		}
		if ($form.find('select[name="cover_type_id"]').length) {
			commonFormDataFields['cover_type_id'] = $form.find('select[name="cover_type_id"]').val();
		}
		
		let nameInputVal = $form.find('input[name="name"]').val();
		
		if (itemType === 'covers' || itemType === 'elements' || itemType === 'overlays') {
			const imageFilesInput = $form.find('input[name="image_file"]')[0];
			const imageFiles = imageFilesInput.files;
			if (imageFiles.length === 0 && imageFilesInput.required) {
				showAlert('Image file(s) are required.', 'danger');
				$submitButton.prop('disabled', false).html(originalButtonText);
				return;
			}
			for (let i = 0; i < imageFiles.length; i++) {
				filesToUpload.push({
					type: 'image',
					file: imageFiles[i],
					derivedName: (imageFiles.length === 1 && nameInputVal) ? nameInputVal : deriveNameFromFilename(imageFiles[i].name)
				});
			}
		} else if (itemType === 'templates') {
			const jsonFilesInput = $form.find('input[name="json_file"]')[0];
			const thumbnailFilesInput = $form.find('input[name="thumbnail_file"]')[0];
			const jsonFiles = jsonFilesInput.files;
			const thumbnailFiles = thumbnailFilesInput.files;
			
			if ((jsonFiles.length === 0 && jsonFilesInput.required) || (thumbnailFiles.length === 0 && thumbnailFilesInput.required)) {
				showAlert('Both JSON and Thumbnail file(s) are required for templates.', 'danger');
				$submitButton.prop('disabled', false).html(originalButtonText);
				return;
			}
			if (jsonFiles.length !== thumbnailFiles.length) {
				showAlert('The number of JSON files must match the number of Thumbnail files.', 'danger');
				$submitButton.prop('disabled', false).html(originalButtonText);
				return;
			}
			for (let i = 0; i < jsonFiles.length; i++) {
				filesToUpload.push({
					type: 'template',
					json_file: jsonFiles[i],
					thumbnail_file: thumbnailFiles[i],
					derivedName: (jsonFiles.length === 1 && nameInputVal) ? nameInputVal : deriveNameFromFilename(jsonFiles[i].name)
				});
			}
		}
		
		if (filesToUpload.length === 0) {
			showAlert('No files selected for upload.', 'warning');
			$submitButton.prop('disabled', false).html(originalButtonText);
			return;
		}
		
		let uploadPromises = [];
		let successCount = 0;
		let errorCount = 0;
		$submitButton.html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading 0/${filesToUpload.length}...`);
		
		filesToUpload.forEach((uploadItem, index) => {
			const formData = new FormData();
			// formData.append('action', 'upload_item'); // Not needed, route implies action
			formData.append('item_type', itemType);
			formData.append('name', uploadItem.derivedName);
			
			for (const key in commonFormDataFields) {
				formData.append(key, commonFormDataFields[key]);
			}
			
			if (uploadItem.type === 'image') {
				formData.append('image_file', uploadItem.file, uploadItem.file.name);
			} else if (uploadItem.type === 'template') {
				formData.append('json_file', uploadItem.json_file, uploadItem.json_file.name);
				formData.append('thumbnail_file', uploadItem.thumbnail_file, uploadItem.thumbnail_file.name);
			}
			
			const promise = $.ajax({
				url: window.adminRoutes.uploadItem,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				dataType: 'json'
			}).done(function(response) {
				if (response.success) {
					successCount++;
				} else {
					errorCount++;
					showAlert(`Error uploading "${escapeHtml(uploadItem.derivedName)}": ${escapeHtml(response.message)}`, 'danger');
				}
			}).fail(function(xhr, status, error) {
				errorCount++;
				showAlert(`AJAX Error uploading "${escapeHtml(uploadItem.derivedName)}": ${escapeHtml(xhr.responseText || error)}`, 'danger');
				console.error("AJAX Error:", status, error, xhr.responseText);
			}).always(function() {
				$submitButton.html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading ${successCount + errorCount}/${filesToUpload.length}...`);
			});
			uploadPromises.push(promise);
		});
		
		Promise.allSettled(uploadPromises).then(() => {
			$submitButton.prop('disabled', false).html(originalButtonText);
			if (successCount > 0) {
				$form[0].reset();
				const currentActiveItemType = $('#adminTab button.active').data('bs-target').replace('#', '').replace('-panel', '');
				if (currentActiveItemType === itemType) {
					const params = new URLSearchParams(window.location.search);
					const page = parseInt(params.get('page'), 10) || 1;
					const search = params.get('search') || '';
					const coverTypeIdFilter = params.get('filter') || '';
					loadItems(itemType, 1, '', coverTypeIdFilter);
				}
			}
			
			if (errorCount === 0 && successCount > 0) {
				showAlert(`Successfully uploaded ${successCount} item(s).`, 'success');
			} else if (errorCount > 0 && successCount === 0) {
				showAlert(`All ${filesToUpload.length} uploads failed. Please check error messages.`, 'danger');
			} else if (errorCount > 0 && successCount > 0) {
				showAlert(`${successCount} of ${filesToUpload.length} items uploaded successfully. ${errorCount} failed.`, 'warning');
			}
		});
	}
	
	function init() {
		$('form[id^="upload"]').on('submit', handleUploadFormSubmit);
	}
	
	return {
		init
	};
})();
