@extends('layouts.adminapp')

@section('admincontent')

@php
    $user = auth()->user();

    $locked = ! $user->hasFeature('stock_transfer');
@endphp

<style>

.page-lock-wrapper{
    position: relative;
}

.lock-overlay{
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(4px);
    z-index: 99;

    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 14px;
}

.lock-card{
    background: white;
    width: 420px;
    max-width: 90%;
    padding: 35px;
    border-radius: 18px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);

    animation: floatCard 2s ease-in-out infinite;
}

.lock-card i{
    font-size: 60px;
    color: #ff9800;
    margin-bottom: 15px;
}

.lock-card h2{
    font-size: 28px;
    margin-bottom: 10px;
    font-weight: 700;
}

.lock-card p{
    color: #666;
    line-height: 1.6;
    margin-bottom: 25px;
}

.upgrade-btn{
    display: inline-block;
    background: linear-gradient(135deg,#ff9800,#ff5722);
    color: white;
    text-decoration: none;
    padding: 12px 24px;
    border-radius: 50px;
    font-weight: bold;
    transition: .3s;
}

.upgrade-btn:hover{
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(255,87,34,.3);
    color: white;
}

.lock-badge{
    display: inline-block;
    background: rgba(255,152,0,.1);
    color: #ff9800;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 13px;
    margin-bottom: 15px;
    font-weight: 600;
}

@keyframes floatCard{
    0%{
        transform: translateY(0px);
    }

    50%{
        transform: translateY(-6px);
    }

    100%{
        transform: translateY(0px);
    }
}

.locked-content{
    pointer-events: none;
    user-select: none;
}

</style>

<div class="page-lock-wrapper">

    @if($locked)

        <div class="lock-overlay">

            <div class="lock-card">

                <div class="lock-badge">
                    PREMIUM FEATURE
                </div>

                <i class="fas fa-lock"></i>

                <h2>Stock Transfer Locked</h2>

                <p>
                    Upgrade to the <strong>Business Plan</strong>
                    to unlock multi-location stock transfers,
                    advanced inventory movement,
                    and warehouse management tools.
                </p>

                <a href="{{ url('/pricing') }}" class="upgrade-btn">
                    Upgrade Plan
                </a>

            </div>

        </div>

    @endif


    <div class="{{ $locked ? 'locked-content' : '' }}">

        <div class="container">

            <h1>Stock Transfer</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('stock-transfers.store') }}" method="POST">
                @csrf

                <!-- From Shop -->
                <div class="form-group">
                    <label for="shop_id">From Shop</label>

                    <select name="shop_id" id="shop_id" class="form-control">
                        <option value="">Select Source Shop</option>

                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}">
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Product -->
                <div class="form-group">
                    <label for="product_id">Product</label>

                    <select name="product_id" id="product_id" class="form-control">
                        <option value="">Select a Product</option>
                    </select>
                </div>

                <!-- To Shop -->
                <div class="form-group">
                    <label for="to_shop_id">To Shop</label>

                    <select name="to_shop_id" id="to_shop_id" class="form-control">
                        <option value="">Select Destination Shop</option>

                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}">
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Quantity -->
                <div class="form-group">
                    <label for="quantity">Quantity</label>

                    <input type="number"
                           name="quantity"
                           id="quantity"
                           class="form-control"
                           min="1">
                </div>

                <!-- Cost Price -->
                <div class="form-group">
                    <label for="cost_price">Cost Price</label>

                    <input type="number"
                           name="cost_price"
                           id="cost_price"
                           class="form-control"
                           step="0.01">
                </div>

                <!-- Selling Price -->
                <div class="form-group">
                    <label for="selling_price">Selling Price</label>

                    <input type="number"
                           name="selling_price"
                           id="selling_price"
                           class="form-control"
                           step="0.01">
                </div>

                <button type="submit" class="btn btn-primary">
                    Transfer Stock
                </button>

            </form>

        </div>

    </div>

</div>


<script>
document.getElementById('shop_id').addEventListener('change', function () {

    const shopId = this.value;

    const productSelect = document.getElementById('product_id');

    productSelect.innerHTML = '<option>Loading...</option>';

    if (shopId) {

        fetch(`/products-by-shop/${shopId}`)

            .then(response => response.json())

            .then(products => {

                productSelect.innerHTML =
                    '<option value="">Select a Product</option>';

                products.forEach(product => {

                    const option = document.createElement('option');

                    option.value = product.id;

                    option.textContent = product.name;

                    productSelect.appendChild(option);
                });
            });
    }
});
</script>

@endsection