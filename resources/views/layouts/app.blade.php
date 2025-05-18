<!DOCTYPE html>
<html lang="en">
<head>
	<!-- Required meta tags -->
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<!-- Bootstrap CSS -->
	<link href="{{ asset('template/assets/vendors/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
	<link href="{{ asset('template/assets/vendors/slick/slick.css') }}" rel="stylesheet" />
	<link href="{{ asset('template/assets/vendors/slick/slick-theme.css') }}" rel="stylesheet" />
	<link href="{{ asset('template/assets/vendors/elagent-icon/style.css') }}" rel="stylesheet" />
	<link href="{{ asset('template/assets/vendors/themify-icon/themify-icons.css') }}" rel="stylesheet" />
	<link href="{{ asset('template/assets/vendors/animation/animate.css') }}" rel="stylesheet" />
	<link href="{{ asset('template/assets/vendors/font-awesome/css/all.min.css') }}" rel="stylesheet" />
	<link href="{{ asset('template/assets/vendors/swiper/swiper.min.css') }}" rel="stylesheet" />
	<link href="{{ asset('template/assets/vendors/icomoon/style.css') }}" rel="stylesheet" />
	<link href="{{ asset('template/assets/css/style.css') }}" rel="stylesheet" />
	<link href="{{ asset('template/assets/css/responsive.css') }}" rel="stylesheet" />
	<link href="{{ asset('/css/style.css') }}" rel="stylesheet" />
	
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
	<link rel="manifest" href="{{ asset('images/site.webmanifest') }}">
	
	<title>@yield('title', 'Free Kindle Covers')</title>
	
	@stack('styles')
</head>
<body data-scroll-animation="true">
<div class="body_wrapper">
	<div class="click_capture"></div>
	
	@include('partials.header')
	
	@yield('content')
	
	@include('partials.footer', ['footerClass' => $footerClass ?? ''])

</div>
<!-- Back to top button -->
<a id="back-to-top" title="Back to Top"></a>
<!-- Optional JavaScript; choose one of the two! -->
<script src="{{ asset('template/assets/js/jquery-3.6.0.min.js') }}"></script>
<!-- Option 2: Separate Popper and Bootstrap JS -->
<script src="{{ asset('template/assets/vendors/bootstrap/js/popper.min.js') }}"></script>
<script src="{{ asset('template/assets/vendors/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('template/assets/vendors/parallax/parallax.js') }}"></script>
<script src="{{ asset('template/assets/vendors/slick/slick.min.js') }}"></script>
<script src="{{ asset('template/assets/js/jquery.counterup.min.js') }}"></script>
<script src="{{ asset('template/assets/js/jquery.waypoints.min.js') }}"></script>
<script src="{{ asset('template/assets/vendors/swiper/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('template/assets/vendors/wow/wow.min.js') }}"></script>
@stack('scripts') {{-- For page-specific scripts --}}
<script src="{{ asset('js/custom.js') }}"></script>
<script src="{{ asset('js/script.js') }}"></script>
</body>
</html>
