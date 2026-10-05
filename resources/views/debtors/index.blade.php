@extends('layouts.adminapp')

@section('admincontent')
<style>
    .dl { max-width: 1100px; margin: 0 auto; padding: 16px; font-family: inherit; }
    .dl h1 { font-size: 1.5rem; margin: 0 0 4px; }
    .dl-sub { color: #6b7280; font-size: .9rem; margin-bottom: 16px; }
    .dl-summary { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
    .dl-stat { flex: 1 1 160px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; }
    .dl-stat span { display: block; font-size: .75rem; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
    .dl-stat strong { font-size: 1.25rem; }
    .dl-filters { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
    .dl-filters input, .dl-filters select { padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .95rem; background: #fff; }
    .dl-filters input { flex: 2 1 240px; }
    .dl-filters select { flex: 1 1 160px; }
    .dl-status { color: #6b7280; font-size: .85rem; min-height: 1.2em; margin-bottom: 8px; }
    .dl-products { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 12px; margin-bottom: 24px; }
    .dl-card { text-align: left; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; cursor: pointer; font: inherit; }
    .dl-card:hover { border-color: #9ca3af; }
    .dl-card.active { border-color: #2563eb; box-shadow: 0 0 0 2px #bfdbfe; }
    .dl-card .n { font-weight: 600; margin-bottom: 6px; word-break: break-word; }
    .dl-card .m { font-size: .82rem; color: #4b5563; line-height: 1.5; }
    .dl-card .b { margin-top: 6px; font-weight: 600; color: #b91c1c; }
    .dl-empty { color: #6b7280; padding: 20px; text-align: center; background: #f9fafb; border-radius: 10px; }
    .dl-detail { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; }
    .dl-detail h2 { font-size: 1.15rem; margin: 0 0 4px; }
    .dl-totals-line { color: #374151; font-size: .9rem; margin-bottom: 12px; }
    .dl-wrap { overflow-x: auto; }
    .dl table { width: 100%; border-collapse: collapse; min-width: 560px; }
    .dl th, .dl td { padding: 10px 8px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: .92rem; }
    .dl th { background: #f9fafb; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
    .dl .r { text-align: right; white-space: nowrap; }
    .dl .phone { color: #6b7280; font-size: .82rem; }
    .dl tfoot td { font-weight: 700; background: #f9fafb; border-top: 2px solid #e5e7eb; }
    .dl .owe { color: #b91c1c; font-weight: 600; }
</style>

<div class="dl">
    <h1>Debtors Log</h1>
    <div class="dl-sub">Products customers are still owing for. Pick a product to see who owes.</div>

    <div class="dl-summary">
        <div class="dl-stat"><span>Products owing</span><strong id="dl-s-products">0</strong></div>
        <div class="dl-stat"><span>Customers owing</span><strong id="dl-s-customers">0</strong></div>
        <div class="dl-stat"><span>Total balance</span><strong id="dl-s-balance">₦0</strong></div>
    </div>

    <div class="dl-filters">
        <input type="search" id="dl-search" placeholder="Search products…" autocomplete="off">
        <select id="dl-cashier">
            <option value="">All cashiers</option>
            @foreach ($cashiers as $cashier)
                <option value="{{ $cashier->id }}">{{ $cashier->name }}</option>
            @endforeach
        </select>
        <select id="dl-shop">
            <option value="">All shops</option>
            @foreach ($shops as $shop)
                <option value="{{ $shop->id }}">{{ $shop->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="dl-status" id="dl-status"></div>
    <div class="dl-products" id="dl-products"></div>
    <div id="dl-detail"></div>
</div>

<script>
(function () {
    var dataUrl = @json(route('debtors.data'));
    var state = { products: [], selected: null, query: '' };

    function $(id) { return document.getElementById(id); }
    function num(n) { return Number(n || 0).toLocaleString('en-NG', { maximumFractionDigits: 2 }); }
    function naira(n) { return '₦' + num(n); }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function load() {
        var params = new URLSearchParams();
        if ($('dl-cashier').value) params.set('cashier_id', $('dl-cashier').value);
        if ($('dl-shop').value) params.set('shop_id', $('dl-shop').value);

        $('dl-status').textContent = 'Loading…';

        fetch(dataUrl + '?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(function (json) {
            state.products = json.products || [];
            if (!state.products.some(function (p) { return p.product_id === state.selected; })) {
                state.selected = null;
            }
            $('dl-s-products').textContent = num(json.summary.products);
            $('dl-s-customers').textContent = num(json.summary.customers);
            $('dl-s-balance').textContent = naira(json.summary.balance);
            $('dl-status').textContent = '';
            render();
        })
        .catch(function () {
            $('dl-status').textContent = 'Could not load debtors. Please refresh the page.';
        });
    }

    function renderProducts() {
        var q = state.query.trim().toLowerCase();
        var list = state.products.filter(function (p) {
            return !q || String(p.name).toLowerCase().indexOf(q) !== -1;
        });

        if (!list.length) {
            $('dl-products').innerHTML = '<div class="dl-empty" style="grid-column:1/-1">' +
                (state.products.length ? 'No products match your search.' : 'No one is owing right now.') + '</div>';
            return;
        }

        $('dl-products').innerHTML = list.map(function (p) {
            return '<button type="button" class="dl-card' + (p.product_id === state.selected ? ' active' : '') +
                '" data-id="' + p.product_id + '">' +
                '<div class="n">' + esc(p.name) + '</div>' +
                '<div class="m">' + num(p.total_quantity) + ' owed · ' + num(p.total_customers) +
                ' customer' + (p.total_customers === 1 ? '' : 's') + '</div>' +
                '<div class="b">' + naira(p.total_balance) + '</div>' +
                '</button>';
        }).join('');
    }

    function renderDetail() {
        var p = state.products.find(function (x) { return x.product_id === state.selected; });

        if (!p) {
            $('dl-detail').innerHTML = state.products.length
                ? '<div class="dl-empty">Select a product above to see the customers owing for it.</div>'
                : '';
            return;
        }

        var rows = p.customers.map(function (c, i) {
            return '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + esc(c.name || 'Unnamed customer') +
                    '<div class="phone">' + esc(c.phone || '—') + '</div></td>' +
                '<td class="r">' + num(c.quantity) + '</td>' +
                '<td class="r">' + naira(c.total) + '</td>' +
                '<td class="r">' + naira(c.paid) + '</td>' +
                '<td class="r owe">' + naira(c.balance) + '</td>' +
                '</tr>';
        }).join('');

        $('dl-detail').innerHTML =
            '<div class="dl-detail">' +
                '<h2>' + esc(p.name) + '</h2>' +
                '<div class="dl-totals-line">Total ' + esc(p.name) + ' owed: <strong>' + num(p.total_quantity) +
                    '</strong> · Total customers: <strong>' + num(p.total_customers) +
                    '</strong> · Total balance: <strong>' + naira(p.total_balance) + '</strong></div>' +
                '<div class="dl-wrap"><table>' +
                    '<thead><tr><th>#</th><th>Customer</th><th class="r">Qty</th><th class="r">Total</th>' +
                    '<th class="r">Paid</th><th class="r">Balance owing</th></tr></thead>' +
                    '<tbody>' + rows + '</tbody>' +
                    '<tfoot><tr><td></td><td>' + num(p.total_customers) + ' customer' +
                        (p.total_customers === 1 ? '' : 's') + '</td>' +
                        '<td class="r">' + num(p.total_quantity) + '</td>' +
                        '<td class="r">' + naira(p.total_amount) + '</td>' +
                        '<td class="r">' + naira(p.total_paid) + '</td>' +
                        '<td class="r owe">' + naira(p.total_balance) + '</td></tr></tfoot>' +
                '</table></div>' +
            '</div>';
    }

    function render() {
        renderProducts();
        renderDetail();
    }

    $('dl-products').addEventListener('click', function (e) {
        var card = e.target.closest('.dl-card');
        if (!card) return;
        state.selected = Number(card.getAttribute('data-id'));
        render();
        $('dl-detail').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    $('dl-search').addEventListener('input', function (e) {
        state.query = e.target.value;
        renderProducts();
    });

    $('dl-cashier').addEventListener('change', load);
    $('dl-shop').addEventListener('change', load);

    load();
})();
</script>
@endsection