@extends('layouts.app')

@section('title', 'Forgot Password - Free Kindle Covers')

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
                <h2 class="title">Forgot Your Password?</h2>
                <div class="sibtitle mb-3">
                    {{ __('No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                </div>
                
                <!-- Session Status -->
                @if (session('status'))
                    <div class="alert alert-success mb-3" role="alert">
                        {{ session('status') }}
                    </div>
                @endif
                
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    
                    <!-- Email Address -->
                    <div class="input-field">
                        {{-- <label for="email" class="form-label">{{ __('Email') }}</label> --}} {{-- Labels are often placeholders in this theme --}}
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus placeholder="Email">
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    
                    <div class="input-field mt-4"> {{-- Add some margin-top for the button --}}
                        <button type="submit" class="bj_theme_btn w-100 border-0">
                            {{ __('Email Password Reset Link') }}
                        </button>
                    </div>
                </form>
                
                <div class="new-user mt-3 text-center">
                    Remember your password? <a href="{{ route('login') }}">Login here</a>
                </div>
                <div class="new-user mt-2 text-center">
                    Don't have an account? <a href="{{ route('register') }}">Register here</a>
                </div>
            </div>
            
            <div class="login-right">
                {{-- You can use a relevant image or the same login image for consistency --}}
                <img src="{{ asset('template/assets/img/login/login-img.png') }}" alt="Image">
            </div>
        </div>
    </div>
@endsection

{{-- No specific scripts needed for this page usually, but can add @push('scripts') if necessary --}}
