@extends('layouts.app')
@section('title', 'Login - Free Kindle Covers')
@section('content')
    <div class="login-area">
        <div class="bg-shapes">
            <img class="wow fadeIn" src="{{ asset('template/assets/img/login/heart-shape-01.png') }}" alt="Image">
            <img class="wow fadeInLeft" src="{{ asset('template/assets/img/login/heart-shape-02.png') }}" alt="Image">
            <img class="wow fadeInLeft" src="{{ asset('template/assets/img/login/heart-shape-03.png') }}" alt="Image">
            <img class="wow" src="{{ asset('template/assets/img/login/heart-shape-04.png') }}" alt="Image">
        </div>
        <div class="login-wrapper">
            <div class="login-left">
                <a href="{{ route('home') }}" class="logo"><img src="{{ asset('template/assets/img/home/logo-dark.png') }}" alt="Image" style="max-width:300px; height: 136px;"></a>
                <h2 class="title">Login to Your Account</h2>
                <div class="sibtitle">Welcome Back! Select Method to login:</div>
                
                {{-- Google Login Button --}}
                <div class="social-links text-center my-3">
                    <a href="{{ route('social.login', 'google') }}" class="btn btn-outline-dark w-100 d-flex align-items-center justify-content-center" style="padding: 10px;">
                        <img src="{{ asset('template/assets/img/login/google-icon.svg') }}" alt="Google" style="height: 20px; margin-right: 10px;"> Sign in with Google
                    </a>
                </div>
                
                <div class="divider"><span>or use your email</span></div>
                
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="input-field">
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="Email">
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="input-field pass-field-with-icon">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Password">
                        <i data-toggleTarget="#password" class="icon fas fa-eye toggle-password"></i>
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-between input-field">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="forget-password">Forgot Password?</a>
                        @endif
                    </div>
                    <button type="submit" class="bj_theme_btn w-100 border-0">Log In</button>
                </form>
                <div class="new-user">
                    New user? <a href="{{ route('register') }}">Create an account</a>
                </div>
            </div>
            <div class="login-right">
                <img src="{{ asset('template/assets/img/login/login-img.png') }}" alt="Image">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.toggle-password').click(function() {
                var target = $(this).attr('data-toggleTarget');
                if ($(target).attr('type') == 'password') {
                    $(target).attr('type', 'text');
                    $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    $(target).attr('type', 'password');
                    $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        });
    </script>
@endpush
