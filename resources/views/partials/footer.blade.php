<footer class="bj_footer_area {{ $footerClass }}" data-bg-color="#001F58">
	<img class="footer_bg_img" src="{{ asset('template/assets/img/home/footer_img.jpg') }}" alt="" />
	<div class="footer_top">
		<div class="container">
			<div class="row">
				<div class="col-lg-2 col-sm-6">
					<div class="f_widget link_widget wow fadeInUp" data-wow-delay="0.2s">
						<h2 class="f_widget_title">Company</h2>
						<ul class="list-unstyled list">
							<li><a href="{{ route('about') }}">About Us</a></li>
							<li><a href="{{ route('contact.show') }}">Contact us</a></li>
							<li><a href="{{ route('blog.index') }}">Blog</a></li>
						</ul>
					</div>
				</div>
				<div class="col-lg-3 col-sm-6">
					<div class="f_widget link_widget ps-lg-5 wow fadeInUp" data-wow-delay="0.3s">
						<h2 class="f_widget_title">Services</h2>
						<ul class="list-unstyled list">
							<li><a href="{{ route('shop.index') }}">Browse Covers</a></li>
							<li><a href="#">Wishlist</a></li>
						</ul>
					</div>
				</div>
				<div class="col-lg-3 col-sm-6">
					<div class="f_widget link_widget ps-lg-5 wow fadeInUp" data-wow-delay="0.3s">
						<h2 class="f_widget_title">Account</h2>
						<ul class="list-unstyled list">
							<li><a href="{{ route('login') }}">Login</a></li>
							<li><a href="{{ route('register') }}">Register</a></li>
							<li><a href="{{ route('password.request') }}">Forgot Password</a></li>
							<li><a href="#">Dashboard</a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="footer_middle wow fadeInUp" data-wow-delay="0.5s">
		<div class="container">
			<div class="row">
				<div class="col-lg-4 col-md-12 text-center text-lg-start">
					<a href="{{ route('home') }}" class="f_logo">
						<img src="{{ asset('template/assets/img/home/logo-light.png') }}" alt="f_logo" style="max-width:160px;" />
					</a>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="footer_social d-flex justify-content-lg-center">
						Follow Us:
						<ul class="list-unstyled f_social_round">
							<li>
								<a href="#"><i class="fa-brands fa-facebook-f"></i></a>
							</li>
							<li>
								<a href="#"><i class="fa-brands fa-instagram"></i></a>
							</li>
							<li>
								<a href="#"><i class="fa-brands fa-twitter"></i></a>
							</li>
							<li>
								<a href="#"><i class="fa-brands fa-youtube"></i></a>
							</li>
						</ul>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="footer_terms text-end">
						<a href="{{ route('terms') }}">Terms of service</a>
						<a href="{{ route('privacy') }}">Privacy policy</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="footer_bottom text-center wow fadeInUp" data-wow-delay="0.6s">
		<p>© {{ date('Y') }} Free Kindle Covers. All Rights Reserved</p>
	</div>
</footer>
