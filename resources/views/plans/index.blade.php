@extends('layouts.adminapp')

@section('admincontent')

@php
    use Carbon\Carbon;

    $status = $user->plan_end && Carbon::parse($user->plan_end)->isFuture()
        ? 'Active'
        : 'Expired';
@endphp

<div class="container">

    <div class="mb-4">
        <h2 class="fw-bold text-primary">
            My Subscription Plan
        </h2>

        <p class="text-muted">
            Manage your subscription, usage, and plan limits.
        </p>
    </div>

    {{-- PLAN DETAILS --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <strong>Current Plan Details</strong>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-4 mb-3">
                    <h6 class="text-muted">Current Plan</h6>

                    <h3 class="fw-bold text-capitalize">
                        {{ str_replace('_', ' ', $user->plan) }}
                    </h3>
                </div>

                <div class="col-md-4 mb-3">
                    <h6 class="text-muted">Status</h6>

                    @if($status == 'Active')
                        <span class="badge bg-success p-2">
                            Active
                        </span>
                    @else
                        <span class="badge bg-danger p-2">
                            Expired
                        </span>
                    @endif
                </div>

                <div class="col-md-4 mb-3">
                    <h6 class="text-muted">Days Remaining</h6>

                    <h4 class="fw-bold text-warning">
                        {{ $daysRemaining > 0 ? $daysRemaining.' Days' : 'Expired' }}
                    </h4>
                </div>

            </div>

            <hr>

            <div class="row">

                <div class="col-md-4">
                    <h6 class="text-muted">
                        Plan Start Date
                    </h6>

                    <p>
                        {{ $user->plan_start
                            ? Carbon::parse($user->plan_start)->format('d M Y')
                            : 'N/A'
                        }}
                    </p>
                </div>

                <div class="col-md-4">
                    <h6 class="text-muted">
                        Expiry Date
                    </h6>

                    <p>
                        {{ $user->plan_end
                            ? Carbon::parse($user->plan_end)->format('d M Y')
                            : 'N/A'
                        }}
                    </p>
                </div>

                <div class="col-md-4">
                    <h6 class="text-muted">
                        Plan Duration
                    </h6>

                    <p>
                        {{ $user->plan_duration ?? 'N/A' }}
                        Month(s)
                    </p>
                </div>

            </div>

        </div>
    </div>

    {{-- USAGE --}}
    <div class="row mb-4">

        <div class="col-md-4">

            <div class="card shadow-sm text-center p-3">

                <h6 class="text-muted">
                    Users
                </h6>

                <h3>
                    {{ $usersUsed }}
                    /
                    {{ $limits['users'] ?? 'Unlimited' }}
                </h3>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow-sm text-center p-3">

                <h6 class="text-muted">
                    Shops
                </h6>

                <h3>
                    {{ $shopsUsed }}
                    /
                    {{ $limits['stores'] ?? 'Unlimited' }}
                </h3>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow-sm text-center p-3">

                <h6 class="text-muted">
                    Products
                </h6>

                <h3>
                    {{ $productsUsed }}
                    /
                    {{ $limits['products'] ?? 'Unlimited' }}
                </h3>

            </div>

        </div>

    </div>

    {{-- FEATURES --}}
    <div class="card shadow-sm mb-4">

        <div class="card-header bg-success text-white">
            <strong>Plan Features</strong>
        </div>

        <div class="card-body">

            <div class="row">

                @foreach($limits['features'] as $feature)

                    <div class="col-md-4 mb-3">

                        <div class="border rounded p-3">

                            <i class="fas fa-check-circle text-success me-2"></i>

                            {{ ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $feature
                                )
                            ) }}

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    </div>

    {{-- PLAN RECOMMENDATION --}}
    <div class="card shadow-sm">

        <div class="card-body text-center">

            <h4 class="mb-3">
                Need More Features?
            </h4>

            <p class="text-muted">

                Upgrade your BloomMonie subscription to unlock
                advanced inventory management, production,
                stock transfers, reporting, and much more.

            </p>

            <a
                href="{{ url('/pricing') }}"
                class="btn btn-warning btn-lg"
            >
                Upgrade Plan
            </a>

        </div>

    </div>

</div>

@endsection