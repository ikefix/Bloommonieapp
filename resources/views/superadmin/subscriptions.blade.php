@extends('layouts.superadminapp')

@section('superadmincontent')

<style>

.page-title{
    font-size:32px;
    font-weight:800;
    color:#111827;
    margin-bottom:8px;
}

.page-subtitle{
    color:#6b7280;
    margin-bottom:30px;
}

.subscription-card{
    background:white;
    border-radius:24px;
    padding:25px;
    box-shadow:0 10px 40px rgba(0,0,0,0.06);
    overflow:hidden;
}

.table{
    margin-bottom:0;
}

.table thead{
    background:#f9fafb;
}

.table thead th{
    border:none;
    padding:18px;
    font-size:14px;
    color:#6b7280;
    text-transform:uppercase;
    letter-spacing:.5px;
}

.table tbody td{
    padding:20px 18px;
    vertical-align:middle;
    border-color:#f3f4f6;
}

.admin-box{
    display:flex;
    align-items:center;
    gap:14px;
}

.admin-avatar{
    width:48px;
    height:48px;
    border-radius:50%;
    background:linear-gradient(135deg,#7c3aed,#a855f7);
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-weight:700;
    font-size:18px;
}

.admin-email{
    color:#6b7280;
    font-size:14px;
}

.plan-badge{
    padding:8px 15px;
    border-radius:50px;
    font-size:13px;
    font-weight:700;
    display:inline-block;
}

.plan-business{
    background:#dcfce7;
    color:#166534;
}

.plan-lite{
    background:#dbeafe;
    color:#1d4ed8;
}

.plan-basic{
    background:#fef3c7;
    color:#92400e;
}

.plan-free_trial{
    background:#fee2e2;
    color:#991b1b;
}

.status-active{
    color:#16a34a;
    font-weight:700;
}

.status-expired{
    color:#dc2626;
    font-weight:700;
}

.product-key{
    font-family:monospace;
    background:#f3f4f6;
    padding:7px 12px;
    border-radius:8px;
    font-size:13px;
}

.expire-soon{
    color:#ea580c;
    font-weight:700;
}

</style>

<div class="container-fluid">

    <h1 class="page-title">
        Subscription Management
    </h1>

    <p class="page-subtitle">
        Monitor all business owners, plans, expiration dates and activation status.
    </p>

    <div class="subscription-card">

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Plan</th>
                        <th>Duration</th>
                        <th>Started</th>
                        <th>Ending</th>
                        <th>Status</th>
                        <th>Product Key</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($admins as $admin)

                        @php

                            $expired = false;

                            if($admin->plan_end){
                                $expired = now()->gt($admin->plan_end);
                            }

                            $daysLeft = null;

                            if($admin->plan_end){
                                $daysLeft = now()->diffInDays($admin->plan_end, false);
                            }

                        @endphp

                        <tr>

                            <td>
                                <div class="admin-box">

                                    <div class="admin-avatar">
                                        {{ strtoupper(substr($admin->email,0,1)) }}
                                    </div>

                                    <div>
                                        <strong>
                                            {{ $admin->name }}
                                        </strong>

                                        <div class="admin-email">
                                            {{ $admin->email }}
                                        </div>
                                    </div>

                                </div>
                            </td>

                            <td>

                                <span class="plan-badge plan-{{ $admin->plan }}">
                                    {{ ucfirst(str_replace('_',' ', $admin->plan)) }}
                                </span>

                            </td>

                            <td>
                                {{ $admin->plan_duration ?? 'Trial Plan' }}
                            </td>

                            <td>
                                {{ $admin->plan_start ?? '---' }}
                            </td>

                            <td>

                                @if($admin->plan_end)

                                    {{ $admin->plan_end }}

                                    @if($daysLeft <= 5 && $daysLeft >= 0)

                                        <div class="expire-soon">
                                            Expiring Soon
                                        </div>

                                    @endif

                                @else

                                    Unlimited

                                @endif

                            </td>

                            <td>

                                @if($expired)

                                    <span class="status-expired">
                                        Expired
                                    </span>

                                @else

                                    <span class="status-active">
                                        Active
                                    </span>

                                @endif

                            </td>

                            <td>

                                <span class="product-key">
                                    {{ $admin->product_key }}
                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="text-center py-5">
                                No Admins Found
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection