<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin Page</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css'
        rel='stylesheet'>

    <!-- Assets -->
    @vite([
        'resources/sass/app.scss',
        'resources/js/app.js',
        'resources/css/app.css'
    ])

</head>
<body>

<div class="layout-wrapper" id="app">

    @include('includes.adminsidebar')

    <main class="main-content">

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

        <div class="trial-banner {{ $daysLeft <= 0 ? 'danger' : '' }}">

            <div class="trial-content">

                <div class="left">

                    <span class="trial-icon">
                        🚀
                    </span>

                    <div class="trial-text">

                        <strong>
                            Free Trial Active
                        </strong>

                        <span>
                            {{ $daysLeft > 0 ? $daysLeft . ' day(s) left' : 'Expired' }}
                        </span>

                        <small>
                            Keep your plan active to avoid service interruption.
                        </small>

                        <small>
                            <a href="{{ url('/pricing') }}"
                                style="text-decoration:none;">
                                Upgrade Plan
                            </a>
                        </small>

                    </div>

                </div>


                @if($daysLeft <= 0)

                    <a href="/show-product-key"
                        class="upgrade-btn blink">
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

    document.getElementById('toggleSidebarBtn')
    .addEventListener('click', function () {

        const sidebar = document.getElementById('adminSidebar');

        sidebar.classList.toggle('sidebar-collapsed');


        const texts = sidebar.querySelectorAll('.sidebar-text');

        texts.forEach(text => {

            text.style.display = sidebar.classList.contains('sidebar-collapsed')
                ? 'inline'
                : 'none';

        });


        const logo = document.getElementById('sidebarLogo');

        logo.style.display = sidebar.classList.contains('sidebar-collapsed')
            ? 'block'
            : 'none';

    });

</script>


<script>

    function toggleSubmenu(button)
    {
        const submenu = button.nextElementSibling;
        const arrow = button.querySelector('.arrow');

        submenu.classList.toggle('show');
        arrow.classList.toggle('rotate');
    }

</script>


</body>
</html>