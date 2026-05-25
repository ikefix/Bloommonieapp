@extends('layouts.adminapp')

@section('admincontent')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Create Invoice</h3>
                </div>
                <div class="card-body">

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ auth()->user()->role === 'admin' ? route('admin.invoices.store') : (auth()->user()->role === 'manager' ? route('manager.invoices.store') : route('cashier.invoices.store')) }}" method="POST">
                        @csrf

                        {{-- Customer Selection --}}
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Select Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select" required>
                                <option value="">-- Choose Customer --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        data-email="{{ $customer->email }}"
                                        data-phone="{{ $customer->phone }}"
                                        data-company="{{ $customer->company }}">
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Customer Info --}}
                        <div class="row mb-3">
                            <div class="col-md-4"><strong>Email:</strong> <span id="customer_email">-</span></div>
                            <div class="col-md-4"><strong>Phone:</strong> <span id="customer_phone">-</span></div>
                            <div class="col-md-4"><strong>Company:</strong> <span id="customer_company">-</span></div>
                        </div>

                        {{-- Shop Selection --}}
                        <div class="mb-3">
                            <label for="shop_id" class="form-label">Select Shop</label>
                            <select name="shop_id" id="shop_id" class="form-select" required>
                                <option value="">-- Choose Shop --</option>
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Product Rows --}}
                        <div class="mb-3">
                            <label class="form-label">Add Products</label>
                            <div id="products_wrapper">
                                <div class="row product_row mb-2">
                                    <div class="col-md-4">
                                        <select class="form-select product_select" required disabled>
                                            <option value="">-- Choose Product --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" class="form-control product_quantity" min="1" value="1">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control product_price" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control product_total" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-sm remove_product">X</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm mt-2" id="add_product_btn">+ Add Product</button>
                        </div>

                        {{-- Hidden input to store JSON --}}
                        <input type="hidden" name="goods" id="goods_json">

                        {{-- Discount + Tax --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Discount (Optional)</label>
                                <input type="number" name="discount" id="discount" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tax (Optional)</label>
                                <input type="number" name="tax" id="tax" class="form-control" min="0" value="0">
                            </div>
                        </div>

                        {{-- Final Total --}}
                        <div class="mb-3">
                            <label class="form-label">Final Total</label>
                            <input type="text" name="total" id="final_total" class="form-control" readonly>
                        </div>

                        {{-- Payment Type --}}
                        <div class="mb-3">
                            <label class="form-label">Payment Type</label>
                            <select name="payment_type" id="payment_type" class="form-select" required>
                                <option value="full">Select Plan</option>
                                <option value="full">Full Payment</option>
                                <option value="part">Part Payment</option>
                            </select>
                        </div>

                        {{-- Amount Paid --}}
                        <div class="mb-3 d-none" id="amount_paid_wrapper">
                            <label class="form-label">Amount Paid</label>
                            <input type="number" name="amount_paid" id="amount_paid" class="form-control" min="0" value="0">
                        </div>

                        {{-- Balance --}}
                        <div class="mb-3">
                            <label class="form-label">Balance</label>
                            <input type="text" name="balance" id="balance" class="form-control" readonly>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg">Create Invoice</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JS --}}
<script>
let products = @json($products);

// CUSTOMER AUTOFILL
const customers = document.querySelector('#customer_id');
const emailSpan = document.querySelector('#customer_email');
const phoneSpan = document.querySelector('#customer_phone');
const companySpan = document.querySelector('#customer_company');

customers.addEventListener('change', function () {
    const selected = customers.selectedOptions[0];
    emailSpan.textContent = selected.dataset.email;
    phoneSpan.textContent = selected.dataset.phone;
    companySpan.textContent = selected.dataset.company;
});

// PRODUCT ROW LOGIC
const shopSelect = document.querySelector('#shop_id');
const productsWrapper = document.querySelector('#products_wrapper');
const addProductBtn = document.querySelector('#add_product_btn');
const goodsInput = document.querySelector('#goods_json');

shopSelect.addEventListener('change', function () {
    const shopId = Number(this.value);
    document.querySelectorAll('.product_select').forEach(select => {
        select.innerHTML = '<option value="">-- Choose Product --</option>';
        if (shopId) {
            select.disabled = false;
            products.filter(p => Number(p.shop_id) === shopId)
                .forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.dataset.price = p.price;
                    opt.textContent = p.name;
                    select.appendChild(opt);
                });
        } else select.disabled = true;
    });
    updateAllTotals();
});

