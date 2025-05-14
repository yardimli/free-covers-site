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
	
	const { getCurrentState } = AppAdmin.State;
	const { loadItems } = AppAdmin.Items;
	const { fetchCoverTypes } = AppAdmin.CoverTypes;
	
	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});
	
	// Initialize modules that set up their own event listeners
	AppAdmin.Upload.init();
	AppAdmin.Edit.init();
	AppAdmin.Delete.init();
	AppAdmin.AiMetadata.init();
	AppAdmin.AiSimilarTemplate.init();
	AppAdmin.AssignTemplates.init();
	
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
		// Attempt to load default items anyway, or show a more prominent error
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
		const state = getCurrentState(itemType); // Get current search query
		loadItems(itemType, 1, state.search, coverTypeId); // Reset to page 1 on filter change
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
		const coverTypeId = $form.find('.cover-type-filter').val() || ''; // Ensure coverTypeId is present
		loadItems(itemType, 1, searchQuery, coverTypeId); // Reset to page 1 on search
	});
});
