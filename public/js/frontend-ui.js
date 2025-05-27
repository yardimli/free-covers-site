function showToast(title, message, bgClass) {
  var toastEl = document.getElementById('actionToast');
  if (toastEl) {
    var toastHeader = toastEl.querySelector('.toast-header');
    var toastBody = toastEl.querySelector('.toast-body');
    
    toastHeader.querySelector('strong').textContent = title;
    // Remove existing bg classes from header
    toastHeader.className = 'toast-header'; // Reset
    // if(bgClass) { // Optional: color header
    //toastHeader.classList.add(bgClass);
    //toastHeader.classList.add('text-white'); // Optional: white text on colored header
    // }
    toastBody.textContent = message;
    
    var toast = new bootstrap.Toast(toastEl);
    toast.show();
  }
}

(function ($) {
  "use strict";
  


  //*============ background color js ==============*/
  $("[data-bg-color]").each(function () {
    var bg_color = $(this).data("bg-color");
    $(this).css({
      "background-color": bg_color,
    });
  });

  //*============ background image js ==============*/
  $("[data-bg-image]").each(function () {
    var bg = $(this).data("bg-image");
    $(this).css({
      background: "no-repeat center 0/cover url(" + bg + ")",
    });
  });
  
  //parallax js

  if ($(".banner_animation_03").length > 0) {
    $(".banner_animation_03").css({"opacity": 1});
    $(".banner_animation_03").parallax({
      scalarX: 7.0,
      scalarY: 10.0,
    });
  }
  

  var $window = $(window);
  var didScroll,
    lastScrollTop = 0,
    delta = 5,
    $mainNav = $("#header"),
    $mainNavHeight = $mainNav.outerHeight(),
    scrollTop;

  $window.on("scroll", function () {
    didScroll = true;
    scrollTop = $(this).scrollTop();
  });

  setInterval(function () {
    if (didScroll) {
      hasScrolled();
      didScroll = false;
    }
  }, 200);

  function hasScrolled() {
    if (Math.abs(lastScrollTop - scrollTop) <= delta) {
      return;
    }
    if (scrollTop > lastScrollTop && scrollTop > $mainNavHeight) {
      $mainNav.css("top", -$mainNavHeight);
    } else {
      if (scrollTop + $(window).height() < $(document).height()) {
        $mainNav.css("top", 0);
      }
    }
    lastScrollTop = scrollTop;
  }

  // === Back to Top Button
  var back_top_btn = $("#back-to-top");

  $(window).scroll(function () {
    if ($(window).scrollTop() > 300) {
      back_top_btn.addClass("show");
    } else {
      back_top_btn.removeClass("show");
    }
  });

  back_top_btn.on("click", function (e) {
    e.preventDefault();
    $("html, body").animate({ scrollTop: 0 }, "300");
  });

  //sticky header
  function navbarFixed() {
    if ($("#header").length) {
      $(window).scroll(function () {
        var scroll = $(window).scrollTop();
        if (scroll) {
          $("#header").addClass("fixed-header");
        } else {
          $("#header").removeClass("fixed-header");
        }
      });
    }
  }
  navbarFixed();


  /*--------------- mobile dropdown js--------*/
  if ($(window).width() < 991) {
    function active_dropdown2() {
      $("li.submenu > a").after(
        '<span class="ti-angle-down mobile_dropdown_icon"/>'
      );
      $(".menu > li .mobile_dropdown_icon").on("click", function () {
        $(this).parent().find("> ul").first().slideToggle(300);
        $(this).parent().siblings().find("> ul").hide(300);
        return false;
      });
    }
    active_dropdown2();
  }

  if ($(".mySwiper").length) {
    var swiper = new Swiper(".mySwiper", {
      pagination: {
        el: ".swiper-pagination",
        type: "progressbar",
      },
      navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
      },
    });
  }

  if ($(".search").length) {
    $(".search a").on("click", function () {
      if ($(this).parent().hasClass("open")) {
        $(this).parent().removeClass("open");
      } else {
        $(this).parent().addClass("open");
        setTimeout(function () {
          $(".menu-search-form .form-control").focus();
        }, 500);
      }

      return false;
    });
  }
  
  /*--------- WOW js-----------*/

  function bodyScrollAnimation() {
    var scrollAnimate = $("body").data("scroll-animation");
    if (scrollAnimate === true) {
      new WOW({}).init();
    }
  }
  bodyScrollAnimation();
  

  $(".toggle-password").click(function () {
    $(this).toggleClass("fa-eye fa-eye-slash");
    var input = $($(this).attr("data-toggleTarget"));
    if (input.attr("type") == "password") {
      input.attr("type", "text");
    } else {
      input.attr("type", "password");
    }
  });

  $(".addres_sidebar_cart_trigger").on("click", function () {
    $(".side_menu").addClass("menu-opened");
    $("body").removeClass("menu-is-closed").addClass("menu-is-opened");
  });

  $(".close_nav,.close_sidebar").on("click", function (e) {
    if ($(".side_menu").hasClass("menu-opened")) {
      $(".side_menu").removeClass("menu-opened");
      $("body").removeClass("menu-is-opened");
    } else {
      $(".side_menu").addClass("menu-opened");
    }
  });

  $(".click_capture").on("click", function () {
    $("body").removeClass("menu-is-opened").addClass("menu-is-closed");
    $(".side_menu").removeClass("menu-opened");
  });

  $("#track_view_more_btn").on("click", function (e) {
    e.preventDefault();
    $(this).css("display", "none");
    $(".single_track_step.second_step,.single_track_step.first_step").css(
      "display",
      "flex"
    );
  });
  

  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Global function for tab arrow controls
  function initTabArrowControls(tabGroupId) {
    const $tabList = $(`#${tabGroupId}.nav-pills`);
    const $tabs = $tabList.find(".nav-link");
    const $prevArrow = $(".prev-tab");
    const $nextArrow = $(".next-tab");

    function updateArrowState() {
      const $activeTab = $tabs.filter(".active");
      $prevArrow.prop("disabled", $activeTab.is(":first-child"));
      $nextArrow.prop("disabled", $activeTab.is(":last-child"));
    }

    function switchTab(direction) {
      const $activeTab = $tabs.filter(".active");

      let $targetTab;
      if (direction === "next") {
        $targetTab = $activeTab.next(".nav-link");
      } else {
        $targetTab = $activeTab.prev(".nav-link");
      }

      if ($targetTab.length) {
        $targetTab.tab("show");
        updateArrowState();
      }
    }

    $prevArrow.on("click", () => switchTab("prev"));
    $nextArrow.on("click", () => switchTab("next"));

    // Update arrow state on tab change
    $('a[data-bs-toggle="pill"]').on("shown.bs.tab", updateArrowState);

    // Initial arrow state
    updateArrowState();
  }

  // Initialize the tab arrow controls

  initTabArrowControls("pills-tab-two");
  
  function activateTabByHash() {
    var hash = window.location.hash;
    if (hash) {
      var tabId = hash + "-tab"; // Add '-tab' to match the tab id
      var $tab = $(tabId);

      if ($tab.length) {
        // Activate the tab
        var tabTrigger = new bootstrap.Tab($tab[0]);
        tabTrigger.show();

        // Add a small delay to ensure the tab content is visible
        setTimeout(function () {
          // Smooth scroll to the tab content
          $("html, body").animate(
            {
              scrollTop: $(hash).offset().top,
            },
            300
          ); // 800ms for smooth scroll effect
        }, 300); // 100ms delay to ensure content visibility
      }
    }
  }

  // Run on page load
  activateTabByHash();

  // Update tab when hash changes
  $(window).on("hashchange", function () {
    activateTabByHash();
  });

  $('a[href="#product_review"]').click(function () {
    activateTabByHash();
  });
})(jQuery);
