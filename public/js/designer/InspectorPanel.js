// free-cover-designer/js/InspectorPanel.js
class InspectorPanel {
	constructor(options) {
		this.$panel = $('#inspectorPanel');
		this.$closeBtn = this.$panel.find('.close-inspector-btn');
		this.$cloneLayerBtn = this.$panel.find('#cloneLayerBtn');
		this.layerManager = options.layerManager;
		this.historyManager = options.historyManager;
		this.canvasManager = options.canvasManager;
		this.currentLayer = null;
		this.googleFontsList = options.googleFontsList || [];
		this.filterUpdateTimeout = null;
		this.filterUpdateDelay = 150; // This was for old filter handling, might not be used by bindRangeAndNumber
		this.textareaChangeTimeout = null; // For text content
		this.init();
	}
	
	init() {
		this.bindEvents();
		this._initFontPicker();
	}
	
	_initFontPicker() {
		// ... (font picker init code remains the same)
		try {
			const $fontInput = this.$panel.find('#inspector-font-family');
			if ($fontInput.length && $.fn.fontpicker) {
				$fontInput.fontpicker({
					lang: 'en',
					variants: false,
					lazyLoad: true,
					showClear: false,
					nrRecents: 3,
					// googleFonts: this.googleFontsList, // From PHP
					// localFonts: { ... },
					onSelect: (font) => {
						this.layerManager._ensureGoogleFontLoaded(font.fontFamily);
						this._updateLayer('fontFamily', font.fontFamily, true);
					}
				});
			} else if (!$fontInput.length) {
				console.warn("InspectorPanel: Font family input not found.");
			} else if (!$.fn.fontpicker) {
				console.warn("InspectorPanel: jsFontPicker plugin not loaded.");
			}
		} catch (e) {
			console.error("Error initializing font picker:", e);
		}
	}
	
