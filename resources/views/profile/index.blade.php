@extends('layouts.guest')

@section('title', 'My Profile')

@section('content')
    <div style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
        
        <!-- Header -->
        <div style="background-color: #ffffff; padding: 24px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01); display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border: 1px solid #e2e8f0;">
            <div>
                <h1 style="margin: 0; font-size: 22px; font-weight: bold; color: #0f172a; letter-spacing: -0.3px;">👤 Customer Account</h1>
                <p style="margin: 4px 0 0 0; font-size: 13.5px; color: #64748b;">Manage your details and view inquiries</p>
            </div>
            <a href="{{ route('logout') }}" data-logout-confirm style="background-color: #ef4444; color: #ffffff; text-decoration: none; padding: 8px 18px; border-radius: 8px; font-size: 13.5px; font-weight: bold; transition: all 0.2s ease; box-shadow: 0 2px 5px rgba(239, 68, 68, 0.15);">
                🚪 Logout
            </a>
        </div>

        <!-- User Information Card -->
        <div style="background-color: #ffffff; padding: 24px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01); border: 1px solid #e2e8f0; margin-bottom: 28px; display: flex; align-items: center; gap: 18px;">
            <div style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #ffffff; width: 64px; height: 64px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; font-weight: bold; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25); text-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h2 style="margin: 0; font-size: 19px; font-weight: bold; color: #0f172a; letter-spacing: -0.2px;">{{ $user->name }}</h2>
                <p style="margin: 4px 0 0 0; font-size: 14.5px; color: #64748b;">{{ $user->email }}</p>
            </div>
        </div>

        <!-- Inquiries List -->
        <h3 style="font-size: 17px; font-weight: bold; color: #0f172a; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; letter-spacing: -0.2px;">
            <i class="bi bi-chat-left-text-fill" style="color: #4f46e5;"></i> My Submitted Inquiries ({{ $inquiries->count() }})
        </h3>

        @if($inquiries->isEmpty())
            <div style="background-color: #ffffff; padding: 48px; text-align: center; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01); border: 1px solid #e2e8f0; color: #64748b;">
                <div style="font-size: 44px; margin-bottom: 12px;">✉️</div>
                <p style="margin: 0; font-size: 14.5px; font-weight: 500;">You haven't submitted any product inquiries yet.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 20px; padding-bottom: 40px;">
                @foreach($inquiries as $inquiry)
                    <div style="background-color: #ffffff; padding: 24px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01); border: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px;">
                            <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 100px; display: inline-flex; align-items: center; gap: 5px; {{ $inquiry->status === 'responded' ? 'background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;' : 'background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;' }}">
                                <span style="width: 6px; height: 6px; border-radius: 50%; display: inline-block; {{ $inquiry->status === 'responded' ? 'background-color: #10b981;' : 'background-color: #f59e0b;' }}"></span>
                                {{ $inquiry->status }}
                            </span>
                            <span style="font-size: 12px; color: #94a3b8; font-weight: 500;">
                                <i class="bi bi-calendar3" style="margin-right: 4px;"></i>
                                {{ $inquiry->created_at ? $inquiry->created_at->format('M d, Y h:i A') : 'N/A' }}
                            </span>
                        </div>

                        <h4 style="margin: 0 0 10px 0; font-size: 15.5px; font-weight: 700; color: #1e293b;">
                            Subject: <span style="font-weight: 500; color: #475569;">"{{ $inquiry->subject }}"</span>
                        </h4>
                        
                        <div style="background-color: #f8fafc; padding: 14px 18px; border-radius: 10px; border: 1px solid #f1f5f9; margin-bottom: 16px;">
                            <strong style="font-size: 13px; color: #475569; display: block; margin-bottom: 4px;">My Message:</strong>
                            <p style="margin: 0; font-size: 13.5px; color: #1e293b; line-height: 1.5; white-space: pre-line;">{!! nl2br(e($inquiry->message)) !!}</p>
                        </div>

                        @if($inquiry->status === 'responded' && !empty($inquiry->response))
                            <div style="background-color: #f5f3ff; padding: 16px 20px; border-radius: 12px; border: 1px solid #e0e7ff; border-left: 4px solid #6366f1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <strong style="font-size: 13.5px; color: #4f46e5; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="bi bi-reply-fill"></i> Store Response:
                                    </strong>
                                    @if($inquiry->responded_at)
                                        <span style="font-size: 11.5px; color: #818cf8; font-weight: 500;">
                                            {{ $inquiry->responded_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                                <p style="margin: 0; font-size: 14px; color: #312e81; line-height: 1.5; white-space: pre-line;">{!! nl2br(e($inquiry->response)) !!}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

    </div>
@endsection
