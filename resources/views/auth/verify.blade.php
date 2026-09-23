
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verify Your Email - Bloommonie</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #0C1F3F;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verification-container {
            width: 100%;
            max-width: 450px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-text {
            color: #ffffff;
            font-size: 30px;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .card {
            background: #ffffff;
            border-radius: 18px;
            padding: 40px 32px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }

        .icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 22px;
            border-radius: 50%;
            background: #eaf1ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        h1 {
            text-align: center;
            color: #0C1F3F;
            font-size: 26px;
            margin-bottom: 12px;
        }

        .description {
            text-align: center;
            color: #667085;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .email {
            text-align: center;
            color: #0C1F3F;
            font-weight: 600;
            margin-bottom: 28px;
            word-break: break-word;
        }

        /*
        |--------------------------------------------------------------------------
        | Messages
        |--------------------------------------------------------------------------
        */

        .message {
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.5;
        }

        .message.error {
            background: #fff1f1;
            color: #b42318;
            border: 1px solid #fecdca;
        }

        .message.success {
            background: #ecfdf3;
            color: #027a48;
            border: 1px solid #abefc6;
        }

        /*
        |--------------------------------------------------------------------------
        | OTP Input
        |--------------------------------------------------------------------------
        */

        .otp-input {
            width: 100%;
            height: 58px;
            border: 1.5px solid #d0d5dd;
            border-radius: 10px;
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 10px;
            color: #0C1F3F;
            outline: none;
            padding-left: 10px;
            transition: 0.2s ease;
        }

        .otp-input:focus {
            border-color: #2F5DA8;
            box-shadow: 0 0 0 3px rgba(47, 93, 168, 0.12);
        }

        .otp-input::placeholder {
            color: #c5cbd5;
            letter-spacing: 8px;
        }

        /*
        |--------------------------------------------------------------------------
        | Buttons
        |--------------------------------------------------------------------------
        */

        .verify-button {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 10px;
            background: #2F5DA8;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.2s ease;
        }

        .verify-button:hover {
            background: #244b8c;
        }

        .resend-section {
            text-align: center;
            margin-top: 24px;
            color: #667085;
            font-size: 14px;
        }

        .resend-button {
            border: none;
            background: transparent;
            color: #2F5DA8;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            font-size: 14px;
        }

        .resend-button:disabled {
            color: #98a2b3;
            cursor: not-allowed;
        }

        .timer {
            margin-top: 8px;
            color: #98a2b3;
            font-size: 13px;
        }

        .expiry {
            text-align: center;
            margin-top: 18px;
            color: #98a2b3;
            font-size: 13px;
        }

        /*
        |--------------------------------------------------------------------------
        | Mobile
        |--------------------------------------------------------------------------
        */

        @media (max-width: 480px) {

            .card {
                padding: 32px 22px;
            }

            h1 {
                font-size: 23px;
            }

            .otp-input {
                font-size: 23px;
                letter-spacing: 7px;
            }
        }
    </style>
</head>

<body>

<div class="verification-container">

    <!-- Logo -->
    <div class="logo">
        <div class="logo-text">
            Bloommonie
        </div>
    </div>


    <!-- Verification Card -->
    <div class="card">

        <!-- Email Icon -->
        <div class="icon">
            ✉️
        </div>


        <!-- Heading -->
        <h1>
            Verify your email
        </h1>


        <!-- Description -->
        <p class="description">
            We've sent a 6-digit verification code to:
        </p>


        <!-- User Email -->
        <div class="email">
            {{ auth()->user()->email }}
        </div>


        <!-- Laravel Error Message -->
        @if ($errors->has('otp'))

            <div class="message error">
                {{ $errors->first('otp') }}
            </div>

        @endif


        <!-- Success Message -->
        @if (session('success'))

            <div class="message success">
                {{ session('success') }}
            </div>

        @endif


        <!-- General Error Message -->
        @if (session('error'))

            <div class="message error">
                {{ session('error') }}
            </div>

        @endif


        <!-- OTP Verification Form -->
        <form
            method="POST"
            action="{{ route('verification.otp') }}"
            id="verificationForm"
        >

            @csrf

            <input
                type="text"
                id="otp"
                name="otp"
                class="otp-input"
                maxlength="6"
                inputmode="numeric"
                autocomplete="one-time-code"
                placeholder="••••••"
                required
                autofocus
            >

            <button
                type="submit"
                class="verify-button"
                id="verifyButton"
            >
                Verify Email
            </button>

        </form>


        <!-- Resend Section -->
        <div class="resend-section">

            Didn't receive the code?

            <form
                method="POST"
                action="{{ route('verification.otp.resend') }}"
                id="resendForm"
                style="display: inline;"
            >

                @csrf

                <button
                    type="submit"
                    id="resendButton"
                    class="resend-button"
                    disabled
                >
                    Resend Code
                </button>

            </form>


            <div
                id="resendTimer"
                class="timer"
            >
                You can resend in 60 seconds
            </div>

        </div>


        <!-- OTP Expiry -->
        <div class="expiry">
            Your verification code expires in 10 minutes.
        </div>

    </div>

</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | OTP Input
    |--------------------------------------------------------------------------
    */

    const otpInput = document.getElementById('otp');

    otpInput.addEventListener('input', function () {

        // Remove anything that isn't a number
        this.value = this.value
            .replace(/\D/g, '')
            .slice(0, 6);

    });


    /*
    |--------------------------------------------------------------------------
    | Verification Button
    |--------------------------------------------------------------------------
    */

    const verificationForm =
        document.getElementById('verificationForm');

    const verifyButton =
        document.getElementById('verifyButton');

    verificationForm.addEventListener('submit', function () {

        if (otpInput.value.length !== 6) {

            alert('Please enter the 6-digit verification code.');

            return;
        }

        verifyButton.disabled = true;

        verifyButton.textContent = 'Verifying...';

    });


    /*
    |--------------------------------------------------------------------------
    | Resend OTP Countdown
    |--------------------------------------------------------------------------
    */

    const resendButton =
        document.getElementById('resendButton');

    const resendTimer =
        document.getElementById('resendTimer');

    let resendSeconds = 60;


    function startResendTimer() {

        resendButton.disabled = true;

        resendSeconds = 60;

        resendTimer.textContent =
            'You can resend in ' +
            resendSeconds +
            ' seconds';


        const interval = setInterval(function () {

            resendSeconds--;

            resendTimer.textContent =
                'You can resend in ' +
                resendSeconds +
                ' seconds';


            if (resendSeconds <= 0) {

                clearInterval(interval);

                resendButton.disabled = false;

                resendTimer.textContent =
                    'You can request a new code now.';

            }

        }, 1000);

    }


    /*
    |--------------------------------------------------------------------------
    | Resend Button
    |--------------------------------------------------------------------------
    */

    const resendForm =
        document.getElementById('resendForm');

    resendForm.addEventListener('submit', function () {

        resendButton.disabled = true;

        resendButton.textContent = 'Sending...';

    });


    /*
    |--------------------------------------------------------------------------
    | Start Countdown
    |--------------------------------------------------------------------------
    */

    startResendTimer();

</script>

</body>
</html>

