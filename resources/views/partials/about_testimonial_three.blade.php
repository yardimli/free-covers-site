{{-- resources/views/partials/about_testimonial_three.blade.php --}}
<section class="bj_testimonial_area_three sec_padding" data-bg-color="#f5f5f5">
	<div class="container">
		<div class="row mb-5">
			<div class="col-lg-6">
				<h2 class="title wow fadeInLeft">Hear From Authors We've Helped</h2>
			</div>
			<div class="col-lg-5 offset-lg-1 wow fadeInRight"> {{-- Adjusted offset for potentially longer text --}}
				<p>Discover how Free Kindle Covers has supported new authors in launching their books successfully. Here’s what some of them have to say about their experience:</p>
			</div>
		</div>
		{{-- This slider uses the class "testimonial_slider_three" which is initialized in frontend-index.js with Slick --}}
		<div class="testimonial_slider_three wow fadeInUp" data-wow-delay="0.6s">
			<div class="single-widget">
				<div class="widget-title">
					<div class="author-img">
						<img src="{{ asset('template/assets/img/home-four/author1.jpg') }}" alt="Author Elena Hayes">
					</div>
					<div class="auth-rating">
						<div class="auth-info">
							<h6>Elena Hayes</h6>
							<span>Author of "The Cybernetic Dawn"</span>
						</div>
						<div class="rating">
							<span>5.0</span><i class="fas fa-star"></i>
						</div>
					</div>
				</div>
				<div class="widget-body">
					<i class="icon-quote"></i>
					<p>As a first-time author, budget was a huge concern. Free Kindle Covers gave me a professional-looking cover for 'The Cybernetic Dawn' that I constantly get compliments on. It truly helped my launch!</p>
					<div class="post-date">
						<img src="{{ asset('template/assets/img/home-four/calender.png') }}" alt="calendar icon"> Mon, June 10, 2024
					</div>
				</div>
			</div>
			
			<div class="single-widget">
				<div class="widget-title">
					<div class="author-img">
						{{-- Assuming you might have different author images, or reuse author1.jpg --}}
						<img src="{{ asset('template/assets/img/home-four/author1.jpg') }}" alt="Author Marcus Chen">
					</div>
					<div class="auth-rating">
						<div class="auth-info">
							<h6>Marcus Chen</h6>
							<span>Author of "Shadows of Eldoria"</span>
						</div>
						<div class="rating">
							<span>4.9</span><i class="fas fa-star"></i>
						</div>
					</div>
				</div>
				<div class="widget-body">
					<i class="icon-quote"></i>
					<p>I was overwhelmed by the thought of cover design until I found this site. The templates for 'Shadows of Eldoria' were easy to customize, and the quality is amazing for a free service. Highly recommend!</p>
					<div class="post-date">
						<img src="{{ asset('template/assets/img/home-four/calender.png') }}" alt="calendar icon"> Fri, May 17, 2024
					</div>
				</div>
			</div>
			
			<div class="single-widget">
				<div class="widget-title">
					<div class="author-img">
						<img src="{{ asset('template/assets/img/home-four/author1.jpg') }}" alt="Author Sarah Miller">
					</div>
					<div class="auth-rating">
						<div class="auth-info">
							<h6>Sarah Miller</h6>
							<span>Author of "The Seaside Murders"</span>
						</div>
						<div class="rating">
							<span>5.0</span><i class="fas fa-star"></i>
						</div>
					</div>
				</div>
				<div class="widget-body">
					<i class="icon-quote"></i>
					<p>Finding Free Kindle Covers was a game-changer for 'The Seaside Murders'. I got a beautiful, genre-appropriate cover without spending a dime, which allowed me to invest more in editing. Thank you!</p>
					<div class="post-date">
						<img src="{{ asset('template/assets/img/home-four/calender.png') }}" alt="calendar icon"> Wed, July 03, 2024
					</div>
				</div>
			</div>
			
			<div class="single-widget">
				<div class="widget-title">
					<div class="author-img">
						<img src="{{ asset('template/assets/img/home-four/author1.jpg') }}" alt="Author David Okoro">
					</div>
					<div class="auth-rating">
						<div class="auth-info">
							<h6>David Okoro</h6>
							<span>Author of "Unlock Your Potential"</span>
						</div>
						<div class="rating">
							<span>4.8</span><i class="fas fa-star"></i>
						</div>
					</div>
				</div>
				<div class="widget-body">
					<i class="icon-quote"></i>
					<p>The variety of templates is fantastic. I found the perfect, clean design for 'Unlock Your Potential' and it looks so professional. This service is a lifeline for indie authors!</p>
					<div class="post-date">
						<img src="{{ asset('template/assets/img/home-four/calender.png') }}" alt="calendar icon"> Tue, June 25, 2024
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
