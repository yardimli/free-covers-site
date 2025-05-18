@extends('layouts.app')

@section('title', 'Register - Free Kindle Covers')

@section('content')
    <div class="login-area registration-area">
        <div class="login-wrapper">
            <div class="login-left">
                <a href="{{ route('home') }}" class="logo"><img src="{{ asset('template/assets/img/home/logo-dark.png') }}" alt="Image" style="max-width: 300px; height: 136px;"></a>
                <h3 class=  "title">Sign Up to Free Kindle Covers</h3>
                <div class="sibtitle">Create Your Account with Just Few Steps</div>
                
                <div class="social-links">
                    <a href="#"><img src="{{ asset('template/assets/img/login/google-icon.svg') }}" alt="Image"></a>
                </div>
                <div class="divider"><span>or</span></div>
                
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="input-field">
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus placeholder="Name">
                        @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="input-field">
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="Email">
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="input-field pass-field-with-icon">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="Password">
                        <i data-toggleTarget="#password" class="icon fas fa-eye toggle-password"></i>
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="input-field pass-field-with-icon">
                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm Password">
                        <i data-toggleTarget="#password-confirm" class="icon fas fa-eye toggle-password"></i>
                    </div>
                    <div class="d-flex justify-content-between input-field">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckChecked" required>
                            <label class="form-check-label" for="flexCheckChecked">
                                I Agreed with the <a href="#">Privacy policy</a>
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="bj_theme_btn w-100 border-0">Register</button>
                </form>
                <div class="new-user">
                    Already have an account? <a href="{{ route('login') }}">Login Here</a>
                </div>
            </div>
            <div class="login-right">
                <img class="mt-auto" src="{{ asset('template/assets/img/login/reginstration-img.png') }}" alt="Image">
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
