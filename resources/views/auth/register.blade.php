@extends('layouts.app')

@section('content')

<div class="container d-flex justify-content-center align-items-center min-vh-100">

    <div class="w-100" style="max-width: 420px;">

        <!-- Logo -->
        <div class="text-center mb-4">
            <h3 class="fw-bold">
                <span style="color:#6f42c1;"> Bloommonie</span>
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
                    placeholder="Email" value="{{ old('email') }}" required>
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Phone Number -->
            <div class="mb-4 position-relative">
                <i class="bi bi-telephone position-absolute top-50 translate-middle-y ms-2 text-muted"></i>
                <input type="text" name="phone"
                    class="form-control border-0 border-bottom ps-5 rounded-0"
                    placeholder="Phone Number" value="{{ old('phone') }}">
                @error('phone')
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
                <div style="font-size:16px; font-weight:800; color:#111827;">
                    Already have an account?
                    <a href="{{ route('login') }}" 
                    style="font-weight:950; color:#1d4ed8; text-decoration:none; margin-left:5px; letter-spacing:0.5px;">
                        SIGN IN
                    </a>
                </div>
            </div>

            <div class="d-flex align-items-center my-4">
            <!-- Divider -->
                <hr class="flex-grow-1">
                <span class="mx-2 small text-muted"></span>
                <hr class="flex-grow-1">
            </div>

            <!-- Social
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
            </div> -->

        </form>

    </div>

</div>

@endsection