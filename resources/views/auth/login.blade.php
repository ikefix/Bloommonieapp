
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
        <h4 class="text-center mb-4 fw-semibold">Welcome Back </h4>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            <div class="mb-4 position-relative">
                <i class="bi bi-envelope position-absolute top-50 translate-middle-y ms-2 text-muted"></i>
                <input type="email" name="email"
                    class="form-control border-0 border-bottom ps-5 rounded-0"
                    placeholder="Please enter your email"
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
                <div style="font-size:16px; font-weight:800; color:#111827;">
                    Don’t have an account?
                    <a href="{{ route('register') }}" 
                    style="font-weight:950; color:#1d4ed8; text-decoration:none; margin-left:5px; letter-spacing:0.5px;">
                        CREATE ACCOUNT
                    </a>
                </div>
            </div>

            <!-- Divider -->
            <div class="d-flex align-items-center my-4">
                <hr class="flex-grow-1">
                <span class="mx-2 small text-muted"></span>
                <hr class="flex-grow-1">
            </div>

            <!-- Social -->
            <!-- <div class="d-flex justify-content-center gap-3">
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