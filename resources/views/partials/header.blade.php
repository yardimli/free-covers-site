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
						
						<li class="nav-item user ms-3">
							<a class="nav-link" href="{{-- route('my-account') --}}#"><i class="icon-user"></i></a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</nav>
</header>
