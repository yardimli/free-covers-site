// public/js/admin/utils.js
window.AppAdmin = window.AppAdmin || {};

AppAdmin.Utils = (function () {
	function showAlert(message, type = 'success') {
		const alertId = 'alert-' + Date.now();
		const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
		$('#alert-messages-container').append(alertHtml);
		setTimeout(() => {
			$('#' + alertId).fadeOut(500, function () {
				$(this).remove();
			});
		}, 5000);
	}
	
	function escapeHtml(unsafe) {
		if (unsafe === null || typeof unsafe === 'undefined') return '';
		return String(unsafe)
			.replace(/&/g, "&")
			.replace(/</g, "<")
			.replace(/>/g, ">")
			.replace(/"/g, "\"")
			.replace(/'/g, "'");
	}
	
	function capitalizeFirstLetter(string) {
		if (!string) return '';
		return string.charAt(0).toUpperCase() + string.slice(1);
	}
	
	function deriveNameFromFilename(filename) {
		let name = filename;
		const lastDot = filename.lastIndexOf('.');
		if (lastDot > 0) {
			name = filename.substring(0, lastDot);
		}
		name = name.replace(/[-_]/g, ' ');
		name = name.replace(/\s+/g, ' ').trim();
		return capitalizeFirstLetter(name);
	}
	
	function renderKeywords(keywords) {
		if (!keywords || !Array.isArray(keywords) || keywords.length === 0) return '';
		const escapedKeywords = keywords.map(k => typeof k === 'string' ? escapeHtml(k.trim()) : '');
		return `<div class="keywords-list">${escapedKeywords.filter(k => k).map(k => `<span>${k}</span>`).join('')}</div>`;
	}
	
	return {
		showAlert,
		escapeHtml,
		capitalizeFirstLetter,
		deriveNameFromFilename,
		renderKeywords
	};
})();
