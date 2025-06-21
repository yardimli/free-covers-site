$(document).ready(function () {
	if ($(".slick_slider").length) {
		$(".slick_slider").slick({});
	}
	
	if ($(".best_slider").length) {
		$(".best_slider").slick({
			infinite: true,
			slidesToShow: 4,
			slidesToScroll: 1,
			responsive: [
				{
					breakpoint: 1199,
					settings: {
						slidesToShow: 3,
					},
				},
				{
					breakpoint: 991,
					settings: {
						slidesToShow: 2,
					},
				},
				{
					breakpoint: 767,
					settings: {
						slidesToShow: 1,
					},
				},
			],
		});
	}
	
	if ($(".testimonial_slider_three").length) {
		$(".testimonial_slider_three").slick({
			dots: true,
			arrows: false,
			slidesToShow: 3,
			slidesToScroll: 1,
			infinite: true,
			loop: true,
			centerMode: true,
			centerPadding: "0px",
			responsive: true,
			responsive: [
				{
					breakpoint: 992,
					settings: {
						slidesToShow: 3,
						centerMode: true,
						centerPadding: "0px",
					},
				},
				{
					breakpoint: 991,
					settings: {
						slidesToShow: 2,
						centerMode: false,
					},
				},
				{
					breakpoint: 767,
					settings: {
						slidesToShow: 1,
						centerMode: false,
					},
				},
			],
		});
	}
	
	// Removed tab-related slick re-initialization logic as tabs are gone.
	
	// Initialize tooltips for any content loaded initially
	if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip === 'function') {
		var initialTooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
		initialTooltipTriggerList.map(function (tooltipTriggerEl) {
			if (!bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
				return new bootstrap.Tooltip(tooltipTriggerEl);
			}
			return bootstrap.Tooltip.getInstance(tooltipTriggerEl);
		});
	}
	
	// REMOVED: AJAX loading logic for genre tabs as this functionality no longer exists on the homepage.
});
