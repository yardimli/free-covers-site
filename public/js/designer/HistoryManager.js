// free-cover-designer/js/HistoryManager.js
class HistoryManager {
	constructor(layerManager, canvasManager, options = {}) { // Added canvasManager
		this.layerManager = layerManager;
		this.canvasManager = canvasManager; // Store canvasManager instance
		this.historyStack = [];
		this.redoStack = [];
		this.maxHistory = options.maxHistory || 50;
		this.onUpdate = options.onUpdate || (() => {});
		this.isRestoring = false; // Flag to prevent re-saving during undo/redo
	}
	
	saveState() {
		if (this.isRestoring) return; // Don't save state if we are currently restoring one
		
		// --- MODIFIED: Include canvas settings in state ---
		const currentState = {
			layers: this.layerManager.getLayers(), // Get a deep copy of layers
			canvasSettings: this.canvasManager.getCanvasBackgroundSettings() // Get canvas background settings
		};
		// --- END MODIFIED ---
		
		// Avoid saving identical consecutive states
		if (this.historyStack.length > 0) {
			const lastState = this.historyStack[this.historyStack.length - 1];
			// Simple stringify for comparison. For complex states, a more robust deep equal might be needed.
			if (JSON.stringify(lastState) === JSON.stringify(currentState)) {
				// console.log("History: State unchanged, not saving.");
				return;
			}
		}
		
		this.historyStack.push(currentState);
		if (this.historyStack.length > this.maxHistory) {
			this.historyStack.shift(); // Remove oldest state
		}
		this.redoStack = []; // Clear redo stack on new action
		this.onUpdate(); // Notify App.js to update button states
		// console.log("History: State saved. Stack size:", this.historyStack.length);
	}
	
	undo() {
		if (this.historyStack.length <= 1) return; // Keep the initial state
		
		this.isRestoring = true;
		const currentState = this.historyStack.pop();
		this.redoStack.push(currentState);
		
		const prevState = this.historyStack[this.historyStack.length - 1];
		this._applyState(prevState);
		
		this.isRestoring = false;
		this.onUpdate();
		// console.log("History: Undo. Stack size:", this.historyStack.length, "Redo stack:", this.redoStack.length);
	}
	
	redo() {
		if (this.redoStack.length === 0) return;
		
		this.isRestoring = true;
		const nextState = this.redoStack.pop();
		this.historyStack.push(nextState);
		this._applyState(nextState);
		
		this.isRestoring = false;
		this.onUpdate();
		// console.log("History: Redo. Stack size:", this.historyStack.length, "Redo stack:", this.redoStack.length);
	}
	
	_applyState(state) {
		if (!state) return;
		// --- MODIFIED: Apply canvas settings ---
		this.layerManager.setLayers(state.layers, false); // false to indicate it's not a template merge
		if (state.canvasSettings) {
			this.canvasManager.setCanvasBackgroundSettings(state.canvasSettings, false); // false to prevent re-saving history
		}
		// --- END MODIFIED ---
	}
	
	canUndo() {
		return this.historyStack.length > 1; // Can undo if more than the initial state
	}
	
	canRedo() {
		return this.redoStack.length > 0;
	}
	
	clear() {
		this.historyStack = [];
		this.redoStack = [];
		// Consider saving an initial blank state here if needed, or let App.js handle initial save
		this.onUpdate();
	}
}
