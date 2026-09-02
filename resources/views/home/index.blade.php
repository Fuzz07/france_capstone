@extends('layouts.guest')

@section('title', "Welcome to " . config('app.name'))

@section('content')
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-grid">
            <div class="hero-content">
                <div class="hero-badge">
                    🎁 Welcome to {{ config('app.name') }}
                </div>
                <h1 class="hero-title">Quality <span>Merchandise</span> for Every Occasion</h1>
                <p class="hero-desc">School supplies, fabrics, and general merchandise carefully curated for families and
                    businesses throughout Bantayan and beyond.</p>
                <div class="hero-actions">
                    <a href="#products" class="btn-primary btn-lg">Browse Our Products</a>
                    @empty($hideAppDownload)
                        <a href="{{ route('mobile.download') }}" class="btn-secondary btn-lg btn-app-download-hero" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Download App
                        </a>
                    @endempty
                    <a href="#inquire" class="btn-secondary btn-lg">Send Inquiry</a>
                </div>

                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-num">{{ $products->count() }}+</div>
                        <div class="stat-label">Quality Products</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">15+</div>
                        <div class="stat-label">Years Experience</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">1,000+</div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                </div>
            </div>

            <div class="hero-image-column">
                <div class="hero-deco-blob"></div>
                <div class="hero-image-wrapper hero-image-clickable" id="heroImageBtn" onclick="openGallery(0)"
                    title="Click to watch the shop video and view photos" role="button" tabindex="0"
                    aria-label="Watch the shop video and view the photo gallery">
                    <img src="{{ asset('images/hero_merchandise.jpg') }}"
                        alt="Mera's Merchandise Storefront Showcase">
                    <div class="gallery-hint-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="23 7 16 12 23 17 23 7"></polygon>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                        </svg>
                        Watch Our Shop Video
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="section-container" id="products">
        <div class="section-header">
            <h2 class="section-title">Our Products</h2>
            <p class="section-desc">Browse our complete catalog of school supplies, fabrics, and general merchandise. Filter
                by category or search for specific items.</p>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <input type="text" id="landingSearch" placeholder="Search products by name or SKU..." class="form-control-lp"
                style="flex: 2; min-width: 260px;">
            <select id="categoryFilter" class="form-control-lp" style="flex: 1; min-width: 200px;">
                <option value="">All Categories</option>
                @php
                    $categories = $products->pluck('category')->unique()->filter();
                @endphp
                @foreach($categories as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <div class="product-grid" id="landingProductGrid">
            @forelse($products as $product)
                <div class="product-card" id="product-{{ $product->id }}" data-name="{{ strtolower($product->name) }}"
                    data-sku="{{ strtolower($product->sku ?? '') }}" data-cat="{{ $product->category ?? '' }}">
                    <div class="prod-header">
                        @if($product->category)
                            <span class="prod-category">{{ $product->category }}</span>
                        @endif
                        <div class="prod-name">{{ $product->name }}</div>
                        @if($product->sku)
                            <div class="prod-sku">SKU: {{ $product->sku }}</div>
                        @endif
                    </div>
                    <div class="stock-indicator">
                        @if($product->quantity > 10)
                            <span class="indicator-dot dot-in-stock"></span>
                            <span style="color: var(--lp-success);">In Stock</span>
                        @elseif($product->quantity > 0)
                            <span class="indicator-dot dot-low-stock"></span>
                            <span style="color: var(--lp-warning);">Low Stock ({{ $product->quantity }} left)</span>
                        @else
                            <span class="indicator-dot dot-out-of-stock"></span>
                            <span style="color: var(--lp-danger);">Out of Stock</span>
                        @endif
                    </div>
                    <div class="prod-footer">
                        <div>
                            <div class="prod-price">₱{{ number_format($product->price, 2) }}</div>
                            @if($product->hasBulkPricing())
                                <div class="landing-bulk-tag">
                                    Bulk: ₱{{ number_format($product->bulk_price, 2) }} ({{ $product->bulk_min_qty }}+ pcs)
                                </div>
                            @endif
                        </div>
                        <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
                            <button type="button" class="btn-inquire"
                                onclick="inquireProduct({{ $product->id }}, '{{ addslashes($product->name) }}', 'general')">Inquire</button>
                            
                        </div>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 80px 24px; color: var(--lp-text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        style="color: var(--lp-text-subtle); margin-bottom: 16px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <p style="font-size: 1.1rem; font-weight: 600;">No products available.</p>
                    <p style="font-size: 0.95rem; margin-top: 4px;">Please check back soon or send an inquiry to check upcoming
                        inventory.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Inquiry Section -->
    <section class="section-container" id="inquire" style="border-top: 1px solid var(--lp-border); padding-top: 100px;">
        <div class="section-header">
            <h2 class="section-title">Send Store Inquiry</h2>
            <p class="section-desc">Submit queries about bulk sales, availability, or pickup schedules directly to Mera's
                Store management team.</p>
        </div>

        @if(session('notice'))
            <div class="alert alert-{{ session('noticeType', 'success') }}">
                @if(session('noticeType') === 'success')
                    <h4 style="margin-top:0;margin-bottom:6px;font-weight:700;">📨 Inquiry Received!</h4>
                    <p style="margin:0;font-size:0.95rem;line-height:1.5;">Thank you for writing. Our store staff will examine your
                        request and follow up at the email address provided.</p>
                @else
                    <p style="margin:0;">{{ session('notice') }}</p>
                @endif
            </div>
        @endif

        <div class="inquiry-split">
            <div class="inquiry-info-column">
                <div class="info-block">
                    <h3 class="info-title">🏬 Visit or Reach Us</h3>
                    <p class="info-text">We are located at Stall No. 18 in the Bantayan Public Market. For custom order
                        pricing lists, drop us an inquiry message directly on this panel and our support team will respond
                        shortly.</p>
                </div>

                <div class="contact-list">
                    <!-- Address -->
                    <div class="contact-item">
                        <div class="contact-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <div>
                            <div class="contact-label">Store Address</div>
                            <div class="contact-value">Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu 6052</div>
                        </div>
                    </div>
                    <!-- Email -->
                    <div class="contact-item">
                        <div class="contact-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                </path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div class="contact-label">Email Support</div>
                            <div class="contact-value">support@merasmerchandise.com</div>
                        </div>
                    </div>
                    <!-- Hours -->
                    <div class="contact-item">
                        <div class="contact-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                        </div>
                        <div>
                            <div class="contact-label">Business Hours</div>
                            <div class="contact-value">Sunday - Friday, 8:00 AM – 6:00 PM</div>
                        </div>
                    </div>
                </div>

                @empty($hideStaffLinks)
                    <div
                        style="font-size: 0.85rem; line-height: 1.5; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                        * Registered staff, cashiers, and store operators can access the checkout POS and inventory dashboard by
                        clicking <strong>Staff Login</strong> at the top right of this page.
                    </div>
                @endempty
            </div>

            <div class="inquiry-form-card">
                <form method="POST" action="{{ route('inquiries.store') }}">
                    @csrf
                    @if(!empty($inquirySource))
                        <input type="hidden" name="source" value="{{ $inquirySource }}">
                    @endif

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="customerName">Your Full Name</label>
                        <input type="text" name="customer_name" id="customerName" class="form-control-lp" required
                            placeholder="Juan Dela Cruz" value="{{ Auth::check() ? Auth::user()->name : old('customer_name') }}"
                            {{ Auth::check() ? 'readonly style=background-color:#f1f5f9;cursor:not-allowed;' : '' }}>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="customerEmail">Email Address</label>
                        <input type="email" name="customer_email" id="customerEmail" class="form-control-lp" required
                            placeholder="juan@gmail.com" value="{{ Auth::check() ? Auth::user()->email : old('customer_email') }}"
                            {{ Auth::check() ? 'readonly style=background-color:#f1f5f9;cursor:not-allowed;' : '' }}>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="subject">Subject</label>
                        <input type="text" name="subject" id="subject"
                            class="form-control-lp @error('subject') error @enderror" required
                            placeholder="e.g. Bulk school supplies quote" value="{{ old('subject') }}">
                        @error('subject')<small style="color: var(--lp-danger);">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom: 32px;">
                        <label for="message">Your Inquiry Message</label>
                        <textarea name="message" id="message" rows="5"
                            class="form-control-lp @error('message') error @enderror" required
                            placeholder="Describe what products or fabric swatches you'd like details on...">{{ old('message') }}</textarea>
                        @error('message')<small style="color: var(--lp-danger);">{{ $message }}</small>@enderror
                    </div>

                    <button type="submit" class="btn-submit-lp" style="width:100%;">
                        📧 Send Inquiry Message
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Shop Gallery Lightbox Modal -->
    <div class="gallery-modal-overlay" id="galleryModal" aria-hidden="true" onclick="handleGalleryOverlayClick(event)">
        <div class="gallery-modal" role="dialog" aria-modal="true" aria-label="Shop Photo Gallery">
            <button class="gallery-close-btn" onclick="closeGallery()" aria-label="Close gallery">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="gallery-modal-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Mera's Store &mdash; Shop Gallery
                </h3>
                <p>A glimpse inside our store at Bantayan Public Market, Stall No. 18</p>
            </div>
            <div class="gallery-main-display">
                <button class="gallery-nav-btn gallery-prev" onclick="shiftGallery(-1)" aria-label="Previous photo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <div class="gallery-main-img-wrapper">
                    <img id="galleryMainImg" src="" alt="Shop photo" class="gallery-main-img" hidden>
                    <video id="galleryMainVideo" class="gallery-main-video"
                        src="{{ asset('images/shop_video.mp4') }}"
                        poster="{{ asset('images/shop_video_poster.jpg') }}"
                        controls playsinline muted preload="auto" hidden>
                        <source src="{{ asset('images/shop_video.mp4') }}" type="video/mp4">
                    </video>
                    <div class="gallery-video-status" id="galleryVideoStatus" hidden></div>
                    <div class="gallery-img-caption" id="galleryCaption"></div>
                </div>
                <button class="gallery-nav-btn gallery-next" onclick="shiftGallery(1)" aria-label="Next photo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
            <div class="gallery-thumbs">
                <div class="gallery-thumb gallery-thumb-video active" onclick="setGallerySlide(0)">
                    <img src="{{ asset('images/shop_video_poster.jpg') }}" alt="Shop video tour">
                    <span class="gallery-thumb-badge">Video</span>
                </div>
                <div class="gallery-thumb" onclick="setGallerySlide(1)">
                    <img src="{{ request()->getBaseUrl() }}/images/shop_gallery_1.jpg" alt="Store interior">
                </div>
                <div class="gallery-thumb" onclick="setGallerySlide(2)">
                    <img src="{{ request()->getBaseUrl() }}/images/shop_gallery_2.jpg" alt="School supplies display">
                </div>
                <div class="gallery-thumb" onclick="setGallerySlide(3)">
                    <img src="{{ request()->getBaseUrl() }}/images/shop_gallery_3.jpg" alt="Fabric and textiles">
                </div>
                <div class="gallery-thumb" onclick="setGallerySlide(4)">
                    <img src="{{ request()->getBaseUrl() }}/images/shop_gallery_4.jpg" alt="Store front">
                </div>
            </div>
            <div class="gallery-dots">
                <span class="gallery-dot active" onclick="setGallerySlide(0)"></span>
                <span class="gallery-dot" onclick="setGallerySlide(1)"></span>
                <span class="gallery-dot" onclick="setGallerySlide(2)"></span>
                <span class="gallery-dot" onclick="setGallerySlide(3)"></span>
                <span class="gallery-dot" onclick="setGallerySlide(4)"></span>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Filter products by category and search
            function filterProducts() {
                const query = document.getElementById('landingSearch').value.toLowerCase().trim();
                const cat = document.getElementById('categoryFilter').value;

                document.querySelectorAll('#landingProductGrid .product-card').forEach(card => {
                    const matchName = card.dataset.name.includes(query) || card.dataset.sku.includes(query);
                    const matchCat = cat === "" || card.dataset.cat === cat;
                    card.style.display = (matchName && matchCat) ? 'flex' : 'none';
                });
            }

            // Pre-select product and scroll to form
            function inquireProduct(id, name, mode, quantity, price, bulkPrice, bulkMin, unit) {
                const subject = document.getElementById('subject');
                const message = document.getElementById('message');

                unit = unit || 'pcs';

                if (mode === 'purchase_all' && quantity > 0) {
                    let effectivePrice = price;
                    let rateNote = "Regular price ₱" + price.toFixed(2) + " / " + unit;
                    if (bulkPrice && bulkMin && quantity >= bulkMin) {
                        effectivePrice = bulkPrice;
                        rateNote = "Bulk discount price ₱" + bulkPrice.toFixed(2) + " / " + unit + " (applied for " + bulkMin + "+ " + unit + ")";
                    }
                    const totalVal = (quantity * effectivePrice).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    if (subject) {
                        subject.value = 'Bulk Purchase All: ' + name + ' (' + quantity + ' ' + unit + ')';
                    }
                    if (message) {
                        message.value = 'Hello Mera\'s Store Team,\n\n' +
                            'I am inquiring to PURCHASE ALL available stock of this specific item:\n' +
                            '• Product: ' + name + '\n' +
                            '• Quantity to Purchase: ' + quantity + ' ' + unit + ' (Entire Remaining Stock)\n' +
                            '• Unit Pricing: ' + rateNote + '\n' +
                            '• Total Estimated Amount: ₱' + totalVal + '\n\n' +
                            'Please confirm stock reservation, payment methods, and pickup or delivery details.';
                    }
                } else {
                    if (subject) {
                        subject.value = 'Inquiry about ' + name;
                    }
                    if (message) {
                        message.value = 'Hello Mera\'s Store Team,\n\n' +
                            'I would like to inquire about ' + name + ' (current availability, bulk buying quotes, and details).';
                    }
                }

                const element = document.getElementById('inquire');
                if (element) {
                    if (window.innerWidth < 768) {
                        window.location.hash = '#inquire';
                    } else {
                        element.scrollIntoView({ behavior: 'smooth' });
                    }
                }

                setTimeout(() => {
                    const nameField = document.getElementById('customerName');
                    if (nameField && !nameField.value) {
                        nameField.focus();
                    } else if (message) {
                        message.focus();
                    }
                }, 800);
            }

            // ── Mobile App Tab / Hash Switching ───────────────
            function handleTabSwitching() {
                if (window.innerWidth >= 768) {
                    // Desktop: restore normal scrolling behavior and display all sections
                    document.querySelectorAll('.hero-section, #products, #inquire').forEach(el => {
                        if (el) el.style.display = '';
                    });
                    return;
                }

                let hash = window.location.hash || '#home';
                // Normalize since Android app bottom navigation may send #inquiries but HTML section is #inquire
                if (hash === '#inquiries') hash = '#inquire';

                const heroSec = document.querySelector('.hero-section');
                const productsSec = document.getElementById('products');
                const inquireSec = document.getElementById('inquire');

                if (!heroSec || !productsSec || !inquireSec) return;

                // Hide all sections initially
                heroSec.style.display = 'none';
                productsSec.style.display = 'none';
                inquireSec.style.display = 'none';

                if (hash === '#home') {
                    heroSec.style.display = 'block';
                    window.scrollTo(0, 0);
                } else if (hash === '#products') {
                    productsSec.style.display = 'block';
                    window.scrollTo(0, 0);
                } else if (hash === '#inquire') {
                    inquireSec.style.display = 'block';
                    window.scrollTo(0, 0);
                }

                updateBottomNavHighlight(hash);
            }

            function updateBottomNavHighlight(activeHash) {
                document.querySelectorAll('.mobile-nav-item').forEach(item => {
                    const href = item.getAttribute('href');
                    if (href) {
                        if (href.includes(activeHash)) {
                            item.classList.add('active');
                        } else if (activeHash === '#home' && href.endsWith('#home')) {
                            item.classList.add('active');
                        } else {
                            item.classList.remove('active');
                        }
                    }
                });
            }

            // Handle initial load and events
            window.addEventListener('hashchange', handleTabSwitching);
            window.addEventListener('resize', handleTabSwitching);
            handleTabSwitching();

            // Attach event listeners
            document.getElementById('categoryFilter').addEventListener('change', filterProducts);
            document.getElementById('landingSearch').addEventListener('input', filterProducts);

            // ── Shop Gallery Lightbox ──────────────────────────
            const gallerySlides = [
                { type: 'video', caption: 'Step inside the store — Stall No. 18, Bantayan Public Market' },
                { src: '{{ asset('images/shop_gallery_1.jpg') }}', caption: 'Store interior — fabrics, school supplies & general merchandise' },
                { src: '{{ asset('images/shop_gallery_2.jpg') }}', caption: 'School supplies — notebooks, pens, folders & more' },
                { src: '{{ asset('images/shop_gallery_3.jpg') }}', caption: 'Fabrics & textiles — a wide range of colors and patterns' },
                { src: '{{ asset('images/shop_gallery_4.jpg') }}', caption: 'Stall No. 18, Bantayan Public Market — visit us anytime!' },
            ];
            let galleryCurrent = 0;

            // ── Reel loading / failure feedback ────────────────
            const galleryVideoStatus = document.getElementById('galleryVideoStatus');

            function showVideoStatus(message, isError) {
                if (!galleryVideoStatus) return;
                galleryVideoStatus.textContent = '';

                if (!isError) {
                    const spinner = document.createElement('div');
                    spinner.className = 'gallery-video-spinner';
                    galleryVideoStatus.appendChild(spinner);
                }

                const label = document.createElement('div');
                label.textContent = message;
                galleryVideoStatus.appendChild(label);

                galleryVideoStatus.classList.toggle('is-error', Boolean(isError));
                galleryVideoStatus.hidden = false;
            }

            function hideVideoStatus() {
                if (!galleryVideoStatus) return;
                galleryVideoStatus.hidden = true;
            }

            (function wireVideoStatus() {
                const video = document.getElementById('galleryMainVideo');
                if (!video) return;

                function reportVideoError() {
                    const reasons = {
                        1: 'loading was cancelled',
                        2: 'a network error occurred',
                        3: 'the video could not be decoded',
                        4: 'the video is missing or in an unsupported format',
                    };
                    const code = video.error ? video.error.code : 0;
                    showVideoStatus('Sorry, the shop video could not load - ' + (reasons[code] || 'unknown error') + '.', true);
                }

                video.addEventListener('waiting', function () {
                    if (gallerySlides[galleryCurrent]?.type === 'video' && !video.paused) {
                        showVideoStatus('Buffering...', false);
                    }
                });
                video.addEventListener('canplay', hideVideoStatus);
                video.addEventListener('canplaythrough', hideVideoStatus);
                video.addEventListener('loadeddata', hideVideoStatus);
                video.addEventListener('playing', hideVideoStatus);
                video.addEventListener('play', hideVideoStatus);
                video.addEventListener('pause', hideVideoStatus);
                video.addEventListener('timeupdate', hideVideoStatus);
                video.addEventListener('error', reportVideoError);

                const source = video.querySelector('source');
                if (source) {
                    source.addEventListener('error', reportVideoError);
                }
            })();

            function openGallery(index) {
                galleryCurrent = index ?? 0;
                const modal = document.getElementById('galleryModal');
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                renderGallerySlide();
            }

            function closeGallery() {
                const modal = document.getElementById('galleryModal');
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                const video = document.getElementById('galleryMainVideo');
                if (video) {
                    video.pause();
                }
                hideVideoStatus();
            }

            function handleGalleryOverlayClick(e) {
                if (e.target === document.getElementById('galleryModal')) closeGallery();
            }

            function setGallerySlide(index) {
                galleryCurrent = index;
                renderGallerySlide();
            }

            function shiftGallery(dir) {
                galleryCurrent = (galleryCurrent + dir + gallerySlides.length) % gallerySlides.length;
                renderGallerySlide();
            }

            function renderGallerySlide() {
                const slide = gallerySlides[galleryCurrent];
                const mainImg = document.getElementById('galleryMainImg');
                const mainVideo = document.getElementById('galleryMainVideo');
                const caption = document.getElementById('galleryCaption');
                const stage = mainVideo.parentElement;

                stage.classList.toggle('is-video', slide.type === 'video');

                if (slide.type === 'video') {
                    mainImg.hidden = true;
                    mainVideo.hidden = false;
                    caption.textContent = slide.caption;
                    mainVideo.muted = true; // Ensure programmatic autoplay is permitted

                    if (mainVideo.readyState >= 2) {
                        hideVideoStatus();
                    } else if (mainVideo.error) {
                        // Error is handled by wireVideoStatus
                    } else {
                        showVideoStatus('Loading video...', false);
                    }

                    if (mainVideo.readyState > 0) {
                        mainVideo.currentTime = 0;
                    }
                    const playPromise = mainVideo.play();
                    if (playPromise !== undefined) {
                        playPromise
                            .then(() => {
                                hideVideoStatus();
                            })
                            .catch(() => {
                                // If autoplay policy blocks silent start, hide spinner so user can use controls
                                hideVideoStatus();
                            });
                    }
                } else {
                    mainVideo.pause();
                    mainVideo.hidden = true;
                    hideVideoStatus();
                    mainImg.hidden = false;
                    mainImg.style.opacity = '0';
                    setTimeout(() => {
                        mainImg.src = slide.src;
                        caption.textContent = slide.caption;
                        mainImg.style.opacity = '1';
                    }, 120);
                }

                document.querySelectorAll('.gallery-thumb').forEach((t, i) => t.classList.toggle('active', i === galleryCurrent));
                document.querySelectorAll('.gallery-dot').forEach((d, i) => d.classList.toggle('active', i === galleryCurrent));
            }

            document.addEventListener('keydown', e => {
                const modal = document.getElementById('galleryModal');
                if (!modal.classList.contains('open')) return;
                if (e.key === 'Escape') closeGallery();
                if (e.key === 'ArrowLeft') shiftGallery(-1);
                if (e.key === 'ArrowRight') shiftGallery(1);
            });

            document.getElementById('heroImageBtn').addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openGallery(0); }
            });
        </script>
    @endpush
@endsection