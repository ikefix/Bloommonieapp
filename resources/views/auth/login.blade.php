{{-- 

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

            <div>
                <a href="{{route('register')}}">Create An Account</a>
            </div>
        </form>
    </div>
</div>
@endsection --}}



@extends('layouts.app')

@section('content')

<div class="container d-flex justify-content-center align-items-center min-vh-100">

    <div class="w-100" style="max-width: 420px;">

        <!-- Logo -->
        <div class="text-center mb-4">
            <h3 class="fw-bold">
                <span style="color:#6f42c1;"> Bloom Mmonie</span>
            </h3>
        </div>

        <!-- Title -->
        <h4 class="text-center mb-4 fw-semibold">Welcome Back 👋</h4>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            <div class="mb-4 position-relative">
                <i class="bi bi-envelope position-absolute top-50 translate-middle-y ms-2 text-muted"></i>
                <input type="email" name="email"
                    class="form-control border-0 border-bottom ps-5 rounded-0"
                    placeholder="Phone or Email"
                    value="{{ old('email') }}"
                    required autofocus>

                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-4 position-relative">
                <i class="bi bi-lock position-absolute top-50 translate-middle-y ms-2 text-muted"></i>
                <input type="password" name="password"
                    class="form-control border-0 border-bottom ps-5 pe-5 rounded-0"
                    placeholder="Password"
                    required>

                @error('password')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Role -->
            <div class="mb-4 position-relative">
                <i class="bi bi-person-badge position-absolute top-50 translate-middle-y ms-2 text-muted"></i>
                <select name="role"
                    class="form-control border-0 border-bottom ps-5 rounded-0"
                    required>
                    <option value="">Login As</option>
                    <option value="superadmin"></option>
                    <option value="cashier">Cashier</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <!-- Remember -->
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember"
                    {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label">
                    Remember Me
                </label>
            </div>

            <!-- Button -->
            <button class="btn w-100 text-white fw-semibold"
                style="background:#163a6b;">
                Login
            </button>

            <!-- Forgot -->
            @if (Route::has('password.request'))
                <div class="text-center mt-3">
                    <a href="{{ route('password.request') }}" class="small text-muted">
                        Forgot Password?
                    </a>
                </div>
            @endif

            <!-- Register -->
            <div class="text-center mt-2">
                <small>
                    Don’t have an account?
                    <a href="{{ route('register') }}" class="fw-bold text-dark">
                        Sign Up
                    </a>
                </small>
            </div>

            <!-- Divider -->
            <div class="d-flex align-items-center my-4">
                <hr class="flex-grow-1">
                <span class="mx-2 small text-muted">Or Login with</span>
                <hr class="flex-grow-1">
            </div>

            <!-- Social -->
            <div class="d-flex justify-content-center gap-3">
                <button type="button" class="btn btn-light shadow-sm">
                    <i class="bi bi-google"></i>
                </button>
                <button type="button" class="btn btn-light shadow-sm">
                    <i class="bi bi-twitter"></i>
                </button>
                <button type="button" class="btn btn-light shadow-sm">
                    <i class="bi bi-facebook"></i>
                </button>
            </div>

        </form>

    </div>

</div>

@endsection