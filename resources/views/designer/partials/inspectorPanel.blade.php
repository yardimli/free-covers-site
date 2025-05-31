{{-- free-cover-site/resources/views/designer/partials/inspectorPanel.blade.php --}}
<div id="inspectorPanel" class="inspector-panel closed">
	<button type="button" class="btn-close close-inspector-btn" aria-label="Close"></button>
	
	<!-- Layer Info & Actions -->
	<div id="inspector-layer-info-actions" class="inspector-section">
		<div class="section-header d-flex justify-content-between align-items-center">
			<span id="inspector-layer-name" title="Layer Name" class="flex-grow-1 text-truncate me-2">No Layer Selected</span>
		</div>
		<div class="section-content">
			<div class="btn-group btn-group-sm w-100" role="group" aria-label="Layer Actions">
				<button type="button" class="btn btn-outline-secondary" id="lockBtn" title="Lock/Unlock Selected"><i
						class="fas fa-lock-open"></i></button>
				<button type="button" class="btn btn-outline-secondary" id="visibilityBtn" title="Toggle Visibility"><i
						class="fas fa-eye"></i></button>
				<button type="button" class="btn btn-outline-secondary" id="bringToFrontBtn" title="Bring to Front"><i
						class="fas fa-arrow-up"></i></button>
				<button type="button" class="btn btn-outline-secondary" id="sendToBackBtn" title="Send to Back"><i
						class="fas fa-arrow-down"></i></button>
				<button type="button" class="btn btn-outline-danger" id="deleteBtn" title="Delete Selected"><i
						class="fas fa-trash-alt"></i></button>
			</div>
		</div>
	</div>
	
	<!-- Alignment (Canvas) -->
	<div id="inspector-alignment" class="inspector-section">
		<div class="section-header">Align (Canvas)</div>
		<div class="section-content">
			<div class="btn-group btn-group-sm w-100 mb-1" role="group" aria-label="Horizontal Alignment">
				<button type="button" class="btn btn-outline-secondary" data-align-layer="left" title="Align Left"><i
						class="fas fa-align-left"></i></button>
				<button type="button" class="btn btn-outline-secondary" data-align-layer="h-center"
				        title="Align Horizontal Center"><i class="fas fa-align-center"></i></button>
				<button type="button" class="btn btn-outline-secondary" data-align-layer="right" title="Align Right"><i
						class="fas fa-align-right"></i></button>
				<button type="button" class="btn btn-outline-secondary" data-align-layer="top" title="Align Top"><i
						class="fas fa-align-left fa-rotate-90"></i></button>
				<button type="button" class="btn btn-outline-secondary" data-align-layer="v-center"
				        title="Align Vertical Center"><i class="fas fa-align-center fa-rotate-90"></i></button>
				<button type="button" class="btn btn-outline-secondary" data-align-layer="bottom" title="Align Bottom"><i
						class="fas fa-align-right fa-rotate-90"></i></button>
			</div>
		</div>
	</div>
	
	<!-- Layer Properties -->
	<div id="inspector-layer" class="inspector-section">
		<div class="section-header">Layer
			<button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" id="cloneLayerBtn"
			        title="Clone Layer" style="display: none;"><i class="fas fa-clone"></i> Clone Layer
			</button>
		</div>
		<div class="section-content">
			
			<!-- Position & Size -->
			<div class="row g-2 mb-2">
				<div class="col-6">
					<label for="inspector-pos-x" class="form-label">X</label>
					<div class="d-flex align-items-center">
						<input type="number" class="form-control form-control-sm inspector-value-input" id="inspector-pos-x"
						       step="1">
						<span class="ms-1 inspector-unit-display">px</span>
					</div>
				</div>
				<div class="col-6">
					<label for="inspector-pos-y" class="form-label">Y</label>
					<div class="d-flex align-items-center">
						<input type="number" class="form-control form-control-sm inspector-value-input" id="inspector-pos-y"
						       step="1">
						<span class="ms-1 inspector-unit-display">px</span>
					</div>
				</div>
			</div>
			<div class="row g-2 mb-3"> {{-- Increased mb for spacing before opacity --}}
				<div class="col-6">
					<label for="inspector-size-width" class="form-label">Width</label>
					<div class="d-flex align-items-center">
						<input type="number" class="form-control form-control-sm inspector-value-input" id="inspector-size-width"
						       step="1" min="1">
						<span class="ms-1 inspector-unit-display">px</span>
					</div>
				</div>
				<div class="col-6">
					<label for="inspector-size-height" class="form-label">Height</label>
					<div class="d-flex align-items-center">
						<input type="number" class="form-control form-control-sm inspector-value-input" id="inspector-size-height"
						       step="1" min="1">
						<span class="ms-1 inspector-unit-display">px</span>
					</div>
				</div>
			</div>
			
			<!-- Opacity -->
			<div class="mb-2">
				<label for="inspector-opacity-slider" class="form-label">Opacity</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-opacity-slider" min="0" max="1"
					       step="0.01" value="1">
					<input type="number" class="form-control form-control-sm inspector-value-input" id="inspector-opacity-input"
					       min="0" max="100" step="1" value="100">
					<span class="ms-1 inspector-unit-display" id="inspector-opacity-unit">%</span>
				</div>
			</div>
			<!-- Rotation -->
			<div class="mb-2">
				<label for="inspector-rotation-slider" class="form-label">Rotation</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-rotation-slider" min="0" max="360"
					       step="1" value="0">
					<input type="number" class="form-control form-control-sm inspector-value-input" id="inspector-rotation-input"
					       min="0" max="360" step="1" value="0">
					<span class="ms-1 inspector-unit-display" id="inspector-rotation-unit">°</span>
				</div>
			</div>
			<!-- Scale -->
			<div class="mb-2">
				<label for="inspector-scale-slider" class="form-label">Scale</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-scale-slider" min="1" max="500" step="1"
					       value="100">
					<input type="number" class="form-control form-control-sm inspector-value-input" id="inspector-scale-input"
					       min="1" max="500" step="1" value="100">
					<span class="ms-1 inspector-unit-display" id="inspector-scale-unit">%</span>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Text Properties -->
	<div id="inspector-text" class="inspector-section" style="display: none;">
		<div class="section-header">Text</div>
		<div class="section-content">
			<!-- Text Content -->
			<div class="mb-3">
				<label for="inspector-text-content" class="form-label">Content</label>
				<textarea id="inspector-text-content" class="form-control form-control-sm" rows="3"></textarea>
			</div>
			<!-- Font Family -->
			<div class="mb-3">
				<input type="text" id="inspector-font-family" class="form-control form-control-sm">
			</div>
			<!-- Font Size, Weight, Style -->
			<div class="row g-2 mb-3">
				<div class="col">
					<label for="inspector-font-size" class="form-label">Size</label>
					<input type="number" id="inspector-font-size" class="form-control form-control-sm" min="1" value="24">
				</div>
				<div class="col">
					<label class="form-label">Style</label>
					<div class="btn-group btn-group-sm w-100" role="group">
						<button type="button" id="inspector-bold-btn" class="btn btn-outline-secondary inspector-text-style-btn"
						        title="Bold"><i class="fas fa-bold"></i></button>
						<button type="button" id="inspector-italic-btn" class="btn btn-outline-secondary inspector-text-style-btn"
						        title="Italic"><i class="fas fa-italic"></i></button>
						<button type="button" id="inspector-underline-btn"
						        class="btn btn-outline-secondary inspector-text-style-btn" title="Underline"><i
								class="fas fa-underline"></i></button>
					</div>
				</div>
			</div>
			<!-- Alignment (Horizontal & Vertical) -->
			<div class="row g-2 mb-3">
				<div class="col">
					<label class="form-label">Horiz Align</label>
					<div class="btn-group btn-group-sm w-100" role="group" aria-label="Horizontal text alignment"
					     id="inspector-text-align">
						<button type="button" class="btn btn-outline-secondary" data-align="left" title="Align Left"><i
								class="fas fa-align-left"></i></button>
						<button type="button" class="btn btn-outline-secondary" data-align="center" title="Align Center"><i
								class="fas fa-align-center"></i></button>
						<button type="button" class="btn btn-outline-secondary" data-align="right" title="Align Right"><i
								class="fas fa-align-right"></i></button>
						<button type="button" class="btn btn-outline-secondary" data-align="justify" title="Align Justify"><i
								class="fas fa-align-justify"></i></button>
					</div>
				</div>
				<div class="col">
					<label class="form-label">Vert Align</label>
					<div class="btn-group btn-group-sm w-100" role="group" aria-label="Vertical text alignment"
					     id="inspector-text-v-align">
						<button type="button" class="btn btn-outline-secondary" data-align-v="flex-start" title="Align Top"><i
								class="fas fa-arrow-up"></i></button>
						<button type="button" class="btn btn-outline-secondary" data-align-v="center" title="Align Middle"><i
								class="fas fa-bars"></i></button>
						<button type="button" class="btn btn-outline-secondary" data-align-v="flex-end" title="Align Bottom"><i
								class="fas fa-arrow-down"></i></button>
					</div>
				</div>
			</div>
			<!-- Spacing -->
			<div class="row g-2 mb-3">
				<div class="col">
					<label for="inspector-letter-spacing" class="form-label">Letter Spacing</label>
					<input type="number" id="inspector-letter-spacing" class="form-control form-control-sm" step="0.1" value="0">
				</div>
				<div class="col">
					<label for="inspector-line-height" class="form-label">Line Height</label>
					<input type="number" id="inspector-line-height" class="form-control form-control-sm" step="0.1" min="0.5"
					       value="1.3">
				</div>
			</div>
		</div>
	</div>
	
	<!-- Text Color -->
	<div id="inspector-color" class="inspector-section" style="display: none;">
		<div class="section-header">Color</div>
		<div class="section-content">
			<div class="color-input-group mb-2">
				<input type="color" class="form-control form-control-color me-2" id="inspector-fill-color" value="#000000"
				       title="Fill Color">
				<div class="d-flex align-items-center">
					<span class="input-group-text">#</span>
					<input type="text" class="form-control" id="inspector-fill-hex" maxlength="6" placeholder="000000">
				</div>
			</div>
			<label for="inspector-fill-opacity-slider" class="form-label visually-hidden">Fill Opacity</label>
			<div class="d-flex align-items-center visually-hidden">
				<input type="range" class="form-range flex-grow-1 me-2" id="inspector-fill-opacity-slider" min="0" max="1"
				       step="0.01" value="1">
				<input type="number" class="form-control form-control-sm inspector-value-input"
				       id="inspector-fill-opacity-input" min="0" max="100" step="1" value="100">
				<span class="ms-1 inspector-unit-display" id="inspector-fill-opacity-unit">%</span>
			</div>
		</div>
	</div>
	
	<!-- Text Border (Stroke) -->
	<div id="inspector-border" class="inspector-section" style="display: none;">
		<div class="section-header">Border (Stroke)</div>
		<div class="section-content">
			<div class="color-input-group mb-2">
				<input type="color" class="form-control form-control-color me-2" id="inspector-border-color" value="#000000"
				       title="Border Color">
				<div class="d-flex align-items-center">
					<span class="input-group-text">#</span>
					<input type="text" class="form-control" id="inspector-border-hex" maxlength="6" placeholder="000000">
				</div>
			</div>
			<label for="inspector-border-opacity-slider" class="form-label visually-hidden">Border Opacity</label>
			<div class="d-flex align-items-center mb-2 visually-hidden">
				<input type="range" class="form-range flex-grow-1 me-2" id="inspector-border-opacity-slider" min="0" max="1"
				       step="0.01" value="1">
				<input type="number" class="form-control form-control-sm inspector-value-input"
				       id="inspector-border-opacity-input" min="0" max="100" step="1" value="100">
				<span class="ms-1 inspector-unit-display" id="inspector-border-opacity-unit">%</span>
			</div>
			<div class="mb-2">
				<label for="inspector-border-weight-slider" class="form-label">Weight</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-border-weight-slider" min="0" max="50"
					       step="0.5" value="0">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-border-weight-input" min="0" max="50" step="0.5" value="0">
					<span class="ms-1 inspector-unit-display" id="inspector-border-weight-unit"></span>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Text Padding -->
	<div id="inspector-text-padding-section" class="inspector-section" style="display: none;">
		<div class="section-header">Padding</div>
		<div class="section-content">
			<div class="mb-2">
				<label for="inspector-text-padding-slider" class="form-label">Text Padding</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-text-padding-slider" min="0" max="100"
					       step="1" value="0">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-text-padding-input" min="0" max="100" step="1" value="0">
					<span class="ms-1 inspector-unit-display" id="inspector-text-padding-unit"></span>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Text Shading (Shadow) -->
	<div id="inspector-text-shading" class="inspector-section" style="display: none;">
		<div class="section-header">
			<span>Shading (Shadow)</span>
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" role="switch" id="inspector-shading-enabled">
				<label class="form-check-label visually-hidden" for="inspector-shading-enabled">Enable Shading</label>
			</div>
		</div>
		<div class="section-content" style="display: none;">
			<div class="color-input-group mb-2">
				<input type="color" class="form-control form-control-color me-2" id="inspector-shading-color" value="#000000"
				       title="Shadow Color">
				<div class="d-flex align-items-center">
					<span class="input-group-text">#</span>
					<input type="text" class="form-control" id="inspector-shading-hex" maxlength="6" placeholder="000000">
				</div>
			</div>
			<label for="inspector-shading-opacity-slider" class="form-label visually-hidden">Shadow Opacity</label>
			<div class="d-flex align-items-center mb-2 visually-hidden">
				<input type="range" class="form-range flex-grow-1 me-2" id="inspector-shading-opacity-slider" min="0" max="1"
				       step="0.01" value="0.5">
				<input type="number" class="form-control form-control-sm inspector-value-input"
				       id="inspector-shading-opacity-input" min="0" max="100" step="1" value="50">
				<span class="ms-1 inspector-unit-display" id="inspector-shading-opacity-unit">%</span>
			</div>
			<div class="mb-2">
				<label for="inspector-shading-blur-slider" class="form-label">Blur</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-shading-blur-slider" min="0" max="100"
					       step="1" value="0">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-shading-blur-input" min="0" max="100" step="1" value="0">
					<span class="ms-1 inspector-unit-display" id="inspector-shading-blur-unit"></span>
				</div>
			</div>
			<div class="mb-2">
				<label for="inspector-shading-offset-slider" class="form-label">Offset</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-shading-offset-slider" min="0" max="100"
					       step="1" value="5">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-shading-offset-input" min="0" max="100" step="1" value="5">
					<span class="ms-1 inspector-unit-display" id="inspector-shading-offset-unit"></span>
				</div>
			</div>
			<div class="mb-2">
				<label for="inspector-shading-angle-slider" class="form-label">Angle</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-shading-angle-slider" min="-180"
					       max="180" step="1" value="45">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-shading-angle-input" min="-180" max="180" step="1" value="45">
					<span class="ms-1 inspector-unit-display" id="inspector-shading-angle-unit">°</span>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Text Background -->
	<div id="inspector-text-background" class="inspector-section" style="display: none;">
		<div class="section-header">
			<span>Background</span>
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" role="switch" id="inspector-background-enabled">
				<label class="form-check-label visually-hidden" for="inspector-background-enabled">Enable Background</label>
			</div>
		</div>
		<div class="section-content" style="display: none;">
			<div class="color-input-group mb-2">
				<input type="color" class="form-control form-control-color me-2" id="inspector-background-color" value="#FFFFFF"
				       title="Background Color">
				<div class="d-flex align-items-center">
					<span class="input-group-text">#</span>
					<input type="text" class="form-control" id="inspector-background-hex" maxlength="6" placeholder="FFFFFF">
				</div>
			</div>
			<label for="inspector-background-opacity-slider" class="form-label">Background Opacity</label>
			<div class="d-flex align-items-center mb-2">
				<input type="range" class="form-range flex-grow-1 me-2" id="inspector-background-opacity-slider" min="0" max="1"
				       step="0.01" value="1">
				<input type="number" class="form-control form-control-sm inspector-value-input"
				       id="inspector-background-opacity-input" min="0" max="100" step="1" value="100">
				<span class="ms-1 inspector-unit-display" id="inspector-background-opacity-unit">%</span>
			</div>
			<div class="mb-2">
				<label for="inspector-background-radius-slider" class="form-label">Corner Radius</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-background-radius-slider" min="0"
					       max="100" step="0.5" value="0">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-background-radius-input" min="0" max="100" step="0.5" value="0">
					<span class="ms-1 inspector-unit-display" id="inspector-background-radius-unit"></span>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Image Blend Mode -->
	<div id="inspector-image-blend-mode" class="inspector-section" style="display: none;">
		<div class="section-header">Blend Mode</div>
		<div class="section-content">
			<select id="inspector-blend-mode" class="form-select form-select-sm">
				<option value="normal">Normal</option>
				<option value="multiply">Multiply</option>
				<option value="screen">Screen</option>
				<option value="overlay">Overlay</option>
				<option value="darken">Darken</option>
				<option value="lighten">Lighten</option>
				<option value="color-dodge">Color Dodge</option>
				<option value="color-burn">Color Burn</option>
				<option value="hard-light">Hard Light</option>
				<option value="soft-light">Soft Light</option>
				<option value="difference">Difference</option>
				<option value="exclusion">Exclusion</option>
				<option value="hue">Hue</option>
				<option value="saturation">Saturation</option>
				<option value="color">Color</option>
				<option value="luminosity">Luminosity</option>
			</select>
		</div>
	</div>
	
	<!-- Image Filters -->
	<div id="inspector-image-filters" class="inspector-section" style="display: none;">
		<div class="section-header">Filters</div>
		<div class="section-content">
			<!-- Brightness -->
			<div class="mb-2">
				<label for="inspector-filter-brightness-slider" class="form-label">Brightness</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-filter-brightness-slider" min="0"
					       max="200" step="1" value="100">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-filter-brightness-input" min="0" max="200" step="1" value="100">
					<span class="ms-1 inspector-unit-display" id="inspector-filter-brightness-unit"></span>
				</div>
			</div>
			<!-- Contrast -->
			<div class="mb-2">
				<label for="inspector-filter-contrast-slider" class="form-label">Contrast</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-filter-contrast-slider" min="0"
					       max="200" step="1" value="100">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-filter-contrast-input" min="0" max="200" step="1" value="100">
					<span class="ms-1 inspector-unit-display" id="inspector-filter-contrast-unit"></span>
				</div>
			</div>
			<!-- Saturation -->
			<div class="mb-2">
				<label for="inspector-filter-saturation-slider" class="form-label">Saturation</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-filter-saturation-slider" min="0"
					       max="200" step="1" value="100">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-filter-saturation-input" min="0" max="200" step="1" value="100">
					<span class="ms-1 inspector-unit-display" id="inspector-filter-saturation-unit"></span>
				</div>
			</div>
			<!-- Grayscale -->
			<div class="mb-2">
				<label for="inspector-filter-grayscale-slider" class="form-label">Grayscale</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-filter-grayscale-slider" min="0"
					       max="100" step="1" value="0">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-filter-grayscale-input" min="0" max="100" step="1" value="0">
					<span class="ms-1 inspector-unit-display" id="inspector-filter-grayscale-unit"></span>
				</div>
			</div>
			<!-- Sepia -->
			<div class="mb-2">
				<label for="inspector-filter-sepia-slider" class="form-label">Sepia</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-filter-sepia-slider" min="0" max="100"
					       step="1" value="0">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-filter-sepia-input" min="0" max="100" step="1" value="0">
					<span class="ms-1 inspector-unit-display" id="inspector-filter-sepia-unit"></span>
				</div>
			</div>
			<!-- Hue Rotate -->
			<div class="mb-2">
				<label for="inspector-filter-hue-rotate-slider" class="form-label">Hue Rotate</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-filter-hue-rotate-slider" min="0"
					       max="360" step="1" value="0">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-filter-hue-rotate-input" min="0" max="360" step="1" value="0">
					<span class="ms-1 inspector-unit-display" id="inspector-filter-hue-rotate-unit">°</span>
				</div>
			</div>
			<!-- Blur -->
			<div class="mb-2">
				<label for="inspector-filter-blur-slider" class="form-label">Blur</label>
				<div class="d-flex align-items-center">
					<input type="range" class="form-range flex-grow-1 me-2" id="inspector-filter-blur-slider" min="0" max="20"
					       step="0.1" value="0">
					<input type="number" class="form-control form-control-sm inspector-value-input"
					       id="inspector-filter-blur-input" min="0" max="20" step="0.1" value="0">
					<span class="ms-1 inspector-unit-display" id="inspector-filter-blur-unit"></span>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Layer Definition -->
	<div id="inspector-definition" class="inspector-section">
		<div class="section-header">Definition</div>
		<div class="section-content">
			<select id="inspector-layer-definition" class="form-select form-select-sm">
				<option value="general">General</option>
				<option value="back_cover_text">Back Cover Text</option>
				<option value="back_cover_title">Back Cover Title</option>
				<option value="back_cover_image">Back Cover Image</option>
				<option value="spine_text">Spine Text</option>
				<option value="cover_title">Cover Title</option>
				<option value="cover_text">Cover Text</option>
				<option value="cover_image">Cover Image</option>
				<option value="cover_background">Cover Background</option>
			</select>
		</div>
	</div>
</div>
