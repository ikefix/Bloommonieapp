@extends('layouts.adminapp')

@section('admincontent')

<div class="container">

    <div class="card shadow-sm">
        <div class="card-header">
            <h4>Create Production Type</h4>
        </div>

        <div class="card-body">

            <form action="{{ route('admin.production.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label>Production Type Name</label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        placeholder="e.g Animal Feed Production"
                        required>
                </div>

                <div class="mb-3">
                    <label>Description</label>

                    <textarea
                        name="description"
                        rows="4"
                        class="form-control"></textarea>
                </div>

                <div class="mb-3">
                    <label>Status</label>

                    <select name="status" class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <button class="btn btn-primary">
                    Save Production Type
                </button>

            </form>

        </div>
    </div>

</div>

@endsection