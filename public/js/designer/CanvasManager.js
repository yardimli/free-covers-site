// free-cover-designer/js/CanvasManager.js
class CanvasManager {
	constructor($canvasArea, $canvasWrapper, $canvas, options) {
		this.$canvasArea = $canvasArea;
		this.$canvasWrapper = $canvasWrapper;
		this.$canvas = $canvas;
		
		// Guide element references
		this.$guideLeft = null; // Spine guide
		this.$guideRight = null; // Spine guide
		this.$bleedGuideRect = null;
		this.$spineSafeAreaGuideRect = null;
		this.$frontCoverSafeAreaGuideRect = null;
		this.$backCoverSafeAreaGuideRect = null;
		this.$centerHorizontalGuide = null;
		this.$centerVerticalFrontGuide = null;
		this.$centerVerticalBackGuide = null;
		this.$barcodePlaceholder = null;
		
		// Dependencies
		this.layerManager = options.layerManager; // Should be passed in App.js
		this.historyManager = options.historyManager; // Should be passed in App.js
		this.onZoomChange = options.onZoomChange || (() => {
		}); // Callback for UI updates
		this.onSizeSet = options.onSizeSet || (() => {
		}); // Callback for when canvas size is set
		
		// State
		this.currentZoom = 0.3;
		this.MIN_ZOOM = 0.1; // Min zoom level
		this.MAX_ZOOM = 5.0; // Max zoom level
		this.isPanning = false;
		this.lastPanX = 0;
		this.lastPanY = 0;
		this.inverseZoomMultiplier = 1;
		this.showLoadingOverlay = options.showLoadingOverlay || function (msg) {
			console.warn("showLoadingOverlay not provided to CanvasManager", msg);
		};
		this.hideLoadingOverlay = options.hideLoadingOverlay || function () {
			console.warn("hideLoadingOverlay not provided to CanvasManager");
		};
		
		// Default Canvas Size (can be overridden by loaded designs/templates)
		this.DEFAULT_CANVAS_WIDTH = 1540;
		this.DEFAULT_CANVAS_HEIGHT = 2475;
		this.currentCanvasWidth = this.DEFAULT_CANVAS_WIDTH;
		this.currentCanvasHeight = this.DEFAULT_CANVAS_HEIGHT;
		this.frontCoverWidth = this.DEFAULT_CANVAS_WIDTH;
		this.spineWidth = 0;
		this.backCoverWidth = 0;
		
		// Guide constants
		this.DPI = 300;
		this.INCH_TO_PX = (inches) => inches * this.DPI;
		
		this.BLEED_MARGIN_PX = this.INCH_TO_PX(0.125); // 37.5px
		this.SPINE_SAFE_H_MARGIN_PX = this.INCH_TO_PX(0.062); // 18.6px
		this.SPINE_SAFE_V_MARGIN_PX = this.INCH_TO_PX(0.062 + 0.125); // 56.1px
		// COVER_SAFE_MARGIN_PX is the same as BLEED_MARGIN_PX for front/back covers
		
		this.DEFAULT_CANVAS_BACKGROUND_COLOR = '#FFFFFF'; // This can remain as a fallback if transparency is toggled off
		this.DEFAULT_CANVAS_IS_TRANSPARENT = true; // Set default to transparent
		this.canvasBackgroundColor = this.DEFAULT_CANVAS_BACKGROUND_COLOR;
		this.canvasIsTransparentBackground = this.DEFAULT_CANVAS_IS_TRANSPARENT;
	}
	
	initialize() {
		this.setCanvasSize({
			totalWidth: this.DEFAULT_CANVAS_WIDTH,
			height: this.DEFAULT_CANVAS_HEIGHT,
			frontWidth: this.DEFAULT_CANVAS_WIDTH,
			spineWidth: 0,
			backWidth: 0
		});
		
		this._applyCanvasBackgroundStyle();
		
		if (this.$canvasArea && this.$canvasArea.length) {
			this.inverseZoomMultiplier = 1 / this.currentZoom;
		} else {
			console.warn("CanvasManager: $canvasArea not found during initialization for setting CSS variable.");
		}
		this.initializePan();
		this.initializeCanvasInteractivity();
		this.initializeZoomControls();
		this.setZoom(this.currentZoom, false);
		this.centerCanvas();
		this.onZoomChange(this.currentZoom, this.MIN_ZOOM, this.MAX_ZOOM);
	}
	
	setCanvasBackgroundSettings(settings, triggerHistorySave = true) {
		this.canvasBackgroundColor = settings.color || this.DEFAULT_CANVAS_BACKGROUND_COLOR;
		this.canvasIsTransparentBackground = !!settings.isTransparent; // Ensure boolean
		
		this._applyCanvasBackgroundStyle();
		
		if (triggerHistorySave && this.historyManager) {
			this.historyManager.saveState();
		}
	}
	
	_applyCanvasBackgroundStyle() {
		if (this.canvasIsTransparentBackground) {
			this.$canvas.addClass('checkered-bg');
			this.$canvas.css('background-color', 'transparent');
		} else {
			this.$canvas.removeClass('checkered-bg');
			this.$canvas.css('background-color', this.canvasBackgroundColor);
		}
	}
	
	getCanvasBackgroundSettings() {
		return {
			color: this.canvasBackgroundColor,
			isTransparent: this.canvasIsTransparentBackground
		};
	}
	
	async addInitialImage(imageUrl) {
		this.showLoadingOverlay("Loading initial image...");
		try {
			const img = await new Promise((resolve, reject) => {
				const image = new Image();
				image.crossOrigin = "Anonymous";
				image.onload = () => resolve(image);
				image.onerror = (err) => {
					console.error("Failed to load initial image:", imageUrl, err);
					reject(new Error(`Failed to load initial image: ${imageUrl}`));
				};
				image.src = imageUrl;
			});
			const layerProps = {
				content: imageUrl,
				x: 0,
				y: 0,
				width: this.currentCanvasWidth,
				height: this.currentCanvasHeight,
				name: 'Initial Cover Image',
				locked: true,
				selectable: false,
				zIndex: -1,
				layerSubType: 'cover-background',
				tags: ['initial-cover-image']
			};
			if (this.layerManager) {
				const newLayer = this.layerManager.addLayer('image', layerProps, false);
				if (newLayer) {
					console.log("Initial image added as layer:", newLayer.id);
				} else {
					console.error("Failed to add initial image layer via LayerManager.");
					throw new Error("LayerManager could not add the initial image.");
				}
			} else {
				console.error("LayerManager not available in CanvasManager to add initial image.");
				throw new Error("LayerManager not available.");
			}
		} catch (error) {
			console.error("Error in addInitialImage:", error);
			alert(`Could not load the initial cover image: ${error.message}`);
			throw error;
		} finally {
			this.hideLoadingOverlay();
		}
	}
	
