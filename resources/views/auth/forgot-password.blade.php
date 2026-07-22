@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <div class="auth-container">
        <div class="auth-header">
            <div class="brand">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon-brand" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                    </path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                <span>{{ config('app.name') }}</span>
            </div>
            <h2>Reset Password</h2>
            <p>Enter your email and we'll send you a secure link to reset your account password.</p>
        </div>

        @if(session('notice'))
            <div class="alert alert-{{ session('noticeType', 'success') }}">{{ session('notice') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required value="{{ old('email') }}" placeholder="your-email@gmail.com">
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <span>Send Reset Link</span>
            </button>
        </form>

        <div class="auth-footer" style="margin-top: 24px;">
            <p>Remembered your password? <a href="{{ route('login') }}">Sign in here</a>.</p>
            <p style="margin-top: 8px;">Back to <a href="{{ route('home') }}">store landing page</a>.</p>
        </div>
    </div>
@endsection
