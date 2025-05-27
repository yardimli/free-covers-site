{{-- resources/views/partials/about_mission.blade.php --}}
<section class="bj_mission_area sec_padding" data-bg-color="#f5f5f5">
	<div class="container">
		<div class="row align-items-center">
			<div class="col-lg-6">
				<div class="bj_video_inner wow fadeInLeft" data-wow-delay="0.2s">
					<img class="img-fluid" src="{{ asset('template/assets/img/about_img.png') }}" alt="Illustrative image of books and design elements">
					{{-- Note: Fancybox needs to be initialized if not already globally done. This is a placeholder video. --}}
					<a class="play-btn" href="https://www.youtube.com/embed/nyxxI6J0MrE" data-fancybox><i class="arrow_triangle-right"></i></a>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="bj_mission_content pe-5 wow fadeInRight" data-wow-delay="0.3s">
					<h2 class="title">Our Mission</h2>
					<p>Our mission is to empower aspiring authors by providing high-quality, professional Kindle cover designs, completely free. We believe that every great story deserves a captivating cover, and financial constraints shouldn't stand in the way of a new author's dream to get published.</p>
					<p>We aim to help new authors launch their books with confidence, providing them with the tools to create covers that not only look stunning but also attract readers and help their work get discovered in a competitive marketplace.</p>
				</div>
			</div>
		</div>
		<div class="row features_box">
			<div class="col-lg-4 col-md-6">
				<div class="bj_features_item text-center wow fadeInUp" data-wow-delay="0.2s">
					<i class="icon-gift icon"></i> {{-- Or use FontAwesome: <i class="fas fa-gift fa-2x text-primary mb-3"></i> --}}
					<h3>Launch Professionally</h3>
					<p>Give your debut novel the professional edge it needs. Our free covers help new authors establish credibility and make a strong first impression.</p>
					<a href="{{ route('shop.index') }}" class="bj_theme_btn text_btn">Browse Designs <i class="arrow_right"></i></a>
				</div>
			</div>
			<div class="col-lg-4 col-md-6">
				<div class="bj_features_item text-center wow fadeInUp" data-wow-delay="0.3s">
					<i class="icon-feather-pen icon"></i> {{-- Or use FontAwesome: <i class="fas fa-edit fa-2x text-primary mb-3"></i> --}}
					<h3>Customize With Ease</h3>
					<p>No design skills? No problem! Our intuitive designer lets you easily personalize templates to perfectly match your book's genre and tone.</p>
					<a href="{{ route('designer.index') }}" class="bj_theme_btn text_btn">Start Designing <i class="arrow_right"></i></a>
				</div>
			</div>
			<div class="col-lg-4 col-md-6">
				<div class="bj_features_item text-center wow fadeInUp" data-wow-delay="0.4s">
					<i class="icon-open-book1 icon"></i> {{-- Or use FontAwesome: <i class="fas fa-eye fa-2x text-primary mb-3"></i> --}}
					<h3>Get Discovered Faster</h3>
					<p>A stunning cover is key to grabbing reader attention. Our designs help your book stand out, increasing visibility for new authors.</p>
					<a href="{{ route('shop.index') }}" class="bj_theme_btn text_btn">See Covers <i class="arrow_right"></i></a>
				</div>
			</div>
		</div>
	</div>
</section>
