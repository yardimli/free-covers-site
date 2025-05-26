// public/js/designer/CanvasSizeSetupPage.js
class CanvasSizeSetupPage {
	constructor() {
		// DOM References (use 'Setup' suffix for IDs to avoid conflicts if reusing CSS)
		this.$presetError = $('#presetError');
		this.$unitRadios = $('input[name="canvasUnit"]');
		this.currentUnit = 'inches';
		this.DPI = 300;
		this.$presetRadioGroupContainer = $('#canvasSizePresetGroup');
		this.$customSizeControls = $('#customSizeControls');
		this.$customWidthInput = $('#customWidthInput');
		this.$customHeightInput = $('#customHeightInput');
		this.$customWidthUnitLabel = $('#customWidthUnit');
		this.$customHeightUnitLabel = $('#customHeightUnit');
		this.$customWidthError = $('#customWidthError');
		this.$customHeightError = $('#customHeightError');
		this.$addSpineAndBackContainer = $('#addSpineAndBackContainer'); // Still referenced for state
		this.$addSpineAndBackCheckbox = $('#addSpineAndBackCheckbox');   // Still referenced for state
		this.$spineControls = $('#spineControls');
		this.$spineInputMethodRadios = $('input[name="spineInputMethod"]');
		this.$spinePixelContainer = $('#spinePixelInputContainer');
		this.$spineWidthInput = $('#spineWidthInput');
		this.$spineWidthError = $('#spineWidthError');
		this.$spineCalculateContainer = $('#spineCalculateInputContainer');
		this.$pageCountInput = $('#pageCountInput');
		this.$pageCountError = $('#pageCountError');
		this.$paperTypeSelect = $('#paperTypeSelect');
		this.$calculatedSpineInfo = $('#calculatedSpineInfo');
		this.$spineCalculationError = $('#spineCalculationError');
		this.$applyBtn = $('#proceedToDesignerBtn'); // Changed ID
		this.$previewContainer = $('#canvasPreviewContainerSetup'); // Changed ID
		this.$previewArea = $('#canvasPreviewAreaSetup'); // Changed ID
		this.$previewFront = $('#previewFrontSetup'); // Changed ID
		this.$previewSpine = $('#previewSpineSetup'); // Changed ID
		this.$previewBack = $('#previewBackSetup'); // Changed ID
		this.pageNumberData = [];
		this.customPresetIdentifier = "custom";
		
		this._initializePresets();
		this._loadPageNumberData();
		this._bindEvents();
		this._initPage();
	}
	
	_initPage() {
		this._resetForm(); // Set initial state
		// Select default preset if passed (e.g., "6x9")
		if (typeof DEFAULT_PRESET_VALUE_SETUP_PAGE !== 'undefined' && DEFAULT_PRESET_VALUE_SETUP_PAGE) {
			const $defaultRadio = this.$presetRadioGroupContainer.find(`input[name="canvasSizePreset"][value="${DEFAULT_PRESET_VALUE_SETUP_PAGE}"]`);
			if ($defaultRadio.length) {
				$defaultRadio.prop('checked', true);
			}
		}
		this._handlePresetChange(); // Update UI based on selected preset
		// _toggleSpineInputMethod is called within _handlePresetChange
		this._updatePreview(); // Initial preview render
	}
	
