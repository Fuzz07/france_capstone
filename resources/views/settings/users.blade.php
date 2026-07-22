@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<style>
    /* Premium Page Header */
    .users-page-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        padding: 28px 32px;
        border-radius: 16px;
        color: #ffffff;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
        margin-bottom: 28px;
        position: relative;
        overflow: hidden;
    }

    .users-page-header::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 60%);
        pointer-events: none;
    }

    .users-page-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .users-page-header p {
        margin: 6px 0 0 0;
        font-size: 14px;
        color: #94a3b8;
        line-height: 1.5;
        max-width: 600px;
    }

    /* Modern Metric Cards Grid */
    .users-metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }

    .user-metric-card {
        background: #ffffff;
        padding: 24px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
        display: flex;
        align-items: center;
        gap: 18px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .user-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
    }

    .user-metric-icon {
        width: 54px;
        height: 54px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }

    .metric-icon-users {
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

    .user-metric-info h4 {
        margin: 0;
        font-size: 13.5px;
        color: #64748b;
        font-weight: 600;
        letter-spacing: 0.1px;
    }

    .user-metric-info h3 {
        margin: 6px 0 0 0;
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
    }

    /* Filters & Actions Header */
    .users-filter-card {
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

    .users-search-form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-grow: 1;
        max-width: 460px;
    }

    .users-search-wrapper {
        position: relative;
        flex-grow: 1;
    }

    .users-search-wrapper i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 15px;
    }

    .users-search-input {
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

    .users-search-input:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12), inset 0 1px 2px rgba(0, 0, 0, 0.02);
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
        padding: 16px 24px;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
    }

    .modern-table td {
        padding: 18px 24px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    .modern-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .modern-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Premium Avatar Gradients */
    .modern-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: 700;
        font-size: 15px;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        flex-shrink: 0;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    /* Dynamic soft pastel gradients based on letter */
    .avatar-g1 { background: linear-gradient(135deg, #6366f1, #4f46e5); } /* Indigo */
    .avatar-g2 { background: linear-gradient(135deg, #10b981, #059669); } /* Emerald */
    .avatar-g3 { background: linear-gradient(135deg, #3b82f6, #1d4ed8); } /* Blue */
    .avatar-g4 { background: linear-gradient(135deg, #f59e0b, #d97706); } /* Amber */
    .avatar-g5 { background: linear-gradient(135deg, #ec4899, #db2777); } /* Pink */

    /* Custom Role Pills with Glowing Indicator */
    .modern-role-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11.5px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 100px;
        text-transform: capitalize;
    }

    .role-pill-admin {
        background-color: #faf5ff;
        color: #7c3aed;
        border: 1px solid #f3e8ff;
    }

    .role-pill-user {
        background-color: #eff6ff;
        color: #2563eb;
        border: 1px solid #dbeafe;
    }

    .role-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }

    .role-dot-admin {
        background-color: #7c3aed;
        box-shadow: 0 0 6px #a78bfa;
    }

    .role-dot-user {
        background-color: #2563eb;
        box-shadow: 0 0 6px #60a5fa;
    }

    /* Beautiful Premium Select Dropdown */
    .modern-select-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
        max-width: 180px;
    }

    .modern-select {
        appearance: none;
        -webkit-appearance: none;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 8px 32px 8px 12px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #334155;
        outline: none;
        background-color: #ffffff;
        cursor: pointer;
        transition: all 0.2s ease;
        width: 100%;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }

    .modern-select:hover {
        border-color: #cbd5e1;
        background-color: #f8fafc;
    }

    .modern-select:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
    }

    .modern-select-wrapper::after {
        content: '\F229'; /* Bootstrap Icons Chevron Down */
        font-family: 'bootstrap-icons';
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 11px;
        color: #64748b;
        pointer-events: none;
        font-weight: bold;
    }

    /* Action Buttons styling */
    .circle-action-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .circle-action-btn:hover {
        background-color: #ef4444;
        color: #ffffff;
        border-color: #ef4444;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2);
        transform: translateY(-1px);
    }

    /* Modal premium styles */
    .user-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .user-modal-overlay.open {
        display: flex;
        opacity: 1;
    }

    .user-modal {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        width: 100%;
        max-width: 440px;
        padding: 32px;
        box-sizing: border-box;
        border: 1px solid rgba(226, 232, 240, 0.8);
        animation: scalePop 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .user-modal-title {
        font-size: 1.3rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 6px 0;
        letter-spacing: -0.4px;
    }

    .user-modal-desc {
        font-size: 0.82rem;
        color: #64748b;
        margin: 0 0 24px 0;
    }

    .modal-form-group {
        margin-bottom: 18px;
    }

    .modal-form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }

    .modal-form-control {
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.9rem;
        width: 100%;
        box-sizing: border-box;
        outline: none;
        transition: all 0.2s ease;
        color: #1e293b;
    }

    .modal-form-control:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .modal-btn-row {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 28px;
    }
</style>

<!-- Premium Page Header -->
<div class="users-page-header">
    <h2>User Accounts</h2>
    <p>Monitor system registrations, regulate customer profiles, promote administrative roles, and manually generate accounts.</p>
</div>

@if(session('notice'))
    <div class="alert alert-{{ session('noticeType', 'success') }}" style="border-radius: 12px; margin-bottom: 24px;">{{ session('notice') }}</div>
@endif

<!-- Error Alerts -->
@if($errors->any())
    <div class="alert alert-danger" style="border-radius: 12px; margin-bottom: 24px;">
        <ul style="margin: 0; padding-left: 16px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Metric Counters Grid -->
<div class="users-metrics-grid">
    <div class="user-metric-card">
        <div class="user-metric-icon metric-icon-users">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="user-metric-info">
            <h4>Total Accounts</h4>
            <h3>{{ $totalUsers }}</h3>
        </div>
    </div>
    <div class="user-metric-card">
        <div class="user-metric-icon metric-icon-admins">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div class="user-metric-info">
            <h4>Administrators</h4>
            <h3>{{ $adminCount }}</h3>
        </div>
    </div>
    <div class="user-metric-card">
        <div class="user-metric-icon metric-icon-customers">
            <i class="bi bi-person-fill-check"></i>
        </div>
        <div class="user-metric-info">
            <h4>Customers / Users</h4>
            <h3>{{ $customerCount }}</h3>
        </div>
    </div>
</div>

<!-- Filters & Actions Header -->
<div class="users-filter-card">
    <form method="GET" action="{{ route('users.index') }}" class="users-search-form">
        <div class="users-search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" name="search" class="users-search-input" placeholder="Search by name, email or role..." value="{{ $search }}">
        </div>
        <button type="submit" class="btn btn-secondary" style="padding: 10px 18px; border-radius: 10px;">Search</button>
        @if(!empty($search))
            <a href="{{ route('users.index') }}" class="btn btn-danger-outline" style="padding: 10px 14px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;" title="Clear Search">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </form>

    <button type="button" class="btn btn-primary" style="padding: 10px 18px; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px;" onclick="openAddUserModal()">
        <i class="bi bi-person-plus-fill"></i> Add User Account
    </button>
</div>

<!-- Modern Premium Table Card -->
<div class="modern-table-card">
    <table class="modern-table">
        <thead>
            <tr>
                <th>Profile & Contact</th>
                <th>System Role</th>
                <th>Authority Management</th>
                <th>Registration Date</th>
                <th style="text-align: right;">Delete</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $u)
                @php
                    // Dynamically set avatar gradient based on first character of name
                    $firstChar = strtoupper(substr($u->name, 0, 1));
                    $avatarClass = 'avatar-g1';
                    if (in_array($firstChar, ['A','B','C','D','E'])) $avatarClass = 'avatar-g1';
                    elseif (in_array($firstChar, ['F','G','H','I','J'])) $avatarClass = 'avatar-g2';
                    elseif (in_array($firstChar, ['K','L','M','N','O'])) $avatarClass = 'avatar-g3';
                    elseif (in_array($firstChar, ['P','Q','R','S','T'])) $avatarClass = 'avatar-g4';
                    else $avatarClass = 'avatar-g5';
                @endphp
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div class="modern-avatar {{ $avatarClass }}">
                                {{ $firstChar }}
                            </div>
                            <div>
                                <strong style="color: #0f172a; font-size: 14.5px; font-weight: 700; letter-spacing: -0.1px; display: inline-flex; align-items: center; gap: 6px;">
                                    {{ $u->name }}
                                    @if($u->id === Auth::id())
                                        <span style="background-color: #e0e7ff; color: #4f46e5; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 100px; text-transform: uppercase;">You</span>
                                    @endif
                                </strong>
                                <div style="color: #64748b; font-size: 13px; margin-top: 3px;">{{ $u->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="modern-role-pill {{ $u->role === 'admin' ? 'role-pill-admin' : 'role-pill-user' }}">
                            <span class="role-dot {{ $u->role === 'admin' ? 'role-dot-admin' : 'role-dot-user' }}"></span>
                            {{ $u->role }}
                        </span>
                    </td>
                    <td>
                        @if($u->id !== Auth::id())
                            <form method="POST" action="{{ route('users.role', $u->id) }}" class="role-select-form" id="roleForm-{{ $u->id }}">
                                @csrf
                                @method('PATCH')
                                <div class="modern-select-wrapper">
                                    <select name="role" class="modern-select" onchange="document.getElementById('roleForm-{{ $u->id }}').submit()">
                                        <option value="user" {{ $u->role === 'user' ? 'selected' : '' }}>Customer (user)</option>
                                        <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Administrator (admin)</option>
                                    </select>
                                </div>
                            </form>
                        @else
                            <span style="font-size: 12.5px; color: #94a3b8; font-style: italic; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="bi bi-lock-fill"></i> Locked
                            </span>
                        @endif
                    </td>
                    <td style="color: #475569; font-size: 13.5px; font-weight: 500;">
                        <i class="bi bi-calendar3" style="color: #94a3b8; margin-right: 6px; font-size: 13px;"></i>
                        {{ $u->created_at ? $u->created_at->format('M d, Y h:i A') : 'N/A' }}
                    </td>
                    <td style="text-align: right;">
                        @if($u->id !== Auth::id())
                            <form method="POST" action="{{ route('users.destroy', $u->id) }}" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="circle-action-btn" onclick="return confirm('Are you sure you want to permanently delete the user account \'{{ addslashes($u->name) }}\'? This action is irreversible.')" title="Delete User">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </form>
                        @else
                            <span style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; color: #cbd5e1; border: 1px dashed #cbd5e1; border-radius: 50%;" title="You cannot delete yourself">
                                <i class="bi bi-shield-fill"></i>
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach

            @if($users->isEmpty())
                <tr>
                    <td colspan="5" style="text-align: center; padding: 64px; color: #64748b;">
                        <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
                        <h4 style="margin: 0 0 6px 0; color: #0f172a; font-weight: 800; font-size: 16px;">No match found</h4>
                        <p style="margin: 0; font-size: 13.5px;">No accounts matched your search keyword.</p>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<!-- Premium Create User Modal -->
<div class="user-modal-overlay" id="addUserModal">
    <div class="user-modal">
        <h3 class="user-modal-title">Create User Account</h3>
        <p class="user-modal-desc">Manually register a secure system profile. Default role is user (customer).</p>
        <form method="POST" action="{{ route('users.store') }}" id="createUserForm">
            @csrf
            <div class="modal-form-group">
                <label for="modal_name">Full Name</label>
                <input type="text" name="name" id="modal_name" class="modal-form-control" required placeholder="Juan Dela Cruz">
            </div>
            <div class="modal-form-group">
                <label for="modal_email">Email Address</label>
                <input type="email" name="email" id="modal_email" class="modal-form-control" required placeholder="juan@gmail.com">
            </div>
            <div class="modal-form-group">
                <label for="modal_password">Password</label>
                <input type="password" name="password" id="modal_password" class="modal-form-control" required placeholder="At least 6 characters">
            </div>
            <div class="modal-form-group">
                <label for="modal_role">System Access Role</label>
                <select name="role" id="modal_role" class="modal-form-control" required style="cursor: pointer;">
                    <option value="user">Customer (user)</option>
                    <option value="admin">Administrator (admin)</option>
                </select>
            </div>

            <div class="modal-btn-row">
                <button type="button" class="btn btn-secondary" style="border-radius: 10px; padding: 10px 18px;" onclick="closeAddUserModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 10px; padding: 10px 18px;">Create Account</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddUserModal() {
        const modal = document.getElementById('addUserModal');
        modal.classList.add('open');
    }

    function closeAddUserModal() {
        const modal = document.getElementById('addUserModal');
        modal.classList.remove('open');
    }

    // Close modal if clicking outside dialog
    document.getElementById('addUserModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAddUserModal();
        }
    });
</script>
@endsection