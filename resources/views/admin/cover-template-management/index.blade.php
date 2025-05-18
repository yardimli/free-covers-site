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
      .cover-template-grid {
          display: flex;
          flex-wrap: wrap;
          gap: 15px; /* Spacing between items */
      }
      .cover-template-item {
          position: relative;
          border: 1px solid #eee;
          padding: 10px;
          background-color: #f9f9f9;
          text-align: center;
          width: 170px; /* Fixed width for each item */
          box-shadow: 0 2px 4px rgba(0,0,0,0.1);
          border-radius: 4px;
      }
      .cover-template-item .cover-image-container {
          position: relative;
          width: 150px; /* Image container width */
          height: 225px; /* Approx 2:3 aspect ratio for covers */
          background-color: #e0e0e0; /* Placeholder background */
          margin-bottom: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          overflow: hidden; /* Clip images */
          border-radius: 3px;
      }
      .cover-template-item .cover-image-container img {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          object-fit: contain; /* Show whole image, letterbox if needed */
      }
      .cover-template-item .cover-image-container .template-overlay {
          /* z-index: 1; */ /* Not strictly needed if base image is also absolute */
      }
      .cover-template-item .template-name {
          font-size: 0.85em;
          color: #333;
          margin-bottom: 8px;
          height: 3em; /* Allow for two lines of text */
          overflow: hidden;
          text-overflow: ellipsis;
          /* white-space: nowrap; */ /* Use with caution, might cut off too much */
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
			<div class="alert alert-info">No covers found with assigned templates.</div>
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
						<div class="cover-template-grid" id="template-grid-{{ $cover->id }}">
							@foreach($cover->templates as $template)
								<div class="cover-template-item" id="cover-{{ $cover->id }}-template-{{ $template->id }}">
									<div class="cover-image-container">
										@if($cover->mockup_url && $cover->mockup_url !== asset('template/assets/img/placeholder-mockup.png'))
											<img src="{{ $cover->mockup_url }}" alt="{{ $cover->name }} Mockup" class="cover-base-image">
										@else
											<span class="text-muted small">No Cover Image</span>
										@endif
										
										@if($template->thumbnail_url && $template->thumbnail_url !== asset('images/placeholder-template-thumbnail.png'))
											<img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }} Overlay" class="template-overlay">
										@else
											{{-- No overlay shown if placeholder, or add a visual cue if needed --}}
										@endif
									</div>
									<p class="template-name" title="{{ $template->name }}">{{ $template->name }}</p>
									<button class="btn btn-sm btn-outline-danger btn-delete-assignment"
									        data-cover-id="{{ $cover->id }}"
									        data-template-id="{{ $template->id }}"
									        data-cover-name="{{ Str::limit($cover->name, 30) }}"
									        data-template-name="{{ Str::limit($template->name, 30) }}">
										<i class="fas fa-times"></i> Remove
									</button>
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
				if (alertMessagesContainer) {
					alertMessagesContainer.insertAdjacentHTML('beforeend', alertHtml);
				} else {
					console.warn('Alert messages container not found. Creating temporary one.');
					let tempContainer = document.getElementById('temp-alert-messages-container');
					if (!tempContainer) {
						tempContainer = document.createElement('div');
						tempContainer.id = 'temp-alert-messages-container';
						tempContainer.style.position = 'fixed';
						tempContainer.style.top = '80px';
						tempContainer.style.right = '20px';
						tempContainer.style.zIndex = '1055';
						document.body.appendChild(tempContainer);
					}
					tempContainer.insertAdjacentHTML('beforeend', alertHtml);
				}
				
				
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
					
					// Use the named route if available, otherwise construct manually
					// Assuming adminRoutes.removeCoverTemplateAssignmentBase is not defined, construct manually.
					// The route is POST admin/covers/{cover}/templates/{template}/remove
					const url = `/admin/covers/${coverId}/templates/${templateId}/remove`;
					
					fetch(url, {
						method: 'POST',
						headers: {
							'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
							'Accept': 'application/json',
							// 'Content-Type': 'application/json' // Not needed if no body is sent
						},
						// body: JSON.stringify({}) // No body needed for this specific route
					})
						.then(response => response.json().then(data => ({ status: response.status, body: data })))
						.then(({ status, body }) => {
							if (status >= 200 && status < 300 && body.success) {
								showAlert(body.message || 'Assignment removed successfully.', 'success');
								const itemToRemove = document.getElementById(`cover-${coverId}-template-${templateId}`);
								if (itemToRemove) {
									itemToRemove.remove();
								}
								
								// Check if the cover card has any templates left
								const templateGrid = document.getElementById(`template-grid-${coverId}`);
								if (templateGrid && templateGrid.children.length === 0) {
									// If grid is empty, remove it and show "no templates" message
									templateGrid.remove();
									const coverCard = document.getElementById(`cover-card-${coverId}`);
									if (coverCard) {
										let noTemplatesMsg = coverCard.querySelector('.no-templates-message');
										if (!noTemplatesMsg) {
											noTemplatesMsg = document.createElement('p');
											noTemplatesMsg.className = 'no-templates-message';
											noTemplatesMsg.id = `no-templates-msg-${coverId}`;
											noTemplatesMsg.textContent = 'This cover currently has no templates assigned.';
											coverCard.appendChild(noTemplatesMsg); // Append after the H4 or to a specific placeholder
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