	setCanvasSize(config) {
		const {
			totalWidth = this.DEFAULT_CANVAS_WIDTH,
			height = this.DEFAULT_CANVAS_HEIGHT,
			frontWidth = totalWidth,
			spineWidth = 0,
			backWidth = 0
		} = config;
		
		this.currentCanvasWidth = parseFloat(totalWidth) || this.DEFAULT_CANVAS_WIDTH;
		this.currentCanvasHeight = parseFloat(height) || this.DEFAULT_CANVAS_HEIGHT;
		this.frontCoverWidth = parseFloat(frontWidth) || this.currentCanvasWidth;
		this.spineWidth = parseFloat(spineWidth) || 0;
		this.backCoverWidth = parseFloat(backWidth) || 0;
		
		console.log("CanvasManager received size config:", {
			totalWidth: this.currentCanvasWidth,
			height: this.currentCanvasHeight,
			frontWidth: this.frontCoverWidth,
			spineWidth: this.spineWidth,
			backWidth: this.backCoverWidth
		});
		
		this.$canvas.css({
			width: this.currentCanvasWidth + 'px',
			height: this.currentCanvasHeight + 'px'
		});
		
		this._updateAllVisualGuides(); // Update all guides based on new dimensions
		
		this.updateWrapperSize();
		this.centerCanvas();
		this.onSizeSet();
	}
	
	_removeExistingVisualGuides() {
		if (this.$guideLeft) this.$guideLeft.remove();
		if (this.$guideRight) this.$guideRight.remove();
		this.$guideLeft = null;
		this.$guideRight = null;
		
		if (this.$bleedGuideRect) this.$bleedGuideRect.remove();
		this.$bleedGuideRect = null;
		
		if (this.$spineSafeAreaGuideRect) this.$spineSafeAreaGuideRect.remove();
		this.$spineSafeAreaGuideRect = null;
		
		if (this.$frontCoverSafeAreaGuideRect) this.$frontCoverSafeAreaGuideRect.remove();
		this.$frontCoverSafeAreaGuideRect = null;
		
		if (this.$backCoverSafeAreaGuideRect) this.$backCoverSafeAreaGuideRect.remove();
		this.$backCoverSafeAreaGuideRect = null;
		
		if (this.$centerHorizontalGuide) this.$centerHorizontalGuide.remove();
		this.$centerHorizontalGuide = null;
		
		if (this.$centerVerticalFrontGuide) this.$centerVerticalFrontGuide.remove();
		this.$centerVerticalFrontGuide = null;
		
		if (this.$centerVerticalBackGuide) this.$centerVerticalBackGuide.remove();
		this.$centerVerticalBackGuide = null;
		
		if (this.$barcodePlaceholder) this.$barcodePlaceholder.remove(); // New: Remove barcode placeholder
		this.$barcodePlaceholder = null;
		
		// Remove any old guide elements if they had different IDs/classes
		$('#canvas-safe-zone-guide').remove(); // Old safe zone guide
	}
	
	_updateAllVisualGuides() {
		this._removeExistingVisualGuides();
		this._drawSpineGuides(); // Existing spine lines
		this._drawBleedGuide();
		this._drawCoverSafeAreaGuides();
		if (this.spineWidth > 0 && (this.backCoverWidth > 0 || this.frontCoverWidth === this.currentCanvasWidth)) { // Spine safe area for print or full-bleed kindle
			this._drawSpineSafeAreaGuide();
		}
		this._drawCenterGuides();
		this._drawBarcodePlaceholder();
	}
	
	_drawSpineGuides() { // Renamed from _updateCanvasGuides and focused
		// Remove existing spine guides first (handled by _removeExistingVisualGuides)
		// Only add guides if spine exists (print mode)
		if (this.spineWidth > 0 && this.backCoverWidth > 0) {
			console.log("Adding canvas spine guides.");
			const guideLeftPos = this.backCoverWidth;
			const guideRightPos = this.backCoverWidth + this.spineWidth;
			
			this.$guideLeft = $('<div>')
				.attr('id', 'canvas-guide-left') // Keep ID for potential specific styling/selection
				.addClass('canvas-guide') // Existing class for spine lines
				.css({left: `${guideLeftPos}px`})
				.appendTo(this.$canvas);
			
			this.$guideRight = $('<div>')
				.attr('id', 'canvas-guide-right') // Keep ID
				.addClass('canvas-guide')
				.css({left: `${guideRightPos}px`})
				.appendTo(this.$canvas);
		} else {
			console.log("No spine/back cover for print, spine guides not added.");
		}
	}
	
	_drawBleedGuide() {
		const guideWidth = this.currentCanvasWidth - (2 * this.BLEED_MARGIN_PX);
		const guideHeight = this.currentCanvasHeight - (2 * this.BLEED_MARGIN_PX);
		
		if (guideWidth <= 0 || guideHeight <= 0) {
			console.warn("Canvas too small for bleed guide. Guide not added.");
			return;
		}
		
		this.$bleedGuideRect = $('<div>')
			.addClass('canvas-guide-rect canvas-bleed-guide-rect')
			.css({
				position: 'absolute',
				left: `${this.BLEED_MARGIN_PX}px`,
				top: `${this.BLEED_MARGIN_PX}px`,
				width: `${guideWidth}px`,
				height: `${guideHeight}px`,
			})
			.appendTo(this.$canvas);
		console.log("Bleed guide (trim box) updated.");
	}
	
	_drawCoverSafeAreaGuides() {
		const safeMargin = this.BLEED_MARGIN_PX; // Cover safe margin is 0.125"
		
		// Front Cover Safe Area
		// Starts after back cover and spine (if they exist)
		let frontCoverVisualStart = this.backCoverWidth + this.spineWidth;
		let frontSafeWidth = this.frontCoverWidth - (3 * safeMargin);
		if (frontCoverVisualStart === 0) {
			frontCoverVisualStart = this.BLEED_MARGIN_PX; // For Kindle, start at bleed margin
			frontSafeWidth = this.frontCoverWidth - (4 * safeMargin); // For Kindle, we consider the full width as the front cover
		}
		
		const frontSafeX = frontCoverVisualStart + safeMargin;
		const frontSafeY = safeMargin * 2;
		const frontSafeHeight = this.currentCanvasHeight - (4 * safeMargin);
		
		if (frontSafeWidth > 0 && frontSafeHeight > 0) {
			this.$frontCoverSafeAreaGuideRect = $('<div>')
				.addClass('canvas-guide-rect canvas-cover-safe-area-guide-rect')
				.css({
					position: 'absolute',
					left: `${frontSafeX}px`,
					top: `${frontSafeY}px`,
					width: `${frontSafeWidth}px`,
					height: `${frontSafeHeight}px`,
				})
				.appendTo(this.$canvas);
			console.log("Front cover safe area guide updated.");
		} else {
			console.warn("Front cover area too small for safe zone guide.");
		}
		
		// Back Cover Safe Area (only if back cover exists and has width)
		if (this.backCoverWidth > 0) {
			const backSafeX = safeMargin * 2;
			const backSafeY = safeMargin * 2;
			const backSafeWidth = this.backCoverWidth - (3 * safeMargin);
			const backSafeHeight = this.currentCanvasHeight - (4 * safeMargin);
			
			if (backSafeWidth > 0 && backSafeHeight > 0) {
				this.$backCoverSafeAreaGuideRect = $('<div>')
					.addClass('canvas-guide-rect canvas-cover-safe-area-guide-rect')
					.css({
						position: 'absolute',
						left: `${backSafeX}px`,
						top: `${backSafeY}px`,
						width: `${backSafeWidth}px`,
						height: `${backSafeHeight}px`,
					})
					.appendTo(this.$canvas);
				console.log("Back cover safe area guide updated.");
			} else {
				console.warn("Back cover area too small for safe zone guide.");
			}
		}
	}
	
