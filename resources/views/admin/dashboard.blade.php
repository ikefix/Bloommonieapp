@extends('layouts.adminapp')

@section('admincontent')


<div class="container">
    <h3>Welcome {{ Auth::user()->name }}</h3>
    <div class="dashboard-stats">
        <div class="stat-box">
            <h4><i class='bx bx-cart'></i> Total Sales For The Week</h4>
            <p>₦{{ number_format($totalSalesThisWeek, 2) }}</p>
        </div>

        <div class="stat-box">
            <h4><i class='bx bx-dollar' ></i> Revenue Today</h4>
            <p>₦{{ number_format($totalRevenueToday, 2) }}</p>
        </div>

        <div class="stat-box">
            <h4><i class='bx bx-package'></i> Products in Stock</h4>
            <p>{{ $productsInStock }}</p>
        </div>

        <div class="stat-box">
            <h4><i class='bx bx-receipt'></i> Top Selling Products</h4>
            <ul>
                @foreach($topSelling as $item)
                    <li>{{ $item->product->name ?? 'Unknown Product' }} - Sold: {{ $item->total_sold }}</li>
                @endforeach
            </ul>
        </div>

        <!-- 🏷️ Discount Summary -->
        <div class="stat-box">
            <h4><i class='bx bx-purchase-tag'></i> Discounts Summary</h4>
            <ul style="list-style-type: none; padding-left: 0; font-size: 14px;">
                <li>🗓️ <strong>Today:</strong> <b><span style="color: #e91e63;">₦{{ number_format($totalDiscountToday, 2) }}</span></b></li>
                <li>📅 <strong>This Week:</strong> <b><span style="color: #3f51b5;">₦{{ number_format($totalDiscountThisWeek, 2) }}</span></b></li> 
                <li>🗓️ <strong>This Month:</strong> <b><span style="color: #4caf50;">₦{{ number_format($totalDiscountThisMonth, 2) }}</span></b></li>
            </ul>
        </div>

        <div class="stat-box">
            <h4><i class='bx bx-line-chart'></i> Profit Summary</h4>
            <ul class="list-unstyled">
                <li>📅 Daily Profit: <span style="color:green;">₦{{ number_format($dailyProfit, 2) }}</span></li>
                <li>📆 Weekly Profit: <span style="color:green;">₦{{ number_format($weeklyProfit, 2) }}</span></li>
                <li>🗓️ Monthly Profit: <span style="color:green;">₦{{ number_format($monthlyProfit, 2) }}</span></li>
            </ul>
        </div>

        <div class="stat-box">
    <h4><i class='bx bx-network-chart'></i> Net Profit & Loss Summary</h4>
    <ul>
        <li>💰 <strong>Today's Net Profit:</strong> 
            <span style="color: {{ $netProfitToday < 0 ? 'red' : 'green' }};">
                ₦{{ number_format($netProfitToday, 2) }}
            </span>
        </li>
        <li>📅 <strong>This Week:</strong> 
            <span style="color: {{ $netProfitWeek < 0 ? 'red' : 'green' }};">
                ₦{{ number_format($netProfitWeek, 2) }}
            </span>
        </li>
        <li>🗓️ <strong>This Month:</strong> 
            <span style="color: {{ $netProfitMonth < 0 ? 'red' : 'green' }};">
                ₦{{ number_format($netProfitMonth, 2) }}
            </span>
        </li>
    </ul>
</div>



    </div>

    <div class="chart-container-flex">
        <div class="chart-box">
            <h4><i class='bx bx-line-chart'></i> Sales Trend</h4>
            <canvas id="salesTrendChart"></canvas>
        </div>
    
        <div class="chart-box">
            <h4><i class='bx bxs-pie-chart-alt-2'></i> Top Selling Products</h4>
            <canvas id="topSellingProductsChart"></canvas>
        </div>
    </div>
    
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
        const salesTrendChart = new Chart(salesTrendCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($salesTrendLabels ?? []) !!},
                datasets: [{
                    label: 'Sales Trend',
                    data: {!! json_encode($salesTrendData ?? []) !!},
                    backgroundColor: 'rgba(241, 10, 222, 1)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: true
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        const topSellingProductsCtx = document.getElementById('topSellingProductsChart').getContext('2d');
        const topSellingProductsChart = new Chart(topSellingProductsCtx, {
            type: 'pie',
            data: {
                labels: {!! json_encode($topSellingProductNames ?? []) !!},
                datasets: [{
                    label: 'Top Selling Products',
                    data: {!! json_encode($topSellingProductSales ?? []) !!},
                    backgroundColor: [
                        'rgba(241, 10, 222, 0.9)',   // Electric blue
                        'rgba(94, 53, 177, 0.9)',    // Deep purple
                        'rgba(255, 87, 34, 0.9)',    // Burnt orange
                        'rgba(46, 125, 50, 0.9)',    // Dark green
                        'rgba(255, 193, 7, 0.9)',    // Gold pop
                        'rgba(233, 30, 99, 0.9)',    // Vivid pink
                        'rgba(0, 172, 193, 0.9)'     // Teal sharp
                    ],
                    hoverOffset: 4
                }]
            }
        });
    </script>
@endsection
