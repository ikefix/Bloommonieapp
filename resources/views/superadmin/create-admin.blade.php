@extends('layouts.superadminapp')

@section('superadmincontent')

<div style="max-width:700px; margin:40px auto; font-family:Arial;">

    <!-- HEADER -->
    <div style="margin-bottom:20px;">
        <h2 style="margin:0;">Create Admin</h2>
        <p style="color:gray;">Create a new admin account and assign a subscription plan</p>
    </div>

    <!-- SUCCESS MESSAGE -->
    @if(session('success'))
        <div style="background:#d1fae5; color:#065f46; padding:10px; border-radius:6px; margin-bottom:15px;">
            {{ session('success') }}
        </div>
    @endif

    <!-- CARD -->
    <div style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">

        <form method="POST" action="{{ route('superadmin.store') }}">
            @csrf

            <!-- EMAIL -->
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Email</label>
                <input type="email" name="email" required
                    style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
            </div>

            <!-- PASSWORD -->
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Password</label>
                <input type="password" name="password" required
                    style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
            </div>

            <!-- PLAN -->
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Plan</label>
                <select name="plan" id="plan" onchange="togglePlanFields()" required
                    style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
                    <option value="">Select Plan</option>
                    <option value="free_trial">Free Trial</option>
                    <option value="basic">Basic</option>
                    <option value="lite">Lite</option>
                    <option value="business">Business</option>
                    <option value="enterprise">Enterprise</option>
                </select>
            </div>

            <!-- DURATION -->
            <div id="durationBox" style="display:none; margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Duration</label>
                <select name="plan_duration"
                    style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
                    <option value="1_month">1 Month</option>
                    <option value="1_year">1 Year</option>
                </select>
            </div>

            <!-- TRIAL DATE -->
            <div id="trialBox" style="display:none; margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Trial End Date</label>
                <input type="date" name="plan_end"
                    style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
            </div>

            <!-- BUTTON -->
            <button type="submit"
                style="width:100%; padding:12px; background:#2563eb; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">
                Create Admin
            </button>

        </form>

    </div>
</div>

<!-- SCRIPT -->
<script>
function togglePlanFields() {
    let plan = document.getElementById('plan').value;

    let durationBox = document.getElementById('durationBox');
    let trialBox = document.getElementById('trialBox');

    durationBox.style.display = 'none';
    trialBox.style.display = 'none';

    if (plan === 'basic' || plan === 'lite' || plan === 'business' || plan === 'enterprise') {
        durationBox.style.display = 'block';
    }

    if (plan === 'free_trial') {
        trialBox.style.display = 'block';
    }
}
</script>

@endsection