	_initializePresets() {
		// Common presets (Kindle and Square removed for this page)
		this.commonPresets = [
			// {value: "1600x2560", label: "Kindle (1600 x 2560 px)", base_size: "kindle", allowSpine: false, type: "common"},
			// {value: "3000x3000", label: "Square (3000 x 3000 px)", base_size: "square", allowSpine: false, type: "common"}
		];
		this.inchPresets = [
			{ value: "1540x2475", label: "5.00\" x 8.00\" (1540x2475 px)", base_size: "5.00x8.00", allowSpine: true, type: "inch" },
			{ value: "1615x2475", label: "5.25\" x 8.00\" (1615x2475 px)", base_size: "5.25x8.00", allowSpine: true, type: "inch" },
			{ value: "1690x2625", label: "5.50\" x 8.50\" (1690x2625 px)", base_size: "5.50x8.50", allowSpine: true, type: "inch" },
			{ value: "1840x2775", label: "6.00\" x 9.00\" (1840x2775 px)", base_size: "6.00x9.00", allowSpine: true, type: "inch" },
			{ value: "1882x2838", label: "6.14\" x 9.21\" (1882x2838 px)", base_size: "6.14x9.21", allowSpine: true, type: "inch" },
			{ value: "2048x2958", label: "6.69\" x 9.61\" (2048x2958 px)", base_size: "6.69x9.61", allowSpine: true, type: "inch" },
		];
		this.mmPresets = [
			{ value: `${Math.round(148 / 25.4 * this.DPI)}x${Math.round(210 / 25.4 * this.DPI)}`, label: "A5 (148 x 210 mm)", base_size: "A5_mm", allowSpine: true, type: "mm", actualUnit: "mm", actualDims: {w: 148, h: 210} },
			{ value: `${Math.round(105 / 25.4 * this.DPI)}x${Math.round(74 / 25.4 * this.DPI)}`, label: "A6 (105 x 74 mm)", base_size: "A6_mm", allowSpine: true, type: "mm", actualUnit: "mm", actualDims: {w: 105, h: 74} },
			{ value: `${Math.round(210 / 25.4 * this.DPI)}x${Math.round(297 / 25.4 * this.DPI)}`, label: "A4 (210 x 297 mm)", base_size: "A4_mm", allowSpine: true, type: "mm", actualUnit: "mm", actualDims: {w: 210, h: 297} },
		];
	}
	
	_loadPageNumberData() {
		try {
			const dataElement = document.getElementById('pageNumberData');
			if (dataElement && dataElement.textContent) {
				this.pageNumberData = JSON.parse(dataElement.textContent);
			} else {
				this.pageNumberData = [];
			}
		} catch (error) {
			this.pageNumberData = [];
		}
	}
	
	_populatePresetRadios(selectedPresetValueToKeep = null) {
		this.$presetRadioGroupContainer.empty();
		let presetsToShow = [...this.commonPresets]; // Will be empty initially based on new _initializePresets
		if (this.currentUnit === 'inches') presetsToShow.push(...this.inchPresets);
		else if (this.currentUnit === 'mm') presetsToShow.push(...this.mmPresets);
		
		// Ensure there's at least one preset type if common is empty
		if (presetsToShow.length === 0 && this.inchPresets.length > 0 && this.currentUnit !== 'mm') {
			presetsToShow.push(...this.inchPresets); // Default to inches if common is empty and no unit preference
		} else if (presetsToShow.length === 0 && this.mmPresets.length > 0 && this.currentUnit !== 'inches') {
			presetsToShow.push(...this.mmPresets);
		}
		
		
		presetsToShow.push({value: this.customPresetIdentifier, label: "Custom Size", allowSpine: true, type: "custom"});
		
		presetsToShow.forEach((preset, index) => {
			const id = `preset_${preset.value.replace(/[^a-zA-Z0-9]/g, '')}_${index}`;
			const $radioDiv = $('<div class="form-check form-check-sm mb-1"></div>');
			const $radioInput = $(`<input class="form-check-input" type="radio" name="canvasSizePreset" id="${id}" value="${preset.value}">`)
				.data('presetData', preset).data('base-size', preset.base_size || '');
			if (selectedPresetValueToKeep && preset.value === selectedPresetValueToKeep) $radioInput.prop('checked', true);
			const $radioLabel = $(`<label class="form-check-label" for="${id}">${preset.label}</label>`);
			$radioDiv.append($radioInput).append($radioLabel);
			this.$presetRadioGroupContainer.append($radioDiv);
		});
		
		if (!this.$presetRadioGroupContainer.find('input[name="canvasSizePreset"]:checked').length && presetsToShow.length > 0) {
			this.$presetRadioGroupContainer.find('input[name="canvasSizePreset"]').first().prop('checked', true);
		}
		this._handlePresetChange();
	}
	
