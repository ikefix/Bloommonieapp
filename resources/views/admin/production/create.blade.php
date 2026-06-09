@extends('layouts.adminapp')

@section('admincontent')

<div class="container">

    <div class="card shadow-sm">

        <div class="card-header">
            <h4>Create Production Batch</h4>
        </div>

        <div class="card-body">

            <form action="{{ route('admin.production.store') }}"
                  method="POST">

                @csrf

                <div class="mb-3">
                    <label>Production Type</label>

                    <select name="production_type_id"
                            class="form-control"
                            required>

                        <option value="">
                            Select Production Type
                        </option>

                        @foreach($productionTypes as $type)

                            <option value="{{ $type->id }}">
                                {{ $type->name }}
                            </option>

                        @endforeach

                    </select>
                </div>

                <div class="mb-3">
                    <label>Production Title</label>

                    <input type="text"
                           name="title"
                           class="form-control"
                           placeholder="Broiler Cycle June 2026"
                           required>
                </div>

                <div class="mb-3">
                    <label>Description</label>

                    <textarea name="description"
                              class="form-control"
                              rows="4"></textarea>
                </div>

                <div class="row">

                    <div class="col-md-6">

                        <label>Start Date</label>

                        <input type="date"
                               name="start_date"
                               class="form-control">
                    </div>

                    <div class="col-md-6">

                        <label>Expected End Date</label>

                        <input type="date"
                               name="end_date"
                               class="form-control">
                    </div>

                </div>

                <br>

                <div class="mb-3">

                    <label>Status</label>

                    <select name="status"
                            class="form-control">

                        <option value="planned">
                            Planned
                        </option>

                        <option value="in_progress">
                            In Progress
                        </option>

                    </select>

                </div>

                <button class="btn btn-primary">
                    Create Batch
                </button>

            </form>

        </div>

    </div>

</div>

@endsection