	bindEvents() {
		const self = this;
		let historySaveScheduled = false;
		const scheduleHistorySave = () => {
			if (!historySaveScheduled) {
				historySaveScheduled = true;
				setTimeout(() => {
					self.historyManager.saveState();
					historySaveScheduled = false;
				}, 300); // Debounce time for non-critical updates
			}
		};
		
		this.$closeBtn.on('click', () => {
			this.hide();
		});
		
		this.$cloneLayerBtn.on('click', () => {
			if (!this.currentLayer || !this.layerManager || !this.historyManager) {
				console.warn("Cannot clone layer: Missing current layer or managers.");
				return;
			}
			const originalLayer = this.currentLayer;
			const clonedProps = JSON.parse(JSON.stringify(originalLayer));
			delete clonedProps.id;
			clonedProps.name = (originalLayer.name || `Layer ${originalLayer.id}`) + " (Copy)";
			clonedProps.x = (parseFloat(clonedProps.x) || 0) + 50;
			clonedProps.y = (parseFloat(clonedProps.y) || 0) + 50;
			clonedProps.zIndex = (parseInt(clonedProps.zIndex) || 0) + 1;
			clonedProps.locked = false;
			const newLayer = this.layerManager.addLayer(clonedProps.type, clonedProps);
			if (newLayer) {
				this.layerManager.selectLayer(newLayer.id);
				this.historyManager.saveState();
			} else {
				console.error("Failed to add cloned layer.");
				alert("Could not clone the layer. Please try again.");
			}
		});
		
		const updateLayer = (prop, value, saveNow = false, saveDebounced = true) => {
			if (this.currentLayer) {
				let updateData = {};
				if (prop.startsWith('filters.')) {
					const filterKey = prop.split('.')[1];
					const currentFilters = this.currentLayer.filters || {...this.layerManager.defaultFilters};
					updateData = {filters: {...currentFilters, [filterKey]: value}};
				} else {
					updateData = {[prop]: value};
				}
				this.layerManager.updateLayerData(this.currentLayer.id, updateData);
				if (saveNow) {
					clearTimeout(this.textareaChangeTimeout); // Clear any pending saves
					historySaveScheduled = false; // Reset debounce flag
					this.historyManager.saveState();
				} else if (saveDebounced) {
					scheduleHistorySave();
				}
			}
		};
		
		this.$panel.find('#inspector-layer-definition').on('change', (e) => {
			const definition = $(e.target).val();
			updateLayer('definition', definition, true);
		});
		
		// Helper for direct number inputs (X, Y, Width, Height)
		const bindDirectNumberInput = (inputId, layerProp, unit = 'px', allowAuto = false) => {
			const $input = this.$panel.find(`#${inputId}`);
			if (!$input.length) {
				console.warn(`InspectorPanel: Input not found #${inputId}`);
				return;
			}
			
			$input.on('input', (e) => {
				if (this.currentLayer) {
					let value = $(e.target).val();
					let processedValue;
					
					if (allowAuto && value.toLowerCase() === 'auto') {
						processedValue = 'auto';
					} else {
						processedValue = parseFloat(value);
						if (isNaN(processedValue)) {
							// If NaN during input, don't update layer yet, let 'change' handle it or user correct it
							return;
						}
					}
					updateLayer(layerProp, processedValue, false, true); // Debounced save
				}
			});
			
			$input.on('change', (e) => { // Handles blur or Enter
				if (this.currentLayer) {
					let value = $(e.target).val();
					const originalValue = this.currentLayer[layerProp];
					let processedValue;
					
					if (allowAuto && value.toLowerCase() === 'auto') {
						processedValue = 'auto';
					} else {
						processedValue = parseFloat(value);
						if (isNaN(processedValue)) {
							$(e.target).val(originalValue === 'auto' ? 'auto' : (parseFloat(originalValue) || 0));
							return;
						}
						const min = parseFloat($input.attr('min'));
						const max = parseFloat($input.attr('max'));
						if (!isNaN(min) && processedValue < min) processedValue = min;
						if (!isNaN(max) && processedValue > max) processedValue = max;
						$(e.target).val(processedValue);
					}
					
					if (processedValue !== originalValue) {
						updateLayer(layerProp, processedValue, true);
					} else if ($(e.target).val() !== (originalValue === 'auto' ? 'auto' : String(parseFloat(originalValue) || 0))) {
						$(e.target).val(originalValue === 'auto' ? 'auto' : (parseFloat(originalValue) || 0));
					}
				}
			});
		};
		
		// Bind X, Y, Width, Height inputs
		bindDirectNumberInput('inspector-pos-x', 'x');
		bindDirectNumberInput('inspector-pos-y', 'y');
		bindDirectNumberInput('inspector-size-width', 'width', 'px', true); // Allow 'auto' for width
		bindDirectNumberInput('inspector-size-height', 'height', 'px', true); // Allow 'auto' for height
		
		
		const bindColorInputGroup = (groupId, layerPropPrefix) => {
			const $picker = $(`#inspector-${groupId}-color`);
			const $hex = $(`#inspector-${groupId}-hex`);
			// The opacity sliders for color groups are visually hidden in the HTML.
			// If they were to be made functional like the main opacity, they'd need similar logic.
			// For now, this function primarily handles color (hex/picker) and assumes opacity is part of the RGBA string
			// or handled by a separate visible opacity slider (like backgroundOpacity).
			
			const updateFromPickerOrHex = (sourceValue, isPicker = false) => {
				if (!this.currentLayer) return;
				let tiny = tinycolor(sourceValue);
				if (!tiny.isValid()) return;
				
				// Preserve alpha from the layer's current value for this property,
				// as the color picker itself doesn't manage alpha for these groups.
				let currentAlpha = 1;
				const propValue = this.currentLayer[layerPropPrefix];
				if (propValue && typeof propValue === 'string') {
					const currentTiny = tinycolor(propValue);
					if (currentTiny.isValid()) {
						currentAlpha = currentTiny.getAlpha();
					}
				}
				// For background, opacity is handled by a separate slider
				if (layerPropPrefix === 'backgroundColor') {
					currentAlpha = this.currentLayer.backgroundOpacity ?? 1;
				}
				
				
				const newRgba = tiny.setAlpha(currentAlpha).toRgbString();
				const hexString = tiny.toHexString().toUpperCase().substring(1);
				
				if (isPicker) {
					$hex.val(hexString);
				} else {
					$picker.val(tiny.toHexString());
				}
				
				updateLayer(layerPropPrefix, newRgba, false, false); // Update color, don't save history yet
			};
			
			$picker.on('input', () => updateFromPickerOrHex($picker.val(), true));
			$hex.on('input', () => updateFromPickerOrHex('#' + $hex.val(), false));
			
			// Save history on final change (e.g., when picker closes or input blurs)
			$picker.on('change', () => this.historyManager.saveState());
			$hex.on('change', () => this.historyManager.saveState());
		};
		
		bindColorInputGroup('fill', 'fill');
		bindColorInputGroup('border', 'stroke');
		bindColorInputGroup('shading', 'shadowColor');
		bindColorInputGroup('background', 'backgroundColor');
		
		const bindRangeAndNumber = (
			sliderId, inputId, unitDisplaySpanId,
			layerProp, unit = '',
			saveDebounced = true, isFilter = false, skipUpdateLayer = false,
			inputMultiplier = 1,
			inputStepOverride = null,
			decimalPlaces = null // Explicit decimal places for input.val() formatting
		) => {
			const $slider = $(`#${sliderId}`);
			const $input = $(`#${inputId}`);
			const $unitDisplay = $(`#${unitDisplaySpanId}`);
			
			if (!$slider.length || !$input.length) {
				// console.warn(`Slider or Input not found for: ${sliderId}, ${inputId}`);
				return;
			}
			
			const sliderMin = parseFloat($slider.attr('min'));
			const sliderMax = parseFloat($slider.attr('max'));
			const sliderStep = parseFloat($slider.attr('step'));
			
			const inputMin = sliderMin * inputMultiplier;
			const inputMax = sliderMax * inputMultiplier;
			const inputActualStep = inputStepOverride !== null ? inputStepOverride : (sliderStep * inputMultiplier);
			
			// Determine decimal places for formatting input value
			let decPlaces;
			if (decimalPlaces !== null) {
				decPlaces = decimalPlaces;
			} else if (inputActualStep > 0 && inputActualStep < 1) {
				decPlaces = inputActualStep.toString().split('.')[1]?.length || 2;
			} else {
				decPlaces = 0; // Integer
			}
			
			// Set attributes for the number input
			$input.attr({min: inputMin, max: inputMax, step: inputActualStep});
			
			const formatInputValue = (val) => {
				return decPlaces > 0 ? parseFloat(val).toFixed(decPlaces) : Math.round(val).toString();
			};
			
			const updateFromSlider = () => {
				const sliderVal = parseFloat($slider.val());
				if (isNaN(sliderVal)) return;
				$input.val(formatInputValue(sliderVal * inputMultiplier));
				if (!skipUpdateLayer) {
					updateLayer(layerProp, sliderVal, false, saveDebounced);
				}
			};
			
			const updateFromInput = (isFinalChange = false) => {
				let rawInputVal = $input.val();
				let inputValNum = parseFloat(rawInputVal);
				console.debug(`Input value: ${rawInputVal}, parsed as: ${inputValNum}`);
				
				if (isNaN(inputValNum)) {
					if (isFinalChange) { // On blur/enter, if invalid, reset from slider
						const currentSliderVal = parseFloat($slider.val());
						$input.val(formatInputValue(currentSliderVal * inputMultiplier));
						// updateLayer already called by slider's change if it was the source
					}
					return;
				}
				
				// Clamp and step-align input value
				let clampedVal = Math.max(inputMin, Math.min(inputMax, inputValNum));
				if (inputActualStep > 0) {
					clampedVal = Math.round(clampedVal / inputActualStep) * inputActualStep;
				}
				
				const formattedClampedVal = formatInputValue(clampedVal);
				
				// Only update input's display value if it differs, or on final change to ensure correctness
				if (isFinalChange || $input.val() !== formattedClampedVal) {
					if (document.activeElement !== $input[0] || isFinalChange) { // Avoid reformatting if user is actively typing and it's valid so far
						$input.val(formattedClampedVal);
					}
				}
				
				const sliderVal = clampedVal / inputMultiplier;
				if (parseFloat($slider.val()) !== sliderVal) {
					$slider.val(sliderVal);
				}
				
				if (!skipUpdateLayer) {
					updateLayer(layerProp, sliderVal, isFinalChange, !isFinalChange && saveDebounced);
				}
			};
			
			$slider.off('input.rangeHelper change.rangeHelper'); // Clear previous
			$input.off('input.rangeHelper change.rangeHelper blur.rangeHelper'); // Clear previous
			
			$slider.on('input.rangeHelper', updateFromSlider);
			$slider.on('change.rangeHelper', () => { // Final change for slider
				const finalSliderVal = parseFloat($slider.val());
				if (!isNaN(finalSliderVal)) {
					$input.val(formatInputValue(finalSliderVal * inputMultiplier));
					if (!skipUpdateLayer) {
						updateLayer(layerProp, finalSliderVal, true);
					}
				}
			});
			
			$input.on('input.rangeHelper', () => updateFromInput(false));
			$input.on('change.rangeHelper', () => updateFromInput(true)); // Handles blur/enter
			
			if ($unitDisplay.length && unit) {
				$unitDisplay.text(unit);
			}
		};
		
		// Layer Opacity (Main)
		bindRangeAndNumber('inspector-opacity-slider', 'inspector-opacity-input', 'inspector-opacity-unit', 'opacity', '%', true, false, false, 100, 1, 0);
		// Rotation & Scale
		bindRangeAndNumber('inspector-rotation-slider', 'inspector-rotation-input', 'inspector-rotation-unit', 'rotation', '°');
		bindRangeAndNumber('inspector-scale-slider', 'inspector-scale-input', 'inspector-scale-unit', 'scale', '%');
		// Border Weight
		bindRangeAndNumber('inspector-border-weight-slider', 'inspector-border-weight-input', 'inspector-border-weight-unit', 'strokeWidth', '', true, false, false, 1, null, 1);
		// Text Size (already number input, no slider)
		this.$panel.find('#inspector-font-size').on('input', (e) => {
			const val = parseInt($(e.target).val());
			if (!isNaN(val) && val > 0) {
				updateLayer('fontSize', val, false, true);
			}
		}).on('change', () => this.historyManager.saveState());
		// Text Spacing & Line Height (already number inputs)
		this.$panel.find('#inspector-letter-spacing').on('input', (e) => updateLayer('letterSpacing', parseFloat($(e.target).val()) || 0, false, true))
			.on('change', () => this.historyManager.saveState());
		this.$panel.find('#inspector-line-height').on('input', (e) => updateLayer('lineHeight', parseFloat($(e.target).val()) || 1.3, false, true))
			.on('change', () => this.historyManager.saveState());
		
		// Text Style Buttons
		this.$panel.find('#inspector-bold-btn').on('click', () => this.toggleStyle('fontWeight', 'bold', 'normal'));
		this.$panel.find('#inspector-italic-btn').on('click', () => this.toggleStyle('fontStyle', 'italic', 'normal'));
		this.$panel.find('#inspector-underline-btn').on('click', () => this.toggleStyle('textDecoration', 'underline', 'none'));
		// Text Alignment (Horizontal)
		this.$panel.find('#inspector-text-align button').on('click', (e) => {
			const align = $(e.currentTarget).data('align');
			updateLayer('align', align, true);
			this.$panel.find('#inspector-text-align button').removeClass('active');
			$(e.currentTarget).addClass('active');
		});
		// Text Alignment (Vertical)
		this.$panel.find('#inspector-text-v-align button').on('click', (e) => {
			const vAlign = $(e.currentTarget).data('alignV');
			updateLayer('vAlign', vAlign, true);
			this.$panel.find('#inspector-text-v-align button').removeClass('active');
			$(e.currentTarget).addClass('active');
		});
		
		// Text Padding
		bindRangeAndNumber('inspector-text-padding-slider', 'inspector-text-padding-input', 'inspector-text-padding-unit', 'textPadding', '');
		
		// Shading (Shadow)
		this.$panel.find('#inspector-shading-enabled').on('change', (e) => {
			const isChecked = $(e.target).prop('checked');
			updateLayer('shadowEnabled', isChecked, true);
			$(e.target).closest('.inspector-section').find('.section-content').toggle(isChecked);
		});
		bindRangeAndNumber('inspector-shading-blur-slider', 'inspector-shading-blur-input', 'inspector-shading-blur-unit', 'shadowBlur', '');
		
		// Special handling for Shading Offset & Angle as they derive shadowOffsetX/Y
		const updateShadowOffsetFromSliders = () => {
			if (!this.currentLayer) return;
			const offset = parseFloat($('#inspector-shading-offset-slider').val());
			const angleRad = parseFloat($('#inspector-shading-angle-slider').val()) * Math.PI / 180;
			const offsetX = Math.round(offset * Math.cos(angleRad));
			const offsetY = Math.round(offset * Math.sin(angleRad));
			// Update internal properties that sliders control
			updateLayer('shadowOffsetInternal', offset, false, false); // No history save yet
			updateLayer('shadowAngleInternal', parseFloat($('#inspector-shading-angle-slider').val()), false, false); // No history save yet
			// Update actual layer properties for X and Y
			updateLayer('shadowOffsetX', offsetX, false, false); // No history save yet
			updateLayer('shadowOffsetY', offsetY, false, true); // Debounce save on the last one
		};
		
		bindRangeAndNumber('inspector-shading-offset-slider', 'inspector-shading-offset-input', 'inspector-shading-offset-unit', 'shadowOffsetInternal', '', false, false, true); // skipUpdateLayer = true
		bindRangeAndNumber('inspector-shading-angle-slider', 'inspector-shading-angle-input', 'inspector-shading-angle-unit', 'shadowAngleInternal', '°', false, false, true); // skipUpdateLayer = true
		
		$('#inspector-shading-offset-slider, #inspector-shading-angle-slider').on('input', updateShadowOffsetFromSliders);
		$('#inspector-shading-offset-input, #inspector-shading-angle-input').on('input change', updateShadowOffsetFromSliders); // Also trigger on input's change
		
		$('#inspector-shading-offset-slider, #inspector-shading-angle-slider, #inspector-shading-offset-input, #inspector-shading-angle-input')
			.on('change', () => this.historyManager.saveState()); // Save history on final change of any of these
		
		
		// Background (Text Only)
		this.$panel.find('#inspector-background-enabled').on('change', (e) => {
			const isChecked = $(e.target).prop('checked');
			updateLayer('backgroundEnabled', isChecked, true);
			$(e.target).closest('.inspector-section').find('.section-content').toggle(isChecked);
		});
		// Background Opacity
		bindRangeAndNumber('inspector-background-opacity-slider', 'inspector-background-opacity-input', 'inspector-background-opacity-unit', 'backgroundOpacity', '%', true, false, false, 100, 1, 0);
		// Background Corner Radius
		bindRangeAndNumber('inspector-background-radius-slider', 'inspector-background-radius-input', 'inspector-background-radius-unit', 'backgroundCornerRadius', '', true, false, false, 1, null, 1);
		
		// Text Content Area
		const $textContentArea = this.$panel.find('#inspector-text-content');
		$textContentArea.on('input', () => {
			if (this.currentLayer && this.currentLayer.type === 'text') {
				const newContent = $textContentArea.val();
				this.layerManager.updateLayerData(this.currentLayer.id, {content: newContent});
				clearTimeout(this.textareaChangeTimeout);
				this.textareaChangeTimeout = setTimeout(() => {
					this.historyManager.saveState();
				}, 750);
			}
		});
		
		// Layer Alignment Buttons (Canvas alignment)
		this.$panel.find('#inspector-alignment button[data-align-layer]').on('click', (e) => {
			const currentLayerId = this.currentLayer?.id;
			const alignType = $(e.currentTarget).data('alignLayer');
			if (!currentLayerId || !this.layerManager || !this.canvasManager) return;
			const layer = this.layerManager.getLayerById(currentLayerId);
			if (!layer) return;
			
			const canvasWidth = this.canvasManager.currentCanvasWidth;
			const canvasHeight = this.canvasManager.currentCanvasHeight;
			const $element = $('#' + layer.id);
			if (!$element.length) return;
			
			const zoom = this.canvasManager.currentZoom;
			let layerWidth = layer.width;
			let layerHeight = layer.height;
			
			// Use rendered dimensions if 'auto' or not a number, considering scale
			const scaleFactor = (layer.scale || 100) / 100;
			let renderedWidth = $element.outerWidth() / zoom; // This is already scaled if transform is applied
			let renderedHeight = $element.outerHeight() / zoom;
			
			// For alignment, we need the unscaled bounding box if we are setting x,y
			// Or, if we use Moveable's logic, it handles transforms.
			// Simpler: use the layer's stored width/height, and if auto, use rendered.
			// The issue is that x,y are top-left of the transformed bounding box.
			// For simplicity, let's use the layer's stored width/height, scaled.
			// If width/height are 'auto', this becomes tricky.
			// Let's assume for now that width/height are numeric for alignment purposes or use rendered.
			
			if (layerWidth === 'auto' || typeof layerWidth !== 'number') {
				layerWidth = renderedWidth / scaleFactor; // Approximate unscaled width
			}
			if (layerHeight === 'auto' || typeof layerHeight !== 'number') {
				layerHeight = renderedHeight / scaleFactor; // Approximate unscaled height
			}
			
			layerWidth *= scaleFactor; // Effective width for alignment
			layerHeight *= scaleFactor; // Effective height for alignment
			
			
			if (isNaN(layerWidth) || isNaN(layerHeight) || layerWidth <= 0 || layerHeight <= 0) {
				console.error("Alignment: Invalid layer dimensions for calculation.", layer, {layerWidth, layerHeight});
				return;
			}
			
			let newX = parseFloat(layer.x);
			let newY = parseFloat(layer.y);
			
			switch (alignType) {
				case 'left':
					newX = 0;
					break;
				case 'h-center':
					newX = (canvasWidth / 2) - (layerWidth / 2);
					break;
				case 'right':
					newX = canvasWidth - layerWidth;
					break;
				case 'top':
					newY = 0;
					break;
				case 'v-center':
					newY = (canvasHeight / 2) - (layerHeight / 2);
					break;
				case 'bottom':
					newY = canvasHeight - layerHeight;
					break;
				default:
					return;
			}
			newX = Math.round(newX);
			newY = Math.round(newY);
			
			if (newX !== Math.round(parseFloat(layer.x)) || newY !== Math.round(parseFloat(layer.y))) {
				this.layerManager.updateLayerData(layer.id, {x: newX, y: newY});
				this.historyManager.saveState();
				const finalUpdatedLayer = this.layerManager.getLayerById(layer.id);
				if (finalUpdatedLayer) this.populate(finalUpdatedLayer);
			}
		});
		
		// Image Filters
		bindRangeAndNumber('inspector-filter-brightness-slider', 'inspector-filter-brightness-input', 'inspector-filter-brightness-unit', 'filters.brightness', '%', true, true, false, 100, 1, 0); // Assuming % unit, 100 multiplier
		bindRangeAndNumber('inspector-filter-contrast-slider', 'inspector-filter-contrast-input', 'inspector-filter-contrast-unit', 'filters.contrast', '%', true, true, false, 100, 1, 0);   // Assuming % unit, 100 multiplier
		bindRangeAndNumber('inspector-filter-saturation-slider', 'inspector-filter-saturation-input', 'inspector-filter-saturation-unit', 'filters.saturation', '%', true, true, false, 100, 1, 0); // Assuming % unit, 100 multiplier
		bindRangeAndNumber('inspector-filter-grayscale-slider', 'inspector-filter-grayscale-input', 'inspector-filter-grayscale-unit', 'filters.grayscale', '%', true, true, false, 100, 1, 0);    // Assuming % unit, 100 multiplier
		bindRangeAndNumber('inspector-filter-sepia-slider', 'inspector-filter-sepia-input', 'inspector-filter-sepia-unit', 'filters.sepia', '%', true, true, false, 100, 1, 0);          // Assuming % unit, 100 multiplier
		bindRangeAndNumber('inspector-filter-hue-rotate-slider', 'inspector-filter-hue-rotate-input', 'inspector-filter-hue-rotate-unit', 'filters.hueRotate', '°', true, true, false, 1, 1, 0);
		bindRangeAndNumber('inspector-filter-blur-slider', 'inspector-filter-blur-input', 'inspector-filter-blur-unit', 'filters.blur', 'px', true, true, false, 1, null, 1);
		
		// Blend Mode
		this.$panel.find('#inspector-blend-mode').on('change', (e) => {
			const blendMode = $(e.target).val();
			updateLayer('blendMode', blendMode, true);
		});
	} // End bindEvents
	