	_bindEvents() {
		this.$unitRadios.on('change', () => {
			this.currentUnit = this.$unitRadios.filter(':checked').val();
			const currentSelectedPresetRadio = this.$presetRadioGroupContainer.find('input[name="canvasSizePreset"]:checked');
			let valueToKeep = currentSelectedPresetRadio.length ? currentSelectedPresetRadio.val() : null;
			// If switching units and current selection is unit-specific, try to clear it or pick a generic/custom one
			const currentPresetData = currentSelectedPresetRadio.data('presetData');
			if (currentPresetData && currentPresetData.type !== 'custom' && currentPresetData.type !== this.currentUnit && currentPresetData.type !== 'common') {
				valueToKeep = null; // Deselect if it's not compatible with the new unit
			}
			this._populatePresetRadios(valueToKeep);
			this._updatePreview();
		});
		
		this.$presetRadioGroupContainer.on('change', 'input[name="canvasSizePreset"]', () => {
			this.$presetError.hide();
			this._handlePresetChange();
			this._updatePreview();
		});
		
		this.$customWidthInput.on('input', () => { this.$customWidthError.hide(); this._updatePreview(); });
		this.$customHeightInput.on('input', () => { this.$customHeightError.hide(); this._updatePreview(); });
		
		// Checkbox change event is not strictly needed for UI toggle as it's hidden, but kept for consistency if state is read
		this.$addSpineAndBackCheckbox.on('change', () => {
			// const isChecked = this.$addSpineAndBackCheckbox.is(':checked');
			// this.$spineControls.toggle(isChecked); // This is now handled by _handlePresetChange
			// if (isChecked) this._toggleSpineInputMethod();
			this._handlePresetChange(); // Re-evaluate spine controls visibility
			this._updatePreview();
		});
		
		this.$spineInputMethodRadios.on('change', () => {
			this._toggleSpineInputMethod();
			this._updatePreview();
		});
		
		let pixelDebounceTimer;
		this.$spineWidthInput.on('input', () => {
			this.$spineWidthError.hide();
			clearTimeout(pixelDebounceTimer);
			pixelDebounceTimer = setTimeout(() => this._updatePreview(), 250);
		});
		
		let calcDebounceTimer;
		const calcUpdateHandler = () => {
			this.$pageCountError.hide();
			this.$spineCalculationError.hide();
			clearTimeout(calcDebounceTimer);
			calcDebounceTimer = setTimeout(() => this._updatePreview(), 300);
		};
		this.$pageCountInput.on('input', calcUpdateHandler);
		this.$paperTypeSelect.on('change', calcUpdateHandler);
		
		this.$applyBtn.on('click', () => this._handleProceedToDesigner());
	}
	
	_handlePresetChange() {
		const $selectedPresetRadio = this.$presetRadioGroupContainer.find('input[name="canvasSizePreset"]:checked');
		if (!$selectedPresetRadio.length) {
			this.$customSizeControls.hide();
			// this.$addSpineAndBackContainer.hide(); // Container is always hidden via CSS
			// this.$addSpineAndBackCheckbox.prop('checked', false); // Checkbox is always checked
			this.$spineControls.hide(); // Hide spine if no preset selected
			return;
		}
		
		const presetData = $selectedPresetRadio.data('presetData');
		
		if (presetData && presetData.value === this.customPresetIdentifier) {
			this.$customSizeControls.show();
			const unitLabel = this.currentUnit === 'mm' ? 'mm' : 'inches';
			this.$customWidthUnitLabel.text(unitLabel);
			this.$customHeightUnitLabel.text(unitLabel);
			if (this.currentUnit === 'mm') {
				this.$customWidthInput.val(this.$customWidthInput.val() || 150);
				this.$customHeightInput.val(this.$customHeightInput.val() || 210);
			} else {
				this.$customWidthInput.val(this.$customWidthInput.val() || 6);
				this.$customHeightInput.val(this.$customHeightInput.val() || 9);
			}
		} else {
			this.$customSizeControls.hide();
		}
		
		// For this page, spine is always added, and all available presets allow spine.
		// So, spine controls should always be visible if a preset is selected.
		// The $addSpineAndBackContainer is hidden by CSS, and checkbox is checked by default.
		if (presetData && presetData.allowSpine) { // This should be true for all non-Kindle/Square presets
			this.$spineControls.show();
			this._toggleSpineInputMethod(); // Ensure correct sub-controls (pixel/calculate) are visible
		} else {
			// This case should ideally not be hit if presets are filtered correctly
			this.$spineControls.hide();
		}
	}
	
