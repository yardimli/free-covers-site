<section>
    <header>
        <h4 class="card-title">{{ __('Profile Information') }}</h4>
        <p class="card-text text-muted"><small>{{ __("Update your account's profile information and email address.") }}</small></p>
    </header>
    
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>
    
    <form method="post" action="{{ route('profile.update') }}" class="mt-4">
        @csrf
        @method('patch')
        
        <div class="mb-3">
            <label for="name" class="form-label">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        
        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mb-3 p-3 bg-light border rounded">
                <p class="text-sm mb-1">
                    {{ __('Your email address is unverified.') }}
                </p>
                <button form="send-verification" class="btn btn-link p-0 text-decoration-none">
                    {{ __('Click here to re-send the verification email.') }}
                </button>
                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 text-sm text-success">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            </div>
        @endif
        
        <div class="d-flex align-items-center">
            <button type="submit" class="btn bj_theme_btn">{{ __('Save') }}</button>
            @if (session('status') === 'profile-updated')
                <p id="profile-updated-status" class="ms-3 text-success mb-0">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
