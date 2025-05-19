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
		this.$addSpineAndBackContainer = $('#addSpineAndBackContainer');
		this.$addSpineAndBackCheckbox = $('#addSpineAndBackCheckbox');
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
		// Select default preset if passed (e.g., Kindle)
		if (typeof DEFAULT_PRESET_VALUE !== 'undefined' && DEFAULT_PRESET_VALUE) {
			const $defaultRadio = this.$presetRadioGroupContainer.find(`input[name="canvasSizePreset"][value="${DEFAULT_PRESET_VALUE}"]`);
			if ($defaultRadio.length) {
				$defaultRadio.prop('checked', true);
			}
		}
		this._handlePresetChange(); // Update UI based on selected preset
		this._toggleSpineInputMethod();
		this._updatePreview(); // Initial preview render
	}
	
	
	_initializePresets() { // Same as CanvasSizeModal.js
		this.commonPresets = [
			{value: "1600x2560", label: "Kindle (1600 x 2560 px)", base_size: "kindle", allowSpine: false, type: "common"},
			{value: "3000x3000", label: "Square (3000 x 3000 px)", base_size: "square", allowSpine: false, type: "common"}
		];
		this.inchPresets = [
			{
				value: "1540x2475",
				label: "5.00\" x 8.00\" (1540x2475 px)",
				base_size: "5.00x8.00",
				allowSpine: true,
				type: "inch"
			},
			{
				value: "1615x2475",
				label: "5.25\" x 8.00\" (1615x2475 px)",
				base_size: "5.25x8.00",
				allowSpine: true,
				type: "inch"
			},
			{
				value: "1690x2625",
				label: "5.50\" x 8.50\" (1690x2625 px)",
				base_size: "5.50x8.50",
				allowSpine: true,
				type: "inch"
			},
			{
				value: "1840x2775",
				label: "6.00\" x 9.00\" (1840x2775 px)",
				base_size: "6.00x9.00",
				allowSpine: true,
				type: "inch"
			},
			{
				value: "1882x2838",
				label: "6.14\" x 9.21\" (1882x2838 px)",
				base_size: "6.14x9.21",
				allowSpine: true,
				type: "inch"
			},
			{
				value: "2048x2958",
				label: "6.69\" x 9.61\" (2048x2958 px)",
				base_size: "6.69x9.61",
				allowSpine: true,
				type: "inch"
			},
		];
		this.mmPresets = [
			{
				value: `${Math.round(148 / 25.4 * this.DPI)}x${Math.round(210 / 25.4 * this.DPI)}`,
				label: "A5 (148 x 210 mm)",
				base_size: "A5_mm",
				allowSpine: true,
				type: "mm",
				actualUnit: "mm",
				actualDims: {w: 148, h: 210}
			},
			{
				value: `${Math.round(105 / 25.4 * this.DPI)}x${Math.round(74 / 25.4 * this.DPI)}`,
				label: "A6 (105 x 74 mm)",
				base_size: "A6_mm",
				allowSpine: true,
				type: "mm",
				actualUnit: "mm",
				actualDims: {w: 105, h: 74}
			},
			{
				value: `${Math.round(210 / 25.4 * this.DPI)}x${Math.round(297 / 25.4 * this.DPI)}`,
				label: "A4 (210 x 297 mm)",
				base_size: "A4_mm",
				allowSpine: true,
				type: "mm",
				actualUnit: "mm",
				actualDims: {w: 210, h: 297}
			},
		];
	}
	
	_loadPageNumberData() { // Same as CanvasSizeModal.js
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
	
	_populatePresetRadios(selectedPresetValueToKeep = null) { // Same as CanvasSizeModal.js
		this.$presetRadioGroupContainer.empty();
		let presetsToShow = [...this.commonPresets];
		if (this.currentUnit === 'inches') presetsToShow.push(...this.inchPresets);
		else if (this.currentUnit === 'mm') presetsToShow.push(...this.mmPresets);
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
		if (!selectedPresetValueToKeep && presetsToShow.length > 0) {
			this.$presetRadioGroupContainer.find('input[name="canvasSizePreset"]').first().prop('checked', true);
		}
		this._handlePresetChange();
	}
	
	_bindEvents() { // Similar to CanvasSizeModal.js, remove modal-specific events
		this.$unitRadios.on('change', () => {
			this.currentUnit = this.$unitRadios.filter(':checked').val();
			const currentSelectedPresetRadio = this.$presetRadioGroupContainer.find('input[name="canvasSizePreset"]:checked');
			let valueToKeep = currentSelectedPresetRadio.length ? currentSelectedPresetRadio.val() : null;
			// ... (logic to handle unit-specific preset retention if needed) ...
			this._populatePresetRadios(valueToKeep);
			this._updatePreview();
		});
		
		this.$presetRadioGroupContainer.on('change', 'input[name="canvasSizePreset"]', () => {
			this.$presetError.hide();
			this._handlePresetChange();
			this._updatePreview();
		});
		this.$customWidthInput.on('input', () => {
			this.$customWidthError.hide();
			this._updatePreview();
		});
		this.$customHeightInput.on('input', () => {
			this.$customHeightError.hide();
			this._updatePreview();
		});
		this.$addSpineAndBackCheckbox.on('change', () => {
			const isChecked = this.$addSpineAndBackCheckbox.is(':checked');
			this.$spineControls.toggle(isChecked);
			if (isChecked) this._toggleSpineInputMethod();
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
	
	_handlePresetChange() { // Same as CanvasSizeModal.js
		const $selectedPresetRadio = this.$presetRadioGroupContainer.find('input[name="canvasSizePreset"]:checked');
		if (!$selectedPresetRadio.length) {
			this.$customSizeControls.hide();
			this.$addSpineAndBackContainer.hide();
			this.$addSpineAndBackCheckbox.prop('checked', false).trigger('change');
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
		
		if (presetData && presetData.allowSpine) {
			this.$addSpineAndBackContainer.show();
		} else {
			this.$addSpineAndBackContainer.hide();
			this.$addSpineAndBackCheckbox.prop('checked', false);
			this.$spineControls.hide();
		}
		if (!this.$addSpineAndBackContainer.is(':visible')) {
			this.$spineControls.hide();
		} else {
			if (this.$addSpineAndBackCheckbox.is(':checked')) {
				this.$spineControls.show();
				this._toggleSpineInputMethod();
			} else {
				this.$spineControls.hide();
			}
		}
	}
	
	_toggleSpineInputMethod() { // Same as CanvasSizeModal.js
		const method = this.$spineInputMethodRadios.filter(':checked').val();
		const spineEnabledByCheckbox = this.$addSpineAndBackCheckbox.is(':checked');
		const spineAllowedByPreset = this.$addSpineAndBackContainer.is(':visible');
		
		if (spineAllowedByPreset && spineEnabledByCheckbox) {
			this.$spineControls.show();
			if (method === 'calculate') {
				this.$spinePixelContainer.hide();
				this.$spineCalculateContainer.show();
			} else {
				this.$spinePixelContainer.show();
				this.$spineCalculateContainer.hide();
			}
		} else {
			this.$spineControls.hide();
			this.$spinePixelContainer.hide();
			this.$spineCalculateContainer.hide();
		}
		this.$spineWidthError.hide();
		this.$pageCountError.hide();
		this.$spineCalculationError.hide();
		this.$calculatedSpineInfo.hide();
	}
	
	_calculateSpineWidthFromPages() { // Same as CanvasSizeModal.js
		const $selectedPresetRadio = this.$presetRadioGroupContainer.find('input[name="canvasSizePreset"]:checked');
		if (!$selectedPresetRadio.length) return {width: null, error: "No preset selected."};
		const baseSize = $selectedPresetRadio.data('base-size');
		const dimensions = this._getCanvasDimensions();
		if (!dimensions) return {width: null, error: "Could not determine front cover width."};
		let frontCoverWidthPx = dimensions.frontWidth;
		const pageCount = parseInt(this.$pageCountInput.val(), 10);
		const paperType = this.$paperTypeSelect.val();
		
		if (isNaN(pageCount) || pageCount <= 0) return {width: null, error: "Invalid page count."};
		if (!baseSize || !paperType || isNaN(frontCoverWidthPx)) return {
			width: null,
			error: "Invalid preset, paper type, or front width."
		};
		if (!this.pageNumberData || this.pageNumberData.length === 0) return {
			width: null,
			error: "Page number data not available."
		};
		
		const sortedMatches = this.pageNumberData
			.filter(entry => entry.size === baseSize && entry.paper_type === paperType && entry.pages >= pageCount)
			.sort((a, b) => a.pages - b.pages);
		const match = sortedMatches.length > 0 ? sortedMatches[0] : null;
		
		if (match) {
			const totalWidthFromData = match.width;
			const calculatedSpineWidth = totalWidthFromData - (2 * frontCoverWidthPx);
			if (calculatedSpineWidth > 0) {
				this.$calculatedSpineInfo.text(`Using data for ${match.pages} pages. Spine: ${calculatedSpineWidth}px`).show();
				return {width: calculatedSpineWidth, error: null};
			} else {
				return {width: null, error: `Calculation error (Result: ${calculatedSpineWidth}px).`};
			}
		} else {
			return {width: null, error: "No data found for this configuration."};
		}
	}
	
	_getSpineWidth() { // Same as CanvasSizeModal.js
		if (!this.$addSpineAndBackCheckbox.is(':checked') || !this.$addSpineAndBackContainer.is(':visible')) return 0;
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
		} else {
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
	
	_getCanvasDimensions() { // Same as CanvasSizeModal.js
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
			if (isNaN(customWidth) || customWidth <= 0) {
				this.$customWidthError.show();
				return null;
			} else {
				this.$customWidthError.hide();
			}
			if (isNaN(customHeight) || customHeight <= 0) {
				this.$customHeightError.show();
				return null;
			} else {
				this.$customHeightError.hide();
			}
			if (this.currentUnit === 'mm') {
				frontWidthPx = Math.round((customWidth / 25.4) * this.DPI);
				finalHeightPx = Math.round((customHeight / 25.4) * this.DPI);
			} else {
				frontWidthPx = Math.round(customWidth * this.DPI);
				finalHeightPx = Math.round(customHeight * this.DPI);
			}
		} else if (presetData && presetData.value) {
			const parts = presetData.value.split('x');
			if (parts.length === 2) {
				frontWidthPx = parseInt(parts[0], 10);
				finalHeightPx = parseInt(parts[1], 10);
				if (isNaN(frontWidthPx) || isNaN(finalHeightPx)) {
					this.$presetError.text("Invalid preset dimension.").show();
					return null;
				}
			} else {
				this.$presetError.text("Invalid preset value.").show();
				return null;
			}
		} else {
			this.$presetError.text("Could not determine preset data.").show();
			return null;
		}
		return {frontWidth: frontWidthPx, height: finalHeightPx};
	}
	
	_validateInputs() { // Same as CanvasSizeModal.js
		let isValid = true;
		this.$presetError.hide();
		this.$customWidthError.hide();
		this.$customHeightError.hide();
		const dimensions = this._getCanvasDimensions();
		if (!dimensions) isValid = false;
		
		const isSpineAndBackChecked = this.$addSpineAndBackCheckbox.is(':checked');
		const isSpineAllowed = this.$addSpineAndBackContainer.is(':visible');
		if (isSpineAllowed && isSpineAndBackChecked) {
			const spineMethod = this.$spineInputMethodRadios.filter(':checked').val();
			if (spineMethod === 'pixels') {
				const spineWidth = parseInt(this.$spineWidthInput.val(), 10);
				if (isNaN(spineWidth) || spineWidth <= 0) {
					this.$spineWidthError.show();
					this.$spineWidthInput.trigger('focus');
					isValid = false;
				} else {
					this.$spineWidthError.hide();
				}
			} else {
				const pageCount = parseInt(this.$pageCountInput.val(), 10);
				if (isNaN(pageCount) || pageCount <= 0) {
					this.$pageCountError.show();
					this.$pageCountInput.trigger('focus');
					isValid = false;
				} else {
					this.$pageCountError.hide();
					const calcResult = this._calculateSpineWidthFromPages();
					if (calcResult.error) {
						this.$spineCalculationError.text(calcResult.error).show();
						isValid = false;
					} else {
						this.$spineCalculationError.hide();
					}
				}
			}
		} else {
			this.$spineWidthError.hide();
			this.$pageCountError.hide();
			this.$spineCalculationError.hide();
		}
		return isValid;
	}
	
	_updatePreview() { // Modified for this page
		const dimensions = this._getCanvasDimensions();
		if (!dimensions) {
			this.$previewArea.hide();
			return;
		}
		this.$previewArea.show();
		const {frontWidth: frontWidthPx, height: coverHeightPx} = dimensions;
		const addSpineAndBack = this.$addSpineAndBackCheckbox.is(':checked') && this.$addSpineAndBackContainer.is(':visible');
		let spineWidthPx = 0;
		let spineDisplayError = false;
		
		if (addSpineAndBack) {
			const spineResult = this._getSpineWidth();
			if (spineResult === null) {
				spineWidthPx = 20;
				spineDisplayError = true;
			} else {
				spineWidthPx = Math.max(0, spineResult);
			}
		}
		
		const previewContainerWidth = this.$previewContainer.width() * 0.95; // Use more space
		const previewContainerHeight = this.$previewContainer.height() * 0.95;
		if (!previewContainerWidth || !previewContainerHeight || !frontWidthPx || !coverHeightPx) {
			this.$previewArea.hide();
			return;
		}
		
		let totalLayoutWidthPx = frontWidthPx;
		if (addSpineAndBack) totalLayoutWidthPx += spineWidthPx + frontWidthPx;
		if (totalLayoutWidthPx <= 0) totalLayoutWidthPx = frontWidthPx;
		
		const scaleX = previewContainerWidth / totalLayoutWidthPx;
		const scaleY = previewContainerHeight / coverHeightPx;
		const scale = Math.min(scaleX, scaleY, 1);
		
		const scaledFrontWidth = frontWidthPx * scale;
		const scaledHeight = coverHeightPx * scale;
		const scaledSpineWidth = spineWidthPx * scale;
		
		// Update Front Panel (with image and overlay)
		this.$previewFront.empty().css({width: scaledFrontWidth + 'px', height: scaledHeight + 'px'});
		if (typeof COVER_IMAGE_URL_PREVIEW !== 'undefined' && COVER_IMAGE_URL_PREVIEW) {
			const $img = $('<img>').attr('src', COVER_IMAGE_URL_PREVIEW).addClass('base-image');
			this.$previewFront.append($img);
			if (typeof TEMPLATE_OVERLAY_URL_PREVIEW !== 'undefined' && TEMPLATE_OVERLAY_URL_PREVIEW) {
				const $overlay = $('<img>').attr('src', TEMPLATE_OVERLAY_URL_PREVIEW).addClass('overlay-image');
				this.$previewFront.append($overlay);
			}
		} else {
			this.$previewFront.text(`Front (${frontWidthPx}x${coverHeightPx}px)`);
		}
		
		
		if (addSpineAndBack) {
			this.$previewSpine.css({width: scaledSpineWidth + 'px', height: scaledHeight + 'px'}).show();
			const spineText = spineDisplayError ? `Spine (Error)` : `Spine (${spineWidthPx}px)`;
			this.$previewSpine.text(spineText);
			this.$previewBack.css({width: scaledFrontWidth + 'px', height: scaledHeight + 'px'}).show();
			this.$previewBack.text(`Back (${frontWidthPx}x${coverHeightPx}px)`); // No image on back for this preview
		} else {
			this.$previewSpine.hide();
			this.$previewBack.hide();
		}
	}
	
	_resetForm() { // Same as CanvasSizeModal.js _resetForm
		this.$unitRadios.filter('[value="inches"]').prop('checked', true);
		this.currentUnit = 'inches';
		this._populatePresetRadios(); // Selects first by default
		this.$customWidthInput.val(6);
		this.$customHeightInput.val(9);
		this.$customSizeControls.hide();
		this.$customWidthError.hide();
		this.$customHeightError.hide();
		this.$addSpineAndBackCheckbox.prop('checked', false);
		this.$spineControls.hide();
		this.$spineInputMethodRadios.filter('[value="calculate"]').prop('checked', true);
		this.$spineWidthInput.val(200);
		this.$pageCountInput.val(200);
		this.$paperTypeSelect.val('bw');
		this.$presetError.hide();
		this.$spineWidthError.hide();
		this.$pageCountError.hide();
		this.$spineCalculationError.hide();
		this.$calculatedSpineInfo.hide();
		// this._toggleSpineInputMethod(); // Called by _handlePresetChange from _populatePresetRadios
		// this._updatePreview(); // Called by _handlePresetChange
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
		let backWidthPx = 0; // Not strictly needed for designer URL but good for context
		
		const addSpineAndBack = this.$addSpineAndBackCheckbox.is(':checked') && this.$addSpineAndBackContainer.is(':visible');
		if (addSpineAndBack) {
			const calculatedOrEnteredSpineWidth = this._getSpineWidth();
			if (calculatedOrEnteredSpineWidth === null) {
				alert("Fix errors in spine width settings.");
				return;
			}
			spineWidthPx = calculatedOrEnteredSpineWidth;
			backWidthPx = frontWidthPx;
			totalWidthPx = frontWidthPx + spineWidthPx + backWidthPx;
		}
		
		const params = [];
		params.push(`w=${totalWidthPx}`);
		params.push(`h=${finalHeightPx}`);
		if (spineWidthPx > 0) {
			params.push(`spine_width=${spineWidthPx}`);
			params.push(`front_width=${frontWidthPx}`); // Designer can use this for guides
		}
		if (typeof ORIGINAL_COVER_IMAGE_PATH_DESIGNER !== 'undefined' && ORIGINAL_COVER_IMAGE_PATH_DESIGNER) {
			params.push(`image_path=${encodeURIComponent(ORIGINAL_COVER_IMAGE_PATH_DESIGNER)}`);
		}
		if (typeof TEMPLATE_JSON_URL_DESIGNER !== 'undefined' && TEMPLATE_JSON_URL_DESIGNER) {
			params.push(`template_url=${encodeURIComponent(TEMPLATE_JSON_URL_DESIGNER)}`);
		}
		
		window.open(`/designer?${params.join('&')}`, '_blank');
		// window.location.href = `/designer?${params.join('&')}`;
	}
}

$(document).ready(function () {
	new CanvasSizeSetupPage();
});
