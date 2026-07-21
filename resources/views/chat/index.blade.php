@extends('layouts.app')

@section('title', 'Customer Support Chat')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h2>Customer Support Chat</h2>
        <p>Coordinate with the team and save replies for customer questions from one workspace.</p>
    </div>
    <div class="page-actions chat-page-actions">
        <div class="chat-live-indicator">
            <span class="chat-live-dot"></span> Live
        </div>
        <span class="support-count-pill">{{ $pendingInquiries }} pending</span>
    </div>
</div>

@if(session('notice'))
    <div class="alert alert-{{ session('noticeType', 'success') }}">{{ session('notice') }}</div>
@endif

<div class="chat-layout support-chat-layout">
    <aside class="support-queue">
        <div class="panel-card support-panel-card">
            <div class="panel-header support-panel-header">
                <div>
                    <h3>Customer Questions</h3>
                    <p>Latest website inquiries</p>
                </div>
                <a href="{{ route('inquiries.index') }}" class="btn btn-secondary btn-sm">View All</a>
            </div>

            <div class="support-stats-row">
                <div>
                    <span>Pending</span>
                    <strong>{{ $pendingInquiries }}</strong>
                </div>
                <div>
                    <span>Responded today</span>
                    <strong>{{ $respondedToday }}</strong>
                </div>
            </div>

            <div class="support-ticket-list">
                @forelse($supportInquiries as $inquiry)
                    <article class="support-ticket {{ $inquiry->status === 'pending' ? 'is-pending' : 'is-responded' }}">
                        <div class="support-ticket-topline">
                            <span class="badge {{ $inquiry->status === 'pending' ? 'badge-warning' : 'badge-success' }}">{{ $inquiry->status === 'pending' ? 'Pending' : 'Responded' }}</span>
                            <time>{{ $inquiry->created_at->format('M d, h:i A') }}</time>
                        </div>
                        <h4>{{ $inquiry->subject }}</h4>
                        <p class="support-ticket-message">{{ $inquiry->message }}</p>
                        <div class="support-customer-meta">
                            <span>{{ $inquiry->customer_name }}</span>
                            <a href="mailto:{{ $inquiry->customer_email }}">{{ $inquiry->customer_email }}</a>
                        </div>

                        @if($inquiry->response)
                            <div class="support-saved-response">
                                <strong>Saved response</strong>
                                <p>{{ $inquiry->response }}</p>
                                <small>
                                    {{ optional($inquiry->respondent)->name ?? 'Staff' }}
                                    @if($inquiry->responded_at)
                                        | {{ $inquiry->responded_at->format('M d, h:i A') }}
                                    @endif
                                </small>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('inquiries.respond', $inquiry) }}" class="support-response-form">
                            @csrf
                            <textarea id="response-{{ $inquiry->id }}" name="response" rows="3" placeholder="Write the customer response..." required>{{ old('response', $inquiry->response) }}</textarea>
                            <div class="support-response-actions">
                                <button type="button" class="btn btn-outline btn-sm" data-target="response-{{ $inquiry->id }}" data-customer="{{ $inquiry->customer_name }}" onclick="insertResponseTemplate(this)">Use Template</button>
                                <button type="submit" class="btn btn-primary btn-sm">Save Response</button>
                            </div>
                        </form>

                        <div class="support-ticket-actions">
                            <button type="button" class="btn btn-secondary btn-sm" data-customer="{{ $inquiry->customer_name }}" data-subject="{{ $inquiry->subject }}" onclick="draftCustomerReply(this)">Discuss in Chat</button>
                            <form method="POST" action="{{ route('inquiries.toggle', $inquiry) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline btn-sm">Mark {{ $inquiry->status === 'pending' ? 'Responded' : 'Pending' }}</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="chat-empty support-empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <p>No customer inquiries yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </aside>

    <section class="chat-main-panel">
        <div class="panel-card chat-workspace-card">
            <div class="panel-header chat-panel-header">
                <div class="chat-heading-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <div>
                        <h3>Team Conversation</h3>
                        <p>{{ $messagesToday }} today | {{ $totalMessages }} total</p>
                    </div>
                </div>
                <button type="button" class="chat-clear-btn" onclick="clearInput()" title="Clear input">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    Clear
                </button>
            </div>

            <div class="chat-box" id="chatbox">
                @forelse($messages as $message)
                    @php $isOwn = $message->user_name === auth()->user()->name; @endphp
                    <div class="chat-message {{ $isOwn ? 'chat-own' : 'chat-other' }}">
                        <div class="chat-msg-avatar {{ $isOwn ? 'chat-avatar-own' : 'chat-avatar-other' }}">
                            {{ strtoupper(substr($message->user_name, 0, 1)) }}
                        </div>
                        <div class="chat-msg-content">
                            <div class="chat-msg-header">
                                <strong class="chat-msg-author {{ $isOwn ? 'chat-author-own' : '' }}">
                                    {{ $isOwn ? 'You' : $message->user_name }}
                                </strong>
                                <span class="chat-msg-time">{{ optional($message->created_at)->diffForHumans() ?? '' }}</span>
                            </div>
                            <div class="chat-bubble {{ $isOwn ? 'chat-bubble-own' : 'chat-bubble-other' }}">
                                {{ $message->message }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="chat-empty">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <p>No messages yet. Start the conversation.</p>
                    </div>
                @endforelse
            </div>

            <div class="chat-quick-replies" id="quickReplies">
                <span class="chat-quick-label">Quick replies:</span>
                <button type="button" class="chat-quick-btn" onclick="setMsg('Hello! How can I help you today?')">Hello</button>
                <button type="button" class="chat-quick-btn" onclick="setMsg('The product you are looking for is available in store.')">Available</button>
                <button type="button" class="chat-quick-btn" onclick="setMsg('Sorry, that item is currently out of stock.')">Out of Stock</button>
                <button type="button" class="chat-quick-btn" onclick="setMsg('Our store hours are Monday to Saturday, 8 AM to 6 PM.')">Hours</button>
                <button type="button" class="chat-quick-btn" onclick="setMsg('Please visit us at Stall No. 18, Bantayan Public Market.')">Address</button>
            </div>

            <form id="msgForm" method="POST" action="{{ route('chat.store') }}" class="chat-input-form">
                @csrf
                <input id="user_name" type="hidden" name="user_name" value="{{ auth()->user()->name }}">
                <div class="chat-input-wrapper">
                    <textarea id="msgInput" name="message" placeholder="Type a team note or customer reply draft..." rows="1" autocomplete="off" required></textarea>
                    <button type="submit" class="chat-send-btn" id="sendBtn" title="Send message">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function scrollToBottom() {
    const box = document.getElementById('chatbox');
    if (box) box.scrollTop = box.scrollHeight;
}
scrollToBottom();

function setMsg(text) {
    const input = document.getElementById('msgInput');
    input.value = text;
    input.focus();
    autoResize(input);
}

function clearInput() {
    const input = document.getElementById('msgInput');
    input.value = '';
    input.style.height = '';
    input.focus();
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 140) + 'px';
}

