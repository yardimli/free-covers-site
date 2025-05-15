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
		if (!confirm('This will attempt to automatically assign templates to all covers that currently have none, using AI. This may take a while and consume API credits. Continue?')) {
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
				
				let aiResultForLog = { goodFitCount: 0 }; // For logging if AI step is skipped
				
				try {
					// Step 1: Open modal and load data
					const modalLoadResult = await AssignTemplatesProgrammatic.loadDataAndShowModal(cover.id);
					
					if (!modalLoadResult.hasTemplates) {
						addToSummary(`Cover ID: ${cover.id} - No assignable templates found or cover type not set. Skipping.`, 'warning');
						successCount++; // Count as processed, just nothing to do
					} else {
						addToSummary(`Cover ID: ${cover.id} - Modal loaded, ${modalLoadResult.templates.length} templates available.`, 'info');
						
						// Step 2: Perform AI choice
						$progressText.text(`AI Evaluating for Cover ID: ${cover.id}...`);
						const aiResult = await AssignTemplatesProgrammatic.performAiChoice();
						aiResultForLog = aiResult; // Store for logging
						addToSummary(`Cover ID: ${cover.id} - AI Choice: ${aiResult.goodFitCount} good, ${aiResult.badFitCount} bad, ${aiResult.errorCount} errors.`, 'info');
						
						if (aiResult.errorCount === 0) { // If AI ran successfully, save the resulting checkbox states
							$progressText.text(`Saving for Cover ID: ${cover.id}...`);
							const saveResult = await AssignTemplatesProgrammatic.saveAssignments({ skipUiUpdates: true });
							if (saveResult.success) {
								addToSummary(`Cover ID: ${cover.id} - Assignments saved. (AI suggested ${aiResult.goodFitCount})`, 'success');
								successCount++;
							} else {
								throw new Error(saveResult.message || 'Save failed.');
							}
						} else {
							addToSummary(`Cover ID: ${cover.id} - AI evaluation had errors. Not saving.`, 'warning');
							failCount++; // Count as failure if AI errors prevent saving
						}
					}
					
					// Step 4: Hide modal and wait for it to be fully hidden
					// Ensure modal is hidden only if it was shown
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
			}
			
			$progressText.text(`Batch complete: ${successCount} covers processed, ${failCount} failed.`);
			$progressBar.addClass(failCount > 0 ? 'bg-warning' : (successCount > 0 ? 'bg-success' : 'bg-info'));
			if (failCount > 0) {
				addToSummary(`Batch finished with ${failCount} failures.`, 'danger');
			} else {
				addToSummary('Batch finished successfully.', 'success');
			}
			// Refresh the covers list on the current tab, page 1
			const activeTab = $('.nav-tabs .nav-link.active').attr('aria-controls').replace('-panel', '');
			if (activeTab === 'covers') { // Only refresh if on covers tab, or always refresh covers
				loadItems('covers', 1, '', '');
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
		const colorClass = type === 'danger' ? 'text-danger' : (type === 'warning' ? 'text-warning' : (type === 'success' ? 'text-success' : 'text-muted'));
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
	
	return { init };
})();
