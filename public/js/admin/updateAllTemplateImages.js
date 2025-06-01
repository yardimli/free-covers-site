$(document).ready(function () {
	let templatePreviewQueue = [];
	let currentDesignerWindow = null;
	let isRefreshingPreviews = false;
	let refreshResults = { succeeded: [], failed: [], total: 0 };
	let currentRefreshTask = null; // Holds the task being processed
	
	// Function to be called by the designer window via window.opener
	window.handleDesignerUpdateComplete = function(templateId, jsonType, success, message = '') {
		// 1. Clear the timeout for the task that just completed.
		if (currentRefreshTask && currentRefreshTask.timeoutId) {
			clearTimeout(currentRefreshTask.timeoutId);
			// currentRefreshTask.timeoutId = null; // Will be nulled with currentRefreshTask itself
		}
		
		// 2. Capture the window reference that just finished.
		const windowThatFinished = currentDesignerWindow;
		
		// 3. CRITICAL: Nullify currentDesignerWindow and currentRefreshTask *synchronously*
		//    before any further processing or starting the next task.
		currentDesignerWindow = null;
		currentRefreshTask = null;
		
		// 4. Attempt to close the window that finished (designer should also try to close itself).
		//    This is a fallback and uses the captured reference.
		if (windowThatFinished && !windowThatFinished.closed) {
			setTimeout(() => { // Give a very brief moment for messages to pass if any
				if (windowThatFinished && !windowThatFinished.closed) {
					console.log(`Admin: Fallback close for T_ID ${templateId} (${jsonType}) window.`);
					windowThatFinished.close();
				}
			}, 100); // Reduced delay, as designer also closes itself.
		}
		
		// 5. Log results
		const taskDescription = `Template ID ${templateId} (${jsonType})`;
		if (success) {
			AppAdmin.Utils.showAlert(`Preview for ${taskDescription} updated successfully.`, 'success');
			refreshResults.succeeded.push({ templateId, jsonType });
		} else {
			AppAdmin.Utils.showAlert(`Failed to update preview for ${taskDescription}: ${message}`, 'danger');
			refreshResults.failed.push({ templateId, jsonType, reason: message });
		}
		
		// 6. Process the next item in the queue.
		//    Since currentDesignerWindow and currentRefreshTask are now null,
		//    processNextTemplatePreview will not be confused.
		processNextTemplatePreview();
	};
	
	function processNextTemplatePreview() {
		if (templatePreviewQueue.length === 0) {
			// ... (existing completion logic) ...
			return;
		}
		
		// currentRefreshTask was set to null by handleDesignerUpdateComplete or at the start.
		// So, we assign the new task from the queue here.
		currentRefreshTask = templatePreviewQueue.shift();
		updateRefreshButtonProgress(); // Uses the new currentRefreshTask
		
		const url = new URL(currentRefreshTask.url, window.location.origin);
		url.searchParams.set('auto_update_preview', 'true');
		
		console.log(`Opening designer for ${currentRefreshTask.jsonType} of Template ID ${currentRefreshTask.templateId}, URL: ${url.toString()}`);
		
		// This check should now be fine because currentDesignerWindow was nulled out
		// by the previous call to handleDesignerUpdateComplete.
		// If, for some extreme reason, a window is still referenced, this is a safety net.
		if (currentDesignerWindow && !currentDesignerWindow.closed) {
			console.warn("Admin: Stale designer window reference detected. Attempting to close it before opening new one.");
			currentDesignerWindow.close();
			currentDesignerWindow = null; // Ensure it's null before reassigning
		}
		
		currentDesignerWindow = window.open(url.toString(), `_blank_designer_${Date.now()}`);
		
		if (!currentDesignerWindow || currentDesignerWindow.closed || typeof currentDesignerWindow.closed == 'undefined') {
			AppAdmin.Utils.showAlert('Popup blocked or failed to open. Aborting current task and trying next. Please check browser settings.', 'danger');
			// Simulate a failure for this task by directly calling handleDesignerUpdateComplete
			// Pass the templateId and jsonType from the currentRefreshTask that failed to open
			window.handleDesignerUpdateComplete(currentRefreshTask.templateId, currentRefreshTask.jsonType, false, "Popup blocked or window failed to open.");
		} else {
			// Assign the timeoutId to the currentRefreshTask object itself
			currentRefreshTask.timeoutId = setTimeout(() => {
				console.error(`Timeout waiting for Template ID ${currentRefreshTask.templateId} (${currentRefreshTask.jsonType}) to complete update.`);
				// Pass the templateId and jsonType from the currentRefreshTask that timed out
				window.handleDesignerUpdateComplete(currentRefreshTask.templateId, currentRefreshTask.jsonType, false, "Processing timed out (60s).");
			}, 60000); // 60 seconds timeout per update
		}
	}
	
	function updateRefreshButtonProgress() {
		const processedCount = refreshResults.succeeded.length + refreshResults.failed.length;
		const progressMessage = `Processing ${currentRefreshTask.jsonType} for T_ID ${currentRefreshTask.templateId} (${processedCount + 1}/${refreshResults.total})...`;
		$('#refreshAllVisibleTemplatePreviewsBtn').html(`<span class="spinner-border spinner-border-sm"></span> ${progressMessage}`);
	}
	
	function processNextTemplatePreview() {
		if (templatePreviewQueue.length === 0) {
			isRefreshingPreviews = false;
			$('#refreshAllVisibleTemplatePreviewsBtn').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh All Previews');
			let summaryMessage = `Template preview refresh finished. Processed: ${refreshResults.total}. Succeeded: ${refreshResults.succeeded.length}, Failed: ${refreshResults.failed.length}.`;
			if (refreshResults.failed.length > 0) {
				summaryMessage += " Check console for details on failures.";
				console.warn("Failed preview refreshes:", refreshResults.failed);
			}
			AppAdmin.Utils.showAlert(summaryMessage, refreshResults.failed.length > 0 ? 'warning' : 'info');
			console.log("Refresh Succeeded:", refreshResults.succeeded);
			
			// Optionally, reload the templates list to show new previews if they are part of the list display
			// const $panel = $('#templates-panel');
			// const currentPage = parseInt($('#templatesPagination .active .page-link').data('page'), 10) || 1;
			// AppAdmin.Items.loadItems('templates', currentPage, $panel.find('.search-input').val() || '', $panel.find('.cover-type-filter').val() || '', false, $panel.find('.sort-by-select').val() || 'id', $panel.find('.sort-direction-select').val() || 'desc');
			return;
		}
		
		// currentRefreshTask was set to null by handleDesignerUpdateComplete or at the start.
		// So, we assign the new task from the queue here.
		currentRefreshTask = templatePreviewQueue.shift();
		updateRefreshButtonProgress(); // Uses the new currentRefreshTask
		
		const url = new URL(currentRefreshTask.url, window.location.origin);
		url.searchParams.set('auto_update_preview', 'true');
		
		console.log(`Opening designer for ${currentRefreshTask.jsonType} of Template ID ${currentRefreshTask.templateId}, URL: ${url.toString()}`);
		
		// This check should now be fine because currentDesignerWindow was nulled out
		// by the previous call to handleDesignerUpdateComplete.
		// If, for some extreme reason, a window is still referenced, this is a safety net.
		if (currentDesignerWindow && !currentDesignerWindow.closed) {
			console.warn("Admin: Stale designer window reference detected. Attempting to close it before opening new one.");
			currentDesignerWindow.close();
			currentDesignerWindow = null; // Ensure it's null before reassigning
		}
		
		currentDesignerWindow = window.open(url.toString(), `_blank_designer_${Date.now()}`);
		
		if (!currentDesignerWindow || currentDesignerWindow.closed || typeof currentDesignerWindow.closed == 'undefined') {
			AppAdmin.Utils.showAlert('Popup blocked or failed to open. Aborting current task and trying next. Please check browser settings.', 'danger');
			// Simulate a failure for this task by directly calling handleDesignerUpdateComplete
			// Pass the templateId and jsonType from the currentRefreshTask that failed to open
			window.handleDesignerUpdateComplete(currentRefreshTask.templateId, currentRefreshTask.jsonType, false, "Popup blocked or window failed to open.");
		} else {
			// Assign the timeoutId to the currentRefreshTask object itself
			currentRefreshTask.timeoutId = setTimeout(() => {
				console.error(`Timeout waiting for Template ID ${currentRefreshTask.templateId} (${currentRefreshTask.jsonType}) to complete update.`);
				// Pass the templateId and jsonType from the currentRefreshTask that timed out
				window.handleDesignerUpdateComplete(currentRefreshTask.templateId, currentRefreshTask.jsonType, false, "Processing timed out (60s).");
			}, 30000); // 60 seconds timeout per update
		}
	}
	
	// Show/hide the refresh button based on active tab
	$('#adminTab button[data-bs-toggle="tab"]').on('shown.bs.tab', function (event) {
		const targetPanelId = $(event.target).data('bs-target');
		if (targetPanelId === '#templates-panel') {
			$('#refreshAllVisibleTemplatePreviewsBtn').show();
		} else {
			$('#refreshAllVisibleTemplatePreviewsBtn').hide();
			if (isRefreshingPreviews) { // Stop process if user navigates away
				AppAdmin.Utils.showAlert('Template refresh process cancelled due to tab change.', 'warning');
				templatePreviewQueue = []; // Clear queue
				if (currentRefreshTask && currentRefreshTask.timeoutId) clearTimeout(currentRefreshTask.timeoutId);
				if (currentDesignerWindow && !currentDesignerWindow.closed) currentDesignerWindow.close();
				isRefreshingPreviews = false;
				$('#refreshAllVisibleTemplatePreviewsBtn').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh All Previews');
			}
		}
		// ... (rest of existing tab change handler in admin.js)
	});
	
	// Initial check in case 'templates' is the default tab from URL
	if ($('#adminTab button[data-bs-target="#templates-panel"]').hasClass('active')) {
		$('#refreshAllVisibleTemplatePreviewsBtn').show();
	}
	
	$('#refreshAllVisibleTemplatePreviewsBtn').on('click', function() {
		if (isRefreshingPreviews) {
			AppAdmin.Utils.showAlert('A refresh process is already running.', 'warning');
			return;
		}
		
		const $visibleTemplateRows = $('#templatesTable tbody tr').filter(function() {
			return $(this).find('.edit-item[data-type="templates"]').length > 0 && $(this).is(":visible");
		});
		
		if ($visibleTemplateRows.length === 0) {
			AppAdmin.Utils.showAlert('No templates currently visible in the grid to refresh.', 'info');
			return;
		}
		
		if (!confirm(`This will attempt to refresh previews for ${$visibleTemplateRows.length} templates. It will open and close new tabs for each available JSON type (Front/Full). This can take some time. Do you want to proceed?`)) {
			return;
		}
		
		isRefreshingPreviews = true;
		$(this).prop('disabled', true); // Initial message set in processNextTemplatePreview
		
		templatePreviewQueue = [];
		refreshResults = { succeeded: [], failed: [], total: 0 };
		
		$visibleTemplateRows.each(function() {
			const $row = $(this);
			const templateId = $row.find('.edit-item[data-type="templates"]').data('id');
			
			const $frontJsonLink = $row.find('a[href*="/designer"][href*="json_type_to_update=front"]');
			if ($frontJsonLink.length > 0 && $frontJsonLink.attr('href') && $frontJsonLink.attr('href') !== '#') {
				templatePreviewQueue.push({
					templateId: templateId,
					jsonType: 'front',
					url: $frontJsonLink.attr('href')
				});
			}
			
			const $fullJsonLink = $row.find('a[href*="/designer"][href*="json_type_to_update=full"]');
			if ($fullJsonLink.length > 0 && $fullJsonLink.attr('href') && $fullJsonLink.attr('href') !== '#') {
				templatePreviewQueue.push({
					templateId: templateId,
					jsonType: 'full',
					url: $fullJsonLink.attr('href')
				});
			}
		});
		console.log(templatePreviewQueue);
		refreshResults.total = templatePreviewQueue.length;
		
		if (templatePreviewQueue.length === 0) {
			AppAdmin.Utils.showAlert('No valid Front/Full JSON edit links found for visible templates.', 'warning');
			isRefreshingPreviews = false;
			$(this).prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh All Previews');
			return;
		}
		
		console.log("Template preview queue initialized:", templatePreviewQueue);
		processNextTemplatePreview();
	});
});
