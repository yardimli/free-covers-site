<header class="header_area header_relative header_blue">
	<nav class="navbar navbar-expand-lg menu_one menu_white" id="header">
		<div class="container">
			<a class="navbar-brand sticky_logo" href="{{ route('home') }}">
				<img src="{{ asset('template/assets/img/home/logo-dark.png') }}" alt="logo" />
			</a>
			<button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="menu_toggle">
                    <span class="hamburger"> <span></span> <span></span> <span></span> </span>
                    <span class="hamburger-cross"> <span></span> <span></span> </span>
                </span>
			</button>
			<div class="collapse navbar-collapse justify-content-between" id="navbarSupportedContent">
				<ul class="navbar-nav menu w_menu ms-auto me-auto">
					<li class="nav-item active">
						<a class="nav-link" href="{{ route('home') }}" role="button" aria-haspopup="true" aria-expanded="false"> HOME </a>
					</li>
					<li class="nav-item">
						<a class="nav-link " href="{{ route('shop.index') }}" role="button" aria-haspopup="true" aria-expanded="false"> Browse Covers </a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="{{ route('blog.index') }}" role="button" aria-haspopup="true" aria-expanded="false"> Blog </a>
					</li>
				</ul>
				<div class="alter_nav">
					<ul class="navbar-nav search_cart menu">
						<li class="nav-item search">
							<a class="nav-link search-btn" href="javascript:void(0);"><i class="icon-search"></i></a>
							<form action="#" method="get" class="menu-search-form">
								<div class="input-group">
									<input type="search" class="form-control" placeholder="Search here.." />
									<button type="submit"> <i class="ti-arrow-right"></i> </button>
								</div>
							</form>
						</li>
						<li class="nav-item shpping-cart dropdown submenu">
							<a class="cart-btn nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<i class="icon-shopping-basket"></i><span class="num">2</span> {{-- Dynamic cart count later --}}
							</a>
							<ul class="dropdown-menu">
								{{-- Static cart items for now, make dynamic later --}}
								<li class="cart-single-item clearfix">
									<div class="cart-img"> <img src="{{ asset('template/assets/img/cart1.jpg') }}" alt="styler" /> </div>
									<div class="cart-content text-left">
										<p class="cart-title"> <a href="#">Pale pink and black buttoned dress</a> </p>
										<p><del>$400.00</del> - $350.00</p>
									</div>
									<div class="cart-remove"> <a href="#" class="action"><span class="ti-close"></span></a> </div>
								</li>
								<li class="cart-single-item clearfix">
									<div class="cart-img"> <img src="{{ asset('template/assets/img/cart1.jpg') }}" alt="styler" /> </div>
									<div class="cart-content text-left">
										<p class="cart-title"> <a href="#">Vera bradley lug- gage large duffel</a> </p>
										<p>$350.00</p>
									</div>
									<div class="cart-remove"> <a href="#" class="action"><span class="ti-close"></span></a> </div>
								</li>
								<li class="cart_f">
									<div class="cart-pricing">
										<p class="total"> Subtotal :<span class="p-total text-end">$358.00</span> </p>
									</div>
									<div class="cart-button text-center">
										<a href="{{-- route('cart') --}}#" class="btn btn-cart get_btn pink">View Cart</a>
										<a href="{{-- route('checkout') --}}#" class="btn btn-cart get_btn dark">Checkout</a>
									</div>
								</li>
							</ul>
						</li>
						<li class="nav-item user ms-3">
							<a class="nav-link" href="{{-- route('my-account') --}}#"><i class="icon-user"></i></a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</nav>
</header>
