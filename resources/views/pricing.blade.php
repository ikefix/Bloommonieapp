<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Choose Plan</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #1e1e2f, #2c3e50);
    color: white;
}

/* Close button */
.header {
    position: fixed;
    top: 15px;
    right: 20px;
}

.close-btn {
    font-size: 20px;
    color: white;
    text-decoration: none;
    background: rgba(0,0,0,0.4);
    padding: 8px 12px;
    border-radius: 50%;
}

/* Title */
.title {
    text-align: center;
    margin-top: 60px;
}

/* Toggle */
.billing-toggle {
    display: flex;
    justify-content: center;
    margin: 20px 0;
    gap: 10px;
}

.billing-toggle button {
    padding: 8px 16px;
    border-radius: 20px;
    border: none;
    cursor: pointer;
    background: rgba(255,255,255,0.2);
    color: white;
    transition: 0.3s;
}

.billing-toggle button.active {
    background: #00bcd4;
}

/* Grid */
.pricing-container {
    max-width: 1200px;
    margin: auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    padding: 20px;
}

/* Card */
.plan-card {
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(10px);
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    transition: 0.3s;
    border: 1px solid rgba(255,255,255,0.1);
}

.plan-card:hover {
    transform: translateY(-6px);
}

.plan-card.featured {
    border: 2px solid #00bcd4;
    transform: scale(1.05);
}

/* Text */
.plan-title {
    font-size: 20px;
}

.plan-price {
    font-size: 28px;
    margin: 10px 0;
}

.plan-price1 {
    font-size: 28px;
    margin: 10px 0;
}

.plan-features {
    text-align: left;
    font-size: 14px;
    margin-top: 15px;
}

.plan-features li {
    margin-bottom: 8px;
}

/* Button */
.plan-btn {
    margin-top: 15px;
    padding: 10px;
    border-radius: 20px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    width: 100%;
}

.buy {
    background: #00bcd4;
    color: white;
}

.buy:hover {
    background: #0097a7;
}

.active-plan {
    background: #4CAF50;
    cursor: not-allowed;
}
</style>
</head>

<body>

<div class="header">
    <a href="/admin-dashboard" class="close-btn">✖</a>
</div>

<h2 class="title">Choose Your Plan 🚀</h2>

<!-- TOGGLE -->
<div class="billing-toggle">
    <button id="monthlyBtn" class="active">Monthly</button>
    <button id="yearlyBtn">Yearly 🔥</button>
</div>

@php
    $user = auth()->user();
    $owner = $user->owner_id 
        ? \App\Models\User::find($user->owner_id)
        : $user;
@endphp

<div class="pricing-container">

    <!-- BASIC -->
    <div class="plan-card">
        <div class="plan-title">Basic</div>

        <div class="plan-price" data-monthly="7000" data-yearly="70000">
            ₦7,000/mo
        </div>

        <ul class="plan-features">
            <li>1 User</li>
            <li>1 Store</li>
            <li>Max 500 Products</li>
            <li>Sales Report</li>
            <li>Stock Adjustment</li>
            <li>Expense Tracking</li>
            <li>Report Download</li>
            <li>24/7 Support</li>
        </ul>

        <button class="plan-btn {{ $owner->plan == 'basic' ? 'active-plan' : 'buy' }}">
            {{ $owner->plan == 'basic' ? 'Chosen Plan' : 'Subscribe' }}
        </button>
    </div>

    <!-- LITE -->
    <div class="plan-card featured">
        <div class="plan-title">Lite 🔥</div>

        <div class="plan-price" data-monthly="10000" data-yearly="100000">
            ₦10,000/mo
        </div>

        <ul class="plan-features">
            <li>Unlimited Users</li>
            <li>2 Stores</li>
            <li>Unlimited Products</li>
            <li>Sales Report</li>
            <li>Stock Adjustment</li>
            <li>Expense Tracking</li>
            <li>24/7 Support</li>
        </ul>

        <button class="plan-btn {{ $owner->plan == 'lite' ? 'active-plan' : 'buy' }}">
            {{ $owner->plan == 'lite' ? 'Chosen Plan' : 'Subscribe' }}
        </button>
    </div>

    <!-- BUSINESS -->
    <div class="plan-card">
        <div class="plan-title">Business</div>

        <div class="plan-price" data-monthly="15000" data-yearly="150000">
            ₦15,000/mo
        </div>

        <ul class="plan-features">
            <li>Unlimited Users</li>
            <li>Unlimited Locations</li>
            <li>Unlimited Products</li>
            <li>Discount Management</li>
            <li>Customer Management</li>
            <li>Profit & Loss</li>
            <li>Barcode Support</li>
            <li>24/7 Support</li>
        </ul>

        <button class="plan-btn {{ $owner->plan == 'business' ? 'active-plan' : 'buy' }}">
            {{ $owner->plan == 'business' ? 'Chosen Plan' : 'Subscribe' }}
        </button>
    </div>

        <!-- ENTERPRISE -->
    <div class="plan-card">
        <div class="plan-title">Enterprise</div>
        <div class="plan-price1">₦700,000</div>

        <ul class="plan-features">
            <li>Unlimited Users</li>
            <li>Unlimited Stores</li>
            <li>Unlimited Products</li>
            <li>Full Features Access</li>
            <li>Priority Support</li>
        </ul>

        <button class="plan-btn buy">
            Subscribe
        </button>
    </div>

</div>

<!-- JS -->
<script>
const monthlyBtn = document.getElementById('monthlyBtn');
const yearlyBtn = document.getElementById('yearlyBtn');
const prices = document.querySelectorAll('.plan-price');

function setMonthly() {
    prices.forEach(price => {
        price.innerText = `₦${price.dataset.monthly}/mo`;
    });

    monthlyBtn.classList.add('active');
    yearlyBtn.classList.remove('active');
}

function setYearly() {
    prices.forEach(price => {
        price.innerText = `₦${price.dataset.yearly}/yr`;
    });

    yearlyBtn.classList.add('active');
    monthlyBtn.classList.remove('active');
}

monthlyBtn.addEventListener('click', setMonthly);
yearlyBtn.addEventListener('click', setYearly);
</script>

</body>
</html>