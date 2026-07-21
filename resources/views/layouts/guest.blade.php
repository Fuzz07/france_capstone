<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="{{ request()->getBaseUrl() }}/css/style.css">
    <link rel="stylesheet" href="{{ request()->getBaseUrl() }}/css/landing.css">
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
                @empty($hideAppDownload)
                    <a href="{{ route('mobile.download') }}" class="guest-link btn-app-download" download>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="M12 3v12"></path>
                            <path d="m7 10 5 5 5-5"></path>
                            <path d="M5 21h14"></path>
                        </svg>
                        <span>Download App</span>
                    </a>
                @endempty
                @empty($hideStaffLinks)
                    <a href="{{ route('login') }}" class="guest-link btn-guest-login">Staff Login</a>
                @endempty
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

    @stack('scripts')
</body>
</html>
