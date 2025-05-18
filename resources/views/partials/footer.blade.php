<footer class="bj_footer_area {{ $footerClass }}" data-bg-color="#001F58">
	<img class="footer_bg_img" src="{{ asset('template/assets/img/home/footer_img.jpg') }}" alt="" />
	<div class="footer_top">
		<div class="container">
			<div class="row">
				<div class="col-lg-2 col-sm-6">
					<div class="f_widget link_widget wow fadeInUp" data-wow-delay="0.2s">
						<h2 class="f_widget_title">Company</h2>
						<ul class="list-unstyled list">
							<li><a href="#">About Us</a></li>
							<li><a href="#">Contact us</a></li>
							<li><a href="{{ route('blog.index') }}">Blog</a></li>
							<li><a href="#">Author</a></li>
							<li><a href="#">Books</a></li>
						</ul>
					</div>
				</div>
				<div class="col-lg-3 col-sm-6">
					<div class="f_widget link_widget ps-lg-5 wow fadeInUp" data-wow-delay="0.3s">
						<h2 class="f_widget_title">Services</h2>
						<ul class="list-unstyled list">
							<li><a href="{{ route('shop.index') }}">Shop</a></li>
							<li><a href="#">Order</a></li>
							<li><a href="#">Cart</a></li>
							<li><a href="#">Checkout</a></li>
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
							<li><a href="#">Profile</a></li>
							<li><a href="#">Dashboard</a></li>
						</ul>
					</div>
				</div>
				<div class="col-lg-4 col-sm-6">
					<div class="f_widget link_widget ps-lg-5 wow fadeInUp" data-wow-delay="0.4s">
						<h2 class="f_widget_title">Newsletter</h2>
						<p>
							Stay updated with our latest designs and freebies
						</p>
						<form action="#" class="d-flex justify-content-end footer-search">
							<input type="email" class="form-control email-form" placeholder="Your email address" />
							<button class="bj_theme_btn btn-Subscribe" type="submit">
								<i class="arrow_right"></i>
							</button>
						</form>
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
						<a href="#">Terms of service</a>
						<a href="#">Privacy policy</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="footer_bottom text-center wow fadeInUp" data-wow-delay="0.6s">
		<p>© {{ date('Y') }} Free Kindle Covers. All Rights Reserved</p>
	</div>
</footer>
