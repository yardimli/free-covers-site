// --- Batch Analyze Text Placements Button ---
$(document).ready(function () {
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
	
	$batchAnalyzeButton.on('click', startBatchProcessing);
	
	
	function updateBatchProgress() {
		const percentage = totalItemsInBatch > 0 ? (processedItemsInBatch / totalItemsInBatch) * 100 : 0;
		$batchProgressBar.css('width', percentage + '%').attr('aria-valuenow', percentage);
		$batchProgressText.text(`${processedItemsInBatch}/${totalItemsInBatch} Processed`);
		$batchProgressSummary.text(`Success: ${successItemsInBatch}, Errors: ${errorItemsInBatch}. Remaining: ${totalItemsInBatch - processedItemsInBatch}`);
	}
	
	async function processSingleCover(coverId) {
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
			.removeClass('bg-success bg-danger bg-warning bg-info progress-bar-animated') // remove all color classes
			.addClass('progress-bar-striped progress-bar-animated'); // Add back animation for processing
		$batchProgressText.text('Fetching items to process...');
		$batchProgressSummary.text('');
		$batchProgressArea.slideDown();
		
		try {
			// 1. Get list of unprocessed cover IDs
			const listResponse = await $.ajax({
				url: window.adminRoutes.getUnprocessedCovers,
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
			} else { // Should not happen if totalItems > 0 and no successes
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
			const params = new URLSearchParams(window.location.search);
			const page = parseInt(params.get('page'), 10) || 1;
			const search = params.get('search') || '';
			const coverTypeIdFilter = params.get('filter') || '';
			loadItems('covers', page, search, coverTypeIdFilter);
		}
	}
});