function draftCustomerReply(button) {
    const customer = button.dataset.customer || 'customer';
    const subject = button.dataset.subject || 'their question';
    setMsg('Customer follow-up for ' + customer + ' about "' + subject + '": ');
}

function insertResponseTemplate(button) {
    const target = document.getElementById(button.dataset.target);
    const customer = button.dataset.customer || 'there';
    if (!target) return;
    target.value = 'Hi ' + customer + ', thank you for reaching out. We checked your inquiry and will be happy to assist you. Please visit us at Stall No. 18, Bantayan Public Market or reply with any additional details.';
    target.focus();
}

const textarea = document.getElementById('msgInput');
if (textarea) {
    textarea.addEventListener('input', () => autoResize(textarea));
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (this.value.trim()) {
                document.getElementById('msgForm').submit();
            }
        }
    });
}

setInterval(function() {
    fetch('{{ route("chat.messages") }}')
        .then(response => response.json())
        .then(messages => {
            const box = document.getElementById('chatbox');
            const authUser = @json(auth()->user()->name);
            const wasAtBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 10;

            if (!messages.length) {
                box.innerHTML = '<div class="chat-empty"><p>No messages yet. Start the conversation.</p></div>';
                return;
            }

            box.innerHTML = messages.map(msg => {
                const isOwn = msg.user_name === authUser;
                const initial = escapeHtml((msg.user_name || '?').charAt(0).toUpperCase());
                const displayName = isOwn ? 'You' : escapeHtml(msg.user_name);
                const time = escapeHtml(msg.created_at || '');
                return `
                    <div class="chat-message ${isOwn ? 'chat-own' : 'chat-other'}">
                        <div class="chat-msg-avatar ${isOwn ? 'chat-avatar-own' : 'chat-avatar-other'}">${initial}</div>
                        <div class="chat-msg-content">
                            <div class="chat-msg-header">
                                <strong class="chat-msg-author ${isOwn ? 'chat-author-own' : ''}">${displayName}</strong>
                                <span class="chat-msg-time">${time}</span>
                            </div>
                            <div class="chat-bubble ${isOwn ? 'chat-bubble-own' : 'chat-bubble-other'}">${escapeHtml(msg.message)}</div>
                        </div>
                    </div>`;
            }).join('');

            if (wasAtBottom) scrollToBottom();
        })
        .catch(() => {});
}, 5000);
</script>
@endpush