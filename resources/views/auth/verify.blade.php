
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
        }

        .otp-input:focus {
            border-color: #2F5DA8;
            box-shadow: 0 0 0 3px rgba(47, 93, 168, 0.12);
        }

        .otp-input::placeholder {
            color: #c5cbd5;
            letter-spacing: 8px;
        }

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

        .verify-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .message {
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.5;
            display: none;
        }

        .message.error {
            display: block;
            background: #fff1f1;
            color: #b42318;
            border: 1px solid #fecdca;
        }

        .message.success {
            display: block;
            background: #ecfdf3;
            color: #027a48;
            border: 1px solid #abefc6;
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

    <div class="logo">
        <div class="logo-text">Bloommonie</div>
    </div>

    <div class="card">

        <div class="icon">
            ✉️
        </div>

        <h1>Verify your email</h1>

        <p class="description">
            We've sent a 6-digit verification code to:
        </p>

        <div class="email">
            {{ auth()->user()->email }}
        </div>

        <div id="message" class="message"></div>

        <form id="verificationForm">
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
            >

            <button
                type="submit"
                id="verifyButton"
                class="verify-button"
            >
                Verify Email
            </button>
        </form>

        <div class="resend-section">
            Didn't receive the code?

            <button
                type="button"
                id="resendButton"
                class="resend-button"
                disabled
            >
                Resend Code
            </button>

            <div id="resendTimer" class="timer">
                You can resend in 60 seconds
            </div>
        </div>

        <div class="expiry">
            Your verification code expires in 10 minutes.
        </div>

    </div>

</div>

<script>
    const form = document.getElementById('verificationForm');
    const otpInput = document.getElementById('otp');
    const verifyButton = document.getElementById('verifyButton');
    const resendButton = document.getElementById('resendButton');
    const resendTimer = document.getElementById('resendTimer');
    const message = document.getElementById('message');

    let resendSeconds = 60;

    /*
     * Only allow numbers in OTP field
     */
    otpInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);

        /*
         * Automatically submit when 6 digits are entered
         */
        if (this.value.length === 6) {
            form.requestSubmit();
        }
    });


    /*
     * Show message
     */
    function showMessage(text, type) {
        message.textContent = text;
        message.className = 'message ' + type;
    }


    /*
     * Verify OTP
     */
    form.addEventListener('submit', async function (event) {

        event.preventDefault();

        const otp = otpInput.value.trim();

        if (otp.length !== 6) {
            showMessage(
                'Please enter the 6-digit verification code.',
                'error'
            );

            return;
        }

        verifyButton.disabled = true;
        verifyButton.textContent = 'Verifying...';

        try {

            const response = await fetch(
                "{{ route('verification.otp') }}",
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'input[name="_token"]'
                            ).value
                    },

                    body: JSON.stringify({
                        otp: otp
                    })
                }
            );

            const data = await response.json();

            if (data.status === true) {

                showMessage(
                    'Email verified successfully. Redirecting...',
                    'success'
                );

                setTimeout(function () {
                    window.location.href = '/admin/dashboard';
                }, 800);

                return;
            }

            showMessage(
                data.message || 'Invalid verification code.',
                'error'
            );

            verifyButton.disabled = false;
            verifyButton.textContent = 'Verify Email';

        } catch (error) {

            showMessage(
                'Something went wrong. Please try again.',
                'error'
            );

            verifyButton.disabled = false;
            verifyButton.textContent = 'Verify Email';
        }
    });


    /*
     * Resend countdown
     */
    function startResendTimer() {

        resendSeconds = 60;

        resendButton.disabled = true;

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
     * Resend OTP
     */
    resendButton.addEventListener('click', async function () {

        resendButton.disabled = true;
        resendButton.textContent = 'Sending...';

        try {

            const response = await fetch(
                "{{ route('verification.otp.resend') }}",
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'input[name="_token"]'
                            ).value
                    }
                }
            );

            const data = await response.json();

            if (data.status === true) {

                showMessage(
                    'A new verification code has been sent to your email.',
                    'success'
                );

                resendButton.textContent = 'Resend Code';

                startResendTimer();

                return;
            }

            showMessage(
                data.message || 'Unable to resend verification code.',
                'error'
            );

            resendButton.disabled = false;
            resendButton.textContent = 'Resend Code';

        } catch (error) {

            showMessage(
                'Something went wrong. Please try again.',
                'error'
            );

            resendButton.disabled = false;
            resendButton.textContent = 'Resend Code';
        }
    });


    /*
     * Start the initial 60-second resend countdown
     */
    startResendTimer();

    /*
     * Focus OTP field when page loads
     */
    otpInput.focus();
</script>

</body>
</html>

