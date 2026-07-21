@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <style>
        /* Premium Glassmorphic Loading Overlay */
        .login-loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .login-loading-overlay.active {
            display: flex;
            opacity: 1;
        }

        .login-loader-box {
            background: #ffffff;
            padding: 36px 44px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(226, 232, 240, 0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            max-width: 90vw;
            width: 320px;
            animation: scalePop 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes scalePop {
            from {
                transform: scale(0.94);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .login-spinner {
            width: 44px;
            height: 44px;
            border: 4px solid var(--color-primary-light);
            border-top-color: var(--color-primary);
            border-radius: 50%;
            animation: spin 0.85s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .login-loading-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--color-secondary);
            margin: 0;
            text-align: center;
        }

        .login-loading-sub {
            font-size: 0.82rem;
            color: var(--color-text-muted);
            margin: -8px 0 0 0;
            text-align: center;
            line-height: 1.4;
        }
    </style>

    <!-- Fullscreen glass loader overlay -->
    <div class="login-loading-overlay" id="loginLoadingOverlay">
        <div class="login-loader-box">
            <div class="login-spinner"></div>
            <h4 class="login-loading-title">Verifying Credentials</h4>
            <p class="login-loading-sub">Securing your connection & establishing session.</p>
        </div>
    </div>

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
            <h2>Sign In</h2>
            <p>Access your store dashboard</p>
        </div>


        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required value="{{ old('email') }}">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block" id="loginSubmitBtn">
                <span id="loginBtnText">Log In</span>
            </button>
        </form>


        <div class="auth-footer">
            <p>Back to <a href="{{ route('home') }}">store landing page</a>.</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const loginForm = document.getElementById('loginForm');
        const loginSubmitBtn = document.getElementById('loginSubmitBtn');
        const loginBtnText = document.getElementById('loginBtnText');
        const loginLoadingOverlay = document.getElementById('loginLoadingOverlay');

        if (loginForm) {
            loginForm.addEventListener('submit', function (event) {
                // Disable inputs and submit button
                loginSubmitBtn.disabled = true;
                loginBtnText.textContent = 'Verifying...';

                // Show full screen glassmorphic loading state
                loginLoadingOverlay.classList.add('active');
            });
        }
    </script>
@endpush