	_drawSpineSafeAreaGuide() {
		// This guide is only relevant if there's a spine.
		if (!(this.spineWidth > 0)) return;
		// For print covers, spine starts after backCover. For Kindle, spine might be conceptual if full bleed.
		// Let's assume spine starts at this.backCoverWidth for print.
		// For Kindle, if spineWidth is set, it's likely from 0 or an offset.
		// The key is that `this.backCoverWidth` would be 0 for Kindle.
		
		const spineActualStartX = this.backCoverWidth; // For print. For Kindle, this is 0.
		
		const safeX = spineActualStartX + this.SPINE_SAFE_H_MARGIN_PX;
		const safeY = this.SPINE_SAFE_V_MARGIN_PX;
		const safeWidth = this.spineWidth - (2 * this.SPINE_SAFE_H_MARGIN_PX);
		const safeHeight = this.currentCanvasHeight - (2 * this.SPINE_SAFE_V_MARGIN_PX);
		
		if (safeWidth <= 0 || safeHeight <= 0) {
			console.warn("Spine area too small for safe zone guide. Guide not added.");
			return;
		}
		
		this.$spineSafeAreaGuideRect = $('<div>')
			.addClass('canvas-guide-rect canvas-spine-safe-area-guide-rect')
			.css({
				position: 'absolute',
				left: `${safeX}px`,
				top: `${safeY}px`,
				width: `${safeWidth}px`,
				height: `${safeHeight}px`,
			})
			.appendTo(this.$canvas);
		console.log("Spine safe area guide updated.");
	}
	
	_drawCenterGuides() {
		// Horizontal Middle Guide
		const midY = this.currentCanvasHeight / 2;
		this.$centerHorizontalGuide = $('<div>')
			.addClass('canvas-center-guide-horizontal')
			.css({
				position: 'absolute',
				left: '0px',
				top: `${midY}px`,
				width: `${this.currentCanvasWidth}px`,
				height: '1px',
			})
			.appendTo(this.$canvas);
		
		// Vertical Middle Guide (Front)
		// Front cover starts after back cover and spine
		const frontCoverVisualStart = this.backCoverWidth + this.spineWidth;
		let frontMidX = frontCoverVisualStart + (this.frontCoverWidth / 2) - (this.BLEED_MARGIN_PX / 2);
		if (this.backCoverWidth === 0) {
			frontMidX = (this.frontCoverWidth / 2);
		}
		
		this.$centerVerticalFrontGuide = $('<div>')
			.addClass('canvas-center-guide-vertical')
			.css({
				position: 'absolute',
				left: `${frontMidX}px`,
				top: '0px',
				width: '1px',
				height: `${this.currentCanvasHeight}px`,
			})
			.appendTo(this.$canvas);
		
		// Vertical Middle Guide (Back - if back cover exists and has width)
		if (this.backCoverWidth > 0) {
			const backMidX = this.backCoverWidth / 2 + (this.BLEED_MARGIN_PX / 2);
			this.$centerVerticalBackGuide = $('<div>')
				.addClass('canvas-center-guide-vertical')
				.css({
					position: 'absolute',
					left: `${backMidX}px`,
					top: '0px',
					width: '1px',
					height: `${this.currentCanvasHeight}px`,
				})
				.appendTo(this.$canvas);
		}
		console.log("Center guides updated.");
	}
	
	_drawBarcodePlaceholder() {
		// Remove existing first (in case of resize or canvas type change)
		if (this.$barcodePlaceholder) {
			this.$barcodePlaceholder.remove();
			this.$barcodePlaceholder = null;
		}
		
		// Only draw for full print covers (when back cover and spine are present)
		if (!(this.spineWidth > 0 && this.backCoverWidth > 0)) {
			return;
		}
		
		const BARCODE_OFFSET_FROM_SAFE_AREA_PX = this.INCH_TO_PX(0.25); // 75px
		const BARCODE_WIDTH_PX = 605;
		const BARCODE_HEIGHT_PX = 365;
		
		// Calculate the bottom-right corner of the *drawn* back cover safe area guide
		// As per _drawCoverSafeAreaGuides for back cover:
		// backSafeX_start_coord = this.BLEED_MARGIN_PX * 2
		// backSafeY_start_coord = this.BLEED_MARGIN_PX * 2
		// backSafeWidth_dim = this.backCoverWidth - (3 * this.BLEED_MARGIN_PX)
		// backSafeHeight_dim = this.currentCanvasHeight - (4 * this.BLEED_MARGIN_PX)
		
		// Right X-coordinate of the back cover safe area guide
		const backCoverSafeAreaGuide_RightX = (this.BLEED_MARGIN_PX * 2) + (this.backCoverWidth - (3 * this.BLEED_MARGIN_PX));
		// Simplifies to: this.backCoverWidth - this.BLEED_MARGIN_PX
		
		// Bottom Y-coordinate of the back cover safe area guide
		const backCoverSafeAreaGuide_BottomY = (this.BLEED_MARGIN_PX * 2) + (this.currentCanvasHeight - (4 * this.BLEED_MARGIN_PX));
		// Simplifies to: this.currentCanvasHeight - (2 * this.BLEED_MARGIN_PX)
		
		// Position the placeholder 0.25 inches INSIDE this safe area guide's bottom-right corner
		const placeholderRightEdgeAbsolute = backCoverSafeAreaGuide_RightX - BARCODE_OFFSET_FROM_SAFE_AREA_PX;
		const placeholderBottomEdgeAbsolute = backCoverSafeAreaGuide_BottomY - BARCODE_OFFSET_FROM_SAFE_AREA_PX;
		
		const placeholderX_css_left = placeholderRightEdgeAbsolute - BARCODE_WIDTH_PX;
		const placeholderY_css_top = placeholderBottomEdgeAbsolute - BARCODE_HEIGHT_PX;
		
		// Basic validation: ensure placeholder is not off-canvas due to small cover size
		// or invalid calculations (e.g. if safe area is too small itself)
		if (placeholderX_css_left < 0 || placeholderY_css_top < 0 ||
			placeholderRightEdgeAbsolute > this.backCoverWidth || // Should not exceed back cover width
			placeholderBottomEdgeAbsolute > this.currentCanvasHeight) { // Should not exceed canvas height
			console.warn("Barcode placeholder cannot be drawn; calculated position is outside valid area or back cover is too small.", {
				placeholderX_css_left, placeholderY_css_top, BARCODE_WIDTH_PX, BARCODE_HEIGHT_PX,
				backCoverWidth: this.backCoverWidth, currentCanvasHeight: this.currentCanvasHeight
			});
			return;
		}
		
		this.$barcodePlaceholder = $('<div>')
			.attr('id', 'canvas-barcode-placeholder') // For styling and removal during export
			.css({
				position: 'absolute',
				left: `${placeholderX_css_left}px`,
				top: `${placeholderY_css_top}px`,
				width: `${BARCODE_WIDTH_PX}px`,
				height: `${BARCODE_HEIGHT_PX}px`,
			})
			.html('<div>BARCODE</div><div>AREA</div>') // Text content
			.appendTo(this.$canvas);
		
		console.log("Barcode placeholder drawn at:", {x: placeholderX_css_left, y: placeholderY_css_top});
	}
	
