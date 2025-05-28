// free-cover-designer/js/App.js:
const IS_ADMIN_MODE = window.IS_ADMIN_DESIGNER_MODE || false;
const TEMPLATE_ID_TO_UPDATE = window.TEMPLATE_ID_TO_UPDATE || null;
const JSON_TYPE_TO_UPDATE = window.JSON_TYPE_TO_UPDATE || null;

$(document).ready(function () {
	// --- DOM References ---
	const $canvas = $('#canvas');
	const $layerList = $('#layerList');
	const $canvasArea = $('#canvas-area');
	const $canvasWrapper = $('#canvas-wrapper');
	const $loadDesignInput = $('#loadDesignInput');
	const $loadingOverlay = $('#export-overlay'); // Re-use the export overlay
	const $loadingOverlayMessage = $('#loading-overlay-message');
	const $inspectorPanelElement = $('#inspectorPanel');
	
	// Sidebar Panel References
	const $sidebarPanelsContainer = $('#sidebar-panels-container');
	const $sidebarPanels = $('.sidebar-panel');
	const $sidebarNavLinks = $('.sidebar-nav .nav-link[data-panel-target]');
	
	// --- Configuration ---
	// IMPORTANT: Set this ID to an existing cover_type ID from your database
	// This will be the default selected type in "Covers" and "Templates" panels.
	const DEFAULT_COVER_TYPE_ID = 2; // Example: 1 for 'eBook', 2 for 'Paperback', etc.
	
	// --- Instantiate Managers ---
	const canvasManager = new CanvasManager($canvasArea, $canvasWrapper, $canvas, {
		onZoomChange: handleZoomChange,
		onSizeSet: () => { // New callback
			if (sidebarManager) { // Ensure sidebarManager is initialized
				sidebarManager.refreshFiltersForCanvasChange();
			}
		},
		showLoadingOverlay: showGlobalLoadingOverlay,
		hideLoadingOverlay: hideGlobalLoadingOverlay
	});
	const canvasSizeModal = new CanvasSizeModal(canvasManager);
	let googleFonts = []; // Placeholder if needed elsewhere
	
	// LayerManager needs CanvasManager
	const layerManager = new LayerManager($canvas, $layerList, {
		onLayerSelect: handleLayerSelectionChange,
		onLayerDataUpdate: handleLayerDataUpdate,
		saveState: () => historyManager.saveState(),
		canvasManager: canvasManager
	});
	
	const historyManager = new HistoryManager(layerManager, {
		onUpdate: updateActionButtons
	});
	
	// Instantiate InspectorPanel AFTER LayerManager and HistoryManager
	const inspectorPanel = new InspectorPanel({
		layerManager: layerManager,
		historyManager: historyManager,
		canvasManager: canvasManager,
		googleFontsList: googleFonts
	});
	
	// Sidebar Manager
	const sidebarManager = new SidebarItemManager({
		templateListSelector: '#templateList',
		coverListSelector: '#coverList',
		coverSearchSelector: '#coverSearch',
		elementListSelector: '#elementList',
		uploadPreviewSelector: '#uploadPreview',
		uploadInputSelector: '#imageUploadInput',
		addImageBtnSelector: '#addImageFromUpload',
		overlaysListSelector: '#overlayList',
		overlaysSearchSelector: '#overlaySearch',
		sidebarPanelsContainerSelector: '#sidebar-panels-container',
		defaultCoverTypeId: DEFAULT_COVER_TYPE_ID, // Pass the default ID
		applyTemplate: (jsonData) => {
			console.log("Applying template via click, removing existing text layers...");
			const existingLayers = layerManager.getLayers();
			const textLayerIdsToDelete = existingLayers
				.filter(layer => layer.type === 'text')
				.map(layer => layer.id);
			if (textLayerIdsToDelete.length > 0) {
				textLayerIdsToDelete.forEach(id => layerManager.deleteLayer(id, false));
				console.log(`Removed ${textLayerIdsToDelete.length} text layers.`);
			} else {
				console.log("No existing text layers found to remove.");
			}
			canvasManager.loadDesign(jsonData, true); // Load as template
		},
		addLayer: (type, props) => layerManager.addLayer(type, props),
		saveState: () => historyManager.saveState(),
		layerManager: layerManager,
		canvasManager: canvasManager,
		showLoadingOverlay: showGlobalLoadingOverlay,
		hideLoadingOverlay: hideGlobalLoadingOverlay
	});
	
	// Set cross-dependencies
	canvasManager.layerManager = layerManager;
	canvasManager.historyManager = historyManager;
	
	// --- Initialization ---
	sidebarManager.loadAll(); // This will now use the defaultCoverTypeId
	layerManager.initializeList();
	canvasManager.initialize(); // Sets default canvas size, zoom, etc.
	initializeGlobalActions();
	initializeSidebarPanelControls();
	inspectorPanel.hide(); // Hide initially
	
	// --- Initial State & Query Param Handling ---
	hideGlobalLoadingOverlay(); // Ensure it's hidden before we start any custom loading
	const urlParams = new URLSearchParams(window.location.search);
	const queryCanvasWidth = urlParams.get('w');
	const queryCanvasHeight = urlParams.get('h');
	const queryFileUrl = urlParams.get('f');
	
	// New params from setup page
	const queryImagePath = urlParams.get('image_path');
	const queryTemplateUrl = urlParams.get('template_url');
	const querySpineWidth = urlParams.get('spine_width');
	const queryFrontWidth = urlParams.get('front_width');
	
	let designLoadedOrSizeSetFromQuery = false;
	
	function finalizeAppSetup() {
		// This function is called after any query param processing (file load, w/h, or neither)
		if (!designLoadedOrSizeSetFromQuery) {
			try {
				const kindlePresetValue = "1600x2560"; // Match the value in PHP/HTML
				// Show the modal, passing the default value
				canvasSizeModal.show({defaultPresetValue: kindlePresetValue});
			} catch (error) {
				console.error("Error showing initial canvas size modal:", error);
			}
		}
		historyManager.saveState(); // Save the true initial state after any loads/resizes
		updateActionButtons(); // Update buttons based on the final initial state
		hideGlobalLoadingOverlay(); // Ensure it's hidden if it was shown by file loading
	}
	
	if (queryFileUrl && IS_ADMIN_MODE && queryFileUrl.includes('/api/templates/')) { // Check if it's an admin template edit
		showGlobalLoadingOverlay("Loading template for editing...");
		fetch(queryFileUrl)
			.then(response => {
				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status} for ${queryFileUrl}`);
				}
				return response.json();
			})
			.then(jsonData => {
				console.log("Successfully fetched template JSON from URL for admin edit:", queryFileUrl);
				if (jsonData && jsonData.canvas) {
					const width = parseInt(jsonData.canvas.width, 10);
					const height = parseInt(jsonData.canvas.height, 10);
					const frontWidth = parseInt(jsonData.canvas.frontWidth, 10) || width;
					const spineWidth = parseInt(jsonData.canvas.spineWidth, 10) || 0;
					const backWidth = parseInt(jsonData.canvas.backWidth, 10) || 0;
					
					if (!isNaN(width) && width > 0 && !isNaN(height) && height > 0) {
						canvasManager.setCanvasSize({
							totalWidth: width,
							height: height,
							frontWidth: frontWidth,
							spineWidth: spineWidth,
							backWidth: backWidth
						});
						canvasManager.loadDesign(jsonData, false); // Load as full design, not as a template to apply
						designLoadedOrSizeSetFromQuery = true;
					} else {
						console.error("Invalid canvas dimensions in fetched template JSON.");
						alert("Error: Template data has invalid canvas dimensions.");
					}
				} else {
					console.error("Invalid template JSON structure (missing canvas data).");
					alert("Error: Could not load template data structure.");
				}
			})
			.catch(error => {
				console.error("Error loading/parsing template from URL for admin edit:", queryFileUrl, error);
				alert(`Failed to load template for editing: ${error.message}. Defaulting to initial setup.`);
			})
			.finally(() => {
				finalizeAppSetup();
			});
	} else if (queryFileUrl) {
		showGlobalLoadingOverlay("Loading design from URL...");
		fetch(queryFileUrl)
			.then(response => {
				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status} for ${queryFileUrl}`);
				}
				return response.json();
			})
			.then(jsonData => {
				console.log("Successfully fetched and parsed design from URL:", queryFileUrl);
				canvasManager.loadDesign(jsonData, false); // Load full design, this will set canvas size
				designLoadedOrSizeSetFromQuery = true;
			})
			.catch(error => {
				console.error("Error loading/parsing design from URL:", queryFileUrl, error);
				alert(`Failed to load design from URL: ${error.message}. Please check the URL and file format. Defaulting to initial setup.`);
				// designLoadedOrSizeSetFromQuery remains false, so modal might be shown
			})
			.finally(() => {
				finalizeAppSetup();
			});
	} else if (queryCanvasWidth && queryCanvasHeight) {
		const width = parseInt(queryCanvasWidth, 10);
		const height = parseInt(queryCanvasHeight, 10);
		
		if (!isNaN(width) && width > 0 && !isNaN(height) && height > 0) {
			showGlobalLoadingOverlay("Setting up canvas...");
			const sizeConfig = {
				totalWidth: width,
				height: height,
				frontWidth: queryFrontWidth ? parseInt(queryFrontWidth, 10) : width,
				spineWidth: querySpineWidth ? parseInt(querySpineWidth, 10) : 0,
				backWidth: (queryFrontWidth && querySpineWidth) ? parseInt(queryFrontWidth, 10) : 0
			};
			canvasManager.setCanvasSize(sizeConfig); // This should also draw guides if spineWidth > 0
			designLoadedOrSizeSetFromQuery = true;
			
			let promiseChain = Promise.resolve();
			
			if (queryImagePath) {
				promiseChain = promiseChain.then(() => {
					showGlobalLoadingOverlay("Loading cover image...");
					// IMPORTANT: image_path is relative to storage/app/public. Asset URL needs /storage/ prefix.
					const imageAssetUrl = `/storage/${queryImagePath}`;
					return canvasManager.addInitialImage(imageAssetUrl);
				});
			}
			
			if (queryTemplateUrl) {
				promiseChain = promiseChain.then(() => {
					showGlobalLoadingOverlay("Applying template...");
					return fetch(queryTemplateUrl)
						.then(response => {
							if (!response.ok) throw new Error(`Failed to fetch template: ${response.status}`);
							return response.json();
						})
						.then(templateData => {
							console.log("Applying template from URL, removing existing text layers...");
							const existingLayers = layerManager.getLayers();
							const textLayerIdsToDelete = existingLayers
								.filter(layer => layer.type === 'text' && (!layer.tags || !layer.tags.includes('initial-cover-image'))) // Don't delete the image itself if it was mistagged
								.map(layer => layer.id);
							if (textLayerIdsToDelete.length > 0) {
								textLayerIdsToDelete.forEach(id => layerManager.deleteLayer(id, false)); // false = don't save history yet
								console.log(`Removed ${textLayerIdsToDelete.length} text layers.`);
							}
							canvasManager.loadDesign(templateData, true); // true for "isTemplate"
						});
				});
			}
			
			promiseChain
				.catch(error => {
					console.error("Error loading initial image or template:", error);
					alert(`Error setting up canvas: ${error.message}`);
				})
				.finally(() => {
					finalizeAppSetup(); // Call this after all query param processing
				});
		} else {
			console.warn("Invalid canvasWidth or canvasHeight in query parameters.");
			finalizeAppSetup(); // Proceed to modal if w/h are invalid
		}
	} else { // Priority 3: No specific setup, normal startup
		finalizeAppSetup();
	}
	
	// --- UI Update Callbacks ---
	function handleLayerSelectionChange(selectedLayer) {
		if (selectedLayer) {
			inspectorPanel.show(selectedLayer);
		} else {
			if ($inspectorPanelElement.hasClass('open')) {
				inspectorPanel.populate(null);
			}
		}
		updateActionButtons();
	}
	
	function handleLayerDataUpdate(updatedLayer) {
		if (inspectorPanel.currentLayer && inspectorPanel.currentLayer.id === updatedLayer.id && $inspectorPanelElement.hasClass('open')) {
			inspectorPanel.populate(updatedLayer);
		}
		updateActionButtons();
	}
	
	function handleZoomChange(currentZoom, minZoom, maxZoom) {
		$('#zoom-percentage-toggle').text(`${Math.round(currentZoom * 100)}%`);
		$('#zoom-in').prop('disabled', currentZoom >= maxZoom);
		$('#zoom-out').prop('disabled', currentZoom <= minZoom);
	}
	
	// --- Sidebar Panel Sliding Logic (Left Side) ---
	function initializeSidebarPanelControls() {
		$sidebarNavLinks.on('click', function (e) {
			e.preventDefault();
			const $link = $(this);
			const targetPanelId = $link.data('panel-target');
			const $targetPanel = $(targetPanelId);
			if (!$targetPanel.length) {
				console.warn("Target panel not found:", targetPanelId);
				return;
			}
			
			// If clicking the already active icon, close the panel
			if ($link.hasClass('active') && $sidebarPanelsContainer.hasClass('open')) {
				closeSidebarPanel();
			} else {
				openSidebarPanel(targetPanelId);
			}
		});
		
		$canvasArea.on('mousedown', function (e) {
			if (e.target === $canvasArea[0] && !$sidebarPanelsContainer.hasClass('closing-by-click')) {
				closeSidebarPanel();
			}
		});
	}
	
	function openSidebarPanel(panelId) {
		const $targetPanel = $(panelId);
		if (!$targetPanel.length) return;
		
		// Deactivate other panels and links
		$sidebarNavLinks.removeClass('active');
		$sidebarPanels.removeClass('active').hide(); // Hide inactive panels
		
		// Activate target panel and link
		$targetPanel.addClass('active').show(); // Show the target panel
		$sidebarNavLinks.filter(`[data-panel-target="${panelId}"]`).addClass('active');
		
		// Open the container
		$sidebarPanelsContainer.addClass('open');
	}
	
	function closeSidebarPanel() {
		$sidebarPanelsContainer.removeClass('open');
		$sidebarNavLinks.removeClass('active');
		// Optional: Add a small delay before hiding panels to allow for slide-out animation
		// setTimeout(() => {
		// if (!$sidebarPanelsContainer.hasClass('open')) { // Check again in case it was reopened
		// $sidebarPanels.removeClass('active').hide();
		// }
		// }, 300); // Match CSS transition duration
	}
	
	// --- END Sidebar Panel Logic ---
	
	
	// --- Global Loading Overlay Functions ---
	function showGlobalLoadingOverlay(message = "Processing...") {
		if ($loadingOverlay.length && $loadingOverlayMessage.length) {
			$loadingOverlayMessage.text(message);
			$loadingOverlay.show();
		} else {
			console.warn("Loading overlay elements not found.");
		}
	}
	
	function hideGlobalLoadingOverlay() {
		if ($loadingOverlay.length) {
			$loadingOverlay.hide();
		}
	}
	
	// --- Global Action Button Setup & Updates ---
	function initializeGlobalActions() {
		// --- Helper to prevent action on disabled links ---
		const preventDisabled = (e, actionFn) => {
			e.preventDefault(); // Always prevent default for links
			if ($(e.currentTarget).hasClass('disabled')) {
				console.log("Action prevented: Button disabled", e.currentTarget.id);
				return;
			}
			actionFn(); // Execute the action
		};
		
		if (!IS_ADMIN_MODE) {
			// If not in admin mode, hide the specific admin buttons if they were rendered
			// Note: The Blade template already hides them if !from_admin_mode,
			// this is just a JS fallback or for buttons not controlled by Blade's @if
			$('#loadDesignPanelLink').hide();
			$('#saveDesignPanelLink').hide();
			$('#updateTemplateInDbLink').hide();
		}
		
		
// Add listener for the "Update Template in DB" button
		if (IS_ADMIN_MODE && TEMPLATE_ID_TO_UPDATE && JSON_TYPE_TO_UPDATE) {
			$('#updateTemplateInDbBtn').on('click', (e) => {
				e.preventDefault();
				if (!canvasManager) {
					alert("CanvasManager is not available.");
					return;
				}
				const designData = canvasManager.getDesignDataAsObject(); // Ensure this method exists
				if (!designData) {
					alert("Could not retrieve current design data.");
					return;
				}
				
				if (confirm(`Are you sure you want to update Template ID ${TEMPLATE_ID_TO_UPDATE} (${JSON_TYPE_TO_UPDATE} JSON) in the database with the current design?`)) {
					showGlobalLoadingOverlay("Updating template in database...");
					$.ajax({
						url: `/admin/templates/${TEMPLATE_ID_TO_UPDATE}/update-json`,
						type: 'POST',
						contentType: 'application/json',
						data: JSON.stringify({
							json_type: JSON_TYPE_TO_UPDATE,
							json_data: designData // Send the design data object
						}),
						headers: {
							'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
						},
						success: function(response) {
							hideGlobalLoadingOverlay();
							if (response.success) {
								alert('Template updated successfully in the database!');
							} else {
								alert('Failed to update template: ' + (response.message || 'Unknown error'));
								console.error("Update template error response:", response);
							}
						},
						error: function(xhr) {
							hideGlobalLoadingOverlay();
							const errorMsg = xhr.responseJSON?.message || xhr.responseJSON?.errors || xhr.statusText || 'Server error';
							alert('Error updating template: ' + JSON.stringify(errorMsg));
							console.error("Update template AJAX error:", xhr);
						}
					});
				}
			});
		} else {
			$('#updateTemplateInDbLink').hide(); // Ensure it's hidden if params are missing
		}
		
		// History Actions
		$('#undoBtn').on('click', () => historyManager.undo());
		$('#redoBtn').on('click', () => historyManager.redo());
		
		// Layer Actions
		$('#deleteBtn').on('click', () => layerManager.deleteSelectedLayer());
		$('#lockBtn').on('click', () => layerManager.toggleSelectedLayerLock());
		$("#visibilityBtn").on('click', () => layerManager.toggleSelectedLayerVisibility());
		$('#bringToFrontBtn').on('click', () => layerManager.moveSelectedLayer('up'));
		$('#sendToBackBtn').on('click', () => layerManager.moveSelectedLayer('down'));
		
		// File Menu Actions
		$('#saveDesign').on('click', (e) => preventDisabled(e, () => canvasManager.saveDesign()));
		$('#loadDesignIconBtn').on('click', (e) => { // New listener for the icon
			e.preventDefault(); // No disabled check needed for load
			$loadDesignInput.click();
		});
		$loadDesignInput.on('change', (event) => { // Keep the change listener
			const file = event.target.files[0];
			console.log("File selected:", file);
			if (file) {
				canvasManager.loadDesign(file, false); // Load full design
			}
			$(event.target).val(''); // Reset input
		});
		
		// Export Actions
		$('#downloadBtn').on('click', (e) => preventDisabled(e, () => canvasManager.exportCanvas('png', true))); // Default to PNG
		
		$('#openCanvasSizeModalBtn').on('click', (e) => {
			e.preventDefault();
			canvasSizeModal.show();
		});
	}
	
	function updateActionButtons() {
		const selectedLayer = layerManager.getSelectedLayer();
		const layers = layerManager.getLayers();
		const hasSelection = !!selectedLayer;
		const isLocked = hasSelection && selectedLayer.locked;
		
		// Enable/disable based on selection and lock status
		$('#deleteBtn').prop('disabled', !hasSelection || isLocked);
		$('#lockBtn').prop('disabled', !hasSelection); // Can always lock/unlock if selected
		
		// Update lock button icon and title
		if (selectedLayer) {
			const lockIconClass = selectedLayer.locked ? 'fa-lock' : 'fa-lock-open';
			$('#lockBtn i').removeClass('fa-lock fa-lock-open').addClass(lockIconClass);
			$('#lockBtn').attr('title', selectedLayer.locked ? 'Unlock Selected' : 'Lock Selected');
		} else {
			$('#lockBtn i').removeClass('fa-lock fa-lock-open').addClass('fa-lock');
			$('#lockBtn').attr('title', 'Lock/Unlock Selected');
		}
		
		// Layer order buttons
		let isAtFront = false;
		let isAtBack = false;
		if (hasSelection && layers.length > 1) {
			// Sort by zIndex to determine position
			const sortedLayers = [...layers].sort((a, b) => (a.zIndex || 0) - (b.zIndex || 0));
			const selectedIndex = sortedLayers.findIndex(l => l.id === selectedLayer.id);
			isAtBack = selectedIndex === 0;
			isAtFront = selectedIndex === sortedLayers.length - 1;
		} else if (layers.length <= 1) { // If only one layer (or none), it's both front and back
			isAtFront = true;
			isAtBack = true;
		}
		$('#bringToFrontBtn').prop('disabled', !hasSelection || isAtFront || isLocked);
		$('#sendToBackBtn').prop('disabled', !hasSelection || isAtBack || isLocked);
		
		// History buttons
		$('#undoBtn').prop('disabled', !historyManager.canUndo());
		$('#redoBtn').prop('disabled', !historyManager.canRedo());
		
		// Export/Save buttons (disabled if no layers)
		const hasLayers = layers.length > 0;
		$('#downloadBtn, #exportPng, #exportJpg').prop('disabled', !hasLayers);
		$('#saveDesign').prop('disabled', !hasLayers);
	}
}); // End document ready
