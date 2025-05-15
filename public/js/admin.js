// public/js/admin.js (Main Orchestrator)
$(document).ready(function() {
	// Ensure AppAdmin and its modules are loaded
	const requiredModules = ['Utils', 'State', 'CoverTypes', 'Items', 'Upload', 'Edit', 'Delete', 'AiMetadata', 'AiSimilarTemplate', 'AssignTemplates'];
	for (const moduleName of requiredModules) {
		if (!window.AppAdmin || !window.AppAdmin[moduleName]) {
			console.error(`Critical Error: AppAdmin.${moduleName} module is missing. Ensure all JS files are loaded correctly and in order.`);
			alert(`Critical error: Admin panel script '${moduleName}' failed to load. Please contact support.`);
			return; // Stop execution if a module is missing
		}
	}
	
	const { showAlert, escapeHtml } = AppAdmin.Utils; // Added escapeHtml here if not already
	const { getCurrentState } = AppAdmin.State;
	const { loadItems } = AppAdmin.Items;
	const { fetchCoverTypes } = AppAdmin.CoverTypes;
	
	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});
	
	// --- Batch Analyze Text Placements Button ---
	let isBatchProcessingRunning = false; // Flag to prevent multiple concurrent runs
	let currentBatchQueue = [];
	let totalItemsInBatch = 0;
	let processedItemsInBatch = 0;
	let successItemsInBatch = 0;
	let errorItemsInBatch = 0;
	
	const $batchProgressArea = $('#batchProgressArea');
	const $batchProgressBar = $('#batchProgressBar');
	const $batchProgressText = $('#batchProgressText');
	const $batchProgressSummary = $('#batchProgressSummary');
	const $batchAnalyzeButton = $('#batchAnalyzeTextPlacementsBtn'); // Cache the button
	
	// Initialize modules that set up their own event listeners
	AppAdmin.Upload.init();
	AppAdmin.Edit.init();
	AppAdmin.Delete.init();
	AppAdmin.AiMetadata.init();
	AppAdmin.AiSimilarTemplate.init();
	AppAdmin.AssignTemplates.init();
	
	function updateBatchProgress() {
		const percentage = totalItemsInBatch > 0 ? (processedItemsInBatch / totalItemsInBatch) * 100 : 0;
		$batchProgressBar.css('width', percentage + '%').attr('aria-valuenow', percentage);
		$batchProgressText.text(`${processedItemsInBatch}/${totalItemsInBatch} Processed`);
		$batchProgressSummary.text(`Success: ${successItemsInBatch}, Errors: ${errorItemsInBatch}. Remaining: ${totalItemsInBatch - processedItemsInBatch}`);
	}
	
	// --- Main Event Handlers & Initialization ---
	fetchCoverTypes().then(() => {
		const activeTabButton = $('#adminTab button[data-bs-toggle="tab"].active');
		if (activeTabButton.length) {
			const initialTargetPanelId = activeTabButton.data('bs-target');
			const initialItemType = initialTargetPanelId.replace('#', '').replace('-panel', '');
			const state = getCurrentState(initialItemType);
			loadItems(initialItemType, state.page, state.search, state.coverTypeId);
		} else {
			loadItems('covers'); // Default if no active tab found
		}
	}).catch(error => {
		console.error("Failed to fetch cover types on initial load:", error);
		AppAdmin.Utils.showAlert("Failed to initialize admin panel: Could not load cover types.", "danger");
		loadItems('covers');
	});
	
	$('#adminTab button[data-bs-toggle="tab"]').on('shown.bs.tab', function (event) {
		const targetPanelId = $(event.target).data('bs-target');
		const itemType = targetPanelId.replace('#', '').replace('-panel', '');
		const state = getCurrentState(itemType);
		loadItems(itemType, state.page, state.search, state.coverTypeId);
	});
	
	// Cover Type Filter Change
	$(document).on('change', '.cover-type-filter', function() {
		const itemType = $(this).data('type');
		const coverTypeId = $(this).val();
		const state = getCurrentState(itemType);
		loadItems(itemType, 1, state.search, coverTypeId);
	});
	
	// Pagination Clicks
	$('.tab-content').on('click', '.pagination .page-link', function(e) {
		e.preventDefault();
		const $link = $(this);
		if ($link.parent().hasClass('disabled') || $link.parent().hasClass('active')) {
			return;
		}
		const itemType = $link.data('type');
		const page = $link.data('page');
		const state = getCurrentState(itemType);
		loadItems(itemType, page, state.search, state.coverTypeId);
	});
	
	// Search Form Submission
	$('.tab-content').on('submit', '.search-form', function(e) {
		e.preventDefault();
		const $form = $(this);
		const itemType = $form.data('type');
		const searchQuery = $form.find('.search-input').val().trim();
		const coverTypeId = $form.find('.cover-type-filter').val() || '';
		loadItems(itemType, 1, searchQuery, coverTypeId);
	});
	
	// --- Batch Analyze Text Placements Button ---
	async function processSingleCover(coverId) {
		// Use the existing route for single item analysis,
		// which is now correctly named `generateAiTextPlacementsBase` + /id/ + action
		// The route name in admin.blade.php for this is `generateAiTextPlacementsBase`
		// and the controller method is `generateAiTextPlacements(Request $request, Cover $cover)`
		const url = window.adminRoutes.generateAiTextPlacementsBase + '/' + coverId + '/generate-ai-text-placements';
		
		try {
			const response = await $.ajax({
				url: url,
				type: 'POST',
				dataType: 'json'
			});
			
			if (response.success) {
				successItemsInBatch++;
			} else {
				errorItemsInBatch++;
				console.warn(`Failed to process cover ${coverId}: ${response.message}`);
			}
		} catch (error) {
			errorItemsInBatch++;
			console.error(`AJAX error processing cover ${coverId}:`, error.statusText, error.responseText);
		} finally {
			processedItemsInBatch++;
			updateBatchProgress();
		}
	}
	
	async function startBatchProcessing() {
		if (isBatchProcessingRunning) {
			showAlert('Batch processing is already running.', 'warning');
			return;
		}
		if (!confirm('Are you sure you want to start batch analyzing text placements? This will process items one by one and may take time.')) {
			return;
		}
		
		isBatchProcessingRunning = true;
		const originalButtonText = $batchAnalyzeButton.html();
		$batchAnalyzeButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
		
		// Reset progress
		processedItemsInBatch = 0;
		successItemsInBatch = 0;
		errorItemsInBatch = 0;
		currentBatchQueue = [];
		
		$batchProgressBar.css('width', '0%').attr('aria-valuenow', 0)
			.removeClass('bg-success bg-danger bg-warning bg-info progress-bar-animated')
			.addClass('progress-bar-striped progress-bar-animated'); // Add back animation for processing
		$batchProgressText.text('Fetching items to process...');
		$batchProgressSummary.text('');
		$batchProgressArea.slideDown();
		
		try {
			// 1. Get list of unprocessed cover IDs
			const listResponse = await $.ajax({
				url: window.adminRoutes.getUnprocessedCovers, // New route defined in admin.blade.php
				type: 'GET',
				dataType: 'json'
			});
			
			if (!listResponse.success || !listResponse.data || !listResponse.data.cover_ids) {
				throw new Error(listResponse.message || 'Failed to fetch list of unprocessed covers.');
			}
			
			currentBatchQueue = listResponse.data.cover_ids;
			totalItemsInBatch = currentBatchQueue.length;
			
			if (totalItemsInBatch === 0) {
				$batchProgressText.text('0/0 Processed');
				$batchProgressSummary.text('No items require text placement analysis.');
				$batchProgressBar.css('width', '100%').addClass('bg-info').removeClass('progress-bar-animated progress-bar-striped');
				showAlert('No covers found needing text placement analysis.', 'info');
				finishBatchProcessing(originalButtonText);
				return;
			}
			
			updateBatchProgress(); // Initial display of 0/total
			
			// 2. Process each cover ID sequentially
			// Using a for...of loop with await ensures sequential processing
			for (const coverId of currentBatchQueue) {
				await processSingleCover(coverId);
				// Optional: Add a small delay to be nice to the server/API
				// await new Promise(resolve => setTimeout(resolve, 200)); // 200ms delay
			}
			
			// 3. Batch finished
			let finalMessage = `Batch processing complete. Processed: ${processedItemsInBatch}/${totalItemsInBatch}. Success: ${successItemsInBatch}, Errors: ${errorItemsInBatch}.`;
			showAlert(finalMessage, successItemsInBatch === totalItemsInBatch && errorItemsInBatch === 0 ? 'success' : 'warning');
			
			if (errorItemsInBatch === 0 && successItemsInBatch > 0) {
				$batchProgressBar.addClass('bg-success');
			} else if (errorItemsInBatch > 0 && successItemsInBatch > 0) {
				$batchProgressBar.addClass('bg-warning');
			} else if (errorItemsInBatch > 0 && successItemsInBatch === 0) {
				$batchProgressBar.addClass('bg-danger');
			} else { // Should not happen if totalItems > 0
				$batchProgressBar.addClass('bg-info');
			}
			
			
		} catch (error) {
			console.error("Error during batch processing orchestration:", error);
			showAlert(`Error during batch setup: ${escapeHtml(error.message || 'Unknown error')}`, 'danger');
			$batchProgressBar.addClass('bg-danger');
			$batchProgressSummary.text(`Batch failed: ${escapeHtml(error.message)}`);
		} finally {
			finishBatchProcessing(originalButtonText);
		}
	}
	
	function finishBatchProcessing(originalButtonText) {
		isBatchProcessingRunning = false;
		$batchAnalyzeButton.prop('disabled', false).html(originalButtonText);
		$batchProgressBar.removeClass('progress-bar-animated progress-bar-striped');
		
		// Reload covers tab to show updates
		const activeItemType = $('#adminTab button[data-bs-toggle="tab"].active').data('bs-target').replace('#', '').replace('-panel', '');
		if (activeItemType === 'covers') {
			const state = getCurrentState('covers');
			loadItems('covers', state.page, state.search, state.coverTypeId);
		}
		// Optionally hide progress bar after a delay
		// setTimeout(() => { $batchProgressArea.slideUp(); }, 15000);
	}
	
	
	$batchAnalyzeButton.on('click', startBatchProcessing);
});
