// public/js/admin/assignTemplates.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.AssignTemplates = (function() {
	const { showAlert, escapeHtml } = AppAdmin.Utils;
	const { loadItems } = AppAdmin.Items; // For user-triggered save
	
	let $assignTemplatesModal, assignTemplatesModal, $assignTemplatesForm,
		$assignableTemplatesList, $noAssignableTemplatesMessage,
		$saveTemplateAssignmentsButton, $coverPreviewContainer,
		$coverPreviewImage, $templateOverlayImage, $previewPlaceholder,
		$aiChooseTemplatesButton, $aiChoiceProgressArea,
		$aiChoiceProgressBar, $aiChoiceProgressText;
	
	// Core logic for loading data into the modal
	// Returns a Promise that resolves with { coverData, templates } when data is loaded and modal is shown, or rejects on error.
	function _loadDataAndShowModal(coverId) {
		return new Promise((resolve, reject) => {
			$('#assignTemplatesCoverId').val(coverId);
			// Cover name will be set from the AJAX response
			
			$('#assignTemplatesCoverTypeName').text('Loading...');
			$assignableTemplatesList.html('<p class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading templates...</p>');
			$noAssignableTemplatesMessage.hide().empty();
			$saveTemplateAssignmentsButton.prop('disabled', true);
			$coverPreviewImage.attr('src', '').hide();
			$templateOverlayImage.attr('src', '').hide();
			$previewPlaceholder.show().text('Loading preview...'); // Default placeholder text
			$aiChoiceProgressArea.hide();
			$aiChoiceProgressBar.width('0%').text('');
			$aiChoiceProgressText.empty();
			$aiChooseTemplatesButton.prop('disabled', true).find('i').removeClass('fa-spinner fa-spin').addClass('fa-robot');
			
			const url = window.adminRoutes.listAssignableTemplatesBase + '/' + coverId + '/assignable-templates';
			$.ajax({
				url: url,
				type: 'GET',
				dataType: 'json',
				success: function(response) {
					if (response.success && response.data) {
						$('#assignTemplatesCoverName').text(escapeHtml(response.data.cover_name || `Cover ID: ${coverId}`));
						$('#assignTemplatesCoverTypeName').text(escapeHtml(response.data.cover_type_name || 'N/A'));
						
						if (response.data.cover_image_url) {
							$coverPreviewImage.attr('src', escapeHtml(response.data.cover_image_url)).show();
							$previewPlaceholder.hide();
						} else {
							$coverPreviewImage.hide();
							$previewPlaceholder.text('No cover preview available').show();
						}
						
						$assignableTemplatesList.empty();
						let templatesFound = false;
						if (response.data.templates && response.data.templates.length > 0) {
							templatesFound = true;
							response.data.templates.forEach(template => {
								const thumbnailUrlData = template.thumbnail_url ? `data-thumbnail-url="${escapeHtml(template.thumbnail_url)}"` : '';
								const checkboxHtml = ` <div class="template-item-host d-inline-block" style="width:30%;" ${thumbnailUrlData}> <input class="form-check-input" type="checkbox" value="${template.id}" id="template_assign_${template.id}" name="template_ids[]" ${template.is_assigned ? 'checked' : ''}> <label class="form-check-label" for="template_assign_${template.id}"> ${escapeHtml(template.name)} </label> </div>`;
								$assignableTemplatesList.append(checkboxHtml);
							});
							$saveTemplateAssignmentsButton.prop('disabled', false);
							$aiChooseTemplatesButton.prop('disabled', false);
						} else {
							const message = response.message || 'No templates found for this cover type.';
							$noAssignableTemplatesMessage.text(escapeHtml(message)).show();
							$saveTemplateAssignmentsButton.prop('disabled', true); // No templates, nothing to save manually
							$aiChooseTemplatesButton.prop('disabled', true); // No templates for AI
						}
						
						assignTemplatesModal.show();
						$assignTemplatesModal.one('shown.bs.modal', () => resolve({
							coverData: response.data,
							templates: response.data.templates || [],
							hasTemplates: templatesFound
						}));
						
					} else {
						showAlert('Error fetching assignable templates: ' + escapeHtml(response.message || 'Unknown error'), 'danger');
						$previewPlaceholder.text('Error loading preview').show();
						reject(new Error(response.message || 'Unknown error fetching assignable templates'));
					}
				},
				error: function(xhr, status, error) {
					showAlert('AJAX Error fetching assignable templates: ' + escapeHtml(xhr.responseText || error), 'danger');
					console.error("AJAX Error (Assignable Templates):", status, error, xhr.responseText);
					$assignableTemplatesList.html('<p class="text-center text-danger">Failed to load templates.</p>');
					$coverPreviewImage.hide();
					$previewPlaceholder.text('Error loading preview').show();
					reject(new Error(xhr.responseText || error));
				}
			});
		});
	}
	
	// Core logic for AI choosing templates
	// Returns a Promise that resolves with { goodFitCount, badFitCount, errorCount, summaryMessage } or rejects on major error.
	async function _performAiChoice() {
		const coverId = $('#assignTemplatesCoverId').val();
		if (!coverId) {
			// showAlert('Cover ID not found for AI choice.', 'danger'); // Less verbose for batch
			return Promise.reject(new Error('Cover ID not found for AI choice.'));
		}
		
		const $templateItems = $assignableTemplatesList.find('.template-item-host');
		if ($templateItems.length === 0) {
			return Promise.resolve({ goodFitCount: 0, badFitCount: 0, errorCount: 0, summaryMessage: 'No templates to process.' });
		}
		
		$aiChooseTemplatesButton.prop('disabled', true).find('i').removeClass('fa-robot').addClass('fa-spinner fa-spin');
		$saveTemplateAssignmentsButton.prop('disabled', true);
		$assignableTemplatesList.find('input[type="checkbox"]').prop('disabled', true);
		$aiChoiceProgressArea.show();
		$aiChoiceProgressBar.width('0%').attr('aria-valuenow', 0).text('0%');
		$aiChoiceProgressText.text('Initializing AI evaluation...');
		
		let processedCount = 0;
		const totalTemplates = $templateItems.length;
		let goodFitCount = 0;
		let badFitCount = 0;
		let errorCount = 0;
		
		for (const item of $templateItems) {
			const $itemHost = $(item);
			const $checkbox = $itemHost.find('input[type="checkbox"]');
			const templateId = $checkbox.val();
			const templateName = $itemHost.find('label').text().trim() || `Template ID ${templateId}`;
			processedCount++;
			const progressPercent = Math.round((processedCount / totalTemplates) * 100);
			$aiChoiceProgressBar.width(progressPercent + '%').attr('aria-valuenow', progressPercent).text(progressPercent + '%');
			$aiChoiceProgressText.html(`Processing ${processedCount}/${totalTemplates}: <i>${escapeHtml(templateName)}</i>...`);
			
			try {
				const response = await $.ajax({
					url: `${window.adminRoutes.aiEvaluateTemplateFitBase}/${coverId}/templates/${templateId}/ai-evaluate-fit`,
					type: 'POST',
					dataType: 'json'
				});
				if (response.success && typeof response.data.should_assign === 'boolean') {
					$checkbox.prop('checked', response.data.should_assign);
					if (response.data.should_assign) goodFitCount++; else badFitCount++;
				} else {
					errorCount++;
					console.warn(`AI evaluation issue for template ${templateId}:`, response.message || 'Invalid AI response');
				}
			} catch (xhr) {
				errorCount++;
				console.error(`AJAX error evaluating template ${templateId}:`, xhr.statusText, xhr.responseText);
			}
			if (processedCount < totalTemplates) {
				await new Promise(resolve => setTimeout(resolve, 300)); // API rate limit consideration
			}
		}
		
		$aiChooseTemplatesButton.prop('disabled', false).find('i').removeClass('fa-spinner fa-spin').addClass('fa-robot');
		$saveTemplateAssignmentsButton.prop('disabled', false);
		$assignableTemplatesList.find('input[type="checkbox"]').prop('disabled', false);
		
		let summaryMessage = `AI processing complete. ${goodFitCount} good fit(s), ${badFitCount} bad fit(s).`;
		if (errorCount > 0) summaryMessage += ` ${errorCount} error(s).`;
		$aiChoiceProgressText.html(summaryMessage);
		// setTimeout(() => $aiChoiceProgressArea.fadeOut(), 8000); // Batch mode will control this visibility
		
		return { goodFitCount, badFitCount, errorCount, summaryMessage };
	}
	
	// Core logic for saving assignments
	// Returns a Promise that resolves with the server response or rejects on error.
	async function _saveAssignments(options = {}) {
		const coverId = $('#assignTemplatesCoverId').val();
		const templateIds = [];
		$assignableTemplatesList.find('input[type="checkbox"]:checked').each(function() {
			templateIds.push($(this).val());
		});
		
		const originalButtonText = $saveTemplateAssignmentsButton.html();
		if (!options.skipUiUpdates) {
			$saveTemplateAssignmentsButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
		}
		
		const url = window.adminRoutes.updateCoverTemplateAssignmentsBase + '/' + coverId + '/assign-templates';
		
		try {
			const response = await $.ajax({
				url: url,
				type: 'POST',
				data: { template_ids: templateIds },
				dataType: 'json',
			});
			
			if (!options.skipUiUpdates) { // For user-triggered save
				if (response.success) {
					showAlert(response.message || 'Template assignments updated successfully!', 'success');
					assignTemplatesModal.hide();
					const params = new URLSearchParams(window.location.search);
					const page = parseInt(params.get('page'), 10) || 1;
					const search = params.get('search') || '';
					const coverTypeIdFilter = params.get('filter') || '';
					loadItems('covers', page, search, coverTypeIdFilter, window.scrollY);
				} else {
					showAlert('Error updating assignments: ' + escapeHtml(response.message || 'Unknown error'), 'danger');
				}
			}
			return response; // Resolve with the server response for batch mode
		} catch (xhr) {
			if (!options.skipUiUpdates) { // For user-triggered save
				showAlert('AJAX Error updating assignments: ' + escapeHtml(xhr.responseText || xhr.statusText || 'Unknown AJAX error'), 'danger');
			}
			console.error("AJAX Error (Update Assignments):", xhr.statusText, xhr.responseText);
			throw new Error(xhr.responseText || xhr.statusText || 'Unknown AJAX error during save');
		} finally {
			if (!options.skipUiUpdates) {
				$saveTemplateAssignmentsButton.prop('disabled', false).html(originalButtonText);
			}
		}
	}
	
	// User-triggered event handlers
	function handleAssignTemplatesClick() {
		const coverId = $(this).data('id');
		_loadDataAndShowModal(coverId)
			.catch(error => {
				console.error("Error in handleAssignTemplatesClick (user):", error.message);
			});
	}
	
	async function handleAiChooseTemplatesClick() {
		try {
			await _performAiChoice();
		} catch (error) {
			showAlert('An error occurred during AI template selection: ' + escapeHtml(error.message), 'danger');
			$aiChooseTemplatesButton.prop('disabled', false).find('i').removeClass('fa-spinner fa-spin').addClass('fa-robot');
			$saveTemplateAssignmentsButton.prop('disabled', false);
			$assignableTemplatesList.find('input[type="checkbox"]').prop('disabled', false);
		}
	}
	
	function handleAssignTemplatesFormSubmit(event) {
		event.preventDefault();
		_saveAssignments() // Uses default options for UI updates
			.catch(error => {
				console.error("Error in handleAssignTemplatesFormSubmit (user):", error.message);
			});
	}
	
	function handleTemplateItemHover(event) {
		const $item = $(this);
		const thumbnailUrl = $item.data('thumbnail-url');
		if (thumbnailUrl) {
			$templateOverlayImage.attr('src', thumbnailUrl).show();
		}
	}
	
	function handleTemplateItemLeave() {
		$templateOverlayImage.hide().attr('src', '');
	}
	
	function init() {
		$assignTemplatesModal = $('#assignTemplatesModal');
		$assignTemplatesForm = $('#assignTemplatesForm');
		$assignableTemplatesList = $('#assignableTemplatesList');
		$noAssignableTemplatesMessage = $('#noAssignableTemplatesMessage');
		$saveTemplateAssignmentsButton = $('#saveTemplateAssignmentsButton');
		$coverPreviewContainer = $('#assignTemplatesCoverPreviewContainer');
		$coverPreviewImage = $('#assignTemplatesCoverPreviewImage');
		$templateOverlayImage = $('#assignTemplatesTemplateOverlay');
		$previewPlaceholder = $('#assignTemplatesPreviewPlaceholder');
		$aiChooseTemplatesButton = $('#aiChooseTemplatesButton');
		$aiChoiceProgressArea = $('#aiChoiceProgressArea');
		$aiChoiceProgressBar = $('#aiChoiceProgressBar');
		$aiChoiceProgressText = $('#aiChoiceProgressText');
		
		if ($assignTemplatesModal.length) {
			if (!assignTemplatesModal) { // Ensure it's initialized only once
				assignTemplatesModal = new bootstrap.Modal($assignTemplatesModal[0]);
			}
		}
		
		$('.tab-content').on('click', '.assign-templates', handleAssignTemplatesClick);
		if ($assignTemplatesForm.length) {
			$assignTemplatesForm.on('submit', handleAssignTemplatesFormSubmit);
		}
		if ($assignableTemplatesList.length) {
			$assignableTemplatesList.on('mouseenter', '.template-item-host', handleTemplateItemHover);
			$assignableTemplatesList.on('mouseleave', '.template-item-host', handleTemplateItemLeave);
		}
		if ($aiChooseTemplatesButton.length) {
			$aiChooseTemplatesButton.on('click', handleAiChooseTemplatesClick);
		}
		
		if ($assignTemplatesModal.length) {
			$assignTemplatesModal.on('hidden.bs.modal', function () {
				if($assignTemplatesForm.length) $assignTemplatesForm[0].reset();
				$assignableTemplatesList.empty();
				$noAssignableTemplatesMessage.hide().empty();
				$('#assignTemplatesCoverName').text('');
				$('#assignTemplatesCoverTypeName').text('');
				$coverPreviewImage.attr('src', '').hide();
				$templateOverlayImage.attr('src', '').hide();
				$previewPlaceholder.text('No preview available').show();
				$aiChoiceProgressArea.hide();
				$aiChoiceProgressBar.width('0%').attr('aria-valuenow', 0).text('');
				$aiChoiceProgressText.empty();
				$aiChooseTemplatesButton.prop('disabled', false).find('i').removeClass('fa-spinner fa-spin').addClass('fa-robot');
				$assignableTemplatesList.find('input[type="checkbox"]').prop('disabled', false);
			});
		}
	}
	
	return {
		init,
		programmatic: { // Namespace for batch-callable functions
			loadDataAndShowModal: _loadDataAndShowModal,
			performAiChoice: _performAiChoice,
			saveAssignments: _saveAssignments,
			hideModal: () => { if (assignTemplatesModal) assignTemplatesModal.hide(); },
			getModalElement: () => $assignTemplatesModal
		}
	};
})();