	_toggleSpineInputMethod() {
		const method = this.$spineInputMethodRadios.filter(':checked').val();
		// Assumes $spineControls is already visible if this function is called.
		if (method === 'calculate') {
			this.$spinePixelContainer.hide();
			this.$spineCalculateContainer.show();
		} else { // 'pixels'
			this.$spinePixelContainer.show();
			this.$spineCalculateContainer.hide();
		}
		this.$spineWidthError.hide();
		this.$pageCountError.hide();
		this.$spineCalculationError.hide();
		this.$calculatedSpineInfo.hide();
	}
	
	_calculateSpineWidthFromPages() {
		const $selectedPresetRadio = this.$presetRadioGroupContainer.find('input[name="canvasSizePreset"]:checked');
		if (!$selectedPresetRadio.length) return {width: null, error: "No preset selected."};
		
		const baseSize = $selectedPresetRadio.data('base-size');
		const dimensions = this._getCanvasDimensions(); // Gets front cover dimensions
		if (!dimensions || !dimensions.frontWidth) return {width: null, error: "Could not determine front cover width."};
		
		let frontCoverWidthPx = dimensions.frontWidth;
		const pageCount = parseInt(this.$pageCountInput.val(), 10);
		const paperType = this.$paperTypeSelect.val();
		
		if (isNaN(pageCount) || pageCount <= 0) return {width: null, error: "Invalid page count."};
		if (!baseSize || !paperType || isNaN(frontCoverWidthPx)) return { width: null, error: "Invalid preset, paper type, or front width." };
		if (!this.pageNumberData || this.pageNumberData.length === 0) return { width: null, error: "Page number data not available." };
		
		const sortedMatches = this.pageNumberData
			.filter(entry => entry.size === baseSize && entry.paper_type === paperType && entry.pages >= pageCount)
			.sort((a, b) => a.pages - b.pages);
		
		const match = sortedMatches.length > 0 ? sortedMatches[0] : null;
		
		if (match) {
			const totalWidthFromData = match.width; // This is total width (Back + Spine + Front)
			// We need frontCoverWidthPx to calculate spine.
			// The `base_size` in page-numbers.json corresponds to the front cover trim size.
			// The `width` in page-numbers.json is the total flat cover width in pixels.
			const calculatedSpineWidth = totalWidthFromData - (2 * frontCoverWidthPx);
			
			if (calculatedSpineWidth > 0) {
				this.$calculatedSpineInfo.text(`Using data for ${match.pages} pages. Spine: ${calculatedSpineWidth}px`).show();
				return {width: calculatedSpineWidth, error: null};
			} else {
				return {width: null, error: `Calculation error (Result: ${calculatedSpineWidth}px). Check if front width matches data.`};
			}
		} else {
			return {width: null, error: "No data found for this configuration."};
		}
	}
	
	_getSpineWidth() {
		// Spine is always added on this page. Checkbox is hidden and checked.
		// if (!this.$addSpineAndBackCheckbox.is(':checked') || !this.$addSpineAndBackContainer.is(':visible')) return 0;
		// The above is simplified to: spine is always active.
		
		const method = this.$spineInputMethodRadios.filter(':checked').val();
		if (method === 'calculate') {
			const result = this._calculateSpineWidthFromPages();
			if (result.error) {
				this.$spineCalculationError.text(result.error).show();
				this.$calculatedSpineInfo.hide();
				return null;
			} else {
				this.$spineCalculationError.hide();
				return result.width;
			}
		} else { // 'pixels'
			const spineWidth = parseInt(this.$spineWidthInput.val(), 10);
			if (isNaN(spineWidth) || spineWidth <= 0) {
				this.$spineWidthError.show();
				return null;
			} else {
				this.$spineWidthError.hide();
				return spineWidth;
			}
		}
	}
	
