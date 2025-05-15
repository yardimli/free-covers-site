// public/js/admin.js (Main Orchestrator)
$(document).ready(function() {
	const requiredModules = [
		'Utils', 'CoverTypes', 'Items', 'Upload', 'Edit', 'Delete',
		'AiMetadata', 'AiSimilarTemplate', 'AssignTemplates', 'TextPlacements',
		'BatchAutoAssignTemplates'
	];
	for (const moduleName of requiredModules) {
		if (!window.AppAdmin || !window.AppAdmin[moduleName]) {
			console.error(`Critical Error: AppAdmin.${moduleName} module is missing. Ensure all JS files are loaded correctly and in order.`);
			alert(`Critical error: Admin panel script '${moduleName}' failed to load. Please contact support.`);
			return;
		}
	}
	
	const { showAlert, escapeHtml } = AppAdmin.Utils;
	const { loadItems } = AppAdmin.Items;
	const { fetchCoverTypes } = AppAdmin.CoverTypes;
	
	$.ajaxSetup({
		headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
	});
	
	AppAdmin.Upload.init();
	AppAdmin.Edit.init();
	AppAdmin.Delete.init();
	AppAdmin.AiMetadata.init();
	AppAdmin.AiSimilarTemplate.init();
	AppAdmin.AssignTemplates.init();
	AppAdmin.TextPlacements.init();
	AppAdmin.BatchAutoAssignTemplates.init();
	
	let popStateHandlingActive = false; // Flag to manage popstate-triggered loads
	
	function loadStateFromUrl() {
		popStateHandlingActive = true; // Signal that loading is URL-driven
		
		const params = new URLSearchParams(window.location.search);
		let itemType = params.get('tab') || 'covers';
		let page = parseInt(params.get('page'), 10) || 1;
		let search = params.get('search') || '';
		let filter = params.get('filter') || '';
		
		const $targetTabButton = $(`#adminTab button[data-bs-target="#${itemType}-panel"]`);
		let effectiveItemType = itemType;
		
		if ($targetTabButton.length) {
			if (!$targetTabButton.hasClass('active')) {
				const tab = new bootstrap.Tab($targetTabButton[0]);
				tab.show(); // Triggers 'shown.bs.tab'. Handler will use popStateHandlingActive.
			} else {
				// Tab is already active. 'shown.bs.tab' won't fire. Load items directly.
				loadItems(effectiveItemType, page, search, filter);
				popStateHandlingActive = false; // Reset flag as 'shown.bs.tab' won't.
			}
		} else {
			// Fallback for invalid tab in URL
			effectiveItemType = 'covers';
			page = 1; search = ''; filter = ''; // Reset params
			const $defaultTabButton = $(`#adminTab button[data-bs-target="#covers-panel"]`);
			if ($defaultTabButton.length) {
				if (!$defaultTabButton.hasClass('active')) {
					const tab = new bootstrap.Tab($defaultTabButton[0]);
					tab.show(); // Triggers 'shown.bs.tab'
				} else {
					loadItems(effectiveItemType, page, search, filter);
					popStateHandlingActive = false; // Reset flag
				}
			} else {
				console.error("Default 'covers' tab not found.");
				popStateHandlingActive = false; // Reset flag
			}
		}
	}
	
	// Initial load
	fetchCoverTypes().then(() => {
		loadStateFromUrl();
	}).catch(error => {
		console.error("Failed to fetch cover types on initial load:", error);
		showAlert("Failed to initialize admin panel: Could not load cover types.", "danger");
		loadStateFromUrl(); // Still attempt to load UI based on URL
	});
	
	// Popstate handler for browser back/forward
	window.addEventListener('popstate', function(event) {
		loadStateFromUrl();
	});
	
	// Tab change handler
	$('#adminTab button[data-bs-toggle="tab"]').on('shown.bs.tab', function (event) {
		const targetPanelId = $(event.target).data('bs-target');
		const itemType = targetPanelId.replace('#', '').replace('-panel', '');
		
		if (popStateHandlingActive) {
			// This 'shown.bs.tab' was triggered by loadStateFromUrl changing to an inactive tab.
			// Parameters should come from the URL.
			const params = new URLSearchParams(window.location.search);
			const urlItemType = params.get('tab') || 'covers';
			// Ensure itemType from event matches URL's tab param, or use event's itemType if URL is out of sync
			if (itemType !== urlItemType) {
				console.warn(`Tab event itemType (${itemType}) differs from URL tab (${urlItemType}). Using event tab's itemType.`);
			}
			
			const page = parseInt(params.get('page'), 10) || 1;
			const search = params.get('search') || '';
			const filter = params.get('filter') || '';
			loadItems(itemType, page, search, filter); // itemType from event is reliable here
			popStateHandlingActive = false; // Reset flag
		} else {
			// Normal user click on a tab, not from popstate
			const page = 1; // Reset to page 1
			const search = $(`#${itemType}-panel .search-input`).val() || ''; // Use current form values
			const filter = $(`#${itemType}-panel .cover-type-filter`).val() || ''; // Use current form values
			loadItems(itemType, page, search, filter);
		}
	});
	
	// Cover Type Filter Change
	$(document).on('change', '.cover-type-filter', function() {
		const itemType = $(this).closest('.tab-pane').attr('id').replace('-panel', '');
		const coverTypeId = $(this).val();
		const searchQuery = $(`#${itemType}-panel .search-input`).val() || '';
		loadItems(itemType, 1, searchQuery, coverTypeId); // Reset to page 1
	});
	
	// Pagination Clicks
	$('.tab-content').on('click', '.pagination .page-link', function(e) {
		e.preventDefault();
		const $link = $(this);
		if ($link.parent().hasClass('disabled') || $link.parent().hasClass('active')) {
			return;
		}
		const itemType = $link.data('type');
		const page = parseInt($link.data('page'), 10);
		const searchQuery = $(`#${itemType}-panel .search-input`).val() || '';
		const coverTypeIdFilter = $(`#${itemType}-panel .cover-type-filter`).val() || '';
		loadItems(itemType, page, searchQuery, coverTypeIdFilter);
	});
	
	// Search Form Submission
	$('.tab-content').on('submit', '.search-form', function(e) {
		e.preventDefault();
		const $form = $(this);
		const itemType = $form.data('type');
		const searchQuery = $form.find('.search-input').val().trim();
		const coverTypeId = $form.find('.cover-type-filter').val() || '';
		loadItems(itemType, 1, searchQuery, coverTypeId); // Reset to page 1
	});
});
