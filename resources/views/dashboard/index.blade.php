@extends('layouts.app')

@section('body_class', 'dashboard-body')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h2>Dashboard Overview</h2>
            <p>Welcome back, {{ auth()->user()->name }}. Here is the status of your store.</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-success">
            <div class="stat-content">
                <h3>Daily Sales</h3>
                <div class="stat-value">&#8369;{{ number_format($dailySales, 2) }}</div>
            </div>
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
        </div>
        <div class="stat-card stat-primary">
            <div class="stat-content">
                <h3>Weekly Sales</h3>
                <div class="stat-value">&#8369;{{ number_format($weeklySales, 2) }}</div>
            </div>
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
            </div>
        </div>
        <div class="stat-card stat-primary">
            <div class="stat-content">
                <h3>Monthly Sales</h3>
                <div class="stat-value">&#8369;{{ number_format($monthlySales, 2) }}</div>
            </div>
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
            </div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-content">
                <h3>Total Revenue</h3>
                <div class="stat-value">&#8369;{{ number_format($totalRevenue, 2) }}</div>
            </div>
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
        </div>
        <div class="stat-card {{ $lowStockCount > 0 ? 'stat-warning' : 'stat-success' }}">
            <div class="stat-content">
                <h3>Low Stock</h3>
                <div class="stat-value">{{ $lowStockCount }}</div>
            </div>
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                    </path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
        </div>
        <div class="stat-card stat-primary">
            <div class="stat-content">
                <h3>Products</h3>
                <div class="stat-value">{{ $totalProducts }}</div>
            </div>
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                    </path>
                    <polyline points="7.5 4.21 12 6.81 16.5 4.21"></polyline>
                    <polyline points="7.5 19.79 7.5 14.6 3 12"></polyline>
                    <polyline points="16.5 19.79 16.5 14.6 21 12"></polyline>
                    <polyline points="12 22.08 12 16.88 12 12"></polyline>
                    <polyline points="12 12 7.5 9.4 3 12"></polyline>
                    <polyline points="12 12 16.5 9.4 21 12"></polyline>
                </svg>
            </div>
        </div>
    </div>

    <div class="dashboard-layout-content">
        <div class="dashboard-left">
            <div class="dashboard-charts-row">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h4>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                <polyline points="16 7 22 7 22 13" />
                            </svg>
                            7-Day Sales Trend
                        </h4>
                        <span class="chart-caption">Revenue by day</span>
                    </div>
                    <div class="chart-wrapper"><canvas id="salesTrendChart"></canvas></div>
                </div>

                <div class="chart-card">
                    <div class="chart-card-header">
                        <h4>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path d="M3 3v18h18" />
                                <rect x="7" y="10" width="3" height="7" />
                                <rect x="12" y="6" width="3" height="11" />
                                <rect x="17" y="13" width="3" height="4" />
                            </svg>
                            Top Products
                        </h4>
                        <span class="chart-caption">Units sold</span>
                    </div>
                    <div class="chart-wrapper"><canvas id="topProductsChart"></canvas></div>
                </div>
            </div>

            <div class="panel-card recent-sales-panel">
                <div class="panel-header">
                    <h3>Recent Sales</h3>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">View Reports</a>
                </div>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Cashier</th>
                                <th>Timestamp</th>
                                <th style="text-align:right;">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <span class="sale-id-pill">#{{ $sale->id }}</span>
                                            <a href="{{ route('sales.print', $sale->id) }}" target="_blank" title="Print Receipt" style="width:26px;height:26px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;border:1.5px solid #cbd5e1;color:var(--color-text-muted);background:white;transition:var(--transition-fast);text-decoration:none;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                            </a>
                                        </div>
                                    </td>
                                    <td>{{ $sale->user->name ?? 'System' }}</td>
                                    <td>{{ $sale->created_at->format('M d, Y h:i A') }}</td>
                                    <td style="text-align:right;">&#8369;{{ number_format($sale->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="table-empty-cell">No sales recorded yet. Start by checking out in the
                                        POS.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="dashboard-right">
            <div class="chart-card category-stock-card">
                <div class="chart-card-header">
                    <h4>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path
                                d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                        </svg>
                        Stock by Category
                    </h4>
                    <span class="chart-caption">Current inventory units</span>
                </div>
                <div class="chart-wrapper"><canvas id="stockCategoryChart"></canvas></div>
            </div>

            <div class="panel-card quick-actions-panel">
                <div class="panel-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="quick-actions">
                    <a href="{{ route('pos.index') }}" class="action-card action-pos">
                        <span class="action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect>
                                <line x1="12" y1="4" x2="12" y2="20"></line>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                            </svg></span>
                        <h4>POS Checkout</h4>
                    </a>
                    <a href="{{ route('products.index') }}" class="action-card action-inv">
                        <span class="action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                                </path>
                            </svg></span>
                        <h4>Inventory</h4>
                    </a>
                    <a href="{{ route('chat.index') }}" class="action-card action-chat">
                        <span class="action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg></span>
                        <h4>Support Chat</h4>
                    </a>
                    <a href="{{ route('reports.index') }}" class="action-card action-reports">
                        <span class="action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="14"></line>
                            </svg></span>
                        <h4>Sales Reports</h4>
                    </a>
                </div>
            </div>

            <div class="panel-card low-stock-panel">
                <div class="panel-header">
                    <h3>Low Stock Alerts</h3>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm">Restock</a>
                </div>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="text-align:center;">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockList as $product)
                                <tr>
                                    <td><strong>{{ $product->name }}</strong><br><small
                                            style="color:#64748b;">&#8369;{{ number_format($product->price, 2) }}</small></td>
                                    <td style="text-align:center;"><span
                                            class="badge {{ $product->quantity === 0 ? 'badge-danger' : 'badge-warning' }}">{{ $product->quantity === 0 ? 'Out of Stock' : $product->quantity . ' left' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="table-empty-cell success-empty">All products are well stocked.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const dashboardChartData = @json($chartData);
        const chartPalette = ['#4f46e5', '#059669', '#0284c7', '#d97706', '#dc2626', '#7c3aed'];
        const moneyLabel = value => 'PHP ' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function createDashboardCharts() {
            if (!window.Chart) return;

            Chart.defaults.font.family = getComputedStyle(document.documentElement).getPropertyValue('--font-sans') || 'Inter, sans-serif';
            Chart.defaults.color = '#64748b';

            new Chart(document.getElementById('salesTrendChart'), {
                type: 'line',
                data: {
                    labels: dashboardChartData.salesTrend.labels,
                    datasets: [{
                        label: 'Sales',
                        data: dashboardChartData.salesTrend.totals,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.12)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#4f46e5'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: context => moneyLabel(context.parsed.y) } }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: value => 'PHP ' + value }, grid: { color: 'rgba(148, 163, 184, 0.18)' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            new Chart(document.getElementById('topProductsChart'), {
                type: 'bar',
                data: {
                    labels: dashboardChartData.topProducts.labels,
                    datasets: [{
                        label: 'Units Sold',
                        data: dashboardChartData.topProducts.units,
                        backgroundColor: chartPalette,
                        borderRadius: 6,
                        maxBarThickness: 36
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148, 163, 184, 0.18)' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            new Chart(document.getElementById('stockCategoryChart'), {
                type: 'doughnut',
                data: {
                    labels: dashboardChartData.stockByCategory.labels,
                    datasets: [{
                        data: dashboardChartData.stockByCategory.stock,
                        backgroundColor: chartPalette,
                        borderColor: '#ffffff',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '58%',
                    plugins: { legend: { position: 'right', labels: { boxWidth: 12, usePointStyle: true } } }
                }
            });
        }

        window.addEventListener('load', createDashboardCharts);
    </script>
@endpush