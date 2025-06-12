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
						@foreach($genreCounts as $genre => $count)
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
										<div class="covers-grid-container"> {{-- Removed slick_slider_tab class --}}
											<div class="row"> {{-- Bootstrap row for grid --}}
												@foreach($currentCovers as $cover)
													<div class="col-lg-4 col-md-6 mb-4"> {{-- 3 columns on large, 2 on medium --}}
														<div class="bj_new_pr_item">
															<a href="{{ route('covers.show', ['cover' => $cover->id, 'template' => $cover->random_template_overlay_id]) }}" class="img cover-image-container">
																<img src="{{ asset('storage/' .$cover->mockup_2d_path) }}" alt="{{ $cover->name }}" class="cover-mockup-image" />
																@if($cover->random_template_overlay_url)
																	<img src="{{ $cover->random_template_overlay_url }}" alt="Template Overlay" class="{{ $cover->has_real_2d ? 'template-overlay-image' : 'template-overlay-image-non-2d' }}" />
																@endif
															</a>
															<div class="bj_new_pr_content_two">
																<div class="d-flex justify-content-between">
																	<a href="{{ route('covers.show', ['cover' => $cover->id, 'template' => $cover->random_template_overlay_id]) }}">
																		<h6>#{{$cover->id }} {{$cover->name }}</h6>
																	</a>
																</div>
																<div class="writer_name">
																	{{ $cover->caption ? Str::limit($cover->caption, 40) : '' }}
																</div>
																<a href="#" class="bj_theme_btn">Customize</a> <!-- Placeholder link -->
															</div>
														</div>
													</div>
												@endforeach
											</div>
										</div>
										<div class="text-center mt-4">
											<a href="{{ route('shop.index', ['category' => $genre]) }}" class="bj_theme_btn strock_btn blue_strock_btn">
												Show all covers in {{ $genre }}
											</a>
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
