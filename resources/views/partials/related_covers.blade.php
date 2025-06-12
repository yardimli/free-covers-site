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
								<img src="{{ asset('storage/' . $relatedCover->mockup_2d_path) }}" alt="{{ $relatedCover->name }}" class="cover-mockup-image img-fluid" style="height: 380px; object-fit: contain;">
								@if($relatedCover->random_template_overlay_url)
									<img src="{{ $relatedCover->random_template_overlay_url }}" alt="Template Overlay" class="{{ $cover->has_real_2d ? 'template-overlay-image' : 'template-overlay-image-non-2d' }}" />
								@endif
							</a>
							<div class="bj_new_pr_content">
								<a href="{{ route('covers.show', $relatedCover->id) }}">
									<h4 class="bj_new_pr_title">#{{ $cover->id }} {{$cover->name }}</h4>
								</a>
							</div>
						</div>
					</div>
				@endforeach
			</div>
		</div>
	</section>
@endif
