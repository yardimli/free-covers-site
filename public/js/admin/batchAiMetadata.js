// public/js/admin/batchAiMetadata.js
window.AppAdmin = window.AppAdmin || {};
AppAdmin.BatchAiMetadata = (function() {
	const { showAlert, escapeHtml } = AppAdmin.Utils;
	const { loadItems } = AppAdmin.Items; // To reload items list if on covers tab
	
	let coversToProcess = [];
	let currentCoverIndex = 0;
	let totalCovers = 0;
	let successCount = 0;
	let errorCount = 0;
	let batchSummaryMessages = []; // Renamed for clarity
	
	const $batchProgressArea = $('#batchProgressArea');
	const $batchProgressBar = $('#batchProgressBar');
	const $batchProgressText = $('#batchProgressText');
	const $batchProgressSummary = $('#batchProgressSummary');
	const $batchButton = $('#batchGenerateMetadataBtn');
	
	function updateProgressBar() {
		const percentage = totalCovers > 0 ? Math.round(((currentCoverIndex) / totalCovers) * 100) : 0;
		$batchProgressBar.css('width', percentage + '%').attr('aria-valuenow', percentage);
		// Display count in progress bar text itself for better visibility
		$batchProgressBar.find('#batchProgressText').text(`${percentage}% (${currentCoverIndex}/${totalCovers})`);
		// Update the separate text if needed, or remove if redundant
		// $batchProgressText.text(...); // This was the span inside the progress bar in the layout
	}
	
	function resetBatchState() {
		coversToProcess = [];
		currentCoverIndex = 0;
		totalCovers = 0;
		successCount = 0;
		errorCount = 0;
		batchSummaryMessages = [];
		$batchButton.prop('disabled', false).html('Batch Generate Metadata');
		$batchProgressArea.hide();
		$batchProgressSummary.empty();
		$batchProgressBar.css('width', '0%').attr('aria-valuenow', 0);
		$batchProgressBar.find('#batchProgressText').text(''); // Clear text in progress bar
	}
	
	function processNextCover() {
		if (currentCoverIndex >= totalCovers) {
			finishBatch();
			return;
		}
		
		const coverData = coversToProcess[currentCoverIndex];
		const coverId = coverData.id;
		const fieldsToGenerate = coverData.fields_to_generate;
		const coverName = coverData.current_name || `ID ${coverId}`;
		
		
		if (!fieldsToGenerate || fieldsToGenerate.length === 0) {
			batchSummaryMessages.push(`<li>Cover "${escapeHtml(coverName)}": No fields needed generation (skipped).</li>`);
			currentCoverIndex++;
			updateProgressBar();
			// Use requestAnimationFrame to avoid call stack overflow for many skips
			requestAnimationFrame(processNextCover);
			return;
		}
		
		// Update text inside progress bar
		$batchProgressBar.find('#batchProgressText').text(`Processing ${currentCoverIndex + 1}/${totalCovers}: "${escapeHtml(coverName)}" for ${fieldsToGenerate.join(', ')}...`);
		
		
		$.ajax({
			url: window.adminRoutes.generateAiMetadata,
			type: 'POST',
			data: {
				item_type: 'covers',
				id: coverId,
				fields_to_generate: fieldsToGenerate.join(',')
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					successCount++;
					const updatedFields = response.data && response.data.updated_fields ? response.data.updated_fields.join(', ') : 'fields';
					batchSummaryMessages.push(`<li class="text-success">Cover "${escapeHtml(coverName)}": Successfully updated ${escapeHtml(updatedFields)}.</li>`);
				} else {
					errorCount++;
					batchSummaryMessages.push(`<li class="text-danger">Cover "${escapeHtml(coverName)}": Error - ${escapeHtml(response.message)}</li>`);
				}
			},
			error: function(xhr) {
				errorCount++;
				batchSummaryMessages.push(`<li class="text-danger">Cover "${escapeHtml(coverName)}": AJAX Error - ${escapeHtml(xhr.statusText || 'Unknown error')}: ${escapeHtml(xhr.responseText.substring(0,100))}</li>`);
			},
			complete: function() {
				currentCoverIndex++;
				updateProgressBar();
				// Add a small delay to prevent overwhelming the server or hitting API rate limits too quickly
				setTimeout(processNextCover, 500); // 500ms delay
			}
		});
	}
	
	function finishBatch() {
		$batchProgressBar.find('#batchProgressText').text('Batch processing complete!');
		let summaryHtml = `<h6>Batch Metadata Generation Summary:</h6>
                           <p>Processed ${totalCovers} covers.
                              <span class="text-success">${successCount} succeeded</span>,
                              <span class="text-danger">${errorCount} failed</span>.
                           </p>
                           <ul class="list-unstyled" style="max-height: 200px; overflow-y: auto;">${batchSummaryMessages.join('')}</ul>`;
		$batchProgressSummary.html(summaryHtml);
		$batchButton.prop('disabled', false).html('Batch Generate Metadata');
		
		showAlert('Batch metadata generation finished. Check summary for details.', 'info');
		
		// Reload items if on covers tab
		const activeTabId = $('.nav-tabs .nav-link.active').attr('aria-controls');
		if (activeTabId === 'covers-panel') {
			const params = new URLSearchParams(window.location.search);
			const page = parseInt(params.get('page'), 10) || 1;
			const search = params.get('search') || '';
			const coverTypeIdFilter = params.get('filter') || '';
			// Give a moment for user to see summary before potential reload
			setTimeout(() => {
				loadItems('covers', page, search, coverTypeIdFilter);
			}, 1000);
		}
	}
	
	function startBatchMetadataGeneration() {
		if (!confirm('Are you sure you want to start batch generating AI metadata for covers that need it? This may take a while, consume API credits, and overwrite existing data for the selected fields.')) {
			return;
		}
		
		resetBatchState();
		$batchButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Fetching...');
		$batchProgressArea.show();
		$batchProgressBar.find('#batchProgressText').text('Fetching list of covers needing metadata...');
		updateProgressBar(); // Show 0%
		
		$.ajax({
			url: window.adminRoutes.getCoversNeedingMetadata,
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data.covers) {
					coversToProcess = response.data.covers;
					totalCovers = coversToProcess.length;
					
					if (totalCovers === 0) {
						$batchProgressBar.find('#batchProgressText').text('No covers found needing metadata updates.');
						showAlert('All covers seem to have sufficient metadata.', 'info');
						setTimeout(resetBatchState, 3000); // Hide progress area after a bit
						return;
					}
					
					$batchProgressSummary.html(`<p class="mb-1">Found ${totalCovers} covers to process. Starting generation...</p>`);
					processNextCover();
					
				} else {
					showAlert(`Error fetching covers list: ${escapeHtml(response.message || 'Unknown error')}`, 'danger');
					resetBatchState();
				}
			},
			error: function(xhr) {
				showAlert(`AJAX Error fetching covers list: ${escapeHtml(xhr.statusText || 'Unknown error')}`, 'danger');
				resetBatchState();
			}
		});
	}
	
	function init() {
		$batchButton.on('click', startBatchMetadataGeneration);
	}
	
	return {
		init
	};
})();