	_getCanvasDimensions() {
		const $selectedPresetRadio = this.$presetRadioGroupContainer.find('input[name="canvasSizePreset"]:checked');
		if (!$selectedPresetRadio.length) {
			this.$presetError.text("Please select a preset.").show();
			return null;
		}
		const presetValue = $selectedPresetRadio.val();
		const presetData = $selectedPresetRadio.data('presetData');
		let frontWidthPx, finalHeightPx;
		
		if (presetValue === this.customPresetIdentifier) {
			let customWidth = parseFloat(this.$customWidthInput.val());
			let customHeight = parseFloat(this.$customHeightInput.val());
			if (isNaN(customWidth) || customWidth <= 0) { this.$customWidthError.show(); return null; } else { this.$customWidthError.hide(); }
			if (isNaN(customHeight) || customHeight <= 0) { this.$customHeightError.show(); return null; } else { this.$customHeightError.hide(); }
			
			if (this.currentUnit === 'mm') {
				frontWidthPx = Math.round((customWidth / 25.4) * this.DPI);
				finalHeightPx = Math.round((customHeight / 25.4) * this.DPI);
			} else { // inches
				frontWidthPx = Math.round(customWidth * this.DPI);
				finalHeightPx = Math.round(customHeight * this.DPI);
			}
		} else if (presetData && presetData.value) {
			const parts = presetData.value.split('x');
			if (parts.length === 2) {
				frontWidthPx = parseInt(parts[0], 10);
				finalHeightPx = parseInt(parts[1], 10);
				if (isNaN(frontWidthPx) || isNaN(finalHeightPx)) { this.$presetError.text("Invalid preset dimension.").show(); return null; }
			} else { this.$presetError.text("Invalid preset value.").show(); return null; }
		} else { this.$presetError.text("Could not determine preset data.").show(); return null; }
		
		return {frontWidth: frontWidthPx, height: finalHeightPx};
	}
	
	_validateInputs() {
		let isValid = true;
		this.$presetError.hide();
		this.$customWidthError.hide();
		this.$customHeightError.hide();
		
		const dimensions = this._getCanvasDimensions();
		if (!dimensions) isValid = false;
		
		// Spine is always active on this page.
		// const isSpineAndBackChecked = this.$addSpineAndBackCheckbox.is(':checked'); // always true
		// const isSpineAllowed = this.$addSpineAndBackContainer.is(':visible'); // always false, but we ignore this for logic
		
		// We assume spine is always being configured if a preset is selected
		const $selectedPresetRadio = this.$presetRadioGroupContainer.find('input[name="canvasSizePreset"]:checked');
		if ($selectedPresetRadio.length) { // Only validate spine if a preset is chosen
			const spineMethod = this.$spineInputMethodRadios.filter(':checked').val();
			if (spineMethod === 'pixels') {
				const spineWidth = parseInt(this.$spineWidthInput.val(), 10);
				if (isNaN(spineWidth) || spineWidth <= 0) {
					this.$spineWidthError.show(); this.$spineWidthInput.trigger('focus'); isValid = false;
				} else { this.$spineWidthError.hide(); }
			} else { // calculate
				const pageCount = parseInt(this.$pageCountInput.val(), 10);
				if (isNaN(pageCount) || pageCount <= 0) {
					this.$pageCountError.show(); this.$pageCountInput.trigger('focus'); isValid = false;
				} else {
					this.$pageCountError.hide();
					const calcResult = this._calculateSpineWidthFromPages();
					if (calcResult.error) {
						this.$spineCalculationError.text(calcResult.error).show(); isValid = false;
					} else { this.$spineCalculationError.hide(); }
				}
			}
		} else { // No preset selected, don't validate spine yet
			this.$spineWidthError.hide();
			this.$pageCountError.hide();
			this.$spineCalculationError.hide();
		}
		return isValid;
	}
	
