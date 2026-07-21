@extends('layouts.app')

@section('title', 'Customer Inquiries')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Customer Inquiries</h2>
        <p>Review, respond to, and update every customer inquiry submitted through the website.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('chat.index') }}" class="btn btn-primary">Open Support Chat</a>
    </div>
</div>

@if(session('notice'))
    <div class="alert alert-{{ session('noticeType', 'success') }}">{{ session('notice') }}</div>
@endif

<div class="inquiry-toolbar panel-card">
    <form method="GET" action="{{ route('inquiries.index') }}" class="inquiry-filter-form">
        <div class="form-group">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Name, email, subject, or message">
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All inquiries</option>
                <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="responded" {{ $statusFilter === 'responded' ? 'selected' : '' }}>Responded</option>
            </select>
        </div>
        <div class="inquiry-filter-actions">
            <button type="submit" class="btn btn-primary">Apply</button>
            <a href="{{ route('inquiries.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

<div class="panel-card inquiry-panel">
    <div class="panel-header inquiry-panel-header">
        <div>
            <h3>Inquiry History</h3>
            <p>{{ $inquiries->count() }} result{{ $inquiries->count() === 1 ? '' : 's' }}</p>
        </div>
    </div>

    <div class="inquiry-list">
        @forelse($inquiries as $inquiry)
            <article class="inquiry-card {{ $inquiry->status === 'pending' ? 'is-pending' : 'is-responded' }}">
                <div class="inquiry-card-main">
                    <div class="inquiry-card-topline">
                        <span class="badge {{ $inquiry->status === 'pending' ? 'badge-warning' : 'badge-success' }}">{{ $inquiry->status === 'pending' ? 'Pending' : 'Responded' }}</span>
                        <time>{{ $inquiry->created_at->format('M d, Y h:i A') }}</time>
                    </div>
                    <h3>{{ $inquiry->subject }}</h3>
                    <p class="inquiry-message">{{ $inquiry->message }}</p>
                    <div class="inquiry-customer-row">
                        <span>{{ $inquiry->customer_name }}</span>
                        <a href="mailto:{{ $inquiry->customer_email }}">{{ $inquiry->customer_email }}</a>
                    </div>

                    @if($inquiry->response)
                        <div class="inquiry-response-box">
                            <strong>Saved response</strong>
                            <p>{{ $inquiry->response }}</p>
                            <small>
                                {{ optional($inquiry->respondent)->name ?? 'Staff' }}
                                @if($inquiry->responded_at)
                                    | {{ $inquiry->responded_at->format('M d, Y h:i A') }}
                                @endif
                            </small>
                        </div>
                    @endif
                </div>

                <div class="inquiry-card-actions">
                    <form method="POST" action="{{ route('inquiries.respond', $inquiry) }}" class="inquiry-response-form">
                        @csrf
                        <label for="response-{{ $inquiry->id }}">Response</label>
                        <textarea id="response-{{ $inquiry->id }}" name="response" rows="4" placeholder="Write or update the customer response..." required>{{ old('response', $inquiry->response) }}</textarea>
                        <button type="submit" class="btn btn-primary btn-sm">Save Response</button>
                    </form>

                    <div class="inquiry-action-row">
                        <form method="POST" action="{{ route('inquiries.toggle', $inquiry) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-secondary btn-sm">Mark {{ $inquiry->status === 'pending' ? 'Responded' : 'Pending' }}</button>
                        </form>
                        <form method="POST" action="{{ route('inquiries.destroy', $inquiry) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this inquiry?');">Delete</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="chat-empty inquiry-empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                <p>No inquiries match the current filters.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection