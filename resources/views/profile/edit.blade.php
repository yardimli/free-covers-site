@extends('layouts.app')

@section('title', 'Profile - Free Kindle Covers')

@section('content')
    <section class="profile-page-section py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="mb-5 text-center h1">{{ __('Profile') }}</h2>
                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4 p-md-5">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>
                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4 p-md-5">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        .profile-page-section .card-title {
            font-size: 1.5rem; /* h4 equivalent */
            margin-bottom: 0.5rem;
        }
        .profile-page-section .card-text small {
            font-size: 0.9em;
        }
        .profile-page-section .form-label {
            font-weight: 500;
        }
        .profile-page-section .btn-link.p-0 {
            vertical-align: baseline;
        }
        /* Ensure bj_theme_btn has good contrast and size if not already defined well */
        .bj_theme_btn {
            padding: 0.5rem 1rem; /* Example padding */
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Script to hide status messages after a delay
        function hideStatusMessage(elementId) {
            const statusElement = document.getElementById(elementId);
            if (statusElement) {
                setTimeout(() => {
                    // Simple fade out effect
                    statusElement.style.transition = 'opacity 0.5s ease';
                    statusElement.style.opacity = '0';
                    setTimeout(() => {
                        statusElement.style.display = 'none';
                    }, 500); // Wait for fade out to complete
                }, 3000); // Start hiding after 3 seconds
            }
        }
        
        hideStatusMessage('profile-updated-status');
        hideStatusMessage('password-updated-status');
        
        // If there are errors in userDeletion bag, show the modal automatically.
        @if ($errors->userDeletion->isNotEmpty() && old('password') !== null) // Check if form was submitted
        var confirmUserDeletionModalElement = document.getElementById('confirmUserDeletionModal');
        if (confirmUserDeletionModalElement) {
            var confirmUserDeletionModal = new bootstrap.Modal(confirmUserDeletionModalElement);
            confirmUserDeletionModal.show();
        }
        @endif
        
        // Focus the password input when delete confirmation modal is shown
        const modalElement = document.getElementById('confirmUserDeletionModal');
        if (modalElement) {
            modalElement.addEventListener('shown.bs.modal', function () {
                const passwordInput = document.getElementById('delete_user_password');
                // Only focus if not already focused by an error state or if it's empty
                if (passwordInput && document.activeElement !== passwordInput) {
                    passwordInput.focus();
                }
            });
        }
    </script>
@endpush
