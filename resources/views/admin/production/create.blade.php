@extends('layouts.adminapp')

@section('admincontent')

@php
    $user = auth()->user();

    $locked = !$user->hasFeature('production_management');

    $message = $user->getLimitMessage(
        'feature',
        'Production Management'
    );
@endphp


<style>

.page-lock-wrapper{
    position: relative;
}

.lock-overlay{
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(4px);
    z-index: 99;

    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 14px;
}

.lock-card{
    background: white;
    width: 420px;
    max-width: 90%;
    padding: 35px;
    border-radius: 18px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);

    animation: floatCard 2s ease-in-out infinite;
}

.lock-card i{
    font-size: 60px;
    color: #ff9800;
    margin-bottom: 15px;
}

.lock-card h2{
    font-size: 28px;
    margin-bottom: 10px;
    font-weight: 700;
}

.lock-card p{
    color: #666;
    line-height: 1.6;
    margin-bottom: 25px;
}

.upgrade-btn{
    display: inline-block;
    background: linear-gradient(135deg,#ff9800,#ff5722);
    color: white;
    text-decoration: none;
    padding: 12px 24px;
    border-radius: 50px;
    font-weight: bold;
    transition: .3s;
}

.upgrade-btn:hover{
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(255,87,34,.3);
    color: white;
}

.lock-badge{
    display: inline-block;
    background: rgba(255,152,0,.1);
    color: #ff9800;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 13px;
    margin-bottom: 15px;
    font-weight: 600;
}

@keyframes floatCard{
    0%{
        transform: translateY(0px);
    }

    50%{
        transform: translateY(-6px);
    }

    100%{
        transform: translateY(0px);
    }
}

.locked-content{
    pointer-events: none;
    user-select: none;
}

</style>


<div class="page-lock-wrapper">

    @if($locked)

        <div class="lock-overlay">

            <div class="lock-card">

                <div class="lock-badge">
                    PREMIUM FEATURE
                </div>

                <i class="fas fa-lock"></i>

                <h2>Production & Manufacturing Locked</h2>

                <p>
                    {{ $message }}
                </p>

                <a href="{{ url('/pricing') }}"
                   class="upgrade-btn">
                    Upgrade Plan
                </a>

            </div>

        </div>

    @endif

    <div class="{{ $locked ? 'locked-content' : '' }}">
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
                            <label>Shop</label>

                            <select name="shop_id"
                                    class="form-control"
                                    required>

                                <option value="">
                                    Select Shop
                                </option>

                                @foreach($shops as $shop)

                                    <option value="{{ $shop->id }}">
                                        {{ $shop->name }}
                                    </option>>

                                @endforeach

                            </select>
                        </div>

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
    </div>
</div>


@endsection