{{-- resources/views/partials/about_popular_authors.blade.php --}}
<section class="bj_author_area sec_padding">
	<div class="container">
		<div class="row mb-5">
			<div class="col-lg-8">
				<h2 class="title wow fadeInLeft" data-wow-delay="0.2s">Most Popular Authors</h2>
			</div>
			<div class="col-lg-4 text-lg-end">
				{{-- Replace '#' with a proper route if an authors page exists or will be created --}}
				<a href="#" class="bj_theme_btn strock_btn ms-0 wow fadeInRight" data-wow-delay="0.3s">VIEW MORE</a>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-3 col-md-4 col-sm-6">
				<div class="bj_team_item wow fadeInUp" data-wow-delay="0.2s">
					<div class="team_img">
						<img src="{{ asset('template/assets/img/team-img1.jpg') }}" alt="Author James Mango">
						<div class="social_icon">
							<a href="#"><i class="social_facebook"></i></a>
							<a href="#"><i class="social_twitter"></i></a>
							<a href="#"><i class="social_googleplus"></i></a>
						</div>
					</div>
					{{-- Replace '#' with a proper route to author's single page --}}
					<a href="#">
						<h5>James Mango</h5>
					</a>
					<span class="name">Author</span>
				</div>
			</div>
			<div class="col-lg-3 col-md-4 col-sm-6">
				<div class="bj_team_item wow fadeInUp" data-wow-delay="0.3s">
					<div class="team_img">
						<img src="{{ asset('template/assets/img/team-img2.jpg') }}" alt="Author Nolan Bator">
						<div class="social_icon">
							<a href="#"><i class="social_facebook"></i></a>
							<a href="#"><i class="social_twitter"></i></a>
							<a href="#"><i class="social_googleplus"></i></a>
						</div>
					</div>
					<a href="#">
						<h5>Nolan Bator</h5>
					</a>
					<span class="name">Author</span>
				</div>
			</div>
			<div class="col-lg-3 col-md-4 col-sm-6">
				<div class="bj_team_item wow fadeInUp" data-wow-delay="0.4s">
					<div class="team_img">
						<img src="{{ asset('template/assets/img/team-img3.jpg') }}" alt="Author Gretchen Curtis">
						<div class="social_icon">
							<a href="#"><i class="social_facebook"></i></a>
							<a href="#"><i class="social_twitter"></i></a>
							<a href="#"><i class="social_googleplus"></i></a>
						</div>
					</div>
					<a href="#">
						<h5>Gretchen Curtis</h5>
					</a>
					<span class="name">Author</span>
				</div>
			</div>
			<div class="col-lg-3 col-md-4 col-sm-6">
				<div class="bj_team_item wow fadeInUp" data-wow-delay="0.5s">
					<div class="team_img">
						<img src="{{ asset('template/assets/img/team-img4.jpg') }}" alt="Author Cristofer Dorwart">
						<div class="social_icon">
							<a href="#"><i class="social_facebook"></i></a>
							<a href="#"><i class="social_twitter"></i></a>
							<a href="#"><i class="social_googleplus"></i></a>
						</div>
					</div>
					<a href="#">
						<h5>Cristofer Dorwart</h5>
					</a>
					<span class="name">Author</span>
				</div>
			</div>
		</div>
	</div>
</section>
