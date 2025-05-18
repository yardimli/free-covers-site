{{-- free-cover-site/resources/views/partials/shop_area.blade.php --}}
@php use Illuminate\Support\Str; @endphp
	<!-- shop area -->
<section class="bj_shop_area sec_padding" data-bg-color="#f5f5f5">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<form role="search" method="get" class="pr_search_form pr_search_form_two input-group" action="{{ route('shop.index') }}">
					{{-- Hidden fields to preserve other filters when searching --}}
					<input type="hidden" name="orderby" value="{{ $sortBy ?? 'latest' }}">
					<input type="hidden" name="category" value="{{ $selectedCategory ?? '' }}">
					<input type="text" name="s" value="{{ $searchTerm ?? '' }}" class="form-control search-field" id="search" placeholder="Search for covers...">
					<button type="submit"><i class="ti-search"></i></button>
				</form>
				<div class="shop_top d-flex align-items-center justify-content-between mt-4">
					<div class="shop_menu_left">{{ $covers->total() }} Covers Found</div>
					<div class="shop_menu_right d-flex align-items-center justify-content-end">
						
						{{-- Category Filter --}}
						@if(isset($availableCategories) && !empty($availableCategories))
							<span class="me-2">Category:</span>
							<form class="woocommerce-ordering ms-1 me-3" method="get" action="{{ route('shop.index') }}" id="categoryFilterForm" style="display: inline-block; min-width: 180px;">
								<input type="hidden" name="s" value="{{ $searchTerm ?? '' }}">
								<input type="hidden" name="orderby" value="{{ $sortBy ?? 'latest' }}">
								<select name="category" class="orderby selectpickers form-select form-select-sm" onchange="this.form.submit();">
									<option value="">All Categories</option>
									@foreach($availableCategories as $categoryName => $count)
										<option value="{{ $categoryName }}" {{ ($selectedCategory ?? '') === $categoryName ? 'selected' : '' }}>
											{{ $categoryName }} ({{ $count }})
										</option>
									@endforeach
								</select>
							</form>
						@endif
						
						{{-- Sort By Filter --}}
						<span class="me-2">Sort by:</span>
						<form class="woocommerce-ordering ms-1" method="get" action="{{ route('shop.index') }}" id="sortForm" style="display: inline-block; min-width: 180px;">
							<input type="hidden" name="s" value="{{ $searchTerm ?? '' }}">
							<input type="hidden" name="category" value="{{ $selectedCategory ?? '' }}">
							<select name="orderby" class="orderby selectpickers form-select form-select-sm" onchange="this.form.submit();">
								<option value="latest" {{ ($sortBy ?? 'latest') === 'latest' ? 'selected' : '' }}>Default sorting (Latest)</option>
								<option value="name_asc" {{ ($sortBy ?? '') === 'name_asc' ? 'selected' : '' }}>Sort by name: A to Z</option>
								<option value="name_desc" {{ ($sortBy ?? '') === 'name_desc' ? 'selected' : '' }}>Sort by name: Z to A</option>
							</select>
						</form>
					</div>
				</div>
				
				@if($covers->isNotEmpty())
					<div class="row mt-4">
						@foreach($covers as $cover)
							<div class="col-lg-3 col-md-4 col-sm-6 projects_item">
								<div class="best_product_item best_product_item_two shop_product">
									<a href="{{ route('covers.show', $cover->id) }}" class="cover-image-container">
									<div class="img">
											<img src="{{ $cover->mockup_url }}" alt="{{ $cover->name }}" class="cover-mockup-image img-fluid">
											@if($cover->random_template_overlay_url)
												<img src="{{ $cover->random_template_overlay_url }}" alt="Template Overlay" class="template-overlay-image" />
											@endif
										{{-- <div class="pr_ribbon">--}}
										{{-- <span class="product-badge">New</span>--}}
										{{-- </div>--}}
									</div>
									</a>
									<div class="bj_new_pr_content">
										<a href="{{ route('covers.show', $cover->id) }}">
											<h5 class="bj_new_pr_title" style="margin-bottom:0px;">#{{ $cover->id }} {{$cover->name }}</h5>
										</a>
										<div class="bj_pr_meta d-flex">
											<div class="writer_name">{{ $cover->caption ? Str::limit($cover->caption, 40) : 'No caption' }}</div>
										</div>
									</div>
								</div>
							</div>
						@endforeach
					</div>
				@else
					<div class="text-center p-5">
						<p>No covers found matching your criteria.</p>
						<a href="{{ route('shop.index') }}" class="bj_theme_btn">Clear Search & Filters</a>
					</div>
				@endif
				
				@if($covers->hasPages())
					<div class="text-center mt-5">
						{{ $covers->links('vendor.pagination.bootstrap-5') }} {{-- Or your custom pagination view --}}
					</div>
				@endif
			</div>
		</div>
	</div>
</section>
<!-- shop area -->
