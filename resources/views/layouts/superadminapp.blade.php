<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Super Admin Page</title>

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
        @include('includes.superadminsidebar')

        <main class="py-4">
            @yield('superadmincontent')
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
</body>
</html>