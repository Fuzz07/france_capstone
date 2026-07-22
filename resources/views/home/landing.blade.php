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
            <p class="hero-desc">School supplies, fabrics, and general merchandise carefully curated for families and businesses throughout Bantayan and beyond.</p>
            <div class="hero-actions">
                <a href="#products" class="btn-primary btn-lg">Browse Our Products</a>
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
            <div class="hero-image-wrapper hero-image-clickable" id="heroImageBtn" onclick="openGallery(0)" title="Click to view shop photos" role="button" tabindex="0" aria-label="View shop gallery">
                <img src="{{ request()->getBaseUrl() }}/images/hero_merchandise.png" alt="Mera's Merchandise Storefront Showcase">
                <div class="gallery-hint-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    View Shop Photos
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Products Section -->
<section class="section-container" id="products">
    <div class="section-header">
        <h2 class="section-title">Our Products</h2>
        <p class="section-desc">Browse our complete catalog of school supplies, fabrics, and general merchandise. Filter by category or search for specific items.</p>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <input type="text" id="landingSearch" placeholder="Search products by name or SKU..." class="form-control-lp" style="flex: 2; min-width: 260px;">
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
            <div class="product-card" data-name="{{ strtolower($product->name) }}" data-sku="{{ strtolower($product->sku ?? '') }}" data-cat="{{ $product->category ?? '' }}">
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
                    <div class="prod-price">₱{{ number_format($product->price, 2) }}</div>
                    <button type="button" class="btn-inquire" onclick="inquireProduct({{ $product->id }}, '{{ addslashes($product->name) }}')">Inquire</button>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 80px 24px; color: var(--lp-text-muted);">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--lp-text-subtle); margin-bottom: 16px;"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                <p style="font-size: 1.1rem; font-weight: 600;">No products available.</p>
                <p style="font-size: 0.95rem; margin-top: 4px;">Please check back soon or send an inquiry to check upcoming inventory.</p>
            </div>
        @endforelse
    </div>
</section>

