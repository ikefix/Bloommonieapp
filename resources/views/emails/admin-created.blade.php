<!DOCTYPE html>
<html>
<head>
    <title>Account Created</title>
</head>
<body style="font-family:Arial; background:#f4f4f4; padding:20px;">

    <div style="max-width:600px; background:#fff; padding:20px; border-radius:8px;">

        <h2 style="color:#2563eb;">Welcome to Bloommonie Inventory</h2>

        <p>Dear {{ $admin->name ?? 'User' }},</p>

        <p>Your admin account has been successfully created.</p>

        <hr>

        <h3>Login Details</h3>

        <p><strong>Email:</strong> {{ $admin->email }}</p>
        <p><strong>Password:</strong> {{ $plainPassword }}</p>

        <p><strong>Plan:</strong> {{ $admin->plan }}</p>
        <p><strong>Product Key:</strong> {{ $admin->product_key }}</p>

        @if($admin->plan_end)
            <p><strong>Expires On:</strong> {{ $admin->plan_end }}</p>
        @endif

        <hr>

        <p style="color:red;">
            ⚠️ Please do not share your login credentials with anyone.
        </p>

        <p>Thank you,<br>Bloommonie Team</p>

    </div>

</body>
</html>