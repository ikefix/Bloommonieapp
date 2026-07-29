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
    </div>
</div>


@endsection