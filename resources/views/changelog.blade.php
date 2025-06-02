@extends('layouts.app')

@section('title', 'Changelog - Free Kindle Covers')

@php
	$footerClass = ''; // Or any specific class you need for the footer on this page
@endphp

@section('content')
	<section class="py-5 bg-light">
		<div class="container">
			<div class="text-center mb-5">
				<h1 class="display-5 fw-bold">Changelog</h1>
				<p class="lead text-muted">Stay updated with the latest changes and improvements to our platform.</p>
			</div>
			<div class="row">
				<div class="col-lg-10 mx-auto">
					@if($changelogs->isEmpty())
						<div class="alert alert-info text-center" role="alert">
							<p class="mb-0">No changelog entries to display at the moment. Check back later!</p>
							@if(auth()->check() && auth()->user()->isAdmin()) {{-- Show hint for admins if git sync might have issues --}}
							<hr>
							<small class="text-muted">Admins: If you expect to see entries, ensure the Git repository is accessible and the `git log` command can run successfully. Check application logs for errors.</small>
							@endif
						</div>
					@else
						<div class="changelog-list">
							@foreach($changelogs as $entry)
								<div class="changelog-entry card mb-4 shadow-sm">
									<div class="card-header bg-white d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
										<h5 class="mb-1 mb-sm-0">
											<span class="badge bg-primary me-2">{{ $entry->commit_date->format('Y-m-d') }}</span>
										</h5>
{{--										<small class="text-muted">--}}
{{--											By {{ $entry->author_name }}--}}
{{--											(Commit: <a href="#" onclick="return false;" title="Full Hash: {{ $entry->commit_hash }}" data-bs-toggle="tooltip" data-bs-placement="top">{{ Str::limit($entry->commit_hash, 7, '') }}</a>)--}}
{{--										</small>--}}
									</div>
									<div class="card-body">
										<p class="card-text mb-0">{{ $entry->message }}</p>
									</div>
								</div>
							@endforeach
						</div>
						
						@if($changelogs->hasPages())
							<div class="d-flex justify-content-center mt-4">
								{{ $changelogs->links() }} {{-- Renders Bootstrap 5 pagination links --}}
							</div>
						@endif
					@endif
				</div>
			</div>
		</div>
	</section>
@endsection

@push('styles')
	<style>
      .changelog-entry .card-header h5 {
          font-size: 1.1rem;
      }
      .changelog-entry .card-text {
          font-size: 0.95rem;
          line-height: 1.6;
      }
      .changelog-entry .card-header small {
          font-size: 0.85rem;
      }
      /* Ensure pagination looks good if not already styled by default Bootstrap 5 */
      .pagination {
          --bs-pagination-padding-x: 0.75rem;
          --bs-pagination-padding-y: 0.375rem;
          --bs-pagination-font-size: 1rem;
          --bs-pagination-color: var(--bs-primary); /* Or your theme's link color */
          --bs-pagination-bg: var(--bs-white);
          --bs-pagination-border-width: var(--bs-border-width);
          --bs-pagination-border-color: var(--bs-gray-300); /* Or your theme's border color */
          --bs-pagination-border-radius: var(--bs-border-radius);
          --bs-pagination-hover-color: var(--bs-link-hover-color);
          --bs-pagination-hover-bg: var(--bs-gray-200); /* Or your theme's hover background */
          --bs-pagination-hover-border-color: var(--bs-gray-300);
          --bs-pagination-focus-color: var(--bs-link-hover-color);
          --bs-pagination-focus-bg: var(--bs-gray-200);
          --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25); /* Adjust color */
          --bs-pagination-active-color: var(--bs-white);
          --bs-pagination-active-bg: var(--bs-primary); /* Or your theme's active color */
          --bs-pagination-active-border-color: var(--bs-primary);
          --bs-pagination-disabled-color: var(--bs-gray-500); /* Or your theme's disabled color */
          --bs-pagination-disabled-bg: var(--bs-white);
          --bs-pagination-disabled-border-color: var(--bs-gray-300);
      }
	</style>
@endpush

@push('scripts')
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// Initialize Bootstrap tooltips for commit hashes
			var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
			var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
				return new bootstrap.Tooltip(tooltipTriggerEl);
			});
		});
	</script>
@endpush
