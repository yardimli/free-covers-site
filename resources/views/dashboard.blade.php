@extends('layouts.app')

@section('title', 'My Dashboard - Free Kindle Covers')

@push('styles')
    <style>
        .dashboard-section {
            margin-bottom: 40px;
            padding: 25px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .dashboard-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .dashboard-section-header h3 {
            margin-bottom: 0;
            font-size: 1.5rem;
            color: #333;
        }
        .dashboard-item-card {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
            min-height: 380px; /* Ensure consistent card height */
            display: flex;
            flex-direction: column;
        }
        .dashboard-item-card:hover {
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
        }
        .dashboard-item-card .cover-image-container {
            height: 250px; /* Fixed height for image container */
            background-color: #f0f0f0; /* Placeholder bg */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dashboard-item-card .cover-mockup-image,
        .dashboard-item-card .template-overlay-image {
            max-height: 100%; /* Ensure image fits within container */
            width: auto;
            max-width: 100%;
        }
        .dashboard-item-card .template-overlay-image {
            /* Adjustments if needed for dashboard view */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: auto !important; /* Override general style if needed */
            height: auto !important;
            max-width: 90%;
            max-height: 90%;
        }
        .dashboard-item-content {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .dashboard-item-content h5 {
            font-size: 1rem;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dashboard-item-actions .btn {
            margin-right: 5px;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .dashboard-item-actions .btn:last-child {
            margin-right: 0;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #777;
            border: 2px dashed #ddd;
            border-radius: 8px;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ccc;
        }
        .upload-image-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #007bff;
            color: #007bff;
            min-height: 150px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-image-card:hover {
            background-color: #e7f3ff;
            border-color: #0056b3;
        }
        .upload-image-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .user-image-thumb {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }
    </style>
@endpush

@section('content')
    <section class="bj_main_features_section pt-5 pb-5" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Welcome, {{ $user->name }}!</h2>
                        <a href="{{ route('designer.index') }}" class="bj_theme_btn">
                            <i class="fas fa-plus-circle me-2"></i>Create New Cover
                        </a>
                    </div>
                    <p class="text-muted">Manage your covers, images, and preferences.</p>
                </div>
            </div>
            
            <!-- My eBook Covers Section -->
            <div class="dashboard-section">
                <div class="dashboard-section-header">
                    <h3><i class="fas fa-book me-2 text-primary"></i>My eBook Covers</h3>
                    @if($ebookCovers->count() > 0)
                        <a href="{{ route('shop.index', ['user_covers' => 'ebook']) }}" class="btn btn-sm btn-outline-primary">View All</a>
                    @endif
                </div>
                @if($ebookCovers->isNotEmpty())
                    <div class="row">
                        @foreach($ebookCovers as $cover)
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="dashboard-item-card">
                                    <a href="{{ route('covers.show', $cover->id) }}" class="cover-image-container">
                                        <img src="{{ $cover->mockup_url }}" alt="{{ $cover->name }}" class="cover-mockup-image">
                                        @if($cover->active_template_overlay_url)
                                            <img src="{{ $cover->active_template_overlay_url }}" alt="Template Overlay" class="template-overlay-image">
                                        @endif
                                    </a>
                                    <div class="dashboard-item-content">
                                        <div>
                                            <h5 data-bs-toggle="tooltip" title="{{ $cover->name }}">{{ Str::limit($cover->name, 25) }}</h5>
                                            <p class="text-muted small mb-2">Last updated: {{ $cover->updated_at->format('M d, Y') }}</p>
                                        </div>
                                        <div class="dashboard-item-actions">
                                            <a href="{{ route('designer.index', ['cover_id' => $cover->id]) }}" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                            <a href="#" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download"></i> DL</a>
                                            {{-- <form action="{{ route('dashboard.covers.favorite', $cover->id) }}" method="POST" style="display:inline;">
																								@csrf
																								<button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-heart"></i></button>
																						</form> --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <p>You haven't created any eBook covers yet.</p>
                        <a href="{{ route('designer.index', ['type' => 'ebook']) }}" class="bj_theme_btn small_btn">Create an eBook Cover</a>
                    </div>
                @endif
            </div>
            
            <!-- My Print Covers Section -->
            <div class="dashboard-section">
                <div class="dashboard-section-header">
                    <h3><i class="fas fa-book-reader me-2 text-success"></i>My Print Covers</h3>
                    @if($printCovers->count() > 0)
                        <a href="{{ route('shop.index', ['user_covers' => 'print']) }}" class="btn btn-sm btn-outline-success">View All</a>
                    @endif
                </div>
                @if($printCovers->isNotEmpty())
                    <div class="row">
                        @foreach($printCovers as $cover)
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="dashboard-item-card">
                                    <a href="{{ route('covers.show', $cover->id) }}" class="cover-image-container">
                                        <img src="{{ $cover->mockup_url ?? asset('template/assets/img/placeholder-mockup.png') }}" alt="{{ $cover->name }}" class="cover-mockup-image">
                                        {{-- Add overlay if applicable for print covers --}}
                                    </a>
                                    <div class="dashboard-item-content">
                                        <div>
                                            <h5 data-bs-toggle="tooltip" title="{{ $cover->name }}">{{ Str::limit($cover->name, 25) }}</h5>
                                            <p class="text-muted small mb-2">Last updated: {{ $cover->updated_at->format('M d, Y') }}</p>
                                        </div>
                                        <div class="dashboard-item-actions">
                                            <a href="{{ route('designer.index', ['cover_id' => $cover->id]) }}" class="btn btn-sm btn-success"><i class="fas fa-edit"></i> Edit</a>
                                            <a href="#" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download"></i> DL</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-print"></i>
                        <p>You haven't created any print covers yet.</p>
                        <a href="{{ route('designer.index', ['type' => 'print']) }}" class="bj_theme_btn small_btn btn-success">Create a Print Cover</a>
                    </div>
                @endif
            </div>
            
            <!-- Favorites Section -->
            <div class="dashboard-section">
                <div class="dashboard-section-header">
                    <h3><i class="fas fa-heart me-2 text-danger"></i>My Favorites</h3>
                    @if($favoriteCovers->count() > 0)
                        <a href="{{ route('shop.index', ['favorites' => 'true']) }}" class="btn btn-sm btn-outline-danger">View All</a>
                    @endif
                </div>
                @if($favoriteCovers->isNotEmpty())
                    <div class="row">
                        @foreach($favoriteCovers as $cover)
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="dashboard-item-card">
                                    <a href="{{ route('covers.show', $cover->id) }}" class="cover-image-container">
                                        <img src="{{ $cover->mockup_url ?? asset('template/assets/img/placeholder-mockup.png') }}" alt="{{ $cover->name }}" class="cover-mockup-image">
                                        @if($cover->active_template_overlay_url) {{-- Assuming similar logic for favorites --}}
                                        <img src="{{ $cover->active_template_overlay_url }}" alt="Template Overlay" class="template-overlay-image">
                                        @endif
                                    </a>
                                    <div class="dashboard-item-content">
                                        <div>
                                            <h5 data-bs-toggle="tooltip" title="{{ $cover->name }}">{{ Str::limit($cover->name, 25) }}</h5>
                                            <p class="text-muted small mb-2">Favorited on: {{ $cover->pivot->created_at->format('M d, Y') ?? 'N/A' }}</p>
                                        </div>
                                        <div class="dashboard-item-actions">
                                            <a href="{{ route('covers.show', $cover->id) }}" class="btn btn-sm btn-danger"><i class="fas fa-eye"></i> View</a>
                                            {{-- <form action="{{ route('dashboard.covers.favorite', $cover->id) }}" method="POST" style="display:inline;">
																								@csrf
																								<button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-heart-broken"></i> Unfavorite</button>
																						</form> --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="far fa-heart"></i>
                        <p>You haven't favorited any covers yet.</p>
                        <a href="{{ route('shop.index') }}" class="bj_theme_btn small_btn btn-danger">Browse Covers</a>
                    </div>
                @endif
            </div>
            
            <!-- My Images Section -->
            <div class="dashboard-section">
                <div class="dashboard-section-header">
                    <h3><i class="fas fa-images me-2 text-info"></i>My Uploaded Images</h3>
                    {{-- <a href="#" class="btn btn-sm btn-outline-info">Manage Images</a> --}}
                </div>
                @if($userImages->isNotEmpty())
                    <div class="row">
                        <div class="col-lg-2 col-md-3 col-sm-4 mb-3">
                            <div class="dashboard-item-card upload-image-card" onclick="document.getElementById('imageUploadInput').click();">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Upload New</span>
                            </div>
                            <input type="file" id="imageUploadInput" style="display:none;" accept="image/*"> {{-- Add JS to handle upload --}}
                        </div>
                        @foreach($userImages as $image)
                            <div class="col-lg-2 col-md-3 col-sm-4 mb-3">
                                <div class="dashboard-item-card">
                                    <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $image->name }}" class="user-image-thumb">
                                    <div class="dashboard-item-content p-2">
                                        <p class="small text-muted mb-1" data-bs-toggle="tooltip" title="{{ $image->name }}">{{ Str::limit($image->name, 15) }}</p>
                                        <button class="btn btn-xs btn-outline-danger w-100"><i class="fas fa-trash-alt"></i> Delete</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="row">
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="dashboard-item-card upload-image-card" onclick="document.getElementById('imageUploadInput').click();">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Upload Your First Image</span>
                            </div>
                            <input type="file" id="imageUploadInput" style="display:none;" accept="image/*">
                        </div>
                        <div class="col-lg-9 col-md-8 col-sm-6 mb-4 d-flex align-items-center">
                            <p class="text-muted">Upload your own images to use in your cover designs. <br>Supported formats: JPG, PNG.</p>
                        </div>
                    </div>
                @endif
            </div>
        
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
        
        // Basic image upload handling (you'll need backend logic)
        // document.getElementById('imageUploadInput').addEventListener('change', function(event) {
        //     const file = event.target.files[0];
        //     if (file) {
        //         console.log('File selected:', file.name);
        //         // Here you would typically use FormData to upload the file via AJAX
        //         // Example:
        //         // const formData = new FormData();
        //         // formData.append('image', file);
        //         // fetch('/dashboard/images/upload', { method: 'POST', body: formData })
        //         // .then(response => response.json())
        //         // .then(data => { console.log(data); window.location.reload(); })
        //         // .catch(error => console.error('Error:', error));
        //         alert('Image upload functionality needs backend implementation.');
        //     }
        // });
    </script>
@endpush
