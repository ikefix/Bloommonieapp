@extends('layouts.adminapp')

@section('admincontent')

<div class="container-fluid p-0">
    <h2 class="mb-4">Customer Maintenance</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="">
        {{-- Left Column: Form --}}
        <div class="form-fess">
            <div class="card p-3 mb-3">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif


                <h4>Create Customer</h4>
                <form action="{{ auth()->user()->role === 'admin' ? route('admin.customers.store') : route('manager.customers.store') }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label>Name</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control form-control-sm"
                            required
                            oninvalid="this.setCustomValidity('Please enter customer name')"
                            oninput="this.setCustomValidity('')"
                        >
                    </div>
                    <div class="mb-2">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label>Phone</label>
                        <input
                            type="text"
                            name="phone"
                            class="form-control form-control-sm"
                            required
                            oninvalid="this.setCustomValidity('Please enter phone number')"
                            oninput="this.setCustomValidity('')"
                        >
                    </div>
                    <div class="mb-2">
                        <label>Address</label>
                        <textarea name="address" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label>Company</label>
                        <input type="text" name="company" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Save Customer</button>
                </form>
            </div>
        </div>

        {{-- Right Column: Customer Table --}}
        <div class="row-three">
            <div class="card p-3">
                <h4>All Customers</h4>
                <div class="mb-3">
                    <input
                        type="text"
                        id="customerSearch"
                        class="form-control"
                        placeholder="Search by name, email, phone, company..."
                    >
                </div>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-bordered table-striped table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 120px;">Name</th>
                                <th style="width: 150px;">Email</th>
                                <th style="width: 100px;">Phone</th>
                                <th style="width: 150px;">Address</th>
                                <th style="width: 120px;">Company</th>
                                <th style="width: 150px;">Notes</th>
                                <th style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="customerTable">
                            @forelse($customers as $customer)
                                <tr>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ Str::limit($customer->email ?? '-', 20) }}</td>
                                    <td>{{ $customer->phone ?? '-' }}</td>
                                    <td>{{ Str::limit($customer->address ?? '-', 30) }}</td>
                                    <td>{{ Str::limit($customer->company ?? '-', 20) }}</td>
                                    <td>{{ Str::limit($customer->notes ?? '-', 30) }}</td>
                                    <td>
                                        <form action="{{ auth()->user()->role === 'admin' ? route('admin.customers.destroy', $customer->id) : route('manager.customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No customers found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('customerSearch').addEventListener('keyup', function () {

    let searchValue = this.value.toLowerCase();

    let rows = document.querySelectorAll('#customerTable tr');

    rows.forEach(function (row) {

        let text = row.textContent.toLowerCase();

        if (text.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }

    });

});
</script>

@endsection
