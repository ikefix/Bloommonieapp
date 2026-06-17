@extends('layouts.superadminapp')

@section('superadmincontent')

<div class="container-fluid px-4">

    {{-- ===== HEADER ===== --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Dashboard</h4>
            <small class="text-muted">Welcome back, {{ Auth::user()->name }}</small>
        </div>
        <span class="text-muted" style="font-size:0.85rem;">
            <i class="bx bx-calendar"></i> {{ now()->format('d M Y') }}
        </span>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="row g-3 mb-4">

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2" style="font-size:1.8rem; color:#6c7ee1;">
                        <i class="bx bxs-user-detail"></i>
                    </div>
                    <h5 class="fw-bold mb-0">{{ $totalAdmins }}</h5>
                    <small class="text-muted">Total Admins</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2" style="font-size:1.8rem; color:#28a745;">
                        <i class="bx bx-user-check"></i>
                    </div>
                    <h5 class="fw-bold mb-0">{{ $activeAdmins }}</h5>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2" style="font-size:1.8rem; color:#dc3545;">
                        <i class="bx bx-user-x"></i>
                    </div>
                    <h5 class="fw-bold mb-0">{{ $inactiveAdmins }}</h5>
                    <small class="text-muted">Inactive</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2" style="font-size:1.8rem; color:#0d6efd;">
                        <i class="bx bx-badge-check"></i>
                    </div>
                    <h5 class="fw-bold mb-0">{{ $subscribedAdmins }}</h5>
                    <small class="text-muted">Subscribed</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2" style="font-size:1.8rem; color:#fd7e14;">
                        <i class="bx bx-error-circle"></i>
                    </div>
                    <h5 class="fw-bold mb-0">{{ $overdueAdmins }}</h5>
                    <small class="text-muted">Overdue</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2" style="font-size:1.8rem; color:#6f42c1;">
                        <i class="bx bx-trending-up"></i>
                    </div>
                    <h5 class="fw-bold mb-0">
                        {{ $totalAdmins > 0 ? round(($activeAdmins / $totalAdmins) * 100) : 0 }}%
                    </h5>
                    <small class="text-muted">Active Rate</small>
                </div>
            </div>
        </div>

    </div>

    {{-- ===== CHART + RECENT ADMINS ===== --}}
    <div class="row g-3 mb-4">

        {{-- Monthly Signups Chart --}}
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bx bx-bar-chart-alt-2 text-primary"></i>
                        Monthly Admin Signups ({{ now()->year }})
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="signupChart" height="120"></canvas>
                </div>
            </div>
        </div>

        {{-- Recent Admins --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bx bx-user-plus text-success"></i>
                        Recently Joined
                    </h6>
                    <a href="{{ route('superadmin.admins.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($recentAdmins as $admin)
                            <li class="list-group-item d-flex align-items-center gap-3 px-3 py-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                     style="width:36px; height:36px; font-size:0.8rem; flex-shrink:0;
                                            background: #6c7ee1;">
                                    {{ strtoupper(substr($admin->name, 0, 2)) }}
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-semibold text-truncate" style="font-size:0.88rem;">{{ $admin->name }}</div>
                                    <div class="text-muted text-truncate" style="font-size:0.78rem;">{{ $admin->email }}</div>
                                </div>
                                <small class="text-muted text-nowrap" style="font-size:0.75rem;">
                                    {{ $admin->created_at->diffForHumans() }}
                                </small>
                            </li>
                        @empty
                            <li class="list-group-item text-muted text-center py-4">No admins yet</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

    </div>

    {{-- ===== MOST ACTIVE ADMINS + SUBSCRIPTION BREAKDOWN ===== --}}
    <div class="row g-3 mb-4">

        {{-- Most Active --}}
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bx bx-trophy text-warning"></i>
                        Most Active Admins
                    </h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Admin</th>
                                <th class="text-center">Productions</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mostActiveAdmins as $i => $admin)
                                <tr>
                                    <td>
                                        <span class="fw-bold" style="color: {{ $i === 0 ? '#fd7e14' : ($i === 1 ? '#6c757d' : '#cd7f32') }};">
                                            #{{ $i + 1 }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold" style="font-size:0.88rem;">{{ $admin->name }}</div>
                                        <div class="text-muted" style="font-size:0.77rem;">{{ $admin->email }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill">{{ $admin->productions_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($admin->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted text-center py-3">No data yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Subscription Breakdown --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bx bx-pie-chart-alt-2 text-info"></i>
                        Subscription Breakdown
                    </h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <canvas id="subChart" height="180"></canvas>
                </div>
            </div>
        </div>

    </div>

</div>

{{-- ===== CHARTS JS ===== --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Monthly signups bar chart
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const signupData = @json($monthlySignups);
    const signupValues = months.map((_, i) => signupData[i + 1] ?? 0);

    new Chart(document.getElementById('signupChart'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Signups',
                data: signupValues,
                backgroundColor: '#6c7ee1cc',
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Subscription doughnut chart
    new Chart(document.getElementById('subChart'), {
        type: 'doughnut',
        data: {
            labels: ['Subscribed', 'Overdue', 'Inactive'],
            datasets: [{
                data: [{{ $subscribedAdmins }}, {{ $overdueAdmins }}, {{ $inactiveAdmins }}],
                backgroundColor: ['#28a745', '#fd7e14', '#dc3545'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } }
            }
        }
    });
</script>

@endsection