	toggleStyle(property, activeValue, inactiveValue) {
		if (this.currentLayer && this.currentLayer.type === 'text') {
			const currentValue = this.currentLayer[property];
			const newValue = (currentValue === activeValue) ? inactiveValue : activeValue;
			this._updateLayer(property, newValue, true);
		}
	}
	
	_updateLayer(property, value, saveNow = false) { // This is an internal helper, main updates go through `updateLayer` in `bindEvents`
		if (this.currentLayer) {
			const previousValue = this.currentLayer[property];
			this.layerManager.updateLayerData(this.currentLayer.id, {[property]: value});
			if (saveNow) {
				this.historyManager.saveState();
			}
			// Re-populate if value actually changed to reflect in UI,
			// but only if not part of a complex update (like color pickers that manage their own UI)
			const updatedLayer = this.layerManager.getLayerById(this.currentLayer.id);
			if (updatedLayer && updatedLayer[property] !== previousValue) {
				// Avoid re-populating for properties handled by bindRangeAndNumber or color pickers,
				// as they update their own UI elements.
				// This _updateLayer is more for simple toggles or direct property sets.
				if (!property.includes("Internal")) { // Heuristic: internal props are for sliders
					this.populate(updatedLayer);
				}
			}
		}
	}
	
	show(layerData) {
		if (!layerData) {
			this.currentLayer = null;
			this.populate(null);
			this.$panel.addClass('open');
			this.$panel.find('#inspector-layer-info-actions, #cloneLayerBtn, #inspector-alignment, #inspector-layer, #inspector-text, #inspector-text-padding-section, #inspector-text-shading, #inspector-text-background, #inspector-color, #inspector-border, #inspector-image-filters, #inspector-image-blend-mode, #inspector-definition').hide();
			$('#inspector-layer-name').text('No Layer Selected').attr('title', 'No Layer Selected');
			return;
		}
		this.currentLayer = layerData;
		this.populate(layerData);
		this.$panel.addClass('open');
		this.$panel.find('#inspector-layer-info-actions').show();
		this.$cloneLayerBtn.show(); // Show clone button when a layer is selected
	}
	
