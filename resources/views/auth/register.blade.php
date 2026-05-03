{{-- @extends('layouts.app')

@section('content')
<style>

.auth-card {
    background: rgba(255, 255, 255, 0.1);
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

input {
    width: 100%;
    padding: 12px 15px;
    border: none;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    margin-bottom: 20px;
}

input:focus {
    outline: none;
    border: 1px solid #00bcd4;
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

.invalid-feedback {
    color: #ffaaaa;
    font-size: 13px;
    margin-top: -15px;
    margin-bottom: 10px;
}

</style>

<div class="login-container hero">
    <div class="auth-card">

        <h2>Create Account 🚀</h2>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <label>Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            <label>Password</label>
            <input type="password" name="password" required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" required>

            <button type="submit" class="btn-submit">
                Start Free Trial
            </button>

            <div style="margin-top: 15px; text-align:center;">
                <a href="{{ route('login') }}">Already have an account?</a>
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
                <span style="color:#6f42c1;"> Bloom monie</span>
            </h3>
        </div>

        <!-- Title -->
        <h4 class="text-center mb-4 fw-semibold">Create Account</h4>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Full Name -->
            <div class="mb-4 position-relative">
                <i class="bi bi-person position-absolute top-50 translate-middle-y ms-2 text-muted"></i>
                <input type="text" name="name"
                    class="form-control border-0 border-bottom ps-5 rounded-0"
                    placeholder="Full Name" value="{{ old('name') }}" required>
                @error('name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Email -->
            <div class="mb-4 position-relative">
                <i class="bi bi-envelope position-absolute top-50 translate-middle-y ms-2 text-muted"></i>
                <input type="email" name="email"
                    class="form-control border-0 border-bottom ps-5 rounded-0"
                    placeholder="Phone or Email" value="{{ old('email') }}" required>
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-4 position-relative">
                <i class="bi bi-lock position-absolute top-50 translate-middle-y ms-2 text-muted"></i>
                <input type="password" name="password"
                    class="form-control border-0 border-bottom ps-5 pe-5 rounded-0"
                    placeholder="Password" required>
                @error('password')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="mb-3 position-relative">
                <i class="bi bi-lock position-absolute top-50 translate-middle-y ms-2 text-muted"></i>
                <input type="password" name="password_confirmation"
                    class="form-control border-0 border-bottom ps-5 rounded-0"
                    placeholder="Confirm Password" required>
            </div>

            <!-- Checkbox -->
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" required>
                <label class="form-check-label">
                    Agree to Policy and Privacy
                </label>
            </div>

            <!-- Button -->
            <button class="btn w-100 text-white fw-semibold"
                style="background:#163a6b;">
                Sign Up
            </button>

            <!-- Login link -->
            <div class="text-center mt-3">
                <small>
                    Already have account?
                    <a href="{{ route('login') }}" class="fw-bold text-dark">Sign In</a>
                </small>
            </div>

            <!-- Divider -->
            <div class="d-flex align-items-center my-4">
                <hr class="flex-grow-1">
                <span class="mx-2 small text-muted">Or Sign up with</span>
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