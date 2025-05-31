{{-- free-cover-site/resources/views/designer/partials/backgroundSettingsModal.blade.php --}}
<div class="modal fade" id="backgroundSettingsModal" tabindex="-1" aria-labelledby="backgroundSettingsModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="backgroundSettingsModalLabel">Canvas Background Settings</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label for="canvasBackgroundColorPicker" class="form-label">Background Color</label>
					<input type="color" class="form-control form-control-color w-100" id="canvasBackgroundColorPicker" value="#FFFFFF" title="Choose background color">
				</div>
				<div class="form-check mb-3">
					<input class="form-check-input" type="checkbox" id="canvasTransparentBackgroundCheckbox">
					<label class="form-check-label" for="canvasTransparentBackgroundCheckbox">
						Transparent Background
					</label>
				</div>
				<small class="form-text text-muted">If "Transparent Background" is checked, the color selection above will be ignored for the canvas, and the canvas will use a checkered pattern for display. Exports will be transparent.</small>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="applyBackgroundSettingsBtn">Apply</button>
			</div>
		</div>
	</div>
</div>
