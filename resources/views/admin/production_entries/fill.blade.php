@extends('layouts.adminapp')

@section('admincontent')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h4 class="mb-0">
            Fill Production Entry - {{ $production->batch_no }}
        </h4>

        @if(session('success'))
            <div class="alert alert-success py-1 px-3 mb-0">
                {{ session('success') }}
            </div>
        @endif

    </div>

    <hr>

    {{-- ================= INPUTS ================= --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            INPUTS
        </div>

        <div class="card-body">

            <form method="POST" action="{{ route('admin.production_entries.store', $production->id) }}">
                @csrf

                <input type="hidden" name="entry_type" value="input">

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th width="80">
                                <button type="button"
                                        class="btn btn-sm btn-primary"
                                        onclick="addRow('inputBody')">
                                    +
                                </button>
                            </th>
                        </tr>
                    </thead>

                    <tbody id="inputBody"></tbody>
                </table>

                <button class="btn btn-primary w-100">
                    Save Inputs
                </button>
            </form>

        </div>
    </div>

    {{-- ================= OUTPUTS ================= --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white">
            OUTPUTS
        </div>

        <div class="card-body">

            <form method="POST" action="{{ route('admin.production_entries.store', $production->id) }}">
                @csrf

                <input type="hidden" name="entry_type" value="output">

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th width="80">
                                <button type="button"
                                        class="btn btn-sm btn-success"
                                        onclick="addRow('outputBody')">
                                    +
                                </button>
                            </th>
                        </tr>
                    </thead>

                    <tbody id="outputBody"></tbody>
                </table>

                <button class="btn btn-success w-100">
                    Save Outputs
                </button>
            </form>

        </div>
    </div>

    {{-- ================= LOSSES ================= --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-danger text-white">
            LOSSES
        </div>

        <div class="card-body">

            <form method="POST" action="{{ route('admin.production_entries.store', $production->id) }}">
                @csrf

                <input type="hidden" name="entry_type" value="loss">

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th width="80">
                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        onclick="addRow('lossBody')">
                                    +
                                </button>
                            </th>
                        </tr>
                    </thead>

                    <tbody id="lossBody"></tbody>
                </table>

                <button class="btn btn-danger w-100">
                    Save Losses
                </button>
            </form>

        </div>
    </div>

</div>

{{-- ================= JAVASCRIPT ================= --}}
<script>
function addRow(tableId) {

    const row = `
        <tr>
            <td><input name="items[][item_name]" class="form-control" required></td>
            <td><input name="items[][quantity]" class="form-control" required></td>
            <td><input name="items[][unit]" class="form-control" placeholder="kg, bags, pcs"></td>
            <td><input name="items[][price]" class="form-control"></td>
            <td>
                <button type="button"
                        class="btn btn-sm btn-outline-danger"
                        onclick="this.closest('tr').remove()">
                    X
                </button>
            </td>
        </tr>
    `;

    document.getElementById(tableId)
        .insertAdjacentHTML('beforeend', row);
}

// auto add first row for UX
addRow('inputBody');
addRow('outputBody');
addRow('lossBody');
</script>

@endsection