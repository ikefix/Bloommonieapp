
@extends('layouts.adminapp')

@section('admincontent')

<div class="container-fluid p-0">
    <h2 class="mb-4">Customer Maintenance</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-fess">
        <div class="card p-3 mb-3">

            <h4>
                {{ isset($editCustomer) ? 'Edit Customer' : 'Create Customer' }}
            </h4>

            <form action="{{ isset($editCustomer)
                ? route(auth()->user()->role . '.customers.update', $editCustomer->id)
                : route(auth()->user()->role . '.customers.store') }}"
                method="POST">

                @csrf

                @if(isset($editCustomer))
                    @method('PUT')
                @endif

                <div class="mb-2">
                    <label>Name</label>
                    <input type="text" name="name"
                        class="form-control form-control-sm"
                        value="{{ old('name', $editCustomer->name ?? '') }}"
                        required>
                </div>

                <div class="mb-2">
                    <label>Email</label>
                    <input type="email" name="email"
                        class="form-control form-control-sm"
                        value="{{ old('email', $editCustomer->email ?? '') }}">
                </div>

                <div class="mb-2">
                    <label>Phone</label>
                    <input type="text" name="phone"
                        class="form-control form-control-sm"
                        value="{{ old('phone', $editCustomer->phone ?? '') }}"
                        required>
                </div>

                <div class="mb-2">
                    <label>Address</label>
                    <textarea name="address"
                        class="form-control form-control-sm"
                        rows="2">{{ old('address', $editCustomer->address ?? '') }}</textarea>
                </div>

                <div class="mb-2">
                    <label>Company</label>
                    <input type="text" name="company"
                        class="form-control form-control-sm"
                        value="{{ old('company', $editCustomer->company ?? '') }}">
                </div>

                <div class="mb-2">
                    <label>Notes</label>
                    <textarea name="notes"
                        class="form-control form-control-sm"
                        rows="2">{{ old('notes', $editCustomer->notes ?? '') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">
                    {{ isset($editCustomer) ? 'Update Customer' : 'Save Customer' }}
                </button>

                @if(isset($editCustomer))
                    <a href="{{ route(auth()->user()->role . '.customers.index') }}"
                        class="btn btn-secondary btn-sm">
                        Cancel
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Customer Table --}}
    <div class="row-three">
        <div class="card p-3">
            <h4>All Customers</h4>

            <div class="mb-3">
                <input type="text" id="customerSearch"
                    class="form-control"
                    placeholder="Search by name, email, phone, company...">
            </div>

            <div class="table-responsive"
                style="max-height: 500px; overflow-y: auto;">

                <table class="table table-bordered table-striped table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Company</th>
                            <th>Notes</th>
                            <th>Action</th>
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
                                    <div class="d-flex gap-1">

                                        {{-- Edit Button --}}
                                        <a href="{{ route(
                                            auth()->user()->role . '.customers.edit',
                                            $customer->id
                                        ) }}"
                                            class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        {{-- Delete Button --}}
                                        <form action="{{ route(
                                            auth()->user()->role . '.customers.destroy',
                                            $customer->id
                                        ) }}"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this customer?')">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="btn btn-danger btn-sm">
                                                Delete
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    No customers found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('customerSearch').addEventListener('keyup', function () {
    let searchValue = this.value.toLowerCase();

    document.querySelectorAll('#customerTable tr').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(searchValue)
            ? ''
            : 'none';
    });
});
</script>

@endsection
