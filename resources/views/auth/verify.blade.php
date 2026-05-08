{{-- @extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Verify Your Email Address') }}</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('A fresh verification link has been sent to your email address.') }}
                        </div>
                    @endif

                    {{ __('Before proceeding, please check your email for a verification link.') }}
                    {{ __('If you did not receive the email') }},
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('click here to request another') }}</button>.
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection --}}



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Email Verification</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial, sans-serif;
    background:linear-gradient(135deg,#0f172a,#1e293b);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    overflow:hidden;
}

/* Background glow */
.bg-glow{
    position:absolute;
    width:500px;
    height:500px;
    background:#06b6d4;
    filter:blur(180px);
    opacity:0.15;
    border-radius:50%;
    top:-100px;
    right:-100px;
}

/* Card */
.verify-card{
    position:relative;
    width:90%;
    max-width:450px;
    background:rgba(255,255,255,0.06);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:24px;
    padding:45px 35px;
    text-align:center;
    color:white;
    box-shadow:0 15px 40px rgba(0,0,0,0.4);
    animation:floatCard 3s ease-in-out infinite alternate;
}

@keyframes floatCard{
    from{
        transform:translateY(0px);
    }

    to{
        transform:translateY(-6px);
    }
}

/* Icon circle */
.icon-box{
    width:90px;
    height:90px;
    margin:auto;
    border-radius:50%;
    background:rgba(6,182,212,0.15);
    display:flex;
    justify-content:center;
    align-items:center;
    border:2px solid rgba(6,182,212,0.3);
    margin-bottom:25px;
    animation:pulse 2s infinite;
}

@keyframes pulse{
    0%{
        transform:scale(1);
    }

    50%{
        transform:scale(1.05);
    }

    100%{
        transform:scale(1);
    }
}

.icon-box i{
    font-size:40px;
    color:#22d3ee;
}

/* Heading */
.verify-card h1{
    font-size:30px;
    margin-bottom:12px;
}

/* Paragraph */
.verify-card p{
    color:#cbd5e1;
    line-height:1.7;
    font-size:15px;
    margin-bottom:25px;
}

/* Alert */
.success-alert{
    background:#14532d;
    border:1px solid #22c55e;
    padding:12px;
    border-radius:12px;
    margin-bottom:20px;
    font-size:14px;
    color:#bbf7d0;
}

/* Button */
.verify-btn{
    width:100%;
    border:none;
    padding:14px;
    border-radius:14px;
    background:linear-gradient(135deg,#06b6d4,#0891b2);
    color:white;
    font-size:15px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

.verify-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(6,182,212,0.3);
}

/* Footer text */
.footer-text{
    margin-top:20px;
    color:#94a3b8;
    font-size:13px;
    line-height:1.6;
}

.footer-text span{
    color:#38bdf8;
    font-weight:bold;
}

</style>
</head>

<body>

<div class="bg-glow"></div>

<div class="verify-card">

    <div class="icon-box">
        <i class="fa-solid fa-envelope-circle-check"></i>
    </div>

    <h1>Verify Your Email</h1>

    <div class="success-alert">
        A verification link has been sent to your email address.
    </div>

    <p>
        Before continuing, please check your inbox and click the verification link.
        This helps secure your account and activate your free trial.
    </p>

    <form method="POST" action="{{ route('verification.resend') }}">
        @csrf

        <button type="submit" class="verify-btn">
            Resend Verification Email
        </button>
    </form>

    <div class="footer-text">
        Didn’t receive the email?<br>
        Check your <span>spam folder</span> or try again.
    </div>

</div>

</body>
</html>