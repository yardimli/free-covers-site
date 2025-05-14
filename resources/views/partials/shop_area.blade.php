@php use Illuminate\Support\Str; @endphp
	<!-- shop area  -->
<section class="bj_shop_area sec_padding" data-bg-color="#f5f5f5">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<form role="search" method="get" class="pr_search_form pr_search_form_two input-group" action="{{ route('shop.index') }}">
					<input type="text" name="s" value="{{ $searchTerm ?? '' }}" class="form-control search-field" id="search" placeholder="Search for covers...">
					<button type="submit"><i class="ti-search"></i></button>
				</form>
				
				<div class="shop_top d-flex align-items-center justify-content-between">
					<div class="shop_menu_left">{{ $covers->total() }} Covers Found</div>
					<div class="shop_menu_right d-flex align-items-center justify-content-end">
						{{-- <div class="filter_widget pb-0 me-3">
								<h3 class="shop_sidebar_title mb-0"><a href="#"><img src="{{ asset('template/assets/img/shop/filter.svg') }}" alt="filter"></a>Filter</h3>
						</div> --}}
						Sort by
						<form class="woocommerce-ordering ms-2" method="get" action="{{ route('shop.index') }}" id="sortForm">
							<input type="hidden" name="s" value="{{ $searchTerm ?? '' }}">
							<select name="orderby" class="orderby selectpickers form-select form-select-sm" onchange="document.getElementById('sortForm').submit();">
								<option value="latest" {{ ($sortBy ?? 'latest') === 'latest' ? 'selected' : '' }}>Default sorting (Latest)</option>
								<option value="name_asc" {{ ($sortBy ?? '') === 'name_asc' ? 'selected' : '' }}>Sort by name: A to Z</option>
								<option value="name_desc" {{ ($sortBy ?? '') === 'name_desc' ? 'selected' : '' }}>Sort by name: Z to A</option>
							</select>
						</form>
					</div>
				</div>
				
				@if($covers->isNotEmpty())
					<div class="row">
						@foreach($covers as $cover)
							<div class="col-lg-3 col-md-4 col-sm-6 projects_item">
								<div class="best_product_item best_product_item_two shop_product">
									<div class="img">
										<a href="{{ route('covers.show', $cover->id) }}" class="cover-image-container">
											<img src="{{ $cover->mockup_url }}" alt="{{ $cover->name }}" class="cover-mockup-image img-fluid">
											@if($cover->random_template_overlay_url)
												<img src="{{ $cover->random_template_overlay_url }}" alt="Template Overlay" class="template-overlay-image" />
											@endif
										</a>
{{--										 <div class="pr_ribbon">--}}
{{--												<span class="product-badge">New</span>--}}
{{--										</div>--}}
										<button type="button" class="bj_theme_btn add-to-cart-automated"
										        data-name="{{ $cover->name }}"
										        data-img="{{ $cover->mockup_url }}"
										        data-price="0" {{-- Price not available on cover model --}}
										        data-mrp="0">
											<i class="icon_pencil-edit"></i>Customize
										</button>
									</div>
									<div class="bj_new_pr_content">
										<a href="{{ route('covers.show', $cover->id) }}">
											<h4 class="bj_new_pr_title" style="margin-bottom:0px;">#{{ $cover->id }}</h4>
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
						<a href="{{ route('shop.index') }}" class="bj_theme_btn">Clear Search</a>
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
<!-- shop area  -->
