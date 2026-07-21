@extends('layouts.app')

@section('title', 'Sales Reports')

@section('content')
    <div class="page-header report-page-header">
        <div class="page-title">
            <h2>Sales Reports</h2>
            <p>Analyze performance for the selected period and review recent transactions.</p>
        </div>
        <div class="page-actions report-actions">
            <button type="button" onclick="window.print()" class="btn btn-outline screen-only">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9" />
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                    <rect x="6" y="14" width="12" height="8" />
                </svg>
                Print
            </button>
            <a href="{{ route('reports.export', ['period' => $period]) }}" class="btn btn-primary screen-only">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <div class="print-cover">
        <div>
            <div class="print-brand-name">{{ config('app.name') }}</div>
            <div class="print-brand-address">Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu 6052</div>
        </div>
        <div class="print-meta-block">
            <div class="print-meta-row"><span>Report Type:</span> {{ $dateLabel }}</div>
            <div class="print-meta-row"><span>Period:</span> {{ $dateRange }}</div>
            <div class="print-meta-row"><span>Generated:</span> {{ now()->format('M d, Y h:i A') }}</div>
        </div>
    </div>

    <div class="report-toolbar screen-only">
        <form method="GET" action="{{ route('reports.index') }}" class="report-filters">
            @foreach(['all' => 'All Time', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $key => $label)
                <button type="submit" name="period" value="{{ $key }}"
                    class="btn btn-outline {{ $period === $key ? 'active' : '' }}">{{ $label }}</button>
            @endforeach
        </form>
        <div class="report-summary-card">
            <span>{{ $dateLabel }}</span>
            <strong>{{ $dateRange }}</strong>
        </div>
    </div>

    <div class="stats-grid print-summary-grid report-stat-grid">
        <div class="stat-card stat-success">
            <div class="stat-content">
                <h3>Total Revenue</h3>
                <div class="stat-value">&#8369;{{ number_format($stats->revenue, 2) }}</div>
            </div>
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23" />
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                </svg>
            </div>
        </div>
        <div class="stat-card stat-primary">
            <div class="stat-content">
                <h3>Transactions</h3>
                <div class="stat-value">{{ $stats->count }}</div>
            </div>
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
            </div>
        </div>
        <div class="stat-card stat-primary">
            <div class="stat-content">
                <h3>Average Sale</h3>
                <div class="stat-value">&#8369;{{ number_format($stats->average, 2) }}</div>
            </div>
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10" />
                    <line x1="12" y1="20" x2="12" y2="4" />
                    <line x1="6" y1="20" x2="6" y2="14" />
                </svg>
            </div>
        </div>
    </div>

    <div class="report-insight-strip screen-only">
        <div>
            <span>Top products listed</span>
            <strong>{{ $productBreakdown->count() }}</strong>
        </div>
        <div>
            <span>Transactions shown</span>
            <strong>{{ $transactions->count() }}</strong>
        </div>
        <div>
            <span>Export scope</span>
            <strong>{{ $dateLabel }}</strong>
        </div>
    </div>

    <div class="dashboard-sections report-sections">
        <div class="panel-card print-section report-table-card">
            <div class="panel-header report-table-header">
                <div>
                    <h3 class="print-section-title">Top Selling Products</h3>
                    <p>Ranked by product revenue in the selected period.</p>
                </div>
                <a href="{{ route('reports.export', ['period' => $period]) }}"
                    class="btn btn-outline btn-sm screen-only">Export Report</a>
            </div>
            <div class="table-responsive">
                <table class="table-custom print-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th style="text-align:center;">Units Sold</th>
                            <th style="text-align:right;">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productBreakdown as $index => $item)
                            <tr class="{{ $index % 2 === 1 ? 'row-alt' : '' }}">
                                <td class="rank-cell">{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $item->name }}</strong>
                                    @if($item->sku)<br><small class="muted-mono">{{ $item->sku }}</small>@endif
                                </td>
                                <td>
                                    <span class="category-pill">{{ $item->category ?: 'General' }}</span>
                                </td>
                                <td style="text-align:center;"><strong>{{ $item->units_sold }}</strong></td>
                                <td style="text-align:right;font-weight:700;color:#059669;">
                                    &#8369;{{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="table-empty-cell">No sales in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-card print-section report-table-card">
            <div class="panel-header report-table-header">
                <div>
                    <h3 class="print-section-title">Recent Transactions</h3>
                    <p>Latest sales included in this report.</p>
                </div>
                <button type="button" onclick="window.print()" class="btn btn-outline btn-sm screen-only">Print</button>
            </div>
            <div class="table-responsive">
                <table class="table-custom print-table">
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Cashier</th>
                            <th>Date &amp; Time</th>
                            <th style="text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $index => $sale)
                            <tr class="{{ $index % 2 === 1 ? 'row-alt' : '' }}">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span class="sale-id-pill">#{{ $sale->id }}</span>
                                        <a href="{{ route('sales.print', $sale->id) }}" target="_blank"
                                            class="action-btn btn-print screen-only" title="Print Receipt"
                                            style="width: 26px; height: 26px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; border: 1.5px solid #cbd5e1; color: var(--color-text-muted); background: white; transition: var(--transition-fast); text-decoration: none;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                                stroke-linejoin="round" style="width: 12px; height: 12px;">
                                                <polyline points="6 9 6 2 18 2 18 9" />
                                                <path
                                                    d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                                                <rect x="6" y="14" width="12" height="8" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                                <td>{{ $sale->user->name ?? 'System' }}</td>
                                <td class="muted-cell">{{ optional($sale->created_at)->format('M d, Y h:i A') ?? 'Unknown' }}
                                </td>
                                <td style="text-align:right;font-weight:700;">&#8369;{{ number_format($sale->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="table-empty-cell">No transactions recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="print-footer">
        Generated by {{ config('app.name') }} | {{ now()->format('F d, Y') }} | Confidential - Internal Use Only
    </div>
@endsection