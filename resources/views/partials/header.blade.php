<header class="header_area header_relative header_blue">
	<nav class="navbar navbar-expand-lg menu_one menu_white" id="header">
		<div class="container">
			<a class="navbar-brand sticky_logo" href="{{ route('home') }}">
				<img src="{{ asset('template/assets/img/home/logo-dark.png') }}" alt="logo"/>
			</a>
			<button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse"
			        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
			        aria-label="Toggle navigation">
                <span class="menu_toggle">
                    <span class="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                    <span class="hamburger-cross">
                        <span></span>
                        <span></span>
                    </span>
                </span>
			</button>
			<div class="collapse navbar-collapse justify-content-between" id="navbarSupportedContent">
				<ul class="navbar-nav menu w_menu ms-auto me-auto">
					<li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
						<a class="nav-link" href="{{ route('home') }}" role="button" aria-haspopup="true" aria-expanded="false">
							HOME
						</a>
					</li>
					<li class="nav-item {{ request()->routeIs('shop.*') ? 'active' : '' }}">
						<a class="nav-link" href="{{ route('shop.index') }}" role="button" aria-haspopup="true"
						   aria-expanded="false">
							Browse Covers
						</a>
					</li>
					<li class="nav-item {{ request()->routeIs('blog.*') ? 'active' : '' }}">
						<a class="nav-link" href="{{ route('blog.index') }}" role="button" aria-haspopup="true"
						   aria-expanded="false">
							Blog
						</a>
					</li>
					<li class="nav-item {{ request()->routeIs('faq') ? 'active' : '' }}"> {{-- <-- ADD THIS BLOCK --}}
						<a class="nav-link" href="{{ route('faq') }}" role="button" aria-haspopup="true" aria-expanded="false">
							FAQ
						</a>
					</li>
				</ul>
				<div class="alter_nav">
					<ul class="navbar-nav search_cart menu">
						<li class="nav-item search">
							<a class="nav-link search-btn" href="javascript:void(0);" aria-label="Open search"><i
									class="icon-search"></i></a>
							<form action="{{ route('shop.index') }}" method="GET" class="menu-search-form">
								<div class="input-group">
									<input type="hidden" name="orderby" value="latest">
									<input type="hidden" name="category" value="">
									<input type="search" name="s" class="form-control" placeholder="Search covers..."
									       aria-label="Search covers"/>
									<button type="submit">
										<i class="ti-arrow-right"></i>
									</button>
								</div>
							</form>
						</li>
						<li class="nav-item user ms-3">
							@auth
								<a class="nav-link" href="{{ route('dashboard') }}"><i class="icon-user"></i></a>
							@else
								<a class="nav-link" href="{{ route('login') }}"><i class="icon-user"></i></a>
							@endauth
						</li>
						@guest
							<li class="nav-item user ms-3">
								<a class="nav-link" href="{{ route('register') }}"><i class="icon_id-2_alt"></i></a>
							</li>
						@endguest
					</ul>
				</div>
			</div>
		</div>
	</nav>
</header>