	_populateDirectNumberInput(inputId, value, allowAuto = false) {
		const $input = this.$panel.find(`#${inputId}`);
		if ($input.length) {
			if (allowAuto && typeof value === 'string' && value.toLowerCase() === 'auto') {
				$input.val('auto');
			} else {
				const numValue = parseFloat(value);
				// Display the number, or empty string if it's not a valid number (e.g. if 'auto' was passed but allowAuto=false)
				// For 'auto' capable fields, this path is taken if value is numeric.
				$input.val(isNaN(numValue) ? '' : numValue);
			}
		}
	}
	
	hide() {
		if (this.$panel.hasClass('open')) {
			this.currentLayer = null;
			this.$panel.removeClass('open');
		}
	}
	
	_populateColorInputGroup(groupId, colorValue, opacityValue = 1) { // opacityValue is fallback if not in colorString
		const $picker = $(`#inspector-${groupId}-color`);
		const $hex = $(`#inspector-${groupId}-hex`);
		// Opacity sliders for color groups are visually hidden, their population is less critical unless made visible.
		// This function focuses on the color part.
		
		let tiny = tinycolor(colorValue || '#000000');
		if (!tiny.isValid()) tiny = tinycolor('#000000');
		
		let alphaToUse = opacityValue; // Default passed opacity
		if (colorValue && (typeof colorValue === 'string')) { // If color string has alpha, it takes precedence
			const parsedColor = tinycolor(colorValue);
			if (parsedColor.isValid()) alphaToUse = parsedColor.getAlpha();
		}
		
		// For background, its opacity is handled by a dedicated visible slider
		if (groupId === 'background' && this.currentLayer) {
			alphaToUse = this.currentLayer.backgroundOpacity ?? 1;
		}
		
		$picker.val(tiny.toHexString()); // Picker gets hex without alpha
		$hex.val(tiny.toHexString().substring(1).toUpperCase());
		
		// If there were visible opacity sliders for these groups, they'd be populated here:
		// const $opacitySlider = $(`#inspector-${groupId}-opacity-slider`);
		// const $opacityInput = $(`#inspector-${groupId}-opacity-input`);
		// if ($opacitySlider.length) $opacitySlider.val(alphaToUse);
		// if ($opacityInput.length) $opacityInput.val(Math.round(alphaToUse * 100));
	}
	
