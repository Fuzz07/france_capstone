<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="{{ request()->getBaseUrl() }}/css/style.css">
    <link rel="stylesheet" href="{{ request()->getBaseUrl() }}/css/landing.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <header class="guest-header">
        <div class="guest-nav-container">
            <a href="{{ route('home') }}" class="guest-brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                <span>{{ config('app.name') }}</span>
            </a>
            <div class="guest-links">
                <a href="{{ route('home') }}#products" class="guest-link">Browse</a>
                <a href="{{ route('home') }}#inquire" class="guest-link">Contact</a>
                @if(Auth::check())
                    <a href="{{ route('profile.index') }}" class="guest-link" style="font-weight: bold; color: #4f46e5;">👤 My Account</a>
                    <a href="{{ route('profile.notifications') }}" class="guest-link">🔔 Alerts</a>
                    <a href="{{ route('logout') }}" class="guest-link" style="color: #ef4444;">Logout</a>
                @else
                    <a href="{{ route('login') }}" class="guest-link btn-guest-login-outline" style="font-weight: bold; color: #4f46e5 !important; border: 1px solid #4f46e5; padding: 6px 14px; border-radius: 6px; margin-right: 4px;">🔑 Log In</a>
                    <a href="{{ route('register') }}" class="guest-link btn-guest-login" style="background-color: #4f46e5; color: #ffffff !important; padding: 6px 14px; border-radius: 6px;">Register</a>
                @endif
            </div>
        </div>
    </header>

    <main class="guest-main-content">
        @yield('content')
    </main>

    <footer class="app-footer">
        <div class="footer-grid">
            <div class="footer-brand-col">
                <a href="{{ route('home') }}" class="footer-brand">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    <span>{{ config('app.name') }}</span>
                </a>
                <p class="footer-desc">Premium school supplies, high-quality fabrics, and general merchandise for families and businesses throughout Bantayan. Proudly serving the community with value and care.</p>
            </div>
            <div class="footer-links-col">
                <h4 class="footer-title">Quick Links</h4>
                <nav class="footer-nav">
                    <a href="{{ route('home') }}#products" class="footer-nav-link">Browse Products</a>
                    <a href="{{ route('home') }}#inquire" class="footer-nav-link">Send Inquiry</a>
                    @empty($hideAppDownload)
                        <a href="{{ route('mobile.download') }}" class="footer-nav-link">Download Android App</a>
                    @endempty
                    @empty($hideStaffLinks)
                        <a href="{{ route('login') }}" class="footer-nav-link">Staff Dashboard Portal</a>
                    @endempty
                </nav>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu 6052</p>
        </div>
    </footer>

    <script>
        window.isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
        
        document.addEventListener('DOMContentLoaded', function() {
            const currentUrl = window.location.href;
            document.querySelectorAll('.mobile-nav-item').forEach(item => {
                const href = item.getAttribute('href');
                if (href) {
                    if (currentUrl.includes('/chat') && href.includes('/chat')) {
                        item.classList.add('active');
                    } else if (currentUrl.includes('/profile') && href.includes('/profile')) {
                        item.classList.add('active');
                    } else if (currentUrl.includes('/notifications') && href.includes('/notifications')) {
                        item.classList.add('active');
                    } else if (currentUrl.includes('/login') && href.includes('/login')) {
                        item.classList.add('active');
                    } else if (currentUrl.includes('/register') && href.includes('/register')) {
                        item.classList.add('active');
                    }
                }
            });
        });
    </script>
    
    @php
        $showWebBottomNav = !session('is_mobile_app');
    @endphp

    @if($showWebBottomNav)
        <!-- Bottom Navigation Bar for Mobile -->
        <div class="mobile-bottom-nav">
            <a href="{{ route('home') }}#home" class="mobile-nav-item">
                <span class="nav-icon"><i class="bi bi-house-door"></i></span>
                <span class="nav-label">Home</span>
            </a>
            <a href="{{ route('home') }}#products" class="mobile-nav-item">
                <span class="nav-icon"><i class="bi bi-grid"></i></span>
                <span class="nav-label">Products</span>
            </a>
            <a href="{{ route('home') }}#inquire" class="mobile-nav-item">
                <span class="nav-icon"><i class="bi bi-envelope"></i></span>
                <span class="nav-label">Inquiry</span>
            </a>
            @if(Auth::check())
                <a href="{{ route('chat.index') }}" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-chat-dots"></i></span>
                    <span class="nav-label">Chat</span>
                </a>
                <a href="{{ route('profile.index') }}" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-person"></i></span>
                    <span class="nav-label">Profile</span>
                </a>
                <a href="{{ route('profile.notifications') }}" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-bell"></i></span>
                    <span class="nav-label">Alerts</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-box-arrow-in-right"></i></span>
                    <span class="nav-label">Sign In</span>
                </a>
                <a href="{{ route('register') }}" class="mobile-nav-item">
                    <span class="nav-icon"><i class="bi bi-person-plus"></i></span>
                    <span class="nav-label">Register</span>
                </a>
            @endif
        </div>
    @endif

    @stack('scripts')
</body>
</html>
