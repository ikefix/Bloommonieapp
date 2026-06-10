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
                            <td>
                                <form action="{{ route('admin.production.status', $production->id) }}"
                                    method="POST">
                                    @csrf
                                    @method('PUT')

                                    <select name="status"
                                            class="form-select form-select-sm"
                                            onchange="this.form.submit()">

                                        <option value="pending"
                                            {{ $production->status == 'pending' ? 'selected' : '' }}>
                                            Pending
                                        </option>

                                        <option value="in_progress"
                                            {{ $production->status == 'in_progress' ? 'selected' : '' }}>
                                            In Progress
                                        </option>

                                        <option value="completed"
                                            {{ $production->status == 'completed' ? 'selected' : '' }}>
                                            Completed
                                        </option>

                                        <option value="cancelled"
                                            {{ $production->status == 'cancelled' ? 'selected' : '' }}>
                                            Cancelled
                                        </option>

                                    </select>
                                </form>
                            </td>

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