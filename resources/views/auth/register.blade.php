@extends('layouts.app')

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
@endsection