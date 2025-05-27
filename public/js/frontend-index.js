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
				{ breakpoint: 1199, settings: { slidesToShow: 3, }, },
				{ breakpoint: 991, settings: { slidesToShow: 2, }, },
				{ breakpoint: 767, settings: { slidesToShow: 1, }, },
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
				{ breakpoint: 992, settings: { slidesToShow: 3, centerMode: true, centerPadding: "0px", }, },
				{ breakpoint: 991, settings: { slidesToShow: 2, centerMode: false, }, },
				{ breakpoint: 767, settings: { slidesToShow: 1, centerMode: false, }, },
			],
		});
	}
	
	$('a[data-bs-toggle="pill"]').on("shown.bs.tab", function (e) {
		// Removed .slick_slider_tab from this condition and call
		if ($(".best_slider,.slick_slider_author").length) {
			$(".best_slider,.slick_slider_author").slick(
				"setPosition"
			);
		}
	});
	
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
	
	// REMOVED: Slick initialization for the first tab as it's no longer a slider
	// var $firstActivePane = $('#pills-tabContent-two .tab-pane.active');
	// if ($firstActivePane.length && $firstActivePane.find(".slick_slider_tab").length && typeof $.fn.slick === 'function') { ... }
	
	// AJAX loading for genre tabs in "Browse By Genres" section
	$('#pills-tab-one a[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
		var $tabLink = $(e.target);
		var genreSlug = $tabLink.data('genre-slug');
		var genreName = $tabLink.data('genre-name'); // Ensure genreName is captured
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
			var ajaxUrl = '/api/genres/' + genreSlug + '/covers?name=' + encodeURIComponent(genreName);
			
			$.ajax({
				url: ajaxUrl,
				type: 'GET',
				dataType: 'json',
				success: function (response) {
					$targetPane.empty();
					if (response.covers && response.covers.length > 0) {
						var coversHtml = '<div class="covers-grid-container"><div class="row">'; // Bootstrap row for grid
						for (var i = 0; i < response.covers.length; i++) {
							var cover = response.covers[i];
							var mockupSrc = cover.mockup_2d_path;
							var overlaySrc = cover.random_template_overlay_url; // This is a full URL
							
							coversHtml += `
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="bj_new_pr_item" style="min-height: 500px;">
                                    <a href="${cover.show_url}" class="img cover-image-container">
                                        <img src="${mockupSrc}" alt="${cover.name}" class="cover-mockup-image" />
                                        ${overlaySrc ? `<img src="${overlaySrc}" alt="Template Overlay" class="template-overlay-image" />` : ''}
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
                                </div>
                            </div>`;
						}
						coversHtml += '</div></div>'; // Close row and covers-grid-container
						$targetPane.html(coversHtml);
						
						// REMOVED: Slick Slider re-initialization for the new content
						// if (typeof $.fn.slick === 'function') { ... }
						
						// START: Add the "Show all covers in category" button
						var shopUrl = '/browse-covers?category=' + encodeURIComponent(genreName);
						var buttonHtml = `
                        <div class="text-center mt-4">
                            <a href="${shopUrl}" class="bj_theme_btn strock_btn blue_strock_btn">
                                Show all covers in ${genreName}
                            </a>
                        </div>`;
						$targetPane.append(buttonHtml);
						
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
