// public/js/admin/assignTemplates.js
window.AppAdmin = window.AppAdmin || {};

AppAdmin.AssignTemplates = (function() {
	const { showAlert, escapeHtml } = AppAdmin.Utils;
	const { getCurrentState } = AppAdmin.State;
	const { loadItems } = AppAdmin.Items;
	
	let $assignTemplatesModal, assignTemplatesModal, $assignTemplatesForm, $assignableTemplatesList, $noAssignableTemplatesMessage, $saveTemplateAssignmentsButton;
	
	function handleAssignTemplatesClick() {
		const coverId = $(this).data('id');
		const coverName = $(this).data('name');
		
		$('#assignTemplatesCoverId').val(coverId);
		$('#assignTemplatesCoverName').text(coverName);
		$('#assignTemplatesCoverTypeName').text('Loading...');
		$assignableTemplatesList.html('<p class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading templates...</p>');
		$noAssignableTemplatesMessage.hide().empty();
		$saveTemplateAssignmentsButton.prop('disabled', true);
		
		const url = window.adminRoutes.listAssignableTemplatesBase + '/' + coverId + '/assignable-templates';
		
		$.ajax({
			url: url,
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data) {
					$('#assignTemplatesCoverTypeName').text(escapeHtml(response.data.cover_type_name || 'N/A'));
					$assignableTemplatesList.empty();
					
					if (response.message && response.data.templates.length === 0) {
						$noAssignableTemplatesMessage.text(escapeHtml(response.message)).show();
						$saveTemplateAssignmentsButton.prop('disabled', true);
					} else if (response.data.templates.length === 0) {
						$noAssignableTemplatesMessage.text('No templates found for this cover type.').show();
						$saveTemplateAssignmentsButton.prop('disabled', true);
					} else {
						response.data.templates.forEach(template => {
							const checkboxHtml = `
                                <div class="form-check">
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
				}
			},
			error: function(xhr, status, error) {
				showAlert('AJAX Error fetching assignable templates: ' + escapeHtml(xhr.responseText || error), 'danger');
				console.error("AJAX Error (Assignable Templates):", status, error, xhr.responseText);
				$assignableTemplatesList.html('<p class="text-center text-danger">Failed to load templates.</p>');
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
	
	function init() {
		$assignTemplatesModal = $('#assignTemplatesModal');
		$assignTemplatesForm = $('#assignTemplatesForm');
		$assignableTemplatesList = $('#assignableTemplatesList');
		$noAssignableTemplatesMessage = $('#noAssignableTemplatesMessage');
		$saveTemplateAssignmentsButton = $('#saveTemplateAssignmentsButton');
		
		if ($assignTemplatesModal.length) {
			assignTemplatesModal = new bootstrap.Modal($assignTemplatesModal[0]);
		}
		
		$('.tab-content').on('click', '.assign-templates', handleAssignTemplatesClick);
		
		if ($assignTemplatesForm.length) {
			$assignTemplatesForm.on('submit', handleAssignTemplatesFormSubmit);
		}
		
		if ($assignTemplatesModal.length) {
			$assignTemplatesModal.on('hidden.bs.modal', function () {
				if($assignTemplatesForm.length) $assignTemplatesForm[0].reset();
				$assignableTemplatesList.empty();
				$noAssignableTemplatesMessage.hide().empty();
				$('#assignTemplatesCoverName').text('');
				$('#assignTemplatesCoverTypeName').text('');
			});
		}
	}
	
	return {
		init
	};
})();