	updateWrapperSize() {
		this.$canvasWrapper.css({
			width: this.currentCanvasWidth * this.currentZoom + 'px',
			height: this.currentCanvasHeight * this.currentZoom + 'px'
		});
		this.$canvas.css({
			transform: `scale(${this.currentZoom})`,
			transformOrigin: 'top left'
		});
	}
	
	calculateLayerBoundingRect(layer) {
		let layerX = layer.x;
		let layerY = layer.y;
		let layerWidth = layer.width;
		let layerHeight = layer.height;
		const centerX = layerX + layerWidth / 2;
		const centerY = layerY + layerHeight / 2;
		if (layer.scale) {
			const scaleRatio = layer.scale / 100;
			const newWidth = layerWidth * scaleRatio;
			const newHeight = layerHeight * scaleRatio;
			layerX = centerX - newWidth / 2;
			layerY = centerY - newHeight / 2;
			layerWidth = newWidth;
			layerHeight = newHeight;
		}
		if (layer.rotation) {
			const angleRad = layer.rotation * Math.PI / 180;
			const halfWidth = layerWidth / 2;
			const halfHeight = layerHeight / 2;
			const corners = [
				{x: -halfWidth, y: -halfHeight},
				{x: halfWidth, y: -halfHeight},
				{x: halfWidth, y: halfHeight},
				{x: -halfWidth, y: halfHeight}
			];
			let minX = Number.MAX_VALUE;
			let minY = Number.MAX_VALUE;
			let maxX = Number.MIN_VALUE;
			let maxY = Number.MIN_VALUE;
			for (const corner of corners) {
				const rotatedX = corner.x * Math.cos(angleRad) - corner.y * Math.sin(angleRad);
				const rotatedY = corner.x * Math.sin(angleRad) + corner.y * Math.cos(angleRad);
				minX = Math.min(minX, rotatedX);
				minY = Math.min(minY, rotatedY);
				maxX = Math.max(maxX, rotatedX);
				maxY = Math.max(maxY, rotatedY);
			}
			layerX = centerX + minX;
			layerY = centerY + minY;
			layerWidth = maxX - minX;
			layerHeight = maxY - minY;
		}
		return {
			x: layerX,
			y: layerY,
			width: layerWidth,
			height: layerHeight,
			x2: layerX + layerWidth,
			y2: layerY + layerHeight
		};
	}
	
	initializeCanvasInteractivity() {
		const div = document.getElementById('canvas');
		const self = this;
		div.addEventListener('mousedown', function (event) {
			const rect = div.getBoundingClientRect();
			let mouseX = event.clientX - rect.left;
			let mouseY = event.clientY - rect.top;
			mouseX = mouseX / self.currentZoom;
			mouseY = mouseY / self.currentZoom;
			console.log(`Clicked at position: x=${mouseX}, y=${mouseY}`);
			let clickableLayers = [];
			for (let i = 0; i < self.layerManager.getLayers().length; i++) {
				const layer = self.layerManager.getLayers()[i];
				const layer_bounding = self.calculateLayerBoundingRect(layer);
				if (layer_bounding.x <= mouseX && layer_bounding.x2 >= mouseX && layer_bounding.y <= mouseY && layer_bounding.y2 >= mouseY) {
					if (!layer.locked) {
						const layerArea = layer_bounding.width * layer_bounding.height;
						clickableLayers.push({layer: layer, zIndex: layer.zIndex, area: layerArea});
						console.log("Click inside layer: " + layer.id + ' z-index: ' + layer.zIndex + ' area: ' + layerArea);
					}
				}
			}
			let selectedLayer = null;
			if (clickableLayers.length > 0) {
				clickableLayers.sort((a, b) => {
					if (a.area !== b.area) {
						return a.area - b.area;
					}
					return b.zIndex - a.zIndex;
				});
				selectedLayer = clickableLayers[0].layer;
				self.layerManager.selectLayer(selectedLayer.id);
				console.log("Selected layer: " + selectedLayer.id + " (area: " + clickableLayers[0].area + ", z-index: " + selectedLayer.zIndex + ")");
			} else {
				self.layerManager.selectLayer(null);
				console.log("No layer selected.");
			}
		});
	}
	
	initializePan() {
		this.$canvasArea.on('mousedown', (e) => {
			const isBackgroundClick = e.target === this.$canvasArea[0] || e.target === this.$canvasWrapper[0];
			const isMiddleMouse = e.which === 2;
			let $clickedLayerElement = $(e.target).closest('#canvas .canvas-element:not(.locked)');
			if ($clickedLayerElement.length === 0) {
				$clickedLayerElement = $(e.target).closest('#canvas .canvas-element');
			}
			let isClickOnLayer = false;
			if ($clickedLayerElement.length > 0 && !isMiddleMouse) {
				const layerId = $clickedLayerElement.data('layerId');
				if (this.layerManager) {
					const layer = this.layerManager.getLayerById(layerId);
					if (layer) {
						isClickOnLayer = true;
					}
				}
			}
			if (isClickOnLayer) {
				return;
			}
			if (isBackgroundClick || isMiddleMouse) {
				this.isPanning = true;
				this.lastPanX = e.clientX;
				this.lastPanY = e.clientY;
				this.$canvasArea.addClass('panning');
				e.preventDefault();
			}
		});
		$(document).on('mousemove.canvasManagerPan', (e) => {
			if (!this.isPanning) return;
			const deltaX = e.clientX - this.lastPanX;
			const deltaY = e.clientY - this.lastPanY;
			this.$canvasArea.scrollLeft(this.$canvasArea.scrollLeft() - deltaX);
			this.$canvasArea.scrollTop(this.$canvasArea.scrollTop() - deltaY);
			this.lastPanX = e.clientX;
			this.lastPanY = e.clientY;
		});
		$(document).on('mouseup.canvasManagerPan mouseleave.canvasManagerPan', (e) => {
			if (this.isPanning) {
				this.isPanning = false;
				this.$canvasArea.removeClass('panning');
			}
		});
	}
	
