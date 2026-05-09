<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Choose Plan</title>

<style>
body{
    margin:0;
    font-family:Arial,sans-serif;
    background:linear-gradient(135deg,#1e1e2f,#2c3e50);
    color:#fff;
}
.header{
    position:fixed;
    top:15px;
    right:20px;
}
.close-btn{
    font-size:20px;
    color:#fff;
    text-decoration:none;
    background:rgba(0,0,0,.4);
    padding:8px 12px;
    border-radius:50%;
}
.title{
    text-align:center;
    margin-top:60px;
}
.billing-toggle{
    display:flex;
    justify-content:center;
    margin:20px 0;
    gap:10px;
}
.billing-toggle button{
    padding:8px 16px;
    border-radius:20px;
    border:none;
    cursor:pointer;
    background:rgba(255,255,255,.2);
    color:#fff;
}
.billing-toggle button.active{
    background:#00bcd4;
}
.pricing-container{
    max-width:1200px;
    margin:auto;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:20px;
    padding:20px;
}
.plan-card{
    background:rgba(255,255,255,.08);
    backdrop-filter:blur(10px);
    padding:25px;
    border-radius:12px;
    text-align:center;
}
.plan-card.featured{
    border:2px solid #00bcd4;
    transform:scale(1.05);
}
.plan-title{
    font-size:20px;
}
.plan-price{
    font-size:28px;
    margin:10px 0;
}
.plan-features{
    text-align:left;
    font-size:14px;
    margin-top:15px;
}
.plan-features li{
    margin-bottom:8px;
}
.plan-btn{
    margin-top:15px;
    padding:12px;
    border-radius:20px;
    border:none;
    cursor:pointer;
    font-weight:bold;
    width:100%;
    display:inline-block;
    text-decoration:none;
}
.buy{
    background:#00bcd4;
    color:#fff;
}
.buy:hover{
    background:#0097a7;
}
.active-plan{
    background:#4CAF50;
    color:#fff;
    cursor:not-allowed;
}
</style>
</head>

<body>

<div class="header">
    <a href="/admin-dashboard" class="close-btn">✖</a>
</div>

<h2 class="title">Choose Your Plan 🚀</h2>

<div class="billing-toggle">
    <button id="monthlyBtn" class="active">Monthly</button>
    <button id="yearlyBtn">Yearly 🔥</button>
</div>

@php
$user = auth()->user();
$owner = $user->owner_id ? \App\Models\User::find($user->owner_id) : $user;
@endphp

<div class="pricing-container">

<!-- BASIC -->
<div class="plan-card">
    <div class="plan-title">Basic</div>

    <div class="plan-price" data-monthly="5000" data-yearly="50000">
        ₦5,000/mo
    </div>

    <ul class="plan-features">
        <li>1 User</li>
        <li>1 Store</li>
        <li>500 Products</li>
        <li>Sales Report</li>
        <li>Profit Report</li>
        <li>Report Download</li>
        <li>Stock Adjustment</li>
        <li>Reciept printing</li>
        <li>Expence Tracking</li>
        <li>Discount Management</li>
        <li>24/7 Support</li>
        
    </ul>

    @if($owner->plan == 'basic')
        <button class="plan-btn active-plan">Chosen Plan</button>
    @else
        <a href="{{ route('subscribe.plan',['plan'=>'basic','billing'=>'monthly']) }}"
           class="plan-btn buy subscribe-btn"
           data-plan="basic">
           Subscribe
        </a>
    @endif
</div>

<!-- LITE -->
<div class="plan-card featured">
    <div class="plan-title">Lite 🔥</div>

    <div class="plan-price" data-monthly="7000" data-yearly="70000">
        ₦7,000/mo
    </div>

    <ul class="plan-features">
        <li>Unlimited Users</li>
        <li>1 Store</li>
        <li>Unlimited Products</li>
        <li>Sales Report</li>
        <li>Profit Report</li>
        <li>Report Download</li>
        <li>Stock Adjustment</li>
        <li>Reciept printing</li>
        <li>Expence Tracking</li>
        <li>Discount Management</li>  
        <li>Barcode Support</li>      
        <li>24/7 Support</li>
    </ul>

    @if($owner->plan == 'lite')
        <button class="plan-btn active-plan">Chosen Plan</button>
    @else
        <a href="{{ route('subscribe.plan',['plan'=>'lite','billing'=>'monthly']) }}"
           class="plan-btn buy subscribe-btn"
           data-plan="lite">
           Subscribe
        </a>
    @endif
</div>

<!-- BUSINESS -->
<div class="plan-card">
    <div class="plan-title">Business</div>

    <div class="plan-price" data-monthly="10000" data-yearly="100000">
        ₦10,000/mo
    </div>

    <ul class="plan-features">
         <li>Unlimited Users</li>
        <li>Unlimited Store</li>
        <li>Unlimited Products</li>
        <li>Sales Report</li>
        <li>Profit Report</li>
        <li>Report Download</li>
        <li>Stock Adjustment</li>
        <li>Reciept printing</li>
        <li>Expence Tracking</li>
        <li>Customer Management</li>  
        <li>Barcode Support</li>
        <li>Discount Management</li>      
        <li>24/7 Support</li>
    </ul>

    @if($owner->plan == 'business')
        <button class="plan-btn active-plan">Chosen Plan</button>
    @else
        <a href="{{ route('subscribe.plan',['plan'=>'business','billing'=>'monthly']) }}"
           class="plan-btn buy subscribe-btn"
           data-plan="business">
           Subscribe
        </a>
    @endif
</div>

</div>

<script>
const monthlyBtn = document.getElementById('monthlyBtn');
const yearlyBtn  = document.getElementById('yearlyBtn');
const prices     = document.querySelectorAll('.plan-price');
const buttons    = document.querySelectorAll('.subscribe-btn');

let billing = "monthly";

/*
|--------------------------------------------------------------------------
| MONTHLY
|--------------------------------------------------------------------------
*/
function setMonthly(){

    billing = "monthly";

    prices.forEach(price=>{
        price.innerText = `₦${price.dataset.monthly}/mo`;
    });

    buttons.forEach(btn=>{
        let plan = btn.dataset.plan;
        btn.href = `/subscribe/${plan}/monthly`;
    });

    monthlyBtn.classList.add('active');
    yearlyBtn.classList.remove('active');
}

/*
|--------------------------------------------------------------------------
| YEARLY
|--------------------------------------------------------------------------
*/
function setYearly(){

    billing = "yearly";

    prices.forEach(price=>{
        price.innerText = `₦${price.dataset.yearly}/yr`;
    });

    buttons.forEach(btn=>{
        let plan = btn.dataset.plan;
        btn.href = `/subscribe/${plan}/yearly`;
    });

    yearlyBtn.classList.add('active');
    monthlyBtn.classList.remove('active');
}

monthlyBtn.addEventListener('click', setMonthly);
yearlyBtn.addEventListener('click', setYearly);
</script>

</body>
</html>