// Add new row
addProductBtn.addEventListener('click', () => {
    const newRow = document.querySelector('.product_row').cloneNode(true);
    newRow.querySelectorAll('input').forEach(i => i.value = i.classList.contains('product_quantity') ? 1 : '');
    newRow.querySelector('.product_select').value = '';
    productsWrapper.appendChild(newRow);
});

// Remove row
productsWrapper.addEventListener('click', function(e) {
    if(e.target.classList.contains('remove_product')){
        if(productsWrapper.querySelectorAll('.product_row').length > 1) e.target.closest('.product_row').remove();
        updateAllTotals();
    }
});

// Update totals
productsWrapper.addEventListener('change', updateAllTotals);
productsWrapper.addEventListener('input', updateAllTotals);
function updateAllTotals() {
    let allTotal = 0;
    const goodsArray = [];

    const discount = Number(document.querySelector('#discount').value) || 0;
    const tax = Number(document.querySelector('#tax').value) || 0;

    let rawItems = [];

    // STEP 1: collect raw items first
    productsWrapper.querySelectorAll('.product_row').forEach(row => {

        const select = row.querySelector('.product_select');
        const qty = Number(row.querySelector('.product_quantity').value) || 1;
        const price = parseFloat(select.selectedOptions[0]?.dataset.price || 0);
        const total = price * qty;

        row.querySelector('.product_price').value = price.toFixed(2);
        row.querySelector('.product_total').value = total.toFixed(2);

        if (select.value) {
            rawItems.push({
                product_id: select.value,
                name: select.selectedOptions[0].textContent,
                quantity: qty,
                price: price,
                total_price: total
            });

            allTotal += total;
        }
    });

    // STEP 2: apply discount proportionally per item
    rawItems.forEach(item => {
        const itemDiscount = (item.total_price / allTotal) * discount;
        const finalItemTotal = item.total_price - itemDiscount;

        goodsArray.push({
            ...item,
            discount: itemDiscount,
            total_price: finalItemTotal
        });
    });

    // STEP 3: update hidden input
    goodsInput.value = JSON.stringify(goodsArray);

    // STEP 4: final total
    const finalTotal = allTotal - discount + tax;
    document.querySelector('#final_total').value = finalTotal.toFixed(2);

    calculateBalance();
}

// DISCOUNT + TAX
document.querySelector('#discount').addEventListener('input', updateAllTotals);
document.querySelector('#tax').addEventListener('input', updateAllTotals);

// PAYMENT LOGIC
const paymentType = document.querySelector('#payment_type');
const amountPaidWrapper = document.querySelector('#amount_paid_wrapper');
const amountPaidInput = document.querySelector('#amount_paid');
const balanceInput = document.querySelector('#balance');

paymentType.addEventListener('change', toggleAmountPaid);
amountPaidInput.addEventListener('input', calculateBalance);

function toggleAmountPaid() {
    const finalTotal = Number(document.querySelector('#final_total').value) || 0;
    if (paymentType.value === "part") {
        amountPaidWrapper.classList.remove('d-none');
        amountPaidInput.value = 0;
    } else {
        amountPaidWrapper.classList.add('d-none');
        amountPaidInput.value = finalTotal;
    }
    calculateBalance();
}

function calculateBalance() {
    const finalTotal = Number(document.querySelector('#final_total').value) || 0;
    const amountPaid = Number(amountPaidInput.value) || 0;
    const balance = finalTotal - amountPaid;
    balanceInput.value = balance.toFixed(2);
}
</script>
@endsection
