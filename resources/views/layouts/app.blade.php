<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/css/app.css'])
</head>
<body>
    <div id="app">
        @include('includes.nav')

        <main class="py-4">
            @yield('content')
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
{{-- CHECK STOCK --}}
<script>
    $(document).ready(function () {
        let selectedProductId = null;
        let currentStock = null;

        // Track selected product ID
        $(document).on('click', '.suggestion-item', function () {
            selectedProductId = $(this).data('product-id');
            let productPrice = $(this).data('product-price');

            // Fetch current stock for the selected product
            $.get(`/api/product/${selectedProductId}`, function (data) {
                currentStock = data.stock_quantity;
            });
        });

        // On form submit, validate stock before proceeding
        $('form').on('submit', function (e) {
            const quantity = parseInt($('#quantity').val());

            // If stock hasn't been fetched yet, let it go through
            if (!currentStock || !selectedProductId) return;

            if (quantity > currentStock) {
                e.preventDefault();

                // Remove any existing warning
                $('#stock-warning').remove();

                // Add new warning below quantity field
                const warning = `<small id="stock-warning" class="text-danger d-block mt-1">Quantity is above remaining stock (${currentStock} left)</small>`;
                $('#quantity').after(warning);
            }
        });
    });
</script>

<script>
    $(document).ready(function () {
        // Handle product search input
        $('#product_name').on('input', function () {
            let query = $(this).val();

            if (query.length < 1) {
                $('#product_suggestions').empty().hide();
                $('#product-error').hide();
                return;
            }

            $.get(`/products/search-suggestions`, { query: query }, function (data) {
                if (data.length > 0) {
                    let suggestions = '<ul class="list-group" style="margin: 0;">';
                    let escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // Escape query for regex
                    let regex = new RegExp(escapedQuery, 'i');

                    data.forEach(function (product) {
                        let highlightedName = product.name.replace(regex, function (match) {
                            return `<strong style="color: #007bff;">${match}</strong>`;
                        });

                        suggestions += `<li data-product-id="${product.id}" data-product-price="${product.price}" class="list-group-item suggestion-item" style="cursor: pointer;">${highlightedName}</li>`;
                    });

                    suggestions += '</ul>';
                    $('#product_suggestions').html(suggestions).show();
                    $('#product-error').hide();
                } else {
                    $('#product_suggestions').empty().hide();
                    $('#product-error').show();
                }
            });
        });

        // Autofill product info when a suggestion is clicked
        $(document).on('click', '.suggestion-item', function () {
            let productName = $(this).text();
            let productId = $(this).data('product-id');
            let productPrice = $(this).data('product-price');

            $('#product_name').val(productName);
            $('#product').val(productId);
            $('#price').val(productPrice); // Display the price
            $('#product_suggestions').empty().hide();

            // Clear the total price field
            $('#total_price').val('');
        });

        // Calculate total price when quantity is entered
        $('#quantity').on('input', function () {
        let quantity = $(this).val();
        let price = $('#price').val();

        if (quantity && price) {
            let totalPrice = quantity * price;
            $('#total_price').val(totalPrice);
        } else {
            // If quantity is empty, clear the total price field
            $('#total_price').val('');
        }
    });

        // Optional: Hide suggestions when clicking outside
        $(document).click(function (e) {
            if (!$(e.target).closest('#product_name, #product_suggestions').length) {
                $('#product_suggestions').hide();
            }
        });
    });
</script>


{{-- FOR PRODUCT UPDATE PRODUCT --}}
<!-- Bootstrap JS (make sure you include this for alert close functionality) -->
{{-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script>
    document.getElementById('category_id').addEventListener('change', function () {
        const categoryId = this.value;
        fetch(`/products/by-category/${categoryId}`)
            .then(res => res.json())
            .then(data => {
                const dataList = document.getElementById('product_suggestions');
                dataList.innerHTML = '';
                data.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.name;
                    dataList.appendChild(option);
                });
            });
    });
</script> --}}




</body>
</html>
