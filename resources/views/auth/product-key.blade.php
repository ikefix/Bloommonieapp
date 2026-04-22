<!DOCTYPE html>
<html>
<head>
    <title>Product Key Verification</title>
</head>
<body style="font-family:Arial; text-align:center; margin-top:100px;">

    <h2>Enter Product Key</h2>

    @if(session('error'))
        <p style="color:red;">{{ session('error') }}</p>
    @endif

    <form method="POST" action="{{ route('product.key.check') }}">
        @csrf

        <input type="text" name="product_key"
               placeholder="ABC-XYZ-123"
               style="padding:10px; width:250px;">

        <br><br>

        <button type="submit" style="padding:10px 20px;">
            Verify
        </button>
    </form>

</body>
</html>