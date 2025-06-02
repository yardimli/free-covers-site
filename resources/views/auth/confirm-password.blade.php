@extends('layouts.app')

@section('title', 'Confirm Password - Free Kindle Covers')

@section('content')
    <div class="login-area"> {{-- Consistent main wrapper --}}
        {{-- Optional: Background shapes for visual consistency --}}
        <div class="bg-shapes">
            <img class="wow fadeIn" src="{{ asset('template/assets/img/login/heart-shape-01.png') }}" alt="Image">
            <img class="wow fadeInLeft" src="{{ asset('template/assets/img/login/heart-shape-02.png') }}" alt="Image">
            <img class="wow fadeInLeft" src="{{ asset('template/assets/img/login/heart-shape-03.png') }}" alt="Image">
            <img class="wow" src="{{ asset('template/assets/img/login/heart-shape-04.png') }}" alt="Image">
        </div>
        
        <div class="login-wrapper">
            <div class="login-left">
                <a href="{{ route('home') }}" class="logo"><img src="{{ asset('template/assets/img/home/logo-dark.png') }}" alt="Image" style="max-width:300px; height: 136px;"></a>
                <h2 class="title">Confirm Password</h2>
                <div class="sibtitle mb-3">
                    {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                </div>
                
                {{-- Display general errors if any, though typically password confirmation errors are field-specific --}}
                @if ($errors->any() && !$errors->has('password'))
                    <div class="alert alert-danger mb-3" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <form method="POST" action="{{ route('password.confirm') }}">
                    @csrf
                    
                    <!-- Password -->
                    <div class="input-field pass-field-with-icon">
                        {{-- <label for="password" class="form-label">{{ __('Password') }}</label> --}}
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Password">
                        <i data-toggleTarget="#password" class="icon fas fa-eye toggle-password"></i>
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    
                    <div class="input-field mt-4">
                        <button type="submit" class="bj_theme_btn w-100 border-0">
                            {{ __('Confirm') }}
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="login-right">
                {{-- You can use a relevant image or the same login image for consistency --}}
                <img src="{{ asset('template/assets/img/login/login-img.png') }}" alt="Image">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // This script is already in your frontend-ui.js and login.blade.php,
            // but including it here ensures it works if those are not loaded or if this page is standalone.
            // If frontend-ui.js is globally included and handles this, you might not need it duplicated.
            $('.toggle-password').click(function() {
                $(this).toggleClass("fa-eye fa-eye-slash");
                var input = $($(this).attr("data-toggleTarget"));
                if (input.attr("type") == "password") {
                    input.attr("type", "text");
                } else {
                    input.attr("type", "password");
                }
            });
        });
    </script>
@endpush
