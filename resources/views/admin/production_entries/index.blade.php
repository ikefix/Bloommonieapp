@extends('layouts.adminapp')

@section('admincontent')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h4>Production Batches</h4>

        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control"
                   placeholder="Search batch..."
                   value="{{ request('search') }}">

            <button class="btn btn-primary">Search</button>
        </form>

    </div>

    <div class="card">

        <div class="card-body">

            <table class="table table-hover">

                <thead>
                    <tr>
                        <th>Batch No</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($productions as $production)

                        <tr>
                            <td>{{ $production->batch_no }}</td>
                            <td>{{ $production->productionType->name ?? '-' }}</td>
                            <td>{{ $production->title }}</td>
                            <td>{{ ucfirst($production->status) }}</td>

                            <td>
                                <a href="{{ route('admin.production_entries.show', $production->id) }}"
                                   class="btn btn-sm btn-primary">
                                    Open
                                </a>
                            </td>
                        </tr>

                    @endforeach

                </tbody>

            </table>


        </div>

    </div>

</div>

@endsection