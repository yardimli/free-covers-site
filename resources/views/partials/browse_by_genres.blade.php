@php use Illuminate\Support\Str; @endphp
<section class="product_tab_showcase_area sec_padding" id="product_tab_showcase_id">
	<div class="container">
		<div class="section_title wow fadeInUp">
			<h2 class="title title_two">Browse By Genres</h2>
		</div>
		<div class="row wow fadeInUp" data-wow-delay="0.2s">
			<div class="col-lg-4">
				@if(!empty($genreCounts))
					<ul class="nav nav-pills tab_slider_thumb" id="pills-tab-one" role="tablist">
						@foreach($genreCounts as $genre => $count)
							@php $genreSlug = Str::slug($genre); @endphp
							<li role="presentation" class="nav-item">
								<a class="nav-link {{ $loop->first ? 'active' : '' }}"
								   id="pills-{{ $genreSlug }}-tab"
								   data-bs-toggle="pill"
								   data-bs-target="#pills-{{ $genreSlug }}"
								   role="tab"
								   aria-controls="pills-{{ $genreSlug }}"
								   aria-selected="{{ $loop->first ? 'true' : 'false' }}"
								   data-genre-slug="{{ $genreSlug }}"
								   data-genre-name="{{ $genre }}">
									<strong>{{ $genre }}</strong>
									<span>({{ $count }} Covers)</span>
								</a>
							</li>
						@endforeach
					</ul>
				@else
					<p>No genres with sufficient covers found to display.</p>
				@endif
			</div>
			<div class="col-lg-8">
				@if(!empty($genreCounts))
					<div class="tab-content" id="pills-tabContent-two">
						@foreach($genreCounts as $genre => $count) {{-- Note: $count is the value here --}}
						@php
							$genreSlug = Str::slug($genre);
							$isFirstLoop = $loop->first;
							$currentCovers = null;
							if ($isFirstLoop && isset($coversForTabs[$genre])) {
									$currentCovers = $coversForTabs[$genre];
							}
						@endphp
						<div class="tab-pane fade {{ $isFirstLoop ? 'show active' : '' }}"
						     id="pills-{{ $genreSlug }}"
						     role="tabpanel"
						     aria-labelledby="pills-{{ $genreSlug }}-tab"
						     data-loaded="{{ $isFirstLoop && $currentCovers && $currentCovers->isNotEmpty() ? 'true' : 'false' }}">
							
							@if($isFirstLoop)
								@if($currentCovers && $currentCovers->isNotEmpty())
									<div class="tab_slider_content slick_slider_tab">
										@foreach($currentCovers->chunk(2) as $coverPair)
											<div class="item"> {{-- Each item is a slick-slide --}}
												@foreach($coverPair as $cover)
													<div class="bj_new_pr_item {{ !$loop->last ? 'mb-3' : '' }}"> {{-- Stacked cover item, add margin if not last in pair --}}
														<a href="{{ route('covers.show', $cover->id) }}" class="img">
															<img src="{{ asset('storage/' .$cover->mockup) }}" alt="{{ $cover->name }}" />
														</a>
														<a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to Wishlist" class="wish_btn" tabindex="-1"><i class="icon_heart_alt"></i></a>
														<div class="bj_new_pr_content_two">
															<div class="d-flex justify-content-between">
																<a href="{{ route('covers.show', $cover->id) }}">
																	<h5>{{ Str::limit($cover->name, 30) }}</h5>
																</a>
																<div class="book_price">
																	<sup>$</sup>25<sup>.00</sup> <!-- Static Price -->
																</div>
															</div>
															<div class="writer_name">
																<i class="icon-user"></i><a href="#">Author Name</a> <!-- Placeholder Author -->
																{{-- Or use actual data: {{ $cover->caption ? Str::limit($cover->caption, 40) : 'Author Name' }} --}}
															</div>
															<div class="ratting">
																<div class="ratting_icon">
																	<i class="fa-solid fa-star"></i>
																	<i class="fa-solid fa-star"></i>
																	<i class="fa-solid fa-star"></i>
																	<i class="fa-solid fa-star"></i>
																	<i class="fa-solid fa-star-half-alt"></i> <!-- Static Rating -->
																</div>
																<span>(252)</span> <!-- Static Review Count -->
															</div>
															<a href="#" class="bj_theme_btn">Buy Now</a> <!-- Placeholder link -->
														</div>
													</div>
												@endforeach
											</div>
										@endforeach
									</div>
								@else
									<p class="p-3">No covers found for {{ $genre }}.</p>
								@endif
							@else
								{{-- Placeholder for other tabs, content will be loaded via AJAX --}}
								<div class="text-center p-5">
									<div class="spinner-border text-primary" role="status">
										<span class="visually-hidden">Loading...</span>
									</div>
									<p>Loading covers for {{ $genre }}...</p>
								</div>
							@endif
						</div>
						@endforeach
					</div>
				@else
					<p>No covers to display in this section.</p>
				@endif
			</div>
		</div>
	</div>
</section>
