<section class="best_selling_pr_area sec_padding" data-bg-color="#f5f5f5">
	<div class="container">
		<div class="section_title section_title_two text-center wow fadeInUp" data-wow-delay="0.2s">
			<h2 class="title title_two">New Arrivals</h2>
			<p>Reading books helps you to develop your communication skill</p>
		</div>
		@if($newArrivals->isNotEmpty())
			<div class="row">
				@foreach($newArrivals as $cover)
					<div class="col-xl-4 col-md-6">
						<div class="bj_new_pr_item_two d-flex wow fadeInUp" data-wow-delay="0.{{ $loop->iteration + 1 }}s">
							<a href="{{ route('covers.show', $cover->id) }}" class="img"><img src="{{ asset('storage/' . $cover->mockup) }}" alt="{{ $cover->name }}" /></a>
							<div class="bj_new_pr_content_two">
								@if($cover->categories && !empty($cover->categories[0]))
									<a href="#" class="category">{{ Str::title($cover->categories[0]) }}</a> {{-- Link to category page later --}}
								@endif
								<a href="{{ route('covers.show', $cover->id) }}">
									<h4 class="bj_new_pr_title">{{ Str::limit($cover->name, 25) }}</h4>
								</a>
								<div class="writer_name">by <a href="#">Author Name</a></div> {{-- Placeholder Author --}}
								<div class="book_price"><sup>$</sup>{{ rand(10, 50) }}<sup>.00</sup></div> {{-- Random Static Price --}}
								<a href="#" class="bj_theme_btn">Buy Now</a> {{-- Placeholder link --}}
							</div>
						</div>
					</div>
				@endforeach
			</div>
			<div class="text-center mt-4">
				<a href="{{ route('shop.index') }}" class="bj_theme_btn strock_btn blue_strock_btn wow fadeInUp" data-wow-delay="0.4s">View All</a>
			</div>
		@else
			<p class="text-center">No new arrivals at the moment.</p>
		@endif
	</div>
</section>
