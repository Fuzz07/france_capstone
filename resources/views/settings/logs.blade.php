@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<style>
    /* Premium Page Header */
    .logs-page-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        padding: 28px 32px;
        border-radius: 16px;
        color: #ffffff;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
        margin-bottom: 28px;
        position: relative;
        overflow: hidden;
    }

    .logs-page-header::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 60%);
        pointer-events: none;
    }

    .logs-page-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .logs-page-header p {
        margin: 6px 0 0 0;
        font-size: 14px;
        color: #94a3b8;
        line-height: 1.5;
        max-width: 600px;
    }

    /* Modern Metric Cards Grid */
    .logs-metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }

    .log-metric-card {
        background: #ffffff;
        padding: 20px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .log-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
    }

    .log-metric-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .metric-icon-total {
        background: rgba(79, 70, 229, 0.08);
        color: #4f46e5;
    }

    .metric-icon-admins {
        background: rgba(124, 58, 237, 0.08);
        color: #7c3aed;
    }

    .metric-icon-customers {
        background: rgba(16, 185, 129, 0.08);
        color: #10b981;
    }

    .metric-icon-guests {
        background: rgba(100, 116, 139, 0.08);
        color: #64748b;
    }

    .log-metric-info h4 {
        margin: 0;
        font-size: 13px;
        color: #64748b;
        font-weight: 600;
        letter-spacing: 0.1px;
    }

    .log-metric-info h3 {
        margin: 4px 0 0 0;
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
    }

    /* Filters & Actions Header */
    .logs-filter-card {
        background: #ffffff;
        padding: 18px 24px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .filter-pills {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13.5px;
        font-weight: 600;
        color: #64748b;
        background: #f1f5f9;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .filter-pill:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .filter-pill.active {
        background: #4f46e5;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    }

    .logs-search-form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-grow: 1;
        max-width: 400px;
    }

    .logs-search-wrapper {
        position: relative;
        flex-grow: 1;
    }

    .logs-search-wrapper i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 15px;
    }

    .logs-search-input {
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 10px 14px 10px 40px;
        font-size: 0.9rem;
        width: 100%;
        outline: none;
        transition: all 0.2s ease;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
        color: #1e293b;
        box-sizing: border-box;
    }

    .logs-search-input:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12), inset 0 1px 2px rgba(0, 0, 0, 0.02);
    }

    .btn-search {
        background: #4f46e5;
        color: #ffffff;
        border: none;
        padding: 10px 18px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-search:hover {
        background: #4338ca;
    }

    .btn-clear-search {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 10px 14px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
    }

    .btn-clear-search:hover {
        background: #e2e8f0;
    }

    /* Modern Table Styling */
    .modern-table-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
        overflow: hidden;
        margin-bottom: 32px;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .modern-table th {
        background-color: #f8fafc;
        padding: 16px 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
    }

    .modern-table td {
        padding: 16px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13.5px;
        color: #334155;
    }

    .modern-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .modern-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Status & Action Badges */
    .action-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge-login { background: rgba(16, 185, 129, 0.08); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
    .badge-logout { background: rgba(100, 116, 139, 0.08); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.2); }
    .badge-register { background: rgba(59, 130, 246, 0.08); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
    .badge-create { background: rgba(13, 148, 136, 0.08); color: #0d9488; border: 1px solid rgba(13, 148, 136, 0.2); }
    .badge-update { background: rgba(124, 58, 237, 0.08); color: #7c3aed; border: 1px solid rgba(124, 58, 237, 0.2); }
    .badge-delete { background: rgba(239, 68, 68, 0.08); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    .badge-settings { background: rgba(245, 158, 11, 0.08); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
    .badge-other { background: rgba(71, 85, 105, 0.08); color: #475569; border: 1px solid rgba(71, 85, 105, 0.2); }

    /* Role Badges */
    .role-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .role-badge.admin {
        background: rgba(124, 58, 237, 0.1);
        color: #7c3aed;
    }

    .role-badge.user {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }

    .role-badge.guest {
        background: rgba(100, 116, 139, 0.1);
        color: #64748b;
    }

    /* IP & Agent Styling */
    .mono-text {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 12px;
        color: #475569;
    }

    .agent-text {
        max-width: 180px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: inline-block;
        cursor: help;
        border-bottom: 1px dotted #cbd5e1;
    }

    /* Custom Pagination Styling */
    .logs-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 24px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }

    .pagination-btn {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #1e293b;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .pagination-btn:hover:not(.disabled) {
        border-color: #4f46e5;
        color: #4f46e5;
        background: #f5f3ff;
    }

    .pagination-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .pagination-info {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
    }

    .empty-state {
        text-align: center;
        padding: 48px 24px;
        color: #64748b;
    }

    .empty-state i {
        font-size: 40px;
        color: #cbd5e1;
        margin-bottom: 12px;
        display: block;
    }

    .empty-state h5 {
        margin: 0 0 6px 0;
        font-size: 16px;
        color: #334155;
        font-weight: 700;
    }

    .empty-state p {
        margin: 0;
        font-size: 13.5px;
    }
</style>

<div class="logs-page-header">
    <h2>System Activity Logs</h2>
    <p>Track, filter, and audit real-time actions executed by Administrators, Staff, and Customers across Mera's Store platform.</p>
</div>

<!-- Metrics Cards Grid -->
<div class="logs-metrics-grid">
    <div class="log-metric-card">
        <div class="log-metric-icon metric-icon-total">
            <i class="bi bi-clock-history"></i>
        </div>
        <div class="log-metric-info">
            <h4>Total Activities</h4>
            <h3>{{ number_format($totalLogsCount) }}</h3>
        </div>
    </div>
    <div class="log-metric-card">
        <div class="log-metric-icon metric-icon-admins">
            <i class="bi bi-shield-lock"></i>
        </div>
        <div class="log-metric-info">
            <h4>Admin Actions</h4>
            <h3>{{ number_format($adminLogsCount) }}</h3>
        </div>
    </div>
    <div class="log-metric-card">
        <div class="log-metric-icon metric-icon-customers">
            <i class="bi bi-people"></i>
        </div>
        <div class="log-metric-info">
            <h4>Customer Actions</h4>
            <h3>{{ number_format($customerLogsCount) }}</h3>
        </div>
    </div>
    <div class="log-metric-card">
        <div class="log-metric-icon metric-icon-guests">
            <i class="bi bi-person-dash"></i>
        </div>
        <div class="log-metric-info">
            <h4>Guest Actions</h4>
            <h3>{{ number_format($guestLogsCount) }}</h3>
        </div>
    </div>
</div>

<!-- Filters and Actions Header -->
<div class="logs-filter-card">
    <div class="filter-pills">
        <a href="{{ route('logs.index', ['filter' => 'all', 'q' => $search]) }}" 
           class="filter-pill {{ $filter === 'all' ? 'active' : '' }}">
            <i class="bi bi-grid"></i> All Logs
        </a>
        <a href="{{ route('logs.index', ['filter' => 'admin', 'q' => $search]) }}" 
           class="filter-pill {{ $filter === 'admin' ? 'active' : '' }}">
            <i class="bi bi-shield-lock"></i> Admin Logs
        </a>
        <a href="{{ route('logs.index', ['filter' => 'customer', 'q' => $search]) }}" 
           class="filter-pill {{ $filter === 'customer' ? 'active' : '' }}">
            <i class="bi bi-people"></i> Customer Logs
        </a>
        <a href="{{ route('logs.index', ['filter' => 'guest', 'q' => $search]) }}" 
           class="filter-pill {{ $filter === 'guest' ? 'active' : '' }}">
            <i class="bi bi-person-dash"></i> Guest Logs
        </a>
    </div>

    <form action="{{ route('logs.index') }}" method="GET" class="logs-search-form">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <div class="logs-search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" name="q" value="{{ $search }}" class="logs-search-input" 
                   placeholder="Search logs by action, user, detail...">
        </div>
        <button type="submit" class="btn-search">Search</button>
        @if(!empty($search))
            <a href="{{ route('logs.index', ['filter' => $filter]) }}" class="btn-clear-search" title="Clear search">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </form>
</div>

<!-- Logs Table Card -->
<div class="modern-table-card">
    <table class="modern-table">
        <thead>
            <tr>
                <th style="width: 180px;">Timestamp</th>
                <th style="width: 180px;">User Name</th>
                <th style="width: 100px;">Role</th>
                <th style="width: 160px;">Action Type</th>
                <th>Activity Details</th>
                <th style="width: 130px;">IP Address</th>
                <th style="width: 150px;">Browser/Agent</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="mono-text">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    <td style="font-weight: 700;">
                        @if($log->user)
                            {{ $log->user_name }}
                        @else
                            <span style="color: #64748b; font-style: italic;">{{ $log->user_name }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="role-badge {{ $log->user_role ?: 'guest' }}">
                            {{ $log->user_role ?: 'guest' }}
                        </span>
                    </td>
                    <td>
                        @php
                            $badgeClass = 'badge-other';
                            $actionLower = strtolower($log->action);
                            if (str_contains($actionLower, 'login')) $badgeClass = 'badge-login';
                            elseif (str_contains($actionLower, 'logout')) $badgeClass = 'badge-logout';
                            elseif (str_contains($actionLower, 'register')) $badgeClass = 'badge-register';
                            elseif (str_contains($actionLower, 'create')) $badgeClass = 'badge-create';
                            elseif (str_contains($actionLower, 'update')) $badgeClass = 'badge-update';
                            elseif (str_contains($actionLower, 'delete')) $badgeClass = 'badge-delete';
                            elseif (str_contains($actionLower, 'seed') || str_contains($actionLower, 'reset') || str_contains($actionLower, 'password')) $badgeClass = 'badge-settings';
                        @endphp
                        <span class="action-badge {{ $badgeClass }}">
                            {{ str_replace('_', ' ', $log->action) }}
                        </span>
                    </td>
                    <td style="font-weight: 500; color: #1e293b;">{{ $log->description }}</td>
                    <td class="mono-text">{{ $log->ip_address ?: 'N/A' }}</td>
                    <td>
                        @if($log->user_agent)
                            <span class="agent-text mono-text" title="{{ $log->user_agent }}">
                                {{ Str::limit($log->user_agent, 20) }}
                            </span>
                        @else
                            <span class="mono-text" style="color: #cbd5e1;">N/A</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="bi bi-journal-x"></i>
                            <h5>No Activities Found</h5>
                            <p>No activity logs match your search or filter options at this time.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pagination Links -->
    @if($logs->hasPages())
        <div class="logs-pagination">
            @if($logs->onFirstPage())
                <span class="pagination-btn disabled">&laquo; Previous</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="pagination-btn">&laquo; Previous</a>
            @endif

            <span class="pagination-info">
                Showing {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries (Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }})
            </span>

            @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="pagination-btn">Next &raquo;</a>
            @else
                <span class="pagination-btn disabled">Next &raquo;</span>
            @endif
        </div>
    @endif
</div>
@endsection
