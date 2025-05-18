@extends('layouts.admin')
@section('title', 'Cover Template Management')

@push('styles')
	<style>
      .cover-template-card {
          border: 1px solid #ddd;
          margin-bottom: 20px;
          padding: 15px;
          border-radius: 5px;
          background-color: #fff;
      }

      /* .cover-template-grid related styles are replaced by Bootstrap .row */

      .cover-template-item {
          position: relative;
          border: 1px solid #eee;
          padding: 10px;
          background-color: #f9f9f9;
          text-align: center;
          /* width: 170px; */ /* REMOVED - Bootstrap columns handle width */
          box-shadow: 0 2px 4px rgba(0,0,0,0.1);
          border-radius: 4px;
          height: 100%; /* For consistent height in a row */
          display: flex;
          flex-direction: column;
          justify-content: space-between;
      }

      .cover-template-item .cover-image-container {
          position: relative; /* For absolute positioning of child images */
          width: 100%; /* Fill the column width */
          height: 225px; /* Fixed height for cover images, adjust as needed */
          background-color: #e0e0e0; /* Placeholder background */
          margin-bottom: 8px;
          display: flex; /* To center placeholder text if no image */
          align-items: center;
          justify-content: center;
          overflow: hidden; /* Clip images */
          border-radius: 3px;
      }

      .cover-template-item .cover-image-container .cover-base-image {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          object-fit: contain; /* Show whole image, letterbox if needed */
      }

      .cover-template-item .cover-image-container .template-overlay {
          position: absolute;
          top: 3%;
          left: 3%;
          width: 94%;
          height: auto; /* Maintain aspect ratio */
          max-height: 94%; /* Max height relative to container's height (0.94 * 225px) */
          object-fit: contain; /* Show whole image */
          z-index: 1; /* Ensure it's on top of the base image */
      }

      /* Styling for the placeholder text when no cover image */
      .cover-template-item .cover-image-container .no-cover-image-text {
          color: #6c757d;
          font-size: 0.9em;
      }

      .cover-template-item .template-name {
          font-size: 0.85em;
          color: #333;
          margin-bottom: 8px;
          height: 3em; /* Allow for up to two lines of text */
          overflow: hidden;
          text-overflow: ellipsis;
          /* white-space: nowrap; */ /* Removed to allow wrapping */
      }

      .cover-template-item .btn-delete-assignment {
          width: 100%;
          font-size: 0.8rem;
      }

      .pagination-container {
          margin-top: 30px;
          margin-bottom: 30px;
      }

      .no-templates-message {
          color: #6c757d;
          font-style: italic;
      }
	</style>
@endpush

@section('content')
	<div class="container-fluid">
		<div class="d-flex justify-content-between align-items-center my-4">
			<h1>Cover Template Management</h1>
			{{-- Optional: Add a link back to the main dashboard or other relevant pages --}}
		</div>
		
		@if($covers->isEmpty())
			<div class="alert alert-info">No covers found. You might want to assign some templates to covers first.</div>
		@else
			@foreach($covers as $cover)
				<div class="cover-template-card" id="cover-card-{{ $cover->id }}">
					<h4 class="mb-3">
						Cover: {{ $cover->name }} (ID: {{ $cover->id }})
						@if($cover->coverType)
							<small class="text-muted fs-6">- {{ $cover->coverType->type_name }}</small>
						@endif
					</h4>
					
					@if($cover->templates->isEmpty())
						<p class="no-templates-message" id="no-templates-msg-{{ $cover->id }}">This cover currently has no templates assigned.</p>
					@else
						<div class="row" id="template-grid-{{ $cover->id }}"> {{-- Changed to Bootstrap row --}}
							@foreach($cover->templates as $template)
								<div class="col-lg-3 col-md-4 col-sm-6 mb-4"> {{-- Added Bootstrap column classes --}}
									<div class="cover-template-item" id="cover-{{ $cover->id }}-template-{{ $template->id }}">
										<div class="cover-image-container">
											@if($cover->mockup_url && $cover->mockup_url !== asset('template/assets/img/placeholder-mockup.png'))
												<img src="{{ $cover->mockup_url }}" alt="{{ $cover->name }} Mockup" class="cover-base-image">
											@else
												{{-- This span will be centered by the flex properties of cover-image-container --}}
												<span class="no-cover-image-text">No Cover Image</span>
											@endif
											
											@if($template->thumbnail_url && $template->thumbnail_url !== asset('images/placeholder-template-thumbnail.png'))
												<img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }} Overlay" class="template-overlay">
											@endif
										</div>
										<p class="template-name" title="{{ $template->name }}">{{ Str::limit($template->name, 50) }}</p>
										<button class="btn btn-sm btn-outline-danger btn-delete-assignment"
										        data-cover-id="{{ $cover->id }}"
										        data-template-id="{{ $template->id }}"
										        data-cover-name="{{ Str::limit($cover->name, 30) }}"
										        data-template-name="{{ Str::limit($template->name, 30) }}">
											<i class="fas fa-times"></i> Remove
										</button>
									</div>
								</div>
							@endforeach
						</div>
					@endif
				</div>
			@endforeach
			
			<div class="pagination-container d-flex justify-content-center">
				{{ $covers->links() }}
			</div>
		@endif
	</div>