	initializeZoomControls() {
		$('#zoom-in').on('click', () => this.zoom(1.25));
		$('#zoom-out').on('click', () => this.zoom(0.8));
		const self = this;
		$('#zoom-options-menu').on('click', '.zoom-option', function (e) {
			e.preventDefault();
			const zoomValue = $(this).data('zoom');
			if (zoomValue === 'fit') {
				self.zoomToFit();
			} else {
				const numericZoom = parseFloat(zoomValue);
				if (!isNaN(numericZoom)) {
					self.setZoom(numericZoom);
				}
			}
		});
	}
	
	getDesignDataAsObject() {
		if (!this.layerManager) {
			console.error("LayerManager not available in CanvasManager for getDesignDataAsObject.");
			return null;
		}
		const sortedLayers = [...this.layerManager.getLayers()].sort((a, b) => (a.zIndex || 0) - (b.zIndex || 0));
		const designData = {
			version: "1.3",
			canvas: {
				width: this.currentCanvasWidth,
				height: this.currentCanvasHeight,
				frontWidth: this.frontCoverWidth,
				spineWidth: this.spineWidth,
				backWidth: this.backCoverWidth,
				backgroundColor: this.canvasBackgroundColor,
				isTransparentBackground: this.canvasIsTransparentBackground
			},
			layers: sortedLayers.map(layer => {
				const {shadowOffsetInternal, shadowAngleInternal, ...layerToSave} = layer;
				return layerToSave;
			})
		};
		return designData;
	}
	
	zoom(factor) {
		const newZoom = this.currentZoom * factor;
		this.setZoom(newZoom);
	}
	
	setZoom(newZoom, triggerCallbacks = true) {
		const oldZoom = this.currentZoom;
		const clampedZoom = Math.max(this.MIN_ZOOM, Math.min(this.MAX_ZOOM, newZoom));
		if (clampedZoom === oldZoom) {
			return;
		}
		this.currentZoom = clampedZoom;
		if (this.$canvasArea && this.$canvasArea.length) {
			this.inverseZoomMultiplier = 1 / this.currentZoom;
		}
		this.updateWrapperSize();
		this.centerCanvas();
		if (triggerCallbacks) {
			this.onZoomChange(this.currentZoom, this.MIN_ZOOM, this.MAX_ZOOM);
		}
	}
	
	zoomToFit() {
		requestAnimationFrame(() => {
			if (!this.$canvasArea || !this.$canvasArea.length || this.currentCanvasWidth <= 0 || this.currentCanvasHeight <= 0) {
				console.warn("Cannot zoomToFit, invalid dimensions or missing canvasArea.");
				return;
			}
			const areaWidth = this.$canvasArea.innerWidth() - 40;
			const areaHeight = this.$canvasArea.innerHeight() - 40;
			if (areaWidth <= 0 || areaHeight <= 0) {
				console.warn("Cannot zoomToFit, invalid area dimensions after padding.");
				return;
			}
			const scaleX = areaWidth / this.currentCanvasWidth;
			const scaleY = areaHeight / this.currentCanvasHeight;
			const newZoom = Math.min(scaleX, scaleY);
			this.setZoom(newZoom);
		});
	}
	
	centerCanvas() {
		requestAnimationFrame(() => {
			if (!this.$canvasArea || !this.$canvasArea.length || !this.$canvasWrapper || !this.$canvasWrapper.length) {
				return;
			}
			const areaWidth = this.$canvasArea.innerWidth();
			const areaHeight = this.$canvasArea.innerHeight();
			const wrapperWidth = this.$canvasWrapper.outerWidth();
			const wrapperHeight = this.$canvasWrapper.outerHeight();
			console.log("Centering canvas: areaWidth=", areaWidth, "areaHeight=", areaHeight, "wrapperWidth=", wrapperWidth, "wrapperHeight=", wrapperHeight);
			const scrollLeft = ((wrapperWidth) / 2) + 250;
			const scrollTop = ((wrapperHeight) / 2) + 150;
			this.$canvasArea.scrollLeft(scrollLeft);
			this.$canvasArea.scrollTop(scrollTop);
		});
	}
	
