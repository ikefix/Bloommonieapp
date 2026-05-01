<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin Page</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">


    <!-- Scripts -->
    {{-- @vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/css/app.css']) --}}
    
    @vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/css/app.css'])
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        {{-- <link rel="stylesheet" href="app.css"> --}}
</head>
<body>
    <div class="layout-wrapper" id="app">
        @include('includes.adminsidebar')

        <main class="py-4">

     @php
    $user = auth()->user();

    $owner = $user->owner_id 
        ? \App\Models\User::find($user->owner_id) 
        : $user;

    $daysLeft = $owner->plan_end 
        ? \Carbon\Carbon::now()->diffInDays($owner->plan_end, false)
        : 0;
@endphp

@if($owner->plan === 'free_trial')
<style>
    .trial-banner {
        position: fixed;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);

        width: fit-content;
        max-width: 95%;

        background: linear-gradient(135deg, #ff9800, #ff5722);
        padding: 12px 18px;
        color: white;
        box-shadow: 0 4px 18px rgba(0,0,0,0.2);
        border-bottom: 2px solid rgba(255,255,255,0.2);
        border-radius: 14px;
        z-index: 999;
    }

    .trial-banner.danger {
        background: linear-gradient(135deg, #ff3d00, #d50000);
        animation: pulseGlow 1.5s infinite;
    }

    .trial-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .trial-icon {
        font-size: 22px;
        animation: floatIcon 2s ease-in-out infinite;
    }

    .trial-text {
        display: flex;
        flex-direction: column;
        font-size: 14px;
    }

    .trial-text strong {
        font-size: 15px;
        letter-spacing: 0.5px;
    }

    .upgrade-btn {
        background: white;
        color: #ff3d00;
        padding: 7px 16px;
        border-radius: 25px;
        font-size: 13px;
        font-weight: bold;
        text-decoration: none;
        transition: 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        white-space: nowrap;
    }

    .upgrade-btn:hover {
        transform: scale(1.05);
        background: #ffe0b2;
    }

    .blink {
        animation: blink 1s infinite;
    }

    @keyframes blink {
        0% { opacity: 1; }
        50% { opacity: 0.4; }
        100% { opacity: 1; }
    }

    @keyframes pulseGlow {
        0% { box-shadow: 0 0 10px rgba(255,0,0,0.3); }
        50% { box-shadow: 0 0 25px rgba(255,0,0,0.6); }
        100% { box-shadow: 0 0 10px rgba(255,0,0,0.3); }
    }

    @keyframes floatIcon {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-3px); }
        100% { transform: translateY(0px); }
    }
</style>

<div class="trial-banner {{ $daysLeft <= 0 ? 'danger' : '' }}">
    <div class="trial-content">

        <div class="left">
            <span class="trial-icon">🚀</span>

            <div class="trial-text">
                <strong>Free Trial Active</strong>

                <span>
                    {{ $daysLeft > 0 ? $daysLeft . ' day(s) left' : 'Expired' }}
                </span>

                <small style="opacity:0.8; font-size:12px;">
                    Keep your plan active to avoid service interruption.
                </small>

                <small>
                     <a style="text-decoration: none" href="{{url('/pricing')}}">Upgrade Plan</a>
                </small>
            </div>
        </div>

        @if($daysLeft <= 0)
            <a href="/show-product-key" class="upgrade-btn blink">
                Activate Now
            </a>
        @endif

    </div>
</div>
@endif

            @yield('admincontent')
        </main>
    </div>
   <script>
document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
    const sidebar = document.getElementById('adminSidebar');
    sidebar.classList.toggle('sidebar-collapsed');

    const texts = sidebar.querySelectorAll('.sidebar-text');
    texts.forEach(text => {
        text.style.display = sidebar.classList.contains('sidebar-collapsed') ? 'inline' : 'none';
    });

    // Hide/show logo
    const logo = document.getElementById('sidebarLogo');
    logo.style.display = sidebar.classList.contains('sidebar-collapsed') ? 'block' : 'none';
});
</script>

<script>
    function toggleSubmenu(button) {
        const submenu = button.nextElementSibling;
        const arrow = button.querySelector('.arrow');
    
        submenu.classList.toggle('show');
        arrow.classList.toggle('rotate');
    }
    
    </script>
</body>
</html>