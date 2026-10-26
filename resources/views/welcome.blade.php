/<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>BloomMonie</title>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Nunito', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: white;
        }

        .preloader {
            position: relative;
            width: 160px;
            height: 160px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .preloader img {
            width: 100px;
            height: 100px;
            z-index: 2;
        }

        /* SVG Spinner */
        .spinner {
            position: absolute;
            width: 200px;
            height: 200px;
            animation: rotate 2.4s linear infinite;
        }

        .path {
            stroke: #38bdf8;
            stroke-width: 1;
            stroke-linecap: round;

            stroke-dasharray: 1, 150;
            stroke-dashoffset: 0;

            animation: dash 1.6s ease-in-out infinite,
                       colorShift 1.4s linear infinite;
        }

        /* Smooth Rotation */
        @keyframes rotate {
            100% { transform: rotate(360deg); }
        }

        /* GOOGLE-STYLE ARC STRETCHING */
        @keyframes dash {
            0% {
                stroke-dasharray: 1, 150;
                stroke-dashoffset: 0;
            }

            50% {
                stroke-dasharray: 90, 150;
                stroke-dashoffset: -35;
            }

            100% {
                stroke-dasharray: 1, 150;
                stroke-dashoffset: -125;
            }
        }

        /* COLORS */
        @keyframes colorShift {
            0%   { stroke: #6d1af3; } /* BLUE */
            35%  { stroke: #000000; } /* PURPLE */
            65%  { stroke: #5612c4; } /* BLACK */
            100% { stroke: #000000; }
        }
    </style>
</head>

<body>

    <div class="preloader">
        <svg class="spinner" viewBox="0 0 50 50">
            <circle class="path" cx="25" cy="25" r="20" fill="none"/>
        </svg>

        <img src="{{ asset('logobloomp.png') }}" alt="BloomMonie Logo">
    </div>

    <script>
        setTimeout(() => {
            window.location.href = "{{ route('login') }}";
        }, 2500);
    </script>

</body>
</html>
