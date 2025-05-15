// public/js/admin/assignTemplates.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.AssignTemplates = (function() {
	const { showAlert, escapeHtml } = AppAdmin.Utils;
	const { getCurrentState } = AppAdmin.State;
	const { loadItems } = AppAdmin.Items;
	let $assignTemplatesModal, assignTemplatesModal, $assignTemplatesForm,
		$assignableTemplatesList, $noAssignableTemplatesMessage, $saveTemplateAssignmentsButton,
		$coverPreviewContainer, $coverPreviewImage, $templateOverlayImage, $previewPlaceholder; // New selectors
	
	function handleAssignTemplatesClick() {
		const coverId = $(this).data('id');
		const coverName = $(this).data('name');
		$('#assignTemplatesCoverId').val(coverId);
		$('#assignTemplatesCoverName').text(escapeHtml(coverName));
		$('#assignTemplatesCoverTypeName').text('Loading...');
		$assignableTemplatesList.html('<p class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading templates...</p>');
		$noAssignableTemplatesMessage.hide().empty();
		$saveTemplateAssignmentsButton.prop('disabled', true);
		
		// Reset preview area
		$coverPreviewImage.attr('src', '').hide();
		$templateOverlayImage.attr('src', '').hide();
		$previewPlaceholder.show();
		
		
		const url = window.adminRoutes.listAssignableTemplatesBase + '/' + coverId + '/assignable-templates';
		$.ajax({
			url: url,
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data) {
					$('#assignTemplatesCoverTypeName').text(escapeHtml(response.data.cover_type_name || 'N/A'));
					
					if (response.data.cover_image_url) {
						$coverPreviewImage.attr('src', escapeHtml(response.data.cover_image_url)).show();
						$previewPlaceholder.hide();
					} else {
						$coverPreviewImage.hide();
						$previewPlaceholder.text('No cover preview available').show();
					}
					
					$assignableTemplatesList.empty();
					if (response.message && response.data.templates.length === 0) {
						$noAssignableTemplatesMessage.text(escapeHtml(response.message)).show();
						$saveTemplateAssignmentsButton.prop('disabled', true);
					} else if (response.data.templates.length === 0) {
						$noAssignableTemplatesMessage.text('No templates found for this cover type.').show();
						$saveTemplateAssignmentsButton.prop('disabled', true);
					} else {
						response.data.templates.forEach(template => {
							const thumbnailUrlData = template.thumbnail_url ? `data-thumbnail-url="${escapeHtml(template.thumbnail_url)}"` : '';
							const checkboxHtml = `
                                <div class="form-check template-item-host" ${thumbnailUrlData}>
                                    <input class="form-check-input" type="checkbox" value="${template.id}" id="template_assign_${template.id}" name="template_ids[]" ${template.is_assigned ? 'checked' : ''}>
                                    <label class="form-check-label" for="template_assign_${template.id}">
                                        ${escapeHtml(template.name)}
                                    </label>
                                </div>`;
							$assignableTemplatesList.append(checkboxHtml);
						});
						$saveTemplateAssignmentsButton.prop('disabled', false);
					}
					assignTemplatesModal.show();
				} else {
					showAlert('Error fetching assignable templates: ' + escapeHtml(response.message || 'Unknown error'), 'danger');
					$previewPlaceholder.text('Error loading preview').show();
				}
			},
			error: function(xhr, status, error) {
				showAlert('AJAX Error fetching assignable templates: ' + escapeHtml(xhr.responseText || error), 'danger');
				console.error("AJAX Error (Assignable Templates):", status, error, xhr.responseText);
				$assignableTemplatesList.html('<p class="text-center text-danger">Failed to load templates.</p>');
				$coverPreviewImage.hide();
				$previewPlaceholder.text('Error loading preview').show();
			}
		});
	}
	
	function handleAssignTemplatesFormSubmit(event) {
		event.preventDefault();
		const coverId = $('#assignTemplatesCoverId').val();
		const templateIds = [];
		$assignableTemplatesList.find('input[type="checkbox"]:checked').each(function() {
			templateIds.push($(this).val());
		});
		
		const originalButtonText = $saveTemplateAssignmentsButton.html();
		$saveTemplateAssignmentsButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
		
		const url = window.adminRoutes.updateCoverTemplateAssignmentsBase + '/' + coverId + '/assign-templates';
		$.ajax({
			url: url,
			type: 'POST',
			data: { template_ids: templateIds },
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAlert(response.message || 'Template assignments updated successfully!', 'success');
					assignTemplatesModal.hide();
					const state = getCurrentState('covers');
					loadItems('covers', state.page, state.search, state.coverTypeId);
				} else {
					showAlert('Error updating assignments: ' + escapeHtml(response.message || 'Unknown error'), 'danger');
				}
			},
			error: function(xhr, status, error) {
				showAlert('AJAX Error updating assignments: ' + escapeHtml(xhr.responseText || error), 'danger');
				console.error("AJAX Error (Update Assignments):", status, error, xhr.responseText);
			},
			complete: function() {
				$saveTemplateAssignmentsButton.prop('disabled', false).html(originalButtonText);
			}
		});
	}
	
	function handleTemplateItemHover(event) {
		const $item = $(this); // The div.template-item-host
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
		
		// New selectors for preview
		$coverPreviewContainer = $('#assignTemplatesCoverPreviewContainer');
		$coverPreviewImage = $('#assignTemplatesCoverPreviewImage');
		$templateOverlayImage = $('#assignTemplatesTemplateOverlay');
		$previewPlaceholder = $('#assignTemplatesPreviewPlaceholder');
		
		
		if ($assignTemplatesModal.length) {
			assignTemplatesModal = new bootstrap.Modal($assignTemplatesModal[0]);
		}
		
		$('.tab-content').on('click', '.assign-templates', handleAssignTemplatesClick);
		
		if ($assignTemplatesForm.length) {
			$assignTemplatesForm.on('submit', handleAssignTemplatesFormSubmit);
		}
		
		// Event delegation for hover on template items
		if ($assignableTemplatesList.length) {
			// Attach to the list, delegate to children with class 'template-item-host'
			$assignableTemplatesList.on('mouseenter', '.template-item-host', handleTemplateItemHover);
			$assignableTemplatesList.on('mouseleave', '.template-item-host', handleTemplateItemLeave);
		}
		
		
		if ($assignTemplatesModal.length) {
			$assignTemplatesModal.on('hidden.bs.modal', function () {
				if($assignTemplatesForm.length) $assignTemplatesForm[0].reset();
				$assignableTemplatesList.empty();
				$noAssignableTemplatesMessage.hide().empty();
				$('#assignTemplatesCoverName').text('');
				$('#assignTemplatesCoverTypeName').text('');
				// Clear preview on modal close
				$coverPreviewImage.attr('src', '').hide();
				$templateOverlayImage.attr('src', '').hide();
				$previewPlaceholder.text('No preview available').show();
			});
		}
	}
	
	return {
		init
	};
})();
