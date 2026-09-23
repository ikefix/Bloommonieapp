<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bloommonie Email Verification</title>
</head>

<body style="
    margin:0;
    padding:0;
    background:#f4f7fb;
    font-family:Arial, Helvetica, sans-serif;
">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 15px;">
    <tr>
        <td align="center">

            <table
                width="100%"
                cellpadding="0"
                cellspacing="0"
                style="
                    max-width:500px;
                    background:#ffffff;
                    border-radius:18px;
                    overflow:hidden;
                    box-shadow:0 8px 30px rgba(12,31,63,0.08);
                "
            >

                <!-- Header -->
                <tr>
                    <td style="
                        background:#0C1F3F;
                        padding:30px;
                        text-align:center;
                    ">

                        <h1 style="
                            margin:0;
                            color:#ffffff;
                            font-size:28px;
                        ">
                            Bloom<span style="color:#5B8FD9;">monie</span>
                        </h1>

                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding:40px 30px;">

                        <h2 style="
                            margin-top:0;
                            color:#0C1F3F;
                            font-size:24px;
                        ">
                            Verify Your Email
                        </h2>

                        <p style="
                            color:#475569;
                            font-size:15px;
                            line-height:1.7;
                        ">
                            Hello {{ $user->name }},
                        </p>

                        <p style="
                            color:#475569;
                            font-size:15px;
                            line-height:1.7;
                        ">
                            Use the verification code below to verify
                            your Bloommonie account.
                        </p>

                        <!-- OTP -->
                        <div style="
                            margin:30px 0;
                            padding:22px;
                            background:#f1f5f9;
                            border-radius:14px;
                            text-align:center;
                        ">

                            <div style="
                                font-size:36px;
                                font-weight:bold;
                                letter-spacing:10px;
                                color:#2F5DA8;
                            ">
                                {{ $otp }}
                            </div>

                        </div>

                        <p style="
                            color:#64748b;
                            font-size:14px;
                            line-height:1.6;
                        ">
                            This code will expire in
                            <strong>10 minutes</strong>.
                        </p>

                        <p style="
                            color:#64748b;
                            font-size:14px;
                            line-height:1.6;
                        ">
                            If you did not create a Bloommonie account,
                            you can safely ignore this email.
                        </p>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="
                        padding:20px 30px;
                        background:#f8fafc;
                        text-align:center;
                    ">

                        <p style="
                            margin:0;
                            color:#94a3b8;
                            font-size:12px;
                        ">
                            © {{ date('Y') }} Bloommonie.
                            All rights reserved.
                        </p>

                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>