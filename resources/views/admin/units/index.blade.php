@extends('layouts.adminapp')

@section('admincontent')

<div class="container">

    <h4>Units Setup</h4>

    <form method="POST" action="{{ route('admin.units.store') }}" class="mb-4">
        @csrf

        <div class="row">
            <div class="col-md-5">
                <input type="text" name="name" class="form-control" placeholder="e.g Kilogram" required>
            </div>

            <div class="col-md-3">
                <input type="text" name="symbol" class="form-control" placeholder="e.g kg">
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary">Add Unit</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Symbol</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            @foreach($units as $unit)
                <tr>
                    <td>{{ $unit->name }}</td>
                    <td>{{ $unit->symbol }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.units.destroy', $unit->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>

@endsection