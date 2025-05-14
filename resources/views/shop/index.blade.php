@extends('layouts.app')

@section('title', 'Browse Covers - Free Kindle Covers')

@push('styles')
@endpush

@section('content')
	{{-- Toast Notification Container --}}
	<div class="toast-container position-fixed p-3 top-0 end-0" style="z-index: 1090;">
		<div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
			<div class="toast-header">
				<strong class="me-auto">Cart Update</strong>
				<small>just now</small>
				<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
			<div class="toast-body">
				Item added to the cart! (This is a demo)
			</div>
		</div>
	</div>
	
	@include('partials.shop_breadcrumb')
	@include('partials.shop_area')
	@include('partials.subscribe')

@endsection

@push('scripts')
	{{-- Add page-specific scripts if needed --}}
	<script>
		document.addEventListener('DOMContentLoaded', function () {
		    // Basic Add to Cart Toast Demo
		    const cartToastEl = document.getElementById('cartToast');
		    const cartToast = bootstrap.Toast.getOrCreateInstance(cartToastEl);
		    document.querySelectorAll('.add-to-cart-automated').forEach(button => {
		        button.addEventListener('click', function() {
		            // In a real app, you'd add to cart via AJAX then show toast
		            cartToast.show();
		        });
		    });
		});
	</script>
@endpush
