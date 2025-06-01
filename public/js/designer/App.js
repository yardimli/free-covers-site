// free-cover-designer/js/App.js:
const IS_ADMIN_MODE = window.IS_ADMIN_DESIGNER_MODE || false;
const TEMPLATE_ID_TO_UPDATE = window.TEMPLATE_ID_TO_UPDATE || null;
const JSON_TYPE_TO_UPDATE = window.JSON_TYPE_TO_UPDATE || null;
const AUTO_UPDATE_PREVIEW = new URLSearchParams(window.location.search).get('auto_update_preview') === 'true';

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
	
	const $backgroundSettingsModal = $('#backgroundSettingsModal');
	const $canvasBackgroundColorPicker = $('#canvasBackgroundColorPicker');
	const $canvasTransparentBackgroundCheckbox = $('#canvasTransparentBackgroundCheckbox');
	const $applyBackgroundSettingsBtn = $('#applyBackgroundSettingsBtn');
	let bsBackgroundSettingsModal = null;
	if ($backgroundSettingsModal.length) {
		bsBackgroundSettingsModal = new bootstrap.Modal($backgroundSettingsModal[0]);
	}
	
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
	
	const historyManager = new HistoryManager(layerManager, canvasManager, { // Added canvasManager
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
	layerManager.saveState = () => historyManager.saveState();
	
	// --- Initialization ---
	sidebarManager.loadAll(); // This will now use the defaultCoverTypeId
	layerManager.initializeList();
	canvasManager.initialize(); // Sets default canvas size, zoom, etc.
	initializeGlobalActions();
	initializeSidebarPanelControls();
	inspectorPanel.hide(); // Hide initially
	
	if ($backgroundSettingsModal.length) {
		$backgroundSettingsModal.on('show.bs.modal', function () {
			const currentSettings = canvasManager.getCanvasBackgroundSettings();
			$canvasBackgroundColorPicker.val(currentSettings.color);
			$canvasTransparentBackgroundCheckbox.prop('checked', currentSettings.isTransparent);
		});
		
		$applyBackgroundSettingsBtn.on('click', function () {
			const newColor = $canvasBackgroundColorPicker.val();
			const newIsTransparent = $canvasTransparentBackgroundCheckbox.prop('checked');
			canvasManager.setCanvasBackgroundSettings({
				color: newColor,
				isTransparent: newIsTransparent
			}); // This will trigger history save
			if (bsBackgroundSettingsModal) {
				bsBackgroundSettingsModal.hide();
			}
		});
	}
	
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
	
	const queryUserDesignId = urlParams.get('ud_id');
	
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
		
		if (IS_ADMIN_DESIGNER_MODE && TEMPLATE_ID_TO_UPDATE && JSON_TYPE_TO_UPDATE && AUTO_UPDATE_PREVIEW) {
			console.log("Designer: Auto-update mode detected for Template ID:", TEMPLATE_ID_TO_UPDATE, "Type:", JSON_TYPE_TO_UPDATE);
			
			const $updateBtn = $('#updateTemplateInDbBtn');
			if ($updateBtn.length > 0) {
				// Wait a bit for canvas to fully render, especially if complex.
				// A more robust way would be to listen to a 'canvasReady' or 'designLoaded' event.
				showGlobalLoadingOverlay(`Auto-updating preview for ${JSON_TYPE_TO_UPDATE}...`); // Show overlay in designer
				setTimeout(() => {
					if (!$updateBtn.prop('disabled')) {
						console.log("Designer: Triggering click on 'Update Template in DB' button.");
						$updateBtn.trigger('click');
						// The AJAX complete handler for this button will call window.opener and close.
					} else {
						console.error("Designer: Auto-update failed. Update button is disabled.");
						if (window.opener && typeof window.opener.handleDesignerUpdateComplete === 'function') {
							window.opener.handleDesignerUpdateComplete(TEMPLATE_ID_TO_UPDATE, JSON_TYPE_TO_UPDATE, false, "Update button was disabled in designer.");
						}
						setTimeout(() => window.close(), 1000); // Close after a delay
					}
				}, 2000); // 2-second delay. Adjust if designs load slower.
			} else {
				console.error("Designer: Auto-update failed. 'Update Template in DB' button not found.");
				if (window.opener && typeof window.opener.handleDesignerUpdateComplete === 'function') {
					window.opener.handleDesignerUpdateComplete(TEMPLATE_ID_TO_UPDATE, JSON_TYPE_TO_UPDATE, false, "Update button not found in designer.");
				}
				setTimeout(() => window.close(), 1000);
			}
		}
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
	} else if (queryUserDesignId) { // <<< --- NEW: Load User Design ---
		showGlobalLoadingOverlay("Loading your saved design...");
		const userDesignJsonUrl = `/user-designs/${queryUserDesignId}/json`;
		fetch(userDesignJsonUrl, {headers: {'Accept': 'application/json'}})
			.then(response => {
				if (!response.ok) {
					if (response.status === 403) throw new Error("Access denied. You may not have permission to load this design.");
					if (response.status === 404) throw new Error("Saved design not found. It might have been deleted.");
					throw new Error(`Error fetching design: ${response.status} ${response.statusText}`);
				}
				return response.json();
			})
			.then(jsonData => {
				console.log("Successfully fetched user design JSON:", userDesignJsonUrl);
				if (jsonData && jsonData.canvas) {
					canvasManager.loadDesign(jsonData, false); // Load as full design
					designLoadedOrSizeSetFromQuery = true;
				} else {
					console.error("Invalid user design JSON structure. Missing 'canvas' property or data is not an object.", jsonData);
					alert("Error: Could not load saved design data due to invalid format.");
				}
			})
			.catch(error => {
				console.error("Error loading/parsing user design from URL:", userDesignJsonUrl, error);
				alert(`Failed to load your saved design: ${error.message}. Defaulting to initial setup.`);
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
	
	function dataURLtoBlob(dataurl) {
		let arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
			bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
		while (n--) {
			u8arr[n] = bstr.charCodeAt(n);
		}
		return new Blob([u8arr], {type: mime});
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
			$('#updateTemplateInDbBtn').on('click', async (e) => { // Make the handler async
				e.preventDefault();
				if (!canvasManager) {
					alert("CanvasManager is not available.");
					return;
				}
				const designData = canvasManager.getDesignDataAsObject();
				if (!designData) {
					alert("Could not retrieve current design data.");
					return;
				}
				
				
				showGlobalLoadingOverlay("Generating preview image...");
				let imageBlob;
				let dataUrl;
				try {
					dataUrl = await canvasManager.exportCanvas('png');
					
					if (!dataUrl) { // <<< --- ADD THIS CHECK
						throw new Error("Canvas export did not return a valid data URL.");
					}
					
					imageBlob = dataURLtoBlob(dataUrl);
				} catch (exportError) {
					hideGlobalLoadingOverlay();
					console.error("Error exporting canvas for template update:", exportError);
					alert("Failed to generate preview image for the template. Update aborted.");
					return;
				}
				
				showGlobalLoadingOverlay("Updating template in database...");
				
				const formData = new FormData();
				formData.append('json_type', JSON_TYPE_TO_UPDATE);
				formData.append('json_data', JSON.stringify(designData)); // Server will json_decode
				formData.append('updated_image_file', imageBlob, `template_preview_${TEMPLATE_ID_TO_UPDATE}_${JSON_TYPE_TO_UPDATE}.png`);
				
				$.ajax({
					url: `/admin/templates/${TEMPLATE_ID_TO_UPDATE}/update-json`,
					type: 'POST',
					data: formData,
					contentType: false, // Important for FormData
					processData: false, // Important for FormData
					headers: {
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
					},
					success: function (response) {
						hideGlobalLoadingOverlay();
						if (response.success) {
							if (!AUTO_UPDATE_PREVIEW) alert('Template updated successfully in the database!');
							
							if (AUTO_UPDATE_PREVIEW && window.opener && typeof window.opener.handleDesignerUpdateComplete === 'function') {
								window.opener.handleDesignerUpdateComplete(TEMPLATE_ID_TO_UPDATE, JSON_TYPE_TO_UPDATE, true, response.message || "Update successful.");
							}
						} else {
							if (!AUTO_UPDATE_PREVIEW) alert('Failed to update template: ' + (response.message || 'Unknown error'));
							console.error("Update template error response:", response);
							
							if (AUTO_UPDATE_PREVIEW && window.opener && typeof window.opener.handleDesignerUpdateComplete === 'function') {
								window.opener.handleDesignerUpdateComplete(TEMPLATE_ID_TO_UPDATE, JSON_TYPE_TO_UPDATE, false, response.message || 'Unknown error from server during update.');
							}
						}
					},
					error: function (xhr) {
						// hideGlobalLoadingOverlay(); // Moved to complete
						const errorMsg = xhr.responseJSON?.message || (xhr.responseJSON?.errors ? JSON.stringify(xhr.responseJSON.errors) : xhr.statusText) || 'Server error';
						if (!AUTO_UPDATE_PREVIEW) alert('Error updating template: ' + errorMsg);
						console.error("Update template AJAX error:", xhr);
						if (AUTO_UPDATE_PREVIEW && window.opener && typeof window.opener.handleDesignerUpdateComplete === 'function') {
							window.opener.handleDesignerUpdateComplete(TEMPLATE_ID_TO_UPDATE, JSON_TYPE_TO_UPDATE, false, `AJAX Error: ${errorMsg}`);
						}
					},
					complete: function() {
						hideGlobalLoadingOverlay(); // Hide overlay once AJAX is fully complete
						if (AUTO_UPDATE_PREVIEW) {
							// Delay closing to ensure message is sent, especially if opener is slow or console logs are pending
							setTimeout(() => {
								console.log("Designer: Auto-update process complete. Closing window.");
								window.close();
							}, 500); // Small delay
						}
					}
				});
				
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
		$('#downloadBtn').on('click', (e) => preventDisabled(e, async () => { // Make it async if not already
			try {
				const dataUrl = await canvasManager.exportCanvas('png'); // Default to PNG, transparent
				if (dataUrl) {
					const filename = `book-cover-export.png`; // Or derive from format
					const a = document.createElement('a');
					a.href = dataUrl;
					a.download = filename;
					document.body.appendChild(a);
					a.click();
					document.body.removeChild(a);
				} else {
					// This case should ideally be handled by exportCanvas throwing an error
					console.error("Download failed: exportCanvas did not return a data URL.");
					alert("Failed to generate image for download. Please try again.");
				}
			} catch (error) {
				console.error("Error during download:", error);
				// Alert is likely already shown by exportCanvas, but you can add another one if needed
				// alert("An error occurred while preparing the image for download.");
			}
		}));
		
		
		$('#openCanvasSizeModalBtn').on('click', (e) => {
			e.preventDefault();
			canvasSizeModal.show();
		});
		
		$('#saveUserDesignBtn').on('click', async (e) => {
			e.preventDefault();
			if (!canvasManager) {
				alert("CanvasManager is not available.");
				return;
			}
			
			const designName = prompt("Enter a name for your design (e.g., My Sci-Fi Cover):", "My Awesome Design");
			if (!designName || designName.trim() === "") {
				alert("Design name cannot be empty.");
				return;
			}
			
			showGlobalLoadingOverlay("Saving your design...");
			
			let designData;
			try {
				designData = canvasManager.getDesignDataAsObject();
				if (!designData) {
					throw new Error("Could not retrieve current design data.");
				}
			} catch (err) {
				hideGlobalLoadingOverlay();
				console.error("Error getting design data:", err);
				alert("Error preparing design data: " + err.message);
				return;
			}
			
			let imageBlob;
			try {
				const dataUrl = await canvasManager.exportCanvas('jpeg');
				if (!dataUrl) {
					throw new Error("Canvas export did not return a valid data URL.");
				}
				imageBlob = dataURLtoBlob(dataUrl); // Assumes dataURLtoBlob function exists
			} catch (exportError) {
				hideGlobalLoadingOverlay();
				console.error("Error exporting canvas for user design save:", exportError);
				alert("Failed to generate preview image for saving. Save aborted.");
				return;
			}
			
			const formData = new FormData();
			formData.append('name', designName.trim());
			formData.append('json_data', JSON.stringify(designData));
			// Sanitize filename a bit for the blob
			const safeFilename = designName.trim().replace(/[^a-z0-9_.-]/gi, '_').substring(0, 50) || 'design_preview';
			formData.append('preview_image_file', imageBlob, `${safeFilename}.png`);
			
			$.ajax({
				url: '/user-designs', // Route: user-designs.store
				type: 'POST',
				data: formData,
				contentType: false,
				processData: false,
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				},
				success: function (response) {
					hideGlobalLoadingOverlay();
					if (response.success) {
						alert('Design saved successfully!');
						// Optionally, you could update a list of saved designs if displayed in designer
						// or provide a link to the dashboard.
					} else {
						let errorMessages = response.message || 'Unknown error';
						if (response.errors) {
							errorMessages += "\nDetails:\n";
							for (const field in response.errors) {
								errorMessages += `- ${response.errors[field].join(', ')}\n`;
							}
						}
						alert('Failed to save design: ' + errorMessages);
						console.error("Save user design error response:", response);
					}
				},
				error: function (xhr) {
					hideGlobalLoadingOverlay();
					const errorMsg = xhr.responseJSON?.message || (xhr.responseJSON?.errors ? JSON.stringify(xhr.responseJSON.errors) : xhr.statusText) || 'Server error';
					alert('Error saving design: ' + errorMsg);
					console.error("Save user design AJAX error:", xhr);
				}
			});
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
