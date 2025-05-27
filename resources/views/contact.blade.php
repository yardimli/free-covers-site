{{-- resources/views/contact.blade.php --}}
@extends('layouts.app')

@php
	$footerClass = ''; // Default footer class
@endphp

@section('title', 'Contact Us - Free Kindle Covers')

@push('styles')
	<style>
      .contact-form-section {
          padding-top: 60px;
          padding-bottom: 60px;
      }
      .contact-form-section .form-control {
          min-height: 50px;
          border-radius: 5px;
          border-color: #e0e0e0;
      }
      .contact-form-section textarea.form-control {
          min-height: 150px;
      }
      .contact-form-section .bj_theme_btn {
          min-width: 180px;
      }
      .alert-success {
          color: #0f5132;
          background-color: #d1e7dd;
          border-color: #badbcc;
      }
      .alert-danger ul {
          margin-bottom: 0;
      }
	</style>
@endpush

@section('content')
	@include('partials.contact_breadcrumb')
	
	<section class="contact-form-section sec_padding" data-bg-color="#f5f5f5">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-lg-8">
					<div class="section_title text-center mb-5 wow fadeInUp" data-wow-delay="0.2s">
						<h2 class="title">Get In Touch</h2>
						<p>Have a question or feedback? Fill out the form below and we'll get back to you as soon as possible.</p>
					</div>
					
					@if(session('success'))
						<div class="alert alert-success alert-dismissible fade show wow fadeInUp" data-wow-delay="0.3s" role="alert">
							{{ session('success') }}
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
					@endif
					
					@if($errors->any())
						<div class="alert alert-danger wow fadeInUp" data-wow-delay="0.3s" role="alert">
							<h6 class="alert-heading">Please correct the errors below:</h6>
							<ul>
								@foreach($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif
					
					<form action="{{ route('contact.submit') }}" method="POST" class="contact_form wow fadeInUp" data-wow-delay="0.4s">
						@csrf
						<div class="row">
							<div class="col-md-6 form-group mb-3">
								<label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
								<input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="John Doe" value="{{ old('name') }}" required>
								@error('name')
								<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<div class="col-md-6 form-group mb-3">
								<label for="email" class="form-label">Your Email <span class="text-danger">*</span></label>
								<input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="john.doe@example.com" value="{{ old('email') }}" required>
								@error('email')
								<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<div class="col-12 form-group mt-0 mb-3"> {{-- Adjusted mt-3 to mt-0 --}}
								<label for="message" class="form-label">Message <span class="text-danger">*</span></label>
								<textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5" placeholder="Your message here..." required>{{ old('message') }}</textarea>
								@error('message')
								<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<div class="col-12 text-center mt-4">
								<button type="submit" class="bj_theme_btn">Send Message</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</section>
@endsection

@push('scripts')
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// Ensure parallax and other template JS runs if needed for this page
			if (typeof $ !== 'undefined') {
				if ($(".banner_animation_03").length > 0 && typeof $.fn.parallax === 'function') {
					$(".banner_animation_03").css({"opacity": 1}).parallax({scalarX: 7.0, scalarY: 10.0});
				}
				if (typeof WOW === 'function' && $("body").data("scroll-animation") === true) {
					new WOW({}).init();
				}
				// Re-initialize tooltips if any are added dynamically or on this page specifically
				var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
				tooltipTriggerList.map(function (tooltipTriggerEl) {
					return new bootstrap.Tooltip(tooltipTriggerEl);
				});
			}
		});
	</script>
@endpush
