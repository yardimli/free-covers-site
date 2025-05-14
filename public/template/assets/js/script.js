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
	
	$('a[data-bs-toggle="pill"]').on("shown.bs.tab", function (e) {
		if ($(".slick_slider_tab,.best_slider,.slick_slider_author").length) {
			$(".slick_slider_tab,.best_slider,.slick_slider_author").slick(
				"setPosition"
			);
		}
	});


// Initialize Slick for the first tab if it's present on page load
	var $firstActivePane = $('#pills-tabContent-two .tab-pane.active');
	if ($firstActivePane.length && $firstActivePane.find(".slick_slider_tab").length && typeof $.fn.slick === 'function') {
		if (!$firstActivePane.find(".slick_slider_tab").hasClass('slick-initialized')) {
			$firstActivePane.find(".slick_slider_tab").slick({
				infinite: true,
				slidesToShow: 3, // Changed from 2 to 3
				slidesToScroll: 1,
				responsive: [
					{breakpoint: 1199, settings: {slidesToShow: 2}},
					{breakpoint: 767, settings: {slidesToShow: 1}},
				],
			});
		}
	}


// Initialize tooltips for initially loaded content
	if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip === 'function') {
		var initialTooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
		initialTooltipTriggerList.map(function (tooltipTriggerEl) {
			if (!bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
				return new bootstrap.Tooltip(tooltipTriggerEl);
			}
			return bootstrap.Tooltip.getInstance(tooltipTriggerEl);
		});
	}

// AJAX loading for genre tabs in "Browse By Genres" section
	$('#pills-tab-one a[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
		var $tabLink = $(e.target);
		var genreSlug = $tabLink.data('genre-slug');
		var genreName = $tabLink.data('genre-name');
		var targetPaneId = $tabLink.attr('data-bs-target');
		var $targetPane = $(targetPaneId);
		
		if (genreSlug && $targetPane.length && $targetPane.data('loaded') === false) {
			$targetPane.html(`
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading covers for ${genreName}...</p>
                </div>
            `);
			
			var genreName = $tabLink.data('genre-name'); // Ensure genreName is captured
			var ajaxUrl = '/api/genres/' + genreSlug + '/covers?name=' + encodeURIComponent(genreName);
			
			$.ajax({
				url: ajaxUrl,
				type: 'GET',
				dataType: 'json',
				success: function (response) {
					$targetPane.empty();
					if (response.covers && response.covers.length > 0) {
						var sliderHtml = '<div class="tab_slider_content slick_slider_tab">';
						for (var i = 0; i < response.covers.length; i += 3) { // Changed from i += 2 to i += 3
							sliderHtml += '<div class="item">'; // Start a new slick-slide item
							
							// Process up to 3 covers in each slider item
							for (var j = 0; j < 2; j++) {
								if (i + j < response.covers.length) {
									var cover = response.covers[i + j];
									var mockupSrc = '/storage/' + cover.mockup;
									
									// Add bottom margin except for the last cover in a group
									var marginClass = (j < 2 && i + j + 1 < response.covers.length) ? 'mb-3' : '';
									
									sliderHtml += `
                    <div class="bj_new_pr_item ${marginClass}">
                        <a href="${cover.show_url}" class="img">
                            <img src="${mockupSrc}" alt="${cover.name}" />
                        </a>
                        <div class="bj_new_pr_content_two">
                            <div class="d-flex justify-content-between">
                                <a href="${cover.show_url}">
                                    <h5>#${cover.id}</h5>
                                </a>
                            </div>
                            <div class="writer_name">
                                ${cover.limited_name}
                            </div>
                            <a href="#" class="bj_theme_btn">Customize</a>
                        </div>
                    </div>`;
								}
							}
							
							sliderHtml += '</div>'; // End slick-slide item
						}
						sliderHtml += '</div>'; // End tab_slider_content
						$targetPane.html(sliderHtml);
						
						// Re-initialize Slick Slider for the new content
						if (typeof $.fn.slick === 'function') {
							$targetPane.find(".slick_slider_tab").slick({
								infinite: true,
								slidesToShow: 3, // Changed from 2 to 3
								slidesToScroll: 1,
								responsive: [
									{breakpoint: 1199, settings: {slidesToShow: 2}},
									{breakpoint: 767, settings: {slidesToShow: 1}},
								],
							});
						}
						
						// Re-initialize tooltips for new content
						if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip === 'function') {
							var tooltipTriggerList = [].slice.call($targetPane[0].querySelectorAll('[data-bs-toggle="tooltip"]'));
							tooltipTriggerList.map(function (tooltipTriggerEl) {
								if (!bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
									return new bootstrap.Tooltip(tooltipTriggerEl);
								}
								return bootstrap.Tooltip.getInstance(tooltipTriggerEl);
							});
						}
					} else {
						$targetPane.html(`<p class="p-3">No covers found for ${genreName}.</p>`);
					}
					$targetPane.data('loaded', true);
				},
				error: function (xhr, status, error) {
					console.error("Error fetching covers for genre " + genreSlug + ":", xhr.responseText);
					$targetPane.html(`<p class="p-3 text-danger">Could not load covers for ${genreName}. Please try again later.</p>`);
				}
			});
		}
	});

});
