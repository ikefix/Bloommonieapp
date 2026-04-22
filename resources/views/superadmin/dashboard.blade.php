@extends('layouts.superadminapp')

@section('superadmincontent')

    <div class="container">
        <h3>Welcome {{ Auth::user()->name }}</h3>
    </div>
@endsection
