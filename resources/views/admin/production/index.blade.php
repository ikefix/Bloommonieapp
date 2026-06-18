@extends('layouts.adminapp')

@section('admincontent')

<div class="container">

    <div class="d-flex justify-content-between mb-3">

        <h4>Production Batches</h4>

        <a href="{{ route('admin.production.create') }}"
           class="btn btn-primary">
            New Production
        </a>

    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>Batch No</th>
                        <th>Production Type</th>
                        <th>Title</th>
                        <th>Warehouse</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                @foreach($productions as $production)

                    <tr>

                        <td>
                            {{ $production->batch_no }}
                        </td>

                        <td>
                            {{ $production->productionType->name }}
                        </td>

                        <td>
                            {{ $production->title }}
                        </td>

                        <td>
                            {{ $production->shop->name }} 
                        </td>

                        <td>
                            {{ ucfirst($production->status) }}
                        </td>

                        <td>
                            {{ $production->start_date }}
                        </td>

                        <td>
                            {{ $production->end_date }}
                        </td>
                        
                        <td>
                            <a href="{{ route('admin.production_entries.fill', $production->id) }}"
                            class="btn btn-sm btn-success">
                                Fill Entry
                            </a>
                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

            {{ $productions->links() }}

        </div>

    </div>

</div>

@endsection