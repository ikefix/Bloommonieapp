@extends('layouts.adminapp')

@section('admincontent')

<div class="container">

    <div class="d-flex justify-content-between mb-3">
        <h4>Production Types</h4>

        <a href="{{ route('admin.production.create') }}"
           class="btn btn-primary">
            Add Production Type
        </a>
    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($productionTypes as $type)

                    <tr>

                        <td>{{ $loop->iteration }}</td>

                        <td>{{ $type->name }}</td>

                        <td>{{ $type->description }}</td>

                        <td>
                            @if($type->status)
                                <span class="badge bg-success">
                                    Active
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    Inactive
                                </span>
                            @endif
                        </td>

                        <td>

                            <form action="{{ route('admin.production.destroy',$type->id) }}"
                                  method="POST">

                                @csrf
                                @method('DELETE')

                                <button class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this production type?')">
                                    Delete
                                </button>

                            </form>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="text-center">
                            No production types found.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

            {{ $productionTypes->links() }}

        </div>

    </div>

</div>

@endsection