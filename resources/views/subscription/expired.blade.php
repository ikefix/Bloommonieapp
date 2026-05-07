<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subscription Expired</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #1e1e2f, #2c3e50);
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
    color: white;
}

/* Card */
.container {
    text-align: center;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(12px);
    padding: 40px;
    border-radius: 15px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.1);
}

/* Icon */
.icon {
    font-size: 50px;
    margin-bottom: 15px;
    animation: shake 1s infinite;
}

/* Text */
h1 {
    margin-bottom: 10px;
}

p {
    opacity: 0.8;
    margin-bottom: 25px;
}

/* Buttons */
.btn {
    display: block;
    margin: 10px 0;
    padding: 12px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
}

.btn-primary {
    background: #ff3d00;
    color: white;
}

.btn-primary:hover {
    background: #d50000;
}

.btn-secondary {
    background: rgba(255,255,255,0.2);
    color: white;
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.3);
}

/* Animation */
@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    50% { transform: translateX(2px); }
    75% { transform: translateX(-2px); }
    100% { transform: translateX(0); }
}
</style>
</head>

<body>

<div class="container">

    <div class="icon">⚠️</div>

    <h1>Subscription Expired</h1>

    <p>Your access has been paused. Renew your plan to continue using the system.</p>

    <a href="/pricing" class="btn btn-primary">
        Renew Subscription
    </a>

    <a href="{{ url('/show-product-key') }}" class="btn btn-secondary">
        Enter Product Key
    </a>

    <!-- Logout -->
    <form method="POST" action="{{ route('logout') }}" style="margin-top:10px;">
        @csrf
        <button type="submit" class="btn btn-secondary" style="width:100%; border:none; cursor:pointer;">
            Logout
        </button>
    </form>

</div>

</body>
</html>