{{-- resources/views/partials/related_covers.blade.php --}}
@php use Illuminate\Support\Str; @endphp
@if(isset($relatedCovers) && $relatedCovers->isNotEmpty())
	<section class="bj_frequently_bought_area sec_padding pt-0" data-bg-color="#f5f5f5">
		<div class="container">
			<div class="section_title text-center wow fadeInUp">
				<h2 class="title_two">You Might Also Like</h2>
				<p>Explore other designs that catch your eye!</p>
			</div>
			<div class="row gy-xl-0 gy-4">
				@foreach($relatedCovers as $relatedCover)
					<div class="col-xl-3 col-lg-4 col-sm-6">
						<div class="bj_new_pr_item mb-0 wow fadeInUp" data-wow-delay="0.{{ $loop->iteration + 1 }}s">
							<a href="{{ route('covers.show', $relatedCover->id) }}" class="img cover-image-container">
								<img src="{{ $relatedCover->mockup_url }}" alt="{{ $relatedCover->name }}" class="cover-mockup-image img-fluid" style="height: 380px; object-fit: contain;">
								@if($relatedCover->random_template_overlay_url)
									<img src="{{ $relatedCover->random_template_overlay_url }}" alt="Template Overlay" class="template-overlay-image" />
								@endif
							</a>
							<div class="bj_new_pr_content">
								<a href="{{ route('covers.show', $relatedCover->id) }}">
									<h4 class="bj_new_pr_title">#{{ $relatedCover->id }}</h4>
								</a>
								<div class="bj_pr_meta d-flex">
									<div class="writer_name" style="min-height: 40px;">{{ $relatedCover->caption ? Str::limit($relatedCover->caption, 35) : 'View Details' }}</div>
								</div>
								<a href="#" class="bj_theme_btn strock_btn add-to-cart-automated" data-name="{{ $relatedCover->name }}" data-price="0" data-img="{{ $relatedCover->mockup_url }}" data-mrp="0">Customize</a>
							</div>
						</div>
					</div>
				@endforeach
			</div>
		</div>
	</section>
@endif