	_populateRangeAndNumber(
		sliderId, inputId, unitDisplaySpanId,
		value, fallback = 0, unit = '',
		inputMultiplier = 1,
		inputStepOverride = null,
		decimalPlaces = null // Explicit decimal places for input.val() formatting
	) {
		const numValue = parseFloat(value);
		const finalValue = isNaN(numValue) ? fallback : numValue; // This is the layer's actual value
		
		const $slider = $(`#${sliderId}`);
		const $input = $(`#${inputId}`);
		const $unitDisplay = $(`#${unitDisplaySpanId}`);
		
		if ($slider.length) $slider.val(finalValue);
		
		if ($input.length) {
			const displayVal = finalValue * inputMultiplier;
			
			let decPlaces;
			if (decimalPlaces !== null) {
				decPlaces = decimalPlaces;
			} else {
				const inputActualStep = inputStepOverride !== null ? inputStepOverride : ((parseFloat($slider.attr('step')) || 1) * inputMultiplier);
				if (inputActualStep > 0 && inputActualStep < 1) {
					decPlaces = inputActualStep.toString().split('.')[1]?.length || 2;
				} else {
					decPlaces = 0;
				}
			}
			$input.val(decPlaces > 0 ? parseFloat(displayVal).toFixed(decPlaces) : Math.round(displayVal).toString());
		}
		
		if ($unitDisplay.length && unit) {
			$unitDisplay.text(unit);
		}
	}
	
	
	populate(layerData) {
		const $layerNameDisplay = $('#inspector-layer-name');
		if (!layerData) {
			// this.hide(); // show() already handles the "No Layer Selected" case
			return;
		}
		this.currentLayer = layerData;
		const layerName = layerData.name || `Layer ${layerData.id}`;
		$layerNameDisplay.text(layerName).attr('title', layerName);
		
		const isText = layerData.type === 'text';
		const isImage = layerData.type === 'image';
		
		this.$panel.find('#inspector-alignment').show();
		this.$panel.find('#inspector-layer').show();
		this.$panel.find('#inspector-definition').show();
		this.$panel.find('#inspector-text').toggle(isText);
		this.$panel.find('#inspector-text-padding-section').toggle(isText);
		this.$panel.find('#inspector-text-shading').toggle(isText);
		this.$panel.find('#inspector-text-background').toggle(isText);
		this.$panel.find('#inspector-color').toggle(isText);
		this.$panel.find('#inspector-border').toggle(isText);
		this.$panel.find('#inspector-image-filters').toggle(isImage);
		this.$panel.find('#inspector-image-blend-mode').toggle(isImage);
		
		// Populate X, Y, Width, Height
		this._populateDirectNumberInput('inspector-pos-x', layerData.x);
		this._populateDirectNumberInput('inspector-pos-y', layerData.y);
		this._populateDirectNumberInput('inspector-size-width', layerData.width, true); // allowAuto = true
		this._populateDirectNumberInput('inspector-size-height', layerData.height, true); // allowAuto = true
		
		// Common Controls
		this._populateRangeAndNumber('inspector-opacity-slider', 'inspector-opacity-input', 'inspector-opacity-unit', layerData.opacity ?? 1, 1, '%', 100, 1, 0);
		this._populateRangeAndNumber('inspector-rotation-slider', 'inspector-rotation-input', 'inspector-rotation-unit', layerData.rotation ?? 0, 0, '°');
		this._populateRangeAndNumber('inspector-scale-slider', 'inspector-scale-input', 'inspector-scale-unit', layerData.scale ?? 100, 100, '%');
		$('#inspector-layer-definition').val(layerData.definition || 'general');
		
		if (isText) {
			$('#inspector-text-content').val(layerData.content || '');
			this._populateColorInputGroup('fill', layerData.fill);
			
			const strokeWidth = parseFloat(layerData.strokeWidth) || 0;
			this._populateColorInputGroup('border', layerData.stroke);
			this._populateRangeAndNumber('inspector-border-weight-slider', 'inspector-border-weight-input', 'inspector-border-weight-unit', strokeWidth, 0, '', 1, null, 1);
			
			// Font
			const font = layerData.fontFamily || 'Arial';
			$('#inspector-font-family').val(font).trigger('change');
			console.log("Font family set to:", font);
			try {
				$('#inspector-font-family').data('fontpicker')?.set(font);
			} catch (e) {
				console.warn("Couldn't update fontpicker selection visually", e)
			}
			
			$('#inspector-font-size').val(layerData.fontSize || 24);
			$('#inspector-letter-spacing').val(layerData.letterSpacing || 0);
			$('#inspector-line-height').val(layerData.lineHeight || 1.3);
			
			$('#inspector-bold-btn').toggleClass('active', layerData.fontWeight === 'bold');
			$('#inspector-italic-btn').toggleClass('active', layerData.fontStyle === 'italic');
			$('#inspector-underline-btn').toggleClass('active', layerData.textDecoration === 'underline');
			$('#inspector-text-align button').removeClass('active');
			$(`#inspector-text-align button[data-align="${layerData.align || 'left'}"]`).addClass('active');
			const vAlign = layerData.vAlign || 'center';
			$('#inspector-text-v-align button').removeClass('active');
			$(`#inspector-text-v-align button[data-align-v="${vAlign}"]`).addClass('active');
			
			this._populateRangeAndNumber('inspector-text-padding-slider', 'inspector-text-padding-input', 'inspector-text-padding-unit', layerData.textPadding || 0, 0, '');
			
			const shadowEnabled = !!layerData.shadowEnabled;
			$('#inspector-shading-enabled').prop('checked', shadowEnabled);
			this.$panel.find('#inspector-text-shading .section-content').toggle(shadowEnabled);
			if (shadowEnabled) {
				this._populateColorInputGroup('shading', layerData.shadowColor);
				this._populateRangeAndNumber('inspector-shading-blur-slider', 'inspector-shading-blur-input', 'inspector-shading-blur-unit', layerData.shadowBlur || 0);
				
				const shadowX = parseFloat(layerData.shadowOffsetX) || 0;
				const shadowY = parseFloat(layerData.shadowOffsetY) || 0;
				const shadowOffsetInternal = Math.sqrt(shadowX * shadowX + shadowY * shadowY);
				let shadowAngleInternal = Math.atan2(shadowY, shadowX) * 180 / Math.PI;
				shadowAngleInternal = Math.round(shadowAngleInternal);
				
				this._populateRangeAndNumber('inspector-shading-offset-slider', 'inspector-shading-offset-input', 'inspector-shading-offset-unit', shadowOffsetInternal, 5);
				this._populateRangeAndNumber('inspector-shading-angle-slider', 'inspector-shading-angle-input', 'inspector-shading-angle-unit', shadowAngleInternal, 45, '°');
			}
			
			const backgroundEnabled = !!layerData.backgroundEnabled;
			$('#inspector-background-enabled').prop('checked', backgroundEnabled);
			this.$panel.find('#inspector-text-background .section-content').toggle(backgroundEnabled);
			if (backgroundEnabled) {
				this._populateColorInputGroup('background', layerData.backgroundColor, layerData.backgroundOpacity); // Passes opacity for initial RGBA
				this._populateRangeAndNumber('inspector-background-opacity-slider', 'inspector-background-opacity-input', 'inspector-background-opacity-unit', layerData.backgroundOpacity ?? 1, 1, '%', 100, 1, 0);
				this._populateRangeAndNumber('inspector-background-radius-slider', 'inspector-background-radius-input', 'inspector-background-radius-unit', layerData.backgroundCornerRadius || 0, 0, '', 1, null, 1);
			}
		} else {
			$('#inspector-text-content').val('');
		}
		
		if (isImage) {
			const filters = layerData.filters || this.layerManager.defaultFilters;
			this._populateRangeAndNumber('inspector-filter-brightness-slider', 'inspector-filter-brightness-input', 'inspector-filter-brightness-unit', filters.brightness, 100);
			this._populateRangeAndNumber('inspector-filter-contrast-slider', 'inspector-filter-contrast-input', 'inspector-filter-contrast-unit', filters.contrast, 100);
			this._populateRangeAndNumber('inspector-filter-saturation-slider', 'inspector-filter-saturation-input', 'inspector-filter-saturation-unit', filters.saturation, 100);
			this._populateRangeAndNumber('inspector-filter-grayscale-slider', 'inspector-filter-grayscale-input', 'inspector-filter-grayscale-unit', filters.grayscale, 0);
			this._populateRangeAndNumber('inspector-filter-sepia-slider', 'inspector-filter-sepia-input', 'inspector-filter-sepia-unit', filters.sepia, 0);
			this._populateRangeAndNumber('inspector-filter-hue-rotate-slider', 'inspector-filter-hue-rotate-input', 'inspector-filter-hue-rotate-unit', filters.hueRotate, 0, '°');
			this._populateRangeAndNumber('inspector-filter-blur-slider', 'inspector-filter-blur-input', 'inspector-filter-blur-unit', filters.blur, 0, '', 1, null, 1);
			$('#inspector-blend-mode').val(layerData.blendMode || 'normal');
		}
	} // End populate
} // End class InspectorPanel
