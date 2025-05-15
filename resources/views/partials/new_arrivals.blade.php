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
							<a href="{{ route('covers.show', $cover->id) }}" class="img cover-image-container">
								<img src="{{ asset('storage/' . $cover->mockup) }}" alt="{{ $cover->name }}" class="cover-mockup-image" />
								@if($cover->random_template_overlay_url)
									<img src="{{ $cover->random_template_overlay_url }}" alt="Template Overlay" class="template-overlay-image" />
								@endif
							</a>
							<div class="bj_new_pr_content_two">
								@if($cover->categories && !empty($cover->categories[0]))
									@php $categoryName = Str::title($cover->categories[0]); @endphp
									<a href="{{ route('shop.index', ['category' => $categoryName]) }}" class="category">{{ $categoryName }}</a>
								@endif
								<a href="{{ route('covers.show', $cover->id) }}">
									<h6 class="bj_new_pr_title">#{{ $cover->id }}<br>{{$cover->name }}</h6>
								</a>
								<div class="writer_name">{{ $cover->caption ? Str::limit($cover->caption, 60) : '' }}</div>
								<a href="#" class="bj_theme_btn">Customize</a>
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