<!-- Inquiry Section -->
<section class="section-container" id="inquire" style="border-top: 1px solid var(--lp-border); padding-top: 100px;">
    <div class="section-header">
        <h2 class="section-title">Send Store Inquiry</h2>
        <p class="section-desc">Submit queries about bulk sales, availability, or pickup schedules directly to Mera's Store management team.</p>
    </div>

    @if(session('notice'))
        <div class="alert alert-{{ session('noticeType', 'success') }}">
            @if(session('noticeType') === 'success')
                <h4 style="margin-top:0;margin-bottom:6px;font-weight:700;">📨 Inquiry Received!</h4>
                <p style="margin:0;font-size:0.95rem;line-height:1.5;">Thank you for writing. Our store staff will examine your request and follow up at the email address provided.</p>
            @else
                <p style="margin:0;">{{ session('notice') }}</p>
            @endif
        </div>
    @endif

    <div class="inquiry-split">
        <div class="inquiry-info-column">
            <div class="info-block">
                <h3 class="info-title">🏬 Visit or Reach Us</h3>
                <p class="info-text">We are located at Stall No. 18 in the Bantayan Public Market. For custom order pricing lists, drop us an inquiry message directly on this panel and our support team will respond shortly.</p>
            </div>

            <div class="contact-list">
                <!-- Address -->
                <div class="contact-item">
                    <div class="contact-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    </div>
                    <div>
                        <div class="contact-label">Store Address</div>
                        <div class="contact-value">Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu 6052</div>
                    </div>
                </div>
                <!-- Email -->
                <div class="contact-item">
                    <div class="contact-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </div>
                    <div>
                        <div class="contact-label">Email Support</div>
                        <div class="contact-value">support@merasmerchandise.com</div>
                    </div>
                </div>
                <!-- Hours -->
                <div class="contact-item">
                    <div class="contact-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="contact-label">Business Hours</div>
                        <div class="contact-value">Sun – Friday, 8:00 AM – 6:00 PM</div>
                    </div>
                </div>
            </div>

            <div style="font-size: 0.85rem; line-height: 1.5; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                * Registered staff, cashiers, and store operators can access the checkout POS and inventory dashboard by clicking <strong>Staff Login</strong> at the top right of this page.
            </div>
        </div>

        <div class="inquiry-form-card">
            <form method="POST" action="{{ route('inquiries.store') }}">
                @csrf
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="customerName">Your Full Name</label>
                    <input type="text" name="customer_name" id="customerName" class="form-control-lp @error('customer_name') error @enderror" required placeholder="Juan Dela Cruz" value="{{ old('customer_name') }}">
                    @error('customer_name')<small style="color: var(--lp-danger);">{{ $message }}</small>@enderror
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="customerEmail">Email Address</label>
                    <input type="email" name="customer_email" id="customerEmail" class="form-control-lp @error('customer_email') error @enderror" required placeholder="juan@gmail.com" value="{{ old('customer_email') }}">
                    @error('customer_email')<small style="color: var(--lp-danger);">{{ $message }}</small>@enderror
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" class="form-control-lp @error('subject') error @enderror" required placeholder="e.g. Bulk school supplies quote" value="{{ old('subject') }}">
                    @error('subject')<small style="color: var(--lp-danger);">{{ $message }}</small>@enderror
                </div>

                <div class="form-group" style="margin-bottom: 32px;">
                    <label for="message">Your Inquiry Message</label>
                    <textarea name="message" id="message" rows="5" class="form-control-lp @error('message') error @enderror" required placeholder="Describe what products or fabric swatches you'd like details on...">{{ old('message') }}</textarea>
                    @error('message')<small style="color: var(--lp-danger);">{{ $message }}</small>@enderror
                </div>

                <button type="submit" class="btn-submit-lp" style="width:100%;">
                    📨 Send Inquiry Message
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Shop Gallery Lightbox Modal -->
<div class="gallery-modal-overlay" id="galleryModal" aria-hidden="true" onclick="handleGalleryOverlayClick(event)">
    <div class="gallery-modal" role="dialog" aria-modal="true" aria-label="Shop Photo Gallery">
        <button class="gallery-close-btn" onclick="closeGallery()" aria-label="Close gallery">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <div class="gallery-modal-header">
            <h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Mera's Store — Shop Gallery
            </h3>
            <p>A glimpse inside our store at Bantayan Public Market, Stall No. 18</p>
        </div>
        <div class="gallery-main-display">
            <button class="gallery-nav-btn gallery-prev" onclick="shiftGallery(-1)" aria-label="Previous photo">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <div class="gallery-main-img-wrapper">
                <img id="galleryMainImg" src="" alt="Shop photo" class="gallery-main-img">
                <div class="gallery-img-caption" id="galleryCaption"></div>
            </div>
            <button class="gallery-nav-btn gallery-next" onclick="shiftGallery(1)" aria-label="Next photo">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </div>
        <div class="gallery-thumbs">
            <div class="gallery-thumb active" onclick="setGallerySlide(0)">
                <img src="{{ request()->getBaseUrl() }}/images/shop_gallery_1.jpg" alt="Store interior">
            </div>
            <div class="gallery-thumb" onclick="setGallerySlide(1)">
                <img src="{{ request()->getBaseUrl() }}/images/shop_gallery_2.jpg" alt="School supplies display">
            </div>
            <div class="gallery-thumb" onclick="setGallerySlide(2)">
                <img src="{{ request()->getBaseUrl() }}/images/shop_gallery_3.jpg" alt="Fabric and textiles">
            </div>
            <div class="gallery-thumb" onclick="setGallerySlide(3)">
                <img src="{{ request()->getBaseUrl() }}/images/shop_gallery_4.jpg" alt="Store front">
            </div>
        </div>
        <div class="gallery-dots">
            <span class="gallery-dot active" onclick="setGallerySlide(0)"></span>
            <span class="gallery-dot" onclick="setGallerySlide(1)"></span>
            <span class="gallery-dot" onclick="setGallerySlide(2)"></span>
            <span class="gallery-dot" onclick="setGallerySlide(3)"></span>
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
    function inquireProduct(id, name) {
        const subject = document.getElementById('subject');
        subject.value = 'Inquiry about ' + name;
        
        const element = document.getElementById('inquire');
        element.scrollIntoView({ behavior: 'smooth' });
        
        setTimeout(() => {
            document.getElementById('customerName').focus();
        }, 800);
    }

    // Attach event listeners
    document.getElementById('categoryFilter').addEventListener('change', filterProducts);
    document.getElementById('landingSearch').addEventListener('input', filterProducts);

    // ── Shop Gallery Lightbox ──────────────────────────
    const gallerySlides = [
        { src: '/images/shop_gallery_1.jpg', caption: 'Store interior — fabrics, school supplies & general merchandise' },
        { src: '/images/shop_gallery_2.jpg', caption: 'School supplies — notebooks, pens, folders & more' },
        { src: '/images/shop_gallery_3.jpg', caption: 'Fabrics & textiles — a wide range of colors and patterns' },
        { src: '/images/shop_gallery_4.jpg', caption: 'Stall No. 18, Bantayan Public Market — visit us anytime!' },
    ];
    let galleryCurrent = 0;

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
        const caption = document.getElementById('galleryCaption');
        mainImg.style.opacity = '0';
        setTimeout(() => {
            mainImg.src = slide.src;
            caption.textContent = slide.caption;
            mainImg.style.opacity = '1';
        }, 120);

        document.querySelectorAll('.gallery-thumb').forEach((t, i) => {
            t.classList.toggle('active', i === galleryCurrent);
        });
        document.querySelectorAll('.gallery-dot').forEach((d, i) => {
            d.classList.toggle('active', i === galleryCurrent);
        });
    }

    // Keyboard support
    document.addEventListener('keydown', e => {
        const modal = document.getElementById('galleryModal');
        if (!modal.classList.contains('open')) return;
        if (e.key === 'Escape') closeGallery();
        if (e.key === 'ArrowLeft') shiftGallery(-1);
        if (e.key === 'ArrowRight') shiftGallery(1);
    });

    // Allow Enter key on hero image
    document.getElementById('heroImageBtn').addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openGallery(0); }
    });
</script>
@endpush
@endsection
