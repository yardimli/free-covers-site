{{-- resources/views/partials/faq_breadcrumb.blade.php --}}
<section class="breadcrumb_area" data-bg-color="#F6F8FB">
	<img class="p_absolute bl_left" src="{{ asset('template/assets/img/v.svg') }}" alt="">
	<img class="p_absolute bl_right" src="{{ asset('template/assets/img/home_one/banner_bg.png') }}" alt="">
	<div class="container">
		<div class="breadcrumb_content">
			<h1 class="f_p f_700 f_size_50 w_color l_height50 mb_20">FAQ</h1>
			<ol class="breadcrumb d-flex justify-content-center">
				<li><a href="{{ route('home') }}">Home</a></li>
				<li class="active">FAQ</li>
			</ol>
		</div>
	</div>
</section>
