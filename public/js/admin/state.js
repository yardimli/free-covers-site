// public/js/admin/state.js
window.AppAdmin = window.AppAdmin || {};

AppAdmin.State = (function() {
	let currentItemStates = {};
	
	function getCurrentState(itemType) {
		return currentItemStates[itemType] || { page: 1, search: '', coverTypeId: '' };
	}
	
	function setCurrentState(itemType, page, search, coverTypeId) {
		currentItemStates[itemType] = { page, search, coverTypeId };
	}
	
	return {
		getCurrentState,
		setCurrentState
	};
})();
