@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <!-- Google Sign-In SDK Loader -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

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

        /* Social Buttons Styling */
        .social-separator {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
            color: var(--lp-text-muted);
            font-size: 0.85rem;
        }

        .social-separator::before,
        .social-separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--lp-border);
        }

        .social-separator:not(:empty)::before {
            margin-right: .5em;
        }

        .social-separator:not(:empty)::after {
            margin-left: .5em;
        }

        .social-buttons {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 8px;
        }

        .btn-social {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #ffffff;
            border: 1px solid var(--lp-border);
            padding: 10px 16px;
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: var(--font-title);
        }

        .btn-social:hover {
            background-color: #f8fafc;
            border-color: var(--lp-border-hover);
            transform: translateY(-1px);
        }

        .btn-google {
            color: #1e293b;
        }

        /* Simulated Consent Dialog Overlay */
        .social-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .social-modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .social-modal {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 380px;
            padding: 24px;
            box-sizing: border-box;
            border: 1px solid #e2e8f0;
            animation: scalePop 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .social-modal-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 8px 0;
            text-align: center;
        }

        .social-modal-desc {
            font-size: 0.85rem;
            color: #64748b;
            margin: 0 0 20px 0;
            text-align: center;
            line-height: 1.4;
        }

        .social-acc-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }

        .social-acc-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
            width: 100%;
        }

        .social-acc-item:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
        }

        .social-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #4f46e5;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .social-avatar-fb {
            background-color: #1877F2 !important;
        }

        .social-info {
            display: flex;
            flex-direction: column;
        }

        .social-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #0f172a;
        }

        .social-email {
            font-size: 0.75rem;
            color: #64748b;
        }

        .social-modal-footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .social-modal-btn {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            color: #475569;
            transition: all 0.2s ease;
        }

        .social-modal-btn:hover {
            background-color: #f1f5f9;
        }

        .social-custom-input {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.85rem;
            width: 100%;
            box-sizing: border-box;
            outline: none;
            margin-bottom: 12px;
        }

        .social-custom-input:focus {
            border-color: #4f46e5;
        }
    </style>

    <!-- Fullscreen glass loader overlay -->
    <div class="login-loading-overlay" id="loginLoadingOverlay">
        <div class="login-loader-box">
            <div class="login-spinner"></div>
            <h4 class="login-loading-title" id="loadingTitle">Verifying Credentials</h4>
            <p class="login-loading-sub" id="loadingSub">Securing your connection & establishing session.</p>
        </div>
    </div>

    <!-- Simulated Social Consent Dialog Modal -->
    <div class="social-modal-overlay" id="socialModalOverlay">
        <div class="social-modal">
            <div style="text-align: center; margin-bottom: 16px;">
                <span id="providerLogo" style="font-size: 32px;">🌐</span>
            </div>
            <h4 class="social-modal-title" id="socialModalTitle">Sign in with Google</h4>
            <p class="social-modal-desc" id="socialModalDesc">to continue to Mera's Merchandise Store</p>
            
            <div class="social-acc-list" id="socialAccountsList">
                <!-- Google accounts dynamically generated -->
            </div>

            <!-- Custom User Account Entry Form -->
            <div id="customAccountForm" style="display: none; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                <input type="text" id="customName" class="social-custom-input" placeholder="Your Full Name">
                <input type="email" id="customEmail" class="social-custom-input" placeholder="Your Email Address">
                <button type="button" class="social-modal-btn" style="background-color: #4f46e5; color: #ffffff; width: 100%; margin-bottom: 12px;" onclick="submitCustomSocial()">Continue</button>
            </div>

            <div class="social-modal-footer">
                <button type="button" class="social-modal-btn" id="socialCancelBtn" onclick="closeSocialConsent()">Cancel</button>
                <button type="button" class="social-modal-btn" id="socialToggleFormBtn" onclick="toggleCustomForm()">Use Another Account</button>
            </div>
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
            <p>Access your customer portal or staff account</p>
        </div>


        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        
        <!-- Local Sign-In Form -->
        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required value="{{ old('email') }}">
            </div>
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="password" style="margin-bottom: 0;">Password</label>
                    <a href="{{ route('password.request') }}" style="font-size: 0.8rem; color: #4f46e5; text-decoration: none; font-weight: 600;">Forgot Password?</a>
                </div>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block" id="loginSubmitBtn">
                <span id="loginBtnText">Log In</span>
            </button>
        </form>

        <!-- Social SSO Separator -->
        <div class="social-separator">
            <span>Or Sign In With</span>
        </div>

        <!-- Social Buttons -->
        <div class="social-buttons">
            <div id="googleButtonDiv" style="width: 100%; display: flex; align-items: center; justify-content: center;">
                <button type="button" class="btn-social btn-google" style="width: 100%;" onclick="openSocialConsent('google')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.5 24c0-1.55-.15-3.24-.47-4.75H24v9.03h12.75c-.55 2.87-2.17 5.3-4.61 6.94l7.19 5.57C43.53 36.21 46.5 30.73 46.5 24z"/>
                        <path fill="#FBBC05" d="M10.54 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.98-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.19-5.57c-2.17 1.45-4.95 2.38-8.7 2.38-6.26 0-11.57-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                    Google
                </button>
            </div>
        </div>

        <div class="auth-footer" style="margin-top: 18px;">
            <p>Don't have an account? <a href="{{ route('register') }}">Sign up here</a>.</p>
            <p style="margin-top: 8px;">Back to <a href="{{ route('home') }}">store landing page</a>.</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const loginForm = document.getElementById('loginForm');
        const loginSubmitBtn = document.getElementById('loginSubmitBtn');
        const loginBtnText = document.getElementById('loginBtnText');
        const loginLoadingOverlay = document.getElementById('loginLoadingOverlay');
        const loadingTitle = document.getElementById('loadingTitle');
        const loadingSub = document.getElementById('loadingSub');

        if (loginForm) {
            loginForm.addEventListener('submit', function (event) {
                loginSubmitBtn.disabled = true;
                loginBtnText.textContent = 'Verifying...';
                
                loadingTitle.textContent = "Verifying Credentials";
                loadingSub.textContent = "Securing your connection & establishing session.";
                loginLoadingOverlay.classList.add('active');
            });
        }

        // Initialize Real Google SDK
        window.addEventListener('load', function() {
            const isMobileApp = @json(session('is_mobile_app', false));
            if (!isMobileApp && typeof google !== 'undefined') {
                google.accounts.id.initialize({
                    client_id: "{{ trim(env('GOOGLE_CLIENT_ID', '1069663364838-9nir3njd1j1ooph3vihgg5snamu9861i.apps.googleusercontent.com'), '\"\'') }}",
                    callback: handleCredentialResponse
                });

                const btnDiv = document.getElementById("googleButtonDiv");
                if (btnDiv) {
                    google.accounts.id.renderButton(
                        btnDiv,
                        { 
                            theme: "outline", 
                            size: "large", 
                            type: "standard", 
                            shape: "rectangular", 
                            text: "signin_with", 
                            logo_alignment: "left",
                            width: btnDiv.offsetWidth || 180
                        }
                    );
                }
            }
        });

        function handleCredentialResponse(response) {
            // Received real Google identity token! Post it to our serverless backend
            loadingTitle.textContent = "Verifying Google Account";
            loadingSub.textContent = "Securing your connection & decrypting credential payload...";
            loginLoadingOverlay.classList.add('active');

            fetch("{{ route('social.login') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    provider: 'google',
                    credential: response.credential
                })
            })
            .then(async res => {
                if (!res.ok) {
                    const text = await res.text();
                    console.error("Google authentication failed. Server output:", text);
                    throw new Error("HTTP " + res.status + " error: " + (text.length > 120 ? text.substring(0, 120) + "..." : text));
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    loginLoadingOverlay.classList.remove('active');
                    alert(data.message || 'Google Single Sign-On failed.');
                }
            })
            .catch(err => {
                loginLoadingOverlay.classList.remove('active');
                console.error(err);
                alert('An error occurred during real Google verification. Details: ' + err.message);
            });
        }

        // Social Consent Dialog Simulation Logic
        let currentProvider = '';
        const googleAccounts = [
            { name: "France Capstone", email: "france.capstone@gmail.com", initial: "F" },
            { name: "Dave Customer", email: "dave.customer@gmail.com", initial: "D" }
        ];

        function openSocialConsent(provider) {
            currentProvider = provider;

            const isMobileApp = @json(session('is_mobile_app', false));

            // If they clicked Google and are NOT in the mobile app, and the Google SDK is successfully loaded, prompt it!
            if (provider === 'google' && !isMobileApp && typeof google !== 'undefined') {
                google.accounts.id.prompt((notification) => {
                    if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
                        // Fall back to simulation if native prompt is skipped or blocked (e.g. adblocker, or no Client ID)
                        renderSimulationModal(provider);
                    }
                });
                return;
            }

            renderSimulationModal(provider);
        }

        function renderSimulationModal(provider) {
            const overlay = document.getElementById('socialModalOverlay');
            const title = document.getElementById('socialModalTitle');
            const desc = document.getElementById('socialModalDesc');
            const logo = document.getElementById('providerLogo');
            const list = document.getElementById('socialAccountsList');
            const customForm = document.getElementById('customAccountForm');
            const toggleBtn = document.getElementById('socialToggleFormBtn');

            // Reset states
            customForm.style.display = 'none';
            toggleBtn.textContent = 'Use Another Account';

            if (provider === 'google') {
                title.textContent = "Sign in with Google";
                desc.textContent = "to continue to Mera's Merchandise Store";
                logo.style.color = "#4285F4";
                logo.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24c0-1.55-.15-3.24-.47-4.75H24v9.03h12.75c-.55 2.87-2.17 5.3-4.61 6.94l7.19 5.57C43.53 36.21 46.5 30.73 46.5 24z"/><path fill="#FBBC05" d="M10.54 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.98-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.19-5.57c-2.17 1.45-4.95 2.38-8.7 2.38-6.26 0-11.57-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>`;
                
                let html = '';
                googleAccounts.forEach(acc => {
                    html += `
                        <button class="social-acc-item" onclick="selectSocialAccount('${acc.name}', '${acc.email}')">
                            <div class="social-avatar">${acc.initial}</div>
                            <div class="social-info">
                                <span class="social-name">${acc.name}</span>
                                <span class="social-email">${acc.email}</span>
                            </div>
                        </button>
                    `;
                });
                list.innerHTML = html;
            }

            overlay.classList.add('active');
        }

        function closeSocialConsent() {
            document.getElementById('socialModalOverlay').classList.remove('active');
        }

        function toggleCustomForm() {
            const form = document.getElementById('customAccountForm');
            const list = document.getElementById('socialAccountsList');
            const btn = document.getElementById('socialToggleFormBtn');

            if (form.style.display === 'none') {
                form.style.display = 'block';
                list.style.display = 'none';
                btn.textContent = 'Show Preset Accounts';
            } else {
                form.style.display = 'none';
                list.style.display = 'flex';
                btn.textContent = 'Use Another Account';
            }
        }

        function selectSocialAccount(name, email) {
            closeSocialConsent();
            authenticateSocial(name, email);
        }

        function submitCustomSocial() {
            const name = document.getElementById('customName').value.trim();
            const email = document.getElementById('customEmail').value.trim();

            if (!name || !email) {
                alert('Please enter both name and email.');
                return;
            }

            closeSocialConsent();
            authenticateSocial(name, email);
        }

        function authenticateSocial(name, email) {
            // Show premium overlay loader
            loadingTitle.textContent = "Connecting via Google";
            loadingSub.textContent = "Establishing secure single sign-on connection...";
            loginLoadingOverlay.classList.add('active');

            // Perform POST to our Laravel Social Login Route
            fetch("{{ route('social.login') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    provider: currentProvider
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1200); // realistic pleasant delay
                } else {
                    loginLoadingOverlay.classList.remove('active');
                    alert('SSO authentication failed. Please try again.');
                }
            })
            .catch(err => {
                loginLoadingOverlay.classList.remove('active');
                console.error(err);
                alert('An error occurred during single sign-on.');
            });
        }
    </script>
@endpush