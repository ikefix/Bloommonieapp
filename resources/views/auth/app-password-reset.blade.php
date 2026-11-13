
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Open Bloommonie</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #0C1F3F;
            font-family: Arial, sans-serif;
        }

        .container {
            width: 100%;
            max-width: 430px;
            padding: 35px 25px;
            background: white;
            border-radius: 20px;
            text-align: center;
        }

        .logo {
            font-size: 30px;
            font-weight: bold;
            color: #2F5DA8;
            margin-bottom: 25px;
        }

        h2 {
            color: #0C1F3F;
        }

        p {
            color: #667085;
            line-height: 1.6;
        }

        .button {
            display: block;
            margin-top: 25px;
            padding: 16px;
            border-radius: 10px;
            background: #2F5DA8;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .small {
            margin-top: 20px;
            font-size: 13px;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="logo">Bloommonie</div>

    <h2>Reset Your Password</h2>

    <p>
        Your password reset request is ready.
        Open the Bloommonie mobile app to create your new password.
    </p>

    <a href="#" id="openApp" class="button">
        Open Bloommonie App
    </a>

    <p class="small" id="status">
        Trying to open Bloommonie...
    </p>

</div>

<script>
    const token = @json($token);
    const email = @json($email);

    const appLink =
        'bloommonie://reset-password' +
        '?token=' + encodeURIComponent(token) +
        '&email=' + encodeURIComponent(email);

    const openButton = document.getElementById('openApp');
    const status = document.getElementById('status');

    openButton.href = appLink;

    // Automatically attempt to open the Flutter application.
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.location.href = appLink;
        }, 700);

        setTimeout(function () {
            status.textContent =
                'If Bloommonie did not open automatically, tap the button above.';
        }, 2500);
    });
</script>

</body>
</html>