	_isGoogleFont(fontFamily) {
		if (!fontFamily) return false;
		const knownLocal = ['arial', 'verdana', 'times new roman', 'georgia', 'courier new', 'serif', 'sans-serif', 'monospace', 'helvetica neue', 'system-ui'];
		const lowerFont = fontFamily.toLowerCase().replace(/['"]/g, '');
		return !knownLocal.includes(lowerFont) && /^[a-z0-9\s]+$/i.test(lowerFont);
	}
	
	async _getEmbeddedFontsCss(layersData) {
		const uniqueGoogleFonts = new Set();
		layersData.forEach(layer => {
			if (layer.type === 'text' && layer.fontFamily && this._isGoogleFont(layer.fontFamily)) {
				uniqueGoogleFonts.add(`family=${encodeURIComponent(layer.fontFamily.trim())}:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900`);
			}
		});
		if (uniqueGoogleFonts.size === 0) {
			return '';
		}
		const fontFamiliesParam = Array.from(uniqueGoogleFonts).join('&');
		const fontUrl = `https://fonts.googleapis.com/css2?${fontFamiliesParam}&display=swap`;
		let originalCss = '';
		try {
			console.log("Fetching Google Fonts CSS:", fontUrl);
			const cssResponse = await fetch(fontUrl, {
				headers: {
					'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
				}
			});
			if (!cssResponse.ok) {
				throw new Error(`CSS fetch failed! status: ${cssResponse.status}`);
			}
			originalCss = await cssResponse.text();
			console.log("Successfully fetched Google Fonts CSS definitions.");
			const fontUrls = originalCss.match(/url\((https:\/\/fonts\.gstatic\.com\/[^)]+)\)/g);
			if (!fontUrls || fontUrls.length === 0) {
				console.warn("No font file URLs found in the fetched CSS.");
				return originalCss;
			}
			const urlsToFetch = fontUrls.map(match => match.substring(4, match.length - 1));
			console.log(`Found ${urlsToFetch.length} font files to fetch and embed.`);
			const fontFetchPromises = urlsToFetch.map(async (url) => {
				try {
					const fontResponse = await fetch(url);
					if (!fontResponse.ok) {
						throw new Error(`Font fetch failed! status: ${fontResponse.status} for ${url}`);
					}
					const blob = await fontResponse.blob();
					const base64 = await this._blobToBase64(blob);
					const mimeType = blob.type || 'font/woff2';
					return {url, base64, mimeType};
				} catch (fontError) {
					console.error(`Failed to fetch or encode font: ${url}`, fontError);
					return {url, error: true};
				}
			});
			const embeddedFontsData = await Promise.all(fontFetchPromises);
			let embeddedCss = originalCss;
			embeddedFontsData.forEach(fontData => {
				if (!fontData.error) {
					const dataUri = `data:${fontData.mimeType};base64,${fontData.base64}`;
					const escapedUrl = fontData.url.replace(/\(/g, '\\(').replace(/\)/g, '\\)');
					const regex = new RegExp(`url\\(${escapedUrl}\\)`, 'g');
					embeddedCss = embeddedCss.replace(regex, `url(${dataUri})`);
				}
			});
			console.log("Finished embedding font data into CSS.");
			return embeddedCss;
		} catch (error) {
			console.error("Error processing Google Fonts for embedding:", error);
			alert("Warning: Could not process Google Font definitions for export. Export might use fallback fonts.");
			return '';
		}
	}
	
	_blobToBase64(blob) {
		return new Promise((resolve, reject) => {
			const reader = new FileReader();
			reader.onloadend = () => {
				const base64String = reader.result.split(',')[1];
				resolve(base64String);
			};
			reader.onerror = reject;
			reader.readAsDataURL(blob);
		});
	}
	
	async exportCanvas(format = 'png') {
		this.showLoadingOverlay(`Exporting as ${format.toUpperCase()}...`);
		this.layerManager.selectLayer(null);
		const originalTransform = this.$canvas.css('transform');
		const originalWrapperWidth = this.$canvasWrapper.css('width');
		const originalWrapperHeight = this.$canvasWrapper.css('height');
		const originalScrollLeft = this.$canvasArea.scrollLeft();
		const originalScrollTop = this.$canvasArea.scrollTop();
		this.$canvas.css('transform', 'scale(1.0)');

		this.$canvas.removeClass('checkered-bg');
		if (!this.canvasIsTransparentBackground) {
			this.$canvas.css('background-color',this.canvasBackgroundColor);
		}
		
		this.$canvasWrapper.css({
			width: this.currentCanvasWidth + 'px',
			height: this.currentCanvasHeight + 'px'
		});
		const wrapperPos = this.$canvasWrapper.position();
		this.$canvasArea.scrollLeft(wrapperPos.left);
		this.$canvasArea.scrollTop(wrapperPos.top);
		const layersData = this.layerManager.getLayers();
		const defaultFilters = this.layerManager.defaultFilters;
		const defaultTransform = this.layerManager.defaultTransform;
		const hiddenLayerIds = layersData.filter(l => !l.visible).map(l => l.id);
		hiddenLayerIds.forEach(id => $(`#${id}`).show());
		const canvasElement = this.$canvas[0];
		const mimeType = format === 'jpeg' ? 'image/jpeg' : 'image/png';
		const quality = format === 'jpeg' ? 0.95 : undefined;
		// const filename = `book-cover-export.${format}`; // Filename handled by App.js
		const embeddedFontCss = await this._getEmbeddedFontsCss(layersData);
		const screenshotOptions = {
			width: this.currentCanvasWidth,
			height: this.currentCanvasHeight,
			scale: 1,
			quality: quality,
			fetch: {},
			onCloneNode: (clonedNode) => {
				if (!clonedNode || clonedNode.id !== 'canvas') {
					console.warn("onCloneNode did not receive the expected #canvas clone.");
					return;
				}
				// Remove all guide elements from the clone
				clonedNode.querySelectorAll('.canvas-guide, .canvas-guide-rect, .canvas-center-guide-horizontal, .canvas-center-guide-vertical').forEach(el => el.remove());
				
				// --- NEW: Remove barcode placeholder from clone ---
				const barcodePlaceholderClone = clonedNode.querySelector('#canvas-barcode-placeholder');
				if (barcodePlaceholderClone) {
					barcodePlaceholderClone.remove();
					console.log("Removed barcode placeholder from cloned node for export.");
				}
				// --- END NEW ---
				
				console.log("Guides and placeholders removed from cloned node for export.");

				if (this.canvasIsTransparentBackground) {
					clonedNode.style.backgroundColor = 'transparent';
				}
				
				if (embeddedFontCss) {
					const style = document.createElement('style');
					style.textContent = embeddedFontCss;
					clonedNode.prepend(style);
					console.log("Injected EMBEDDED Google Fonts CSS into cloned node.");
				}
				clonedNode.style.transform = 'scale(1.0)';
				clonedNode.querySelectorAll('.canvas-element.selected').forEach(el => el.classList.remove('selected'));
				hiddenLayerIds.forEach(id => {
					const el = clonedNode.querySelector(`#${id}`);
					if (el) el.style.display = 'block';
				});
				layersData.forEach(layer => {
					const clonedElement = clonedNode.querySelector(`#${layer.id}`);
					if (!clonedElement) return;
					clonedElement.style.mixBlendMode = layer.blendMode || 'normal';
					const rotation = layer.rotation || defaultTransform.rotation;
					const scale = (layer.scale || defaultTransform.scale) / 100;
					clonedElement.style.transform = `rotate(${rotation}deg) scale(${scale})`;
					clonedElement.style.transformOrigin = 'center center';
					if (layer.type === 'image') {
						const clonedImg = clonedElement.querySelector('img');
						if (clonedImg) {
							const filters = layer.filters || defaultFilters;
							let filterString = '';
							if (filters.brightness !== 100) filterString += `brightness(${filters.brightness}%) `;
							if (filters.contrast !== 100) filterString += `contrast(${filters.contrast}%) `;
							if (filters.saturation !== 100) filterString += `saturate(${filters.saturation}%) `;
							if (filters.grayscale !== 0) filterString += `grayscale(${filters.grayscale}%) `;
							if (filters.sepia !== 0) filterString += `sepia(${filters.sepia}%) `;
							if (filters.hueRotate !== 0) filterString += `hue-rotate(${filters.hueRotate}deg) `;
							if (filters.blur !== 0) filterString += `blur(${filters.blur}px) `;
							clonedImg.style.filter = filterString.trim() || 'none';
						}
					}
					if (layer.type === 'text') {
						const clonedTextContent = clonedElement.querySelector('.text-content');
						if (clonedTextContent) {
							let fontFamily = layer.fontFamily || 'Arial';
							if (fontFamily.includes(' ') && !fontFamily.startsWith("'") && !fontFamily.startsWith('"')) {
								fontFamily = `"${fontFamily}"`;
							}
							clonedTextContent.style.fontFamily = fontFamily;
							clonedTextContent.style.fontSize = (layer.fontSize || 16) + 'px';
							clonedTextContent.style.fontWeight = layer.fontWeight || 'normal';
							clonedTextContent.style.fontStyle = layer.fontStyle || 'normal';
							clonedTextContent.style.textDecoration = layer.textDecoration || 'none';
							clonedTextContent.style.color = layer.fill || 'rgba(0,0,0,1)';
							clonedTextContent.style.textAlign = layer.align || 'left';
							clonedTextContent.style.justifyContent = layer.align || 'left';
							clonedTextContent.style.display = 'flex';
							clonedTextContent.style.alignItems = layer.vAlign || 'center';
							clonedTextContent.style.lineHeight = layer.lineHeight || 1.3;
							clonedTextContent.style.letterSpacing = (layer.letterSpacing || 0) + 'px';
							clonedTextContent.style.whiteSpace = 'pre-wrap';
							clonedTextContent.style.wordWrap = 'break-word';
							if (layer.shadowEnabled && layer.shadowColor) {
								const shadow = `${layer.shadowOffsetX || 0}px ${layer.shadowOffsetY || 0}px ${layer.shadowBlur || 0}px ${layer.shadowColor}`;
								clonedTextContent.style.textShadow = shadow;
							} else {
								clonedTextContent.style.textShadow = 'none';
							}
							const strokeWidth = parseFloat(layer.strokeWidth) || 0;
							if (strokeWidth > 0 && layer.stroke) {
								const strokeColor = layer.stroke || 'rgba(0,0,0,1)';
								clonedTextContent.style.webkitTextStrokeWidth = strokeWidth + 'px';
								clonedTextContent.style.webkitTextStrokeColor = strokeColor;
								clonedTextContent.style.textStrokeWidth = strokeWidth + 'px';
								clonedTextContent.style.textStrokeColor = strokeColor;
								clonedTextContent.style.paintOrder = 'stroke fill';
							} else {
								clonedTextContent.style.webkitTextStrokeWidth = '0';
								clonedTextContent.style.textStrokeWidth = '0';
							}
							if (layer.backgroundEnabled && layer.backgroundColor) {
								let bgColor = layer.backgroundColor;
								const bgOpacity = layer.backgroundOpacity ?? 1;
								if (bgOpacity < 1) {
									try {
										let tiny = tinycolor(bgColor);
										if (tiny.isValid()) {
											bgColor = tiny.setAlpha(bgOpacity).toRgbString();
										}
									} catch (e) { /* ignore */
									}
								}
								clonedElement.style.backgroundColor = bgColor;
								clonedElement.style.borderRadius = (layer.backgroundCornerRadius || 0) + 'px';
							} else {
								clonedElement.style.backgroundColor = 'transparent';
								clonedElement.style.borderRadius = '0';
							}
						}
					}
				});
			}
		};
		let screenshotPromise;
		if (format === 'jpeg') {
			screenshotPromise = modernScreenshot.domToJpeg(canvasElement, screenshotOptions);
		} else {
			screenshotPromise = modernScreenshot.domToPng(canvasElement, screenshotOptions);
		}
		try {
			const dataUrl = await screenshotPromise;
			return dataUrl;
		} catch (err) {
			console.error(`Error exporting canvas with modernScreenshot (.${format}):`, err);
			alert(`Error exporting canvas as ${format.toUpperCase()}. Check console. Embedded font processing might have failed.`);
			return null; // Return null or throw to indicate failure
		} finally {
			hiddenLayerIds.forEach(id => {
				const layer = this.layerManager.getLayerById(id);
				if (layer && !layer.visible) {
					$(`#${id}`).hide();
				}
			});
			this.$canvas.css('transform', originalTransform);
			if (this.canvasIsTransparentBackground) {
				this.$canvas.addClass('checkered-bg');
			}
			this.$canvasWrapper.css({width: originalWrapperWidth, height: originalWrapperHeight});
			this.$canvasArea.scrollLeft(originalScrollLeft);
			this.$canvasArea.scrollTop(originalScrollTop);
			const selectedLayer = this.layerManager.getSelectedLayer();
			if (selectedLayer) {
				$(`#${selectedLayer.id}`).addClass('selected');
			}
			this.hideLoadingOverlay();
			console.log("Export process finished, restored original state.");
		}
	}
	
	saveDesign() {
		const designData = this.getDesignDataAsObject();
		const jsonData = JSON.stringify(designData, null, 2);
		const blob = new Blob([jsonData], {type: 'application/json'});
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = 'book-cover-design.json';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	}
	
	loadDesign(source, isTemplate = false) {
		if (!source) return;
		const handleLoad = (designData) => {
			try {
				if (designData && designData.layers && designData.canvas) {
					let sizeConfig;
					if (designData.canvas.frontWidth !== undefined) {
						sizeConfig = {
							totalWidth: designData.canvas.width,
							height: designData.canvas.height,
							frontWidth: designData.canvas.frontWidth,
							spineWidth: designData.canvas.spineWidth || 0,
							backWidth: designData.canvas.backWidth || 0
						};
					} else {
						sizeConfig = {
							totalWidth: designData.canvas.width,
							height: designData.canvas.height,
							frontWidth: designData.canvas.width,
							spineWidth: 0,
							backWidth: 0
						};
					}
					
					const bgColor = designData.canvas.backgroundColor || this.DEFAULT_CANVAS_BACKGROUND_COLOR;
					const isTransparent = designData.canvas.isTransparentBackground !== undefined
						? designData.canvas.isTransparentBackground
						: this.DEFAULT_CANVAS_IS_TRANSPARENT;
					this.setCanvasBackgroundSettings({ color: bgColor, isTransparent: isTransparent }, false);
					
					if (isTemplate && !IS_ADMIN_MODE) {
						console.log("Applying template: Calculating centering offset.");
						if (designData.layers.length > 0) {
							let templateMinX = Infinity;
							let templateMinY = Infinity;
							let templateMaxX = -Infinity;
							let templateMaxY = -Infinity;
							designData.layers.forEach(layer => {
								const x = parseFloat(layer.x) || 0;
								const y = parseFloat(layer.y) || 0;
								let width, height;
								if (layer.width === 'auto' || isNaN(parseFloat(layer.width))) {
									width = 0;
								} else {
									width = parseFloat(layer.width);
								}
								if (layer.height === 'auto' || isNaN(parseFloat(layer.height))) {
									height = 0;
								} else {
									height = parseFloat(layer.height);
								}
								templateMinX = Math.min(templateMinX, x);
								templateMinY = Math.min(templateMinY, y);
								templateMaxX = Math.max(templateMaxX, x + width);
								templateMaxY = Math.max(templateMaxY, y + height);
							});
							const templateEffectiveWidth = (templateMaxX === -Infinity) ? 0 : templateMaxX - templateMinX;
							const templateEffectiveHeight = (templateMaxY === -Infinity) ? 0 : templateMaxY - templateMinY;
							const canvasCenterX = this.currentCanvasWidth / 2;
							const canvasCenterY = this.currentCanvasHeight / 2;
							const templateCenterX = templateMinX + templateEffectiveWidth / 2;
							const templateCenterY = templateMinY + templateEffectiveHeight / 2;
							const offsetX = canvasCenterX - templateCenterX;
							const offsetY = canvasCenterY - templateCenterY;
							console.log(`Template original bounds: minX=${templateMinX}, minY=${templateMinY}, maxX=${templateMaxX}, maxY=${templateMaxY}`);
							console.log(`Template effective dims: width=${templateEffectiveWidth}, height=${templateEffectiveHeight}`);
							console.log(`Canvas center: X=${canvasCenterX}, Y=${canvasCenterY}`);
							console.log(`Template center: X=${templateCenterX}, Y=${templateCenterY}`);
							console.log(`Calculated offset: dX=${offsetX}, dY=${offsetY}`);
							designData.layers.forEach(layer => {
								layer.x = (parseFloat(layer.x) || 0) + offsetX;
								layer.y = (parseFloat(layer.y) || 0) + offsetY;
							});
						}
						if (this.frontCoverWidth > 0 && this.spineWidth > 0 && this.backCoverWidth > 0) {
							console.log("Applying definition-based constraints for template layers after centering.");
							const frontCoverStartX = this.backCoverWidth + this.spineWidth + this.BLEED_MARGIN_PX; // Adjusted for bleed
							designData.layers.forEach(layer => {
								let currentX = layer.x;
								let currentY = layer.y;
								let currentWidth = (layer.width === 'auto' || isNaN(parseFloat(layer.width))) ? 'auto' : parseFloat(layer.width);
								let currentHeight = (layer.height === 'auto' || isNaN(parseFloat(layer.height))) ? 'auto' : parseFloat(layer.height);
								const originalX = currentX;
								const originalY = currentY;
								const originalWidth = currentWidth;
								const originalHeight = currentHeight;
								let modified = false;
								
								const topSafe = this.BLEED_MARGIN_PX;
								const bottomSafe = this.currentCanvasHeight - this.BLEED_MARGIN_PX;
								
								if (layer.definition === "back_cover_text" || layer.definition === "back_cover_title" || layer.definition === "cover_title" || layer.definition === "cover_text") {
									if (currentY < topSafe) {
										currentY = topSafe;
										modified = true;
									}
									if (layer.y !== currentY) layer.y = currentY;
									if (currentHeight !== 'auto') {
										if (currentY + currentHeight > bottomSafe) {
											const newHeight = bottomSafe - currentY;
											currentHeight = Math.max(0, newHeight);
											modified = true;
										}
										if (layer.height !== currentHeight) layer.height = currentHeight;
									}
								}
								if (layer.definition === "back_cover_text" || layer.definition === "back_cover_title") {
									const backLeftSafe = this.BLEED_MARGIN_PX;
									const backRightSafe = this.backCoverWidth - this.BLEED_MARGIN_PX;
									if (currentX < backLeftSafe) {
										currentX = backLeftSafe;
										modified = true;
									}
									if (layer.x !== currentX) layer.x = currentX;
									if (currentWidth !== 'auto') {
										if (currentX + currentWidth > backRightSafe) {
											const newWidth = backRightSafe - currentX;
											currentWidth = Math.max(0, newWidth);
											modified = true;
										}
										if (layer.width !== currentWidth) layer.width = currentWidth;
									}
								} else if (layer.definition === "cover_title" || layer.definition === "cover_text") {
									const frontRightSafe = this.currentCanvasWidth - this.BLEED_MARGIN_PX;
									if (currentX < frontCoverStartX) {
										currentX = frontCoverStartX;
										modified = true;
									}
									if (layer.x !== currentX) layer.x = currentX;
									if (currentWidth !== 'auto') {
										if (currentX + currentWidth > frontRightSafe) {
											const newWidth = frontRightSafe - currentX;
											currentWidth = Math.max(0, newWidth);
											modified = true;
										}
										if (layer.width !== currentWidth) layer.width = currentWidth;
									}
								}
								if (modified) {
									console.log(`Layer ${layer.id} (${layer.definition}): Constrained. ` + `Original: x=${originalX.toFixed(2)}, y=${originalY.toFixed(2)}, w=${originalWidth}, h=${originalHeight}. ` + `New: x=${layer.x.toFixed(2)}, y=${layer.y.toFixed(2)}, w=${layer.width}, h=${layer.height}`);
								}
							});
						}
					}
					if (!isTemplate) {
						console.log("Loading full design: Clearing history and setting canvas size.");
						this.historyManager.clear();
						this.setCanvasSize(sizeConfig);
					} else {
						console.log("Applying template: Keeping existing canvas size and non-text layers.");
					}
					this.layerManager.setLayers(designData.layers, isTemplate);
					this.historyManager.saveState();
					if (!isTemplate) {
						this.zoomToFit();
						this.centerCanvas();
					}
					this.layerManager.selectLayer(null);
				} else {
					console.error("Invalid design data structure:", designData);
					alert('Invalid design file format. Check console for details.');
				}
			} catch (error) {
				console.error("Error processing loaded design:", error);
				alert('Error applying the design data.');
			}
		};
		const handleError = (error, statusText = "") => {
			console.error("Error loading design:", statusText, error);
			alert(`Error reading or fetching the design file: ${statusText}`);
		};
		if (typeof source === 'object' && source !== null && !(source instanceof File)) {
			console.log("Loading design from pre-parsed object.");
			handleLoad(source);
		} else if (source instanceof File) {
			const reader = new FileReader();
			reader.onload = (e) => {
				try {
					const designData = JSON.parse(e.target.result);
					handleLoad(designData);
				} catch (parseError) {
					handleError(parseError, "JSON Parsing Error");
				}
			};
			reader.onerror = () => handleError(reader.error, "File Reading Error");
			reader.readAsText(source);
		} else if (typeof source === 'string') {
			$.getJSON(source)
				.done((data) => handleLoad(data))
				.fail((jqXHR, textStatus, errorThrown) => handleError(errorThrown, `${textStatus} (${jqXHR.status})`));
		} else {
			alert('Invalid source type for loading design.');
		}
	}
	
	destroy() {
		this.$canvasArea.off('mousedown mousemove mouseup mouseleave');
		$(document).off('.canvasManagerPan');
		$('#zoom-in').off('click');
		$('#zoom-out').off('click');
		$('#zoom-options-menu').off('click');
		
		this._removeExistingVisualGuides(); // Remove all visual guides
		
		this.$canvasArea = null;
		this.$canvasWrapper = null;
		this.$canvas = null;
		this.layerManager = null;
		this.historyManager = null;
		this.onZoomChange = null;
		console.log("CanvasManager destroyed.");
	}
}