	_updatePreview() {
		const dimensions = this._getCanvasDimensions();
		if (!dimensions) {
			this.$previewArea.hide();
			this.$previewArea.css('--preview-bg-image', 'none').removeClass('has-background-image');
			return;
		}
		this.$previewArea.show();
		
		// Set background image for the ::before pseudo-element via CSS custom property
		if (typeof FULL_COVER_THUMBNAIL_URL_PREVIEW !== 'undefined' && FULL_COVER_THUMBNAIL_URL_PREVIEW) {
			this.$previewArea.css('--preview-bg-image', `url('${FULL_COVER_THUMBNAIL_URL_PREVIEW}')`);
			this.$previewArea.addClass('has-background-image');
		} else {
			this.$previewArea.css('--preview-bg-image', 'none');
			this.$previewArea.removeClass('has-background-image');
		}
		
		const {frontWidth: frontWidthPx, height: coverHeightPx} = dimensions;
		
		// Spine is always added for print setup.
		const addSpineAndBack = true; // Simplified for this page
		
		let spineWidthPx = 0;
		let spineDisplayError = false;
		
		if (addSpineAndBack) { // This will always be true
			const spineResult = this._getSpineWidth();
			if (spineResult === null) { // Error in getting spine width
				spineWidthPx = 20; // Default small width for visual placeholder on error
				spineDisplayError = true;
			} else {
				spineWidthPx = Math.max(0, spineResult);
			}
		}
		
		const previewContainerWidth = this.$previewContainer.width() * 0.95;
		const previewContainerHeight = this.$previewContainer.height() * 0.95;
		
		if (!previewContainerWidth || !previewContainerHeight || !frontWidthPx || !coverHeightPx) {
			this.$previewArea.hide();
			return;
		}
		
		let totalLayoutWidthPx = frontWidthPx;
		if (addSpineAndBack) totalLayoutWidthPx += spineWidthPx + frontWidthPx; // Back + Spine + Front
		if (totalLayoutWidthPx <= 0) totalLayoutWidthPx = frontWidthPx; // Fallback
		
		const scaleX = previewContainerWidth / totalLayoutWidthPx;
		const scaleY = previewContainerHeight / coverHeightPx;
		const scale = Math.min(scaleX, scaleY, 1); // Don't scale up beyond 1
		
		const scaledFrontWidth = frontWidthPx * scale;
		const scaledHeight = coverHeightPx * scale;
		const scaledSpineWidth = spineWidthPx * scale;
		const scaledBackWidth = frontWidthPx * scale; // Back is same as front
		
		// Clear previous content (like images if they were there) and set text
		this.$previewFront.empty().css({width: scaledFrontWidth + 'px', height: scaledHeight + 'px'})
			.text(`Front (${frontWidthPx}x${coverHeightPx}px)`);
		
		if (addSpineAndBack) {
			this.$previewSpine.empty().css({width: scaledSpineWidth + 'px', height: scaledHeight + 'px'}).show();
			const spineText = spineDisplayError ? `Spine (Error)` : `Spine (${spineWidthPx}px)`;
			this.$previewSpine.text(spineText);
			
			this.$previewBack.empty().css({width: scaledBackWidth + 'px', height: scaledHeight + 'px'}).show();
			this.$previewBack.text(`Back (${frontWidthPx}x${coverHeightPx}px)`);
		} else { // Should not happen on this page
			this.$previewSpine.hide();
			this.$previewBack.hide();
		}
	}
	
