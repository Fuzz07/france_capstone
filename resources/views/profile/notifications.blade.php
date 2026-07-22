@extends('layouts.guest')

@section('title', 'My Alerts')

@section('content')
    <div style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
        
        <!-- Header -->
        <div style="background-color: #ffffff; padding: 24px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01); border: 1px solid #e2e8f0; margin-bottom: 24px;">
            <h1 style="margin: 0; font-size: 22px; font-weight: bold; color: #0f172a; letter-spacing: -0.3px;">🔔 Notifications</h1>
            <p style="margin: 4px 0 0 0; font-size: 13.5px; color: #64748b;">Live alerts regarding your submitted product inquiries</p>
        </div>

        <!-- Notifications list -->
        @if($notifications->isEmpty())
            <div style="background-color: #ffffff; padding: 64px 20px; text-align: center; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01); border: 1px solid #e2e8f0; color: #64748b;">
                <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                <h3 style="font-size: 17px; font-weight: bold; color: #0f172a; margin-bottom: 8px; letter-spacing: -0.2px;">No new alerts</h3>
                <p style="margin: 0; font-size: 14.5px;">You don't have any active store response notifications at the moment.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 20px; padding-bottom: 40px;">
                @foreach($notifications as $notification)
                    <div style="background-color: #ffffff; padding: 24px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01); border: 1px solid #e2e8f0; border-left: 4px solid #4f46e5; display: flex; gap: 18px; align-items: flex-start;">
                        <div style="background-color: #e0e7ff; width: 44px; height: 44px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 20px; flex-shrink: 0; color: #4f46e5; box-shadow: 0 2px 5px rgba(79, 70, 229, 0.1);">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                        <div style="flex-grow: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                                <h4 style="margin: 0; font-size: 15px; font-weight: bold; color: #0f172a; letter-spacing: -0.1px;">Admin Replied to your Inquiry</h4>
                                <span style="font-size: 12px; color: #94a3b8; font-weight: 500;">
                                    @if($notification->responded_at)
                                        {{ $notification->responded_at->diffForHumans() }}
                                    @elseif($notification->updated_at)
                                        {{ $notification->updated_at->diffForHumans() }}
                                    @else
                                        Recently
                                    @endif
                                </span>
                            </div>
                            <p style="margin: 0 0 12px 0; font-size: 13.5px; color: #475569;">
                                Subject: <strong>"{{ $notification->subject }}"</strong>
                            </p>
                            <div style="background-color: #f8fafc; padding: 14px 18px; border-radius: 10px; font-size: 13.5px; color: #334155; line-height: 1.5; border: 1px dashed #cbd5e1;">
                                <strong style="color: #4f46e5; display: block; margin-bottom: 4px;">Reply response:</strong>
                                {!! nl2br(e(str($notification->response)->limit(200))) !!}
                            </div>
                            <a href="{{ route('profile.index') }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #4f46e5; text-decoration: none; font-weight: bold; margin-top: 14px; transition: all 0.2s ease;">
                                View Full Conversation <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
@endsection