@endsection

@push('scripts')
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const alertMessagesContainer = document.getElementById('alert-messages-container'); // From admin layout
			
			function showAlert(message, type = 'success', duration = 5000) {
				const alertId = 'toast-' + Date.now();
				const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert" style="min-width: 300px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
				
				let targetContainer = alertMessagesContainer;
				if (!targetContainer) {
					console.warn('Alert messages container not found. Creating temporary one.');
					targetContainer = document.getElementById('temp-alert-messages-container');
					if (!targetContainer) {
						targetContainer = document.createElement('div');
						targetContainer.id = 'temp-alert-messages-container';
						Object.assign(targetContainer.style, {
							position: 'fixed', top: '80px', right: '20px', zIndex: '1055'
						});
						document.body.appendChild(targetContainer);
					}
				}
				targetContainer.insertAdjacentHTML('beforeend', alertHtml);
				
				const newAlert = document.getElementById(alertId);
				if (newAlert) {
					const bsAlert = new bootstrap.Alert(newAlert);
					if (duration > 0) {
						setTimeout(() => {
							if (newAlert) bsAlert.close();
						}, duration);
					}
				}
			}
			
			document.querySelectorAll('.btn-delete-assignment').forEach(button => {
				button.addEventListener('click', function () {
					const coverId = this.dataset.coverId;
					const templateId = this.dataset.templateId;
					const coverName = this.dataset.coverName;
					const templateName = this.dataset.templateName;
					
					if (!confirm(`Are you sure you want to remove template "${templateName}" from cover "${coverName}"?`)) {
						return;
					}
					
					const originalButtonHtml = this.innerHTML;
					this.disabled = true;
					this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
					
					const url = `/admin/covers/${coverId}/templates/${templateId}/remove`;
					
					fetch(url, {
						method: 'POST',
						headers: {
							'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
							'Accept': 'application/json',
						},
					})
						.then(response => response.json().then(data => ({ status: response.status, body: data })))
						.then(({ status, body }) => {
							if (status >= 200 && status < 300 && body.success) {
								showAlert(body.message || 'Assignment removed successfully.', 'success');
								const itemToRemove = document.getElementById(`cover-${coverId}-template-${templateId}`);
								if (itemToRemove) {
									// itemToRemove is the .cover-template-item div. We need to remove its parent column.
									const columnToRemove = itemToRemove.closest('.col-lg-3, .col-md-4, .col-sm-6');
									if (columnToRemove) {
										columnToRemove.remove();
									} else {
										itemToRemove.remove(); // Fallback
									}
								}
								
								const templateGrid = document.getElementById(`template-grid-${coverId}`);
								if (templateGrid && templateGrid.children.length === 0) {
									templateGrid.remove();
									const coverCard = document.getElementById(`cover-card-${coverId}`);
									if (coverCard) {
										let noTemplatesMsg = coverCard.querySelector('.no-templates-message');
										if (!noTemplatesMsg) {
											noTemplatesMsg = document.createElement('p');
											noTemplatesMsg.className = 'no-templates-message';
											noTemplatesMsg.id = `no-templates-msg-${coverId}`;
											noTemplatesMsg.textContent = 'This cover currently has no templates assigned.';
											// Insert after the H4
											const heading = coverCard.querySelector('h4');
											if (heading && heading.nextSibling) {
												coverCard.insertBefore(noTemplatesMsg, heading.nextSibling);
											} else {
												coverCard.appendChild(noTemplatesMsg);
											}
										}
										noTemplatesMsg.style.display = 'block';
									}
								}
							} else {
								showAlert(body.message || 'Failed to remove assignment. Please try again.', 'danger');
								this.disabled = false;
								this.innerHTML = originalButtonHtml;
							}
						})
						.catch(error => {
							console.error('Error:', error);
							showAlert('An unexpected network error occurred. Please try again.', 'danger');
							this.disabled = false;
							this.innerHTML = originalButtonHtml;
						});
				});
			});
		});
	</script>
@endpush
