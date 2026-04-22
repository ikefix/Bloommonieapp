{{-- @extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Role Selection Dropdown -->
                        <div class="row mb-3">
                            <label for="role" class="col-md-4 col-form-label text-md-end">{{ __('Login As') }}</label>

                            <div class="col-md-6">
                                <select id="role" class="form-control" name="role" required>
                                    <option value="cashier">Cashier</option>
                                    <option value="manager">Manager</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection --}}














@extends('layouts.app')

@section('content')
<style>


    .auth-card {
        background: rgba(255, 255, 255, 0.1); /* Transparent white */
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 40px;
        border-radius: 12px;
        max-width: 500px;
        width: 100%;
        color: #fff;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
    }

    .auth-card h2 {
        text-align: center;
        margin-bottom: 30px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #f0f0f0;
    }

    input,
    select {
        width: 100%;
        padding: 12px 15px;
        border: none;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        margin-bottom: 20px;
    }

    input:focus,
    select:focus {
        outline: none;
        border: 1px solid #00bcd4;
    }

    .form-check {
        margin-bottom: 20px;
        display: flex;
        text-align: center;
        align-items: center;
    }

    .form-check input {
        margin-right: 10px;
        margin-bottom: 0;
        width: 15px;
    }

    .form-check label{
        margin-bottom: 0
    }

    .btn-submit {
        background: #00bcd4;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        color: white;
        font-size: 16px;
        cursor: pointer;
        width: 100%;
        transition: background 0.3s ease;
    }

    .btn-submit:hover {
        background: #0097a7;
    }

    .forgot-link {
        display: inline-block;
        margin-top: 15px;
        font-size: 14px;
        text-align: center;
        color: #ddd;
        text-decoration: underline;
    }

    .invalid-feedback {
        color: #ffaaaa;
        font-size: 13px;
        margin-top: -15px;
        margin-bottom: 10px;
    }

    option{
        color: black
    }
</style>

<div class="login-container hero">
    <div class="auth-card">
        <h2>{{ __('Login') }}</h2>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">{{ __('Email Address') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Enter your email">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            <label for="password">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required placeholder="Enter your password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            <label for="role">{{ __('Login As') }}</label>
            <select id="role" name="role" required>
                <option value="cashier">Cashier</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
                <option value="superadmin"></option>
            </select>

            <div class="form-check">
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">{{ __('Remember Me') }}</label>
            </div>

            <button type="submit" class="btn-submit">{{ __('Login') }}</button>

            @if (Route::has('password.request'))
                <a class="forgot-link" href="{{ route('password.request') }}">
                    {{ __('Forgot Your Password?') }}
                </a>
            @endif
        </form>
    </div>
</div>
@endsection
