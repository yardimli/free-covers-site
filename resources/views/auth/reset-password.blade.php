@extends('layouts.app')
@section('title', 'Reset Password - Free Kindle Covers')

@section('content')
    <div class="login-area"> {{-- Main wrapper, consistent with login.blade.php --}}
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
                <h2 class="title">Reset Your Password</h2>
                <div class="sibtitle">Enter your email and new password below.</div>
                
                <form method="POST" action="{{ route('password.store') }}">
                    @csrf
                    
                    <!-- Password Reset Token -->
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">
                    
                    <!-- New Password -->
                    <div class="input-field pass-field-with-icon">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="New Password">
                        <i data-toggleTarget="#password" class="icon fas fa-eye toggle-password"></i>
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    
                    <!-- Confirm New Password -->
                    <div class="input-field pass-field-with-icon">
                        <input id="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm New Password">
                        <i data-toggleTarget="#password_confirmation" class="icon fas fa-eye toggle-password"></i>
                        @error('password_confirmation')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    
                    <div class="input-field mt-4"> {{-- Add some margin-top for the button --}}
                        <button type="submit" class="bj_theme_btn w-100 border-0">
                            {{ __('Reset Password') }}
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
