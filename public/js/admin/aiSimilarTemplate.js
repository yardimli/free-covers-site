// public/js/admin/aiSimilarTemplate.js
window.AppAdmin = window.AppAdmin || {};

AppAdmin.AiSimilarTemplate = (function() {
	const { showAlert, escapeHtml } = AppAdmin.Utils;
	
	let $generateSimilarTemplateModal, generateSimilarTemplateModal, $generateSimilarTemplateForm;
	
	function handleGenerateSimilarTemplateClick() {
		const itemId = $(this).data('id');
		$('#aiOriginalTemplatePreview').text('Loading original template...');
		$('#aiTemplatePrompt').val('');
		
		$.ajax({
			url: window.adminRoutes.getItemDetails,
			type: 'GET',
			data: { item_type: 'templates', id: itemId }, // 'action' key not used
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data.json_content) {
					const item = response.data;
					$('#aiOriginalTemplateId').val(item.id);
					$('#aiOriginalTemplateJsonContent').val(item.json_content); // Store raw JSON string
					try {
						// For display, parse and stringify. For submission, use raw.
						const prettyJson = JSON.stringify(JSON.parse(item.json_content), null, 2);
						$('#aiOriginalTemplatePreview').text(prettyJson);
					} catch (e) {
						$('#aiOriginalTemplatePreview').text(item.json_content);
						showAlert('Original template JSON is not valid, showing raw content.', 'warning');
					}
					const defaultPrompt = `Create a JSON file similar to the one above. Make sure all fields for each layer are present. Make the ID's unique and human-readable like title-1, author-1, artist-1, etc. This JSON is a front cover, change it to include back cover and spine. The theme of the cover is: Make the spine 300 width. Use rotation 90 on the spine text. Include both author name and book title on spine. On the back cover, add the title and author name on the top using the fonts and colors of the front cover. Under it add the back cover text, write 2–3 paragraphs relatable to the title. The location for new layers should be 100px away from the sides. The width of the back cover should not extend into the spine. The x position of the spine texts should be at the center of the cover. Update the canvas to include appropriate values like: "canvas": {"width": 4196, "height": 2958, "frontWidth": 2048, "spineWidth": 300, "backWidth": 2048 }, updated based on the input size.`;
					$('#aiTemplatePrompt').val(defaultPrompt);
					$generateSimilarTemplateModal.find('.modal-title').text(`Generate Similar to: ${escapeHtml(item.name)}`);
					generateSimilarTemplateModal.show();
				} else {
					showAlert(`Error fetching template details or JSON content missing: ${escapeHtml(response.message || 'Unknown error')}`, 'danger');
					$('#aiOriginalTemplatePreview').text('Failed to load original template.');
				}
			},
			error: function(xhr, status, error) {
				showAlert(`AJAX Error fetching template details: ${escapeHtml(xhr.responseText || error)}`, 'danger');
				$('#aiOriginalTemplatePreview').text('Failed to load original template.');
			}
		});
	}
	
	function handleGenerateSimilarTemplateFormSubmit(event) {
		event.preventDefault();
		const $submitButton = $('#submitAiGenerateTemplateButton');
		const originalButtonText = $submitButton.html();
		$submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...');
		
		const formData = {
			// action: 'generate_similar_template', // Not needed
			// item_type: 'templates', // Not needed for this specific route
			original_template_id: $('#aiOriginalTemplateId').val(),
			original_json_content: $('#aiOriginalTemplateJsonContent').val(), // Send the raw JSON string
			user_prompt: $('#aiTemplatePrompt').val()
		};
		
		$.ajax({
			url: window.adminRoutes.generateSimilarTemplate,
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data && response.data.generated_json_content && response.data.filename) {
					const filename = response.data.filename;
					const jsonContent = response.data.generated_json_content;
					const blob = new Blob([jsonContent], { type: 'application/json;charset=utf-8;' });
					const link = document.createElement("a");
					
					if (link.download !== undefined) {
						const url = URL.createObjectURL(blob);
						link.setAttribute("href", url);
						link.setAttribute("download", filename);
						link.style.visibility = 'hidden';
						document.body.appendChild(link);
						link.click();
						document.body.removeChild(link);
						URL.revokeObjectURL(url);
						showAlert(`AI-generated template "${escapeHtml(filename)}" is being downloaded.`, 'success');
					} else {
						showAlert('Generated JSON content is ready, but your browser does not support direct download. Please copy the content manually if needed.', 'warning');
						console.log("Generated JSON for manual copy:", jsonContent);
					}
					generateSimilarTemplateModal.hide();
				} else {
					showAlert(`Error generating similar template: ${escapeHtml(response.message || 'Unknown error. Check console.')}`, 'danger');
				}
			},
			error: function(xhr, status, error) {
				showAlert(`AJAX Error generating similar template: ${escapeHtml(xhr.responseText || error)}`, 'danger');
				console.error("AJAX Error (Generate Similar Template):", status, error, xhr.responseText);
			},
			complete: function() {
				$submitButton.prop('disabled', false).html(originalButtonText);
			}
		});
	}
	
	function init() {
		$generateSimilarTemplateModal = $('#generateSimilarTemplateModal');
		$generateSimilarTemplateForm = $('#generateSimilarTemplateForm');
		if ($generateSimilarTemplateModal.length) {
			generateSimilarTemplateModal = new bootstrap.Modal($generateSimilarTemplateModal[0]);
		}
		
		$('.tab-content').on('click', '.generate-similar-template', handleGenerateSimilarTemplateClick);
		
		if ($generateSimilarTemplateForm.length) {
			$generateSimilarTemplateForm.on('submit', handleGenerateSimilarTemplateFormSubmit);
		}
		
		if ($generateSimilarTemplateModal.length) {
			$generateSimilarTemplateModal.on('hidden.bs.modal', function () {
				$('#aiOriginalTemplatePreview').text('Loading original template...');
				$('#aiTemplatePrompt').val('');
				$('#aiOriginalTemplateId').val('');
				$('#aiOriginalTemplateJsonContent').val('');
				if ($generateSimilarTemplateForm.length) $generateSimilarTemplateForm[0].reset();
			});
		}
	}
	
	return {
		init
	};
})();
