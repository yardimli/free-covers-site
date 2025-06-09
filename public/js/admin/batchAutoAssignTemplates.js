// public/js/admin/batchAutoAssignTemplates.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.BatchAutoAssignTemplates = (function() {
	const { showAlert, escapeHtml } = AppAdmin.Utils;
	const { loadItems } = AppAdmin.Items;
	const AssignTemplatesProgrammatic = AppAdmin.AssignTemplates.programmatic;
	
	let $batchButton, $progressArea, $progressBar, $progressText, $progressSummary;
	let isProcessing = false;
	
	function init() {
		$batchButton = $('#autoAssignTemplatesBtn');
		$progressArea = $('#batchProgressArea');
		$progressBar = $('#batchProgressBar');
		$progressText = $('#batchProgressText');
		$progressSummary = $('#batchProgressSummary');
		
		$batchButton.on('click', startBatchProcess);
	}
	
	async function startBatchProcess() {
		if (isProcessing) {
			showAlert('A batch process is already running.', 'warning');
			return;
		}
		if (!confirm('This will attempt to automatically assign up to 2 suitable random templates to all covers that currently have none, using AI. This may take a while and consume API credits. Continue?')) {
			return;
		}
		
		isProcessing = true;
		$batchButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
		$progressArea.show();
		$progressBar.width('0%').removeClass('bg-success bg-danger bg-warning').addClass('progress-bar-animated');
		$progressText.text('Fetching covers...');
		$progressSummary.empty().append('<h6>Batch Log:</h6><ul class="list-unstyled" style="max-height: 200px; overflow-y: auto;"></ul>');
		
		try {
			const response = await $.ajax({
				url: window.adminRoutes.getCoversWithoutTemplates,
				type: 'GET',
				dataType: 'json'
			});
			
			if (!response.success || !response.data.covers) {
				throw new Error(response.message || 'Failed to fetch covers.');
			}
			
			const coversToProcess = response.data.covers;
			if (coversToProcess.length === 0) {
				$progressText.text('No covers found without templates.');
				$progressBar.width('100%').addClass('bg-success');
				addToSummary('No covers require template assignment.', 'info');
				finishBatch(true);
				return;
			}
			
			let successCount = 0;
			let failCount = 0;
			
			for (let i = 0; i < coversToProcess.length; i++) {
				const cover = coversToProcess[i];
				const progressPercent = Math.round(((i + 1) / coversToProcess.length) * 100);
				$progressBar.width(progressPercent + '%');
				$progressText.text(`Processing ${i + 1}/${coversToProcess.length}: Cover "${escapeHtml(cover.name)}" (ID: ${cover.id})`);
				addToSummary(`Starting Cover ID: ${cover.id} ("${escapeHtml(cover.name)}")`, 'info');
				
				try {
					// Step 1: Open modal and load data
					const modalLoadResult = await AssignTemplatesProgrammatic.loadDataAndShowModal(cover.id);
					const $assignableTemplatesList = $('#assignableTemplatesList'); // Get this reference after modal is populated
					
					if (!modalLoadResult.hasTemplates || !modalLoadResult.templates || modalLoadResult.templates.length === 0) {
						addToSummary(`Cover ID: ${cover.id} - No assignable templates found or cover type not set. Skipping.`, 'warning');
						successCount++; // Count as processed, just nothing to do
					} else {
						addToSummary(`Cover ID: ${cover.id} - Modal loaded, ${modalLoadResult.templates.length} templates available. Shuffling and evaluating...`, 'info');
						
						// Shuffle templates
						let availableTemplates = [...modalLoadResult.templates]; // Create a mutable copy
						for (let k = availableTemplates.length - 1; k > 0; k--) {
							const j = Math.floor(Math.random() * (k + 1));
							[availableTemplates[k], availableTemplates[j]] = [availableTemplates[j], availableTemplates[k]];
						}
						
						let assignedCountForThisCover = 0;
						// Clear all checkboxes in the modal first
						$assignableTemplatesList.find('input[type="checkbox"]').prop('checked', false);
						
						const totalTemplatesToTry = availableTemplates.length;
						let triedTemplatesCount = 0;
						
						for (const templateToTry of availableTemplates) {
							if (assignedCountForThisCover >= 2) {
								addToSummary(`Cover ID: ${cover.id} - Reached 2 good fit templates. Moving to save.`, 'info');
								break; // Stop trying more templates for this cover
							}
							
							triedTemplatesCount++;
							const templateId = templateToTry.id;
							const templateName = templateToTry.name || `Template ID ${templateId}`;
							
							$progressText.text(`Cover ${i + 1}/${coversToProcess.length}: ID ${cover.id}. Evaluating template ${triedTemplatesCount}/${totalTemplatesToTry} ("${escapeHtml(templateName)}")...`);
							
							try {
								const evalResponse = await $.ajax({
									url: `${window.adminRoutes.aiEvaluateTemplateFitBase}/${cover.id}/templates/${templateId}/ai-evaluate-fit`,
									type: 'POST',
									dataType: 'json'
								});
								
								if (evalResponse.success && typeof evalResponse.data.should_assign === 'boolean') {
									if (evalResponse.data.should_assign) {
										$assignableTemplatesList.find(`#template_assign_${templateId}`).prop('checked', true);
										assignedCountForThisCover++;
										addToSummary(`Cover ID: ${cover.id} - Template "${escapeHtml(templateName)}" (ID: ${templateId}) is a GOOD FIT. (${assignedCountForThisCover}/2)`, 'success');
									} else {
										addToSummary(`Cover ID: ${cover.id} - Template "${escapeHtml(templateName)}" (ID: ${templateId}) not a good fit.`, 'muted');
									}
								} else {
									addToSummary(`Cover ID: ${cover.id} - AI eval issue for template "${escapeHtml(templateName)}" (ID: ${templateId}): ${escapeHtml(evalResponse.message || 'Invalid AI response')}`, 'warning');
								}
							} catch (xhrEval) {
								let errorDetail = xhrEval.statusText || 'Unknown error';
								if (xhrEval.responseJSON && xhrEval.responseJSON.message) {
									errorDetail = xhrEval.responseJSON.message;
								}
								addToSummary(`Cover ID: ${cover.id} - AJAX error evaluating template "${escapeHtml(templateName)}" (ID: ${templateId}): ${escapeHtml(errorDetail)}`, 'danger');
							}
							
							// API rate limit consideration, only if more templates to try and haven't found 2 yet
							if (triedTemplatesCount < totalTemplatesToTry && assignedCountForThisCover < 2) {
								await new Promise(resolve => setTimeout(resolve, 500)); // 0.5 second delay
							}
						} // End loop for templates of current cover
						
						if (assignedCountForThisCover > 0) {
							$progressText.text(`Saving ${assignedCountForThisCover} assignment(s) for Cover ID: ${cover.id}...`);
							const saveResult = await AssignTemplatesProgrammatic.saveAssignments({ skipUiUpdates: true });
							if (saveResult.success) {
								addToSummary(`Cover ID: ${cover.id} - ${assignedCountForThisCover} assignments saved successfully.`, 'success');
								successCount++;
							} else {
								throw new Error(saveResult.message || `Save failed for Cover ID ${cover.id}.`);
							}
						} else {
							addToSummary(`Cover ID: ${cover.id} - No suitable templates found after trying ${triedTemplatesCount} options. Nothing to save.`, 'info');
							successCount++; // Processed, even if no assignments
						}
					}
					
					// Hide modal and wait for it to be fully hidden
					if ($('#assignTemplatesModal').hasClass('show')) {
						await new Promise(resolve => {
							AssignTemplatesProgrammatic.getModalElement().one('hidden.bs.modal', resolve);
							AssignTemplatesProgrammatic.hideModal();
						});
					}
					await new Promise(resolve => setTimeout(resolve, 200)); // Small pause between covers
					
				} catch (error) {
					failCount++;
					console.error(`Error processing Cover ID ${cover.id}:`, error);
					addToSummary(`Cover ID: ${cover.id} - FAILED: ${escapeHtml(error.message)}`, 'danger');
					if ($('#assignTemplatesModal').hasClass('show')) {
						await new Promise(resolve => {
							AssignTemplatesProgrammatic.getModalElement().one('hidden.bs.modal', resolve);
							AssignTemplatesProgrammatic.hideModal();
						});
					}
				}
			} // End loop for covers
			
			$progressText.text(`Batch complete: ${successCount} covers processed, ${failCount} failed.`);
			$progressBar.addClass(failCount > 0 ? 'bg-warning' : (successCount > 0 ? 'bg-success' : 'bg-info'));
			if (failCount > 0) {
				addToSummary(`Batch finished with ${failCount} failures.`, 'danger');
			} else {
				addToSummary('Batch finished successfully.', 'success');
			}
			
			// Refresh the covers list on the current tab, page 1
			const activeTab = $('.nav-tabs .nav-link.active').attr('aria-controls').replace('-panel', '');
			if (activeTab === 'covers') {
				loadItems('covers', 1, '', '', $('#filterNoTemplatesBtn').hasClass('active'));
			}
			
		} catch (error) {
			console.error("Batch Auto Assign Error:", error);
			$progressText.text('Batch process failed: ' + escapeHtml(error.message));
			$progressBar.width('100%').addClass('bg-danger');
			addToSummary(`Critical Batch Error: ${escapeHtml(error.message)}`, 'danger');
		} finally {
			finishBatch(failCount === 0);
		}
	}
	
	function addToSummary(message, type = 'info') {
		const colorClass = type === 'danger' ? 'text-danger' : (type === 'warning' ? 'text-warning' : (type === 'success' ? 'text-success' : (type === 'muted' ? 'text-muted' : 'text-info')));
		const $list = $progressSummary.find('ul');
		$list.append(`<li class="small ${colorClass}">${escapeHtml(message)}</li>`);
		$list.scrollTop($list[0].scrollHeight); // Scroll to bottom
	}
	
	function finishBatch(isSuccessOverall) {
		isProcessing = false;
		$batchButton.prop('disabled', false).html('Auto Assign Templates');
		$progressBar.removeClass('progress-bar-animated');
		// Optionally hide progress bar after a delay
		// setTimeout(() => $progressArea.hide(), 30000);
	}
	
	return {
		init
	};
})();
