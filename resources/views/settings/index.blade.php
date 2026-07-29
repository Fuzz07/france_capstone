@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h2>System Settings</h2>
            <p>Admin utilities for data maintenance, exports, and system overview.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('logout') }}" class="btn btn-danger" data-logout-confirm
                style="display:inline-flex;align-items:center;gap:8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                Log Out
            </a>
        </div>
    </div>

    @if(session('notice'))
        <div class="alert alert-{{ session('noticeType', 'success') }}">{{ session('notice') }}</div>
    @endif

    <div class="settings-grid">
        {{-- System Overview --}}
        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <h3>System Overview</h3>
                    <p class="settings-timestamp">Snapshot updated {{ now()->format('M d, Y h:i A') }}</p>
                </div>
            </div>

            <div class="settings-profile">
                <div class="settings-profile-avatar">{{ strtoupper(substr($currentUser->name, 0, 2)) }}</div>
                <div>
                    <h4>{{ $currentUser->name }}</h4>
                    <p>{{ ucfirst($currentUser->role) }} · {{ $currentUser->email }}</p>
                </div>
            </div>

            <div class="settings-detail-list">
                <div><span>Total Products</span><strong>{{ $tableCounts['products'] }}</strong></div>
                <div><span>Total Sales</span><strong>{{ $tableCounts['sales'] }}</strong></div>
                <div><span>Transactions</span><strong>{{ $tableCounts['sale_items'] }}</strong></div>
                <div><span>Inquiries</span><strong>{{ $tableCounts['inquiries'] }}</strong></div>
                <div><span>Chat Messages</span><strong>{{ $tableCounts['messages'] }}</strong></div>
                <div><span>Users</span><strong>{{ $tableCounts['users'] }}</strong></div>
                <div><span>Admin Users</span><strong>{{ $adminCount }}</strong></div>
                <div><span>Last
                        Sale</span><strong>{{ $lastSaleAt ? \Carbon\Carbon::parse($lastSaleAt)->format('M d, Y h:i A') : 'Never' }}</strong>
                </div>
            </div>

            <div class="stats-grid" style="margin-top:24px;">
                <div class="stat-card stat-success">
                    <div>
                        <h4>Total Revenue</h4>
                        <div class="stat-value">₱{{ number_format($totalRevenue, 2) }}</div>
                    </div>
                </div>
                <div class="stat-card stat-primary">
                    <div>
                        <h4>Sales Count</h4>
                        <div class="stat-value">{{ $tableCounts['sales'] }}</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div>
                        <h4>Active Users</h4>
                        <div class="stat-value">{{ $tableCounts['users'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Side Panel --}}
        <div class="settings-side-panel">
            {{-- Export & Backup --}}
            <div class="panel-card settings-export-panel">
                <div class="panel-header">
                    <h3>Export &amp; Backup</h3>
                </div>
                <div class="settings-export-lead">
                    <div>
                        <h4>Download records</h4>
                        <p>Export your data for reporting or backups. Choose from full or segmented exports.</p>
                    </div>
                    <div class="settings-export-count">Fast</div>
                </div>
                <div class="settings-note">
                    Exports are generated instantly and downloaded as CSV files.
                </div>
                <div class="settings-export-list">
                    <a href="{{ route('settings.export', ['export' => 'all']) }}" class="settings-export-option">
                        <div class="settings-export-icon">📁</div>
                        <div class="settings-export-copy">
                            <strong>Full system export</strong>
                            <small>Products, sales, transactions, and more</small>
                        </div>
                        <div class="settings-export-count">All</div>
                    </a>
                    <a href="{{ route('settings.export', ['export' => 'products']) }}" class="settings-export-option">
                        <div class="settings-export-icon">🛍️</div>
                        <div class="settings-export-copy">
                            <strong>Product catalog</strong>
                            <small>Download all product listings and categories</small>
                        </div>
                        <div class="settings-export-count">Products</div>
                    </a>
                    <a href="{{ route('settings.export', ['export' => 'sales']) }}" class="settings-export-option">
                        <div class="settings-export-icon">💰</div>
                        <div class="settings-export-copy">
                            <strong>Sales history</strong>
                            <small>Export sales totals and transaction records</small>
                        </div>
                        <div class="settings-export-count">Sales</div>
                    </a>
                </div>
            </div>

            {{-- Change Admin Password --}}
            <div class="panel-card">
                <div class="panel-header">
                    <h3>Change Admin Password</h3>
                </div>
                <form method="POST" action="{{ route('settings.change-password') }}" style="display:flex; flex-direction:column; gap:12px; padding-top:8px;">
                    @csrf
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <label for="current_password" style="font-size:12px; font-weight:600; color:#475569;">Current Password</label>
                        <input type="password" name="current_password" id="current_password" required placeholder="Enter current password" 
                            style="border:1px solid #cbd5e1; border-radius:8px; padding:10px 12px; font-size:13.5px; outline:none; transition:border-color 0.2s;"
                            onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#cbd5e1'">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <label for="new_password" style="font-size:12px; font-weight:600; color:#475569;">New Password</label>
                        <input type="password" name="new_password" id="new_password" required placeholder="At least 6 characters" 
                            style="border:1px solid #cbd5e1; border-radius:8px; padding:10px 12px; font-size:13.5px; outline:none; transition:border-color 0.2s;"
                            onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#cbd5e1'">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <label for="new_password_confirmation" style="font-size:12px; font-weight:600; color:#475569;">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" required placeholder="Confirm new password" 
                            style="border:1px solid #cbd5e1; border-radius:8px; padding:10px 12px; font-size:13.5px; outline:none; transition:border-color 0.2s;"
                            onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#cbd5e1'">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px;">Update Password</button>
                </form>
            </div>

            {{-- Maintenance Actions --}}
            <div class="panel-card">
                <div class="panel-header">
                    <h3>Maintenance Actions</h3>
                </div>
                <div class="settings-action-stack">

                    <form method="POST" action="{{ route('settings.action') }}"
                        onsubmit="return confirm('This will reset and reload demo products. Continue?')">
                        @csrf
                        <input type="hidden" name="action" value="seed_products">
                        <button type="submit" class="btn btn-primary btn-block">Seed Demo Products</button>
                    </form>

                    <form method="POST" action="{{ route('settings.action') }}"
                        onsubmit="return confirm('This will permanently delete all sales history. Are you sure?')">
                        @csrf
                        <input type="hidden" name="action" value="reset_sales">
                        <button type="submit" class="btn btn-danger btn-block">Reset Sales History</button>
                    </form>
                    <form method="POST" action="{{ route('settings.action') }}"
                        onsubmit="return confirm('This will clear all chat messages. Are you sure?')">
                        @csrf
                        <input type="hidden" name="action" value="reset_chat">
                        <button type="submit" class="btn btn-danger btn-block">Clear Chat History</button>
                    </form>
                </div>
            </div>


        </div>
    </div>
@endsection