	_resetForm() {
		this.$unitRadios.filter('[value="inches"]').prop('checked', true);
		this.currentUnit = 'inches';
		this._populatePresetRadios(); // Populates with filtered presets, selects first by default
		
		this.$customWidthInput.val(6);
		this.$customHeightInput.val(9);
		this.$customSizeControls.hide(); // Hide custom by default
		this.$customWidthError.hide();
		this.$customHeightError.hide();
		
		this.$addSpineAndBackCheckbox.prop('checked', true); // Always checked
		// this.$spineControls.hide(); // _handlePresetChange will show it
		this.$spineInputMethodRadios.filter('[value="calculate"]').prop('checked', true);
		this.$spineWidthInput.val(200);
		this.$pageCountInput.val(200);
		this.$paperTypeSelect.val('bw');
		
		this.$presetError.hide();
		this.$spineWidthError.hide();
		this.$pageCountError.hide();
		this.$spineCalculationError.hide();
		this.$calculatedSpineInfo.hide();
		
		// _handlePresetChange is called by _populatePresetRadios, which will call _toggleSpineInputMethod
		// _updatePreview is called by _handlePresetChange
	}
	
	_handleProceedToDesigner() {
		if (!this._validateInputs()) return;
		
		const dimensions = this._getCanvasDimensions();
		if (!dimensions) {
			alert("Error determining canvas dimensions.");
			return;
		}
		const {frontWidth: frontWidthPx, height: finalHeightPx} = dimensions;
		
		let totalWidthPx = frontWidthPx;
		let spineWidthPx = 0;
		let backWidthPx = 0;
		
		// Determine if spine and back are being added.
		// This respects the checkbox state, even if it's currently hidden and forced to 'checked'.
		// If you unhide the checkbox in the future, this logic will adapt.
		const addSpineAndBack = this.$addSpineAndBackCheckbox.is(':checked') && this.$addSpineAndBackContainer.is(':visible');
		// FOR CURRENT SETUP (checkbox hidden and checked): addSpineAndBack will effectively be true if $addSpineAndBackContainer is visible.
		// If $addSpineAndBackContainer is hidden (as it is now), we'll treat it as if spine/back is always intended for this page.
		// Let's refine this: for setup-canvas, we always assume print layout.
		const isPrintLayoutSetup = true; // This page is specifically for print setup
		
		if (isPrintLayoutSetup) { // Always true for this page's purpose
			const calculatedOrEnteredSpineWidth = this._getSpineWidth();
			if (calculatedOrEnteredSpineWidth === null) { // Error occurred
				alert("Please fix errors in spine width settings before proceeding.");
				return;
			}
			spineWidthPx = calculatedOrEnteredSpineWidth;
			backWidthPx = frontWidthPx; // Back cover width is same as front
			totalWidthPx = frontWidthPx + spineWidthPx + backWidthPx;
		}
		
		const params = [];
		params.push(`w=${totalWidthPx}`);
		params.push(`h=${finalHeightPx}`);
		if (spineWidthPx > 0) {
			params.push(`spine_width=${spineWidthPx}`);
			params.push(`front_width=${frontWidthPx}`); // Designer uses this for guides
		}
		
		// Determine which image path to use for the designer
		let imagePathForDesigner = '';
		if (isPrintLayoutSetup && typeof FULL_COVER_IMAGE_PATH_DESIGNER !== 'undefined' && FULL_COVER_IMAGE_PATH_DESIGNER) {
			imagePathForDesigner = FULL_COVER_IMAGE_PATH_DESIGNER;
		} else if (typeof ORIGINAL_COVER_IMAGE_PATH_DESIGNER !== 'undefined' && ORIGINAL_COVER_IMAGE_PATH_DESIGNER) {
			// Fallback to front slice if full cover path isn't available or if not a print layout (future proofing)
			imagePathForDesigner = ORIGINAL_COVER_IMAGE_PATH_DESIGNER;
		}
		
		if (imagePathForDesigner) {
			params.push(`image_path=${encodeURIComponent(imagePathForDesigner)}`);
		}
		
		if (typeof TEMPLATE_JSON_URL_DESIGNER !== 'undefined' && TEMPLATE_JSON_URL_DESIGNER) {
			params.push(`template_url=${encodeURIComponent(TEMPLATE_JSON_URL_DESIGNER)}`);
		}
		
		// Open in a new tab/window
		window.location.href = `/designer?${params.join('&')}`;
		// window.open(`/designer?${params.join('&')}`, '_blank');
	}}

$(document).ready(function () {
	new CanvasSizeSetupPage();
});
