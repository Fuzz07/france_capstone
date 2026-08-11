@extends('layouts.app')

@section('title', 'Point of Sale')

@section('content')
<style>
    .pos-search-form {
        display: flex;
        gap: 8px;
        align-items: center;
        max-width: 320px;
        flex: 1;
    }
    .pos-search-input-wrapper {
        position: relative;
        flex: 1;
    }
    .pos-search-input-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 15px;
        height: 15px;
        color: var(--color-text-subtle);
        pointer-events: none;
    }
    .pos-search-field {
        padding-left: 32px !important;
        padding-right: 28px !important;
        border-radius: var(--radius-sm) !important;
        font-size: 0.85rem !important;
        height: 36px !important;
        padding-block: 0 !important;
        border: 1.5px solid #e2e8f0 !important;
        background-color: #f8fafc !important;
        width: 100%;
        outline: none;
        transition: var(--transition-fast);
    }
    .pos-search-field:focus {
        background-color: #ffffff !important;
        border-color: var(--color-primary) !important;
    }
    .pos-clear-search {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(148,163,184,0.15);
        border: none;
        color: var(--color-text-muted);
        font-size: 0.7rem;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        text-decoration: none;
        transition: var(--transition-fast);
    }
    .pos-clear-search:hover { color: white; background-color: var(--color-danger); }
    .pos-search-btn {
        height: 36px !important;
        border-radius: var(--radius-sm) !important;
        padding: 0 16px !important;
        font-size: 0.85rem !important;
    }
    .print-only-copy { display: none !important; }
    @media print {
        body.printing-receipt { background-color: #ffffff !important; color: #000000 !important; }
        body.printing-receipt * { visibility: hidden !important; }
        body.printing-receipt .receipt-print-container,
        body.printing-receipt .receipt-print-container * { visibility: visible !important; display: block !important; }
        body.printing-receipt .receipt-print-container table,
        body.printing-receipt .receipt-print-container table * { display: table !important; visibility: visible !important; }
        body.printing-receipt .receipt-print-container tr { display: table-row !important; }
        body.printing-receipt .receipt-print-container th,
        body.printing-receipt .receipt-print-container td { display: table-cell !important; }
        body.printing-receipt .receipt-print-container {
            display: block !important;
            position: absolute !important;
            top: 0 !important; left: 0 !important;
            width: 72mm !important;
            padding: 0 !important; margin: 0 !important;
            background: #ffffff !important;
        }
        body.printing-receipt .receipt-copy {
            font-family: 'Courier New', Courier, monospace !important;
            font-size: 10pt !important;
            border: none !important;
            background: #ffffff !important;
            padding: 0 0 12px 0 !important;
            margin: 0 0 20px 0 !important;
            width: 72mm !important;
            page-break-inside: avoid !important;
        }
        body.printing-receipt .receipt-tear-divider {
            display: block !important;
            text-align: center !important;
            border-top: 1.5px dashed #000000 !important;
            margin: 24px 0 !important;
            padding-top: 8px !important;
            font-family: monospace !important;
            font-size: 8pt !important;
            width: 72mm !important;
        }
        body.printing-receipt .print-only-copy { display: block !important; visibility: visible !important; }
    }
</style>
    <div class="page-header">
        <div class="page-title">
            <h2>Point of Sale (POS)</h2>
            <p>Select products, build the cart, and finalize customer checkout.</p>
        </div>
    </div>

    <style>
        /* GCash QR Modal */
        .gcash-qr-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 20000;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.18s ease, visibility 0.18s ease;
        }

        .gcash-qr-modal-overlay.open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .gcash-qr-modal {
            width: 100%;
            max-width: 520px;
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 18px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.45);
            color: #fff;
            overflow: hidden;
        }

        .gcash-qr-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 18px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .gcash-qr-modal-header h3 {
            font-size: 1.05rem;
            margin: 0;
        }

        .gcash-qr-modal-close {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .gcash-qr-modal-close:hover {
            background: rgba(239, 68, 68, 0.18);
            border-color: rgba(239, 68, 68, 0.35);
            color: #f87171;
        }

        .gcash-qr-modal-body {
            padding: 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .gcash-qr-img-modal {
            width: 420px;
            height: 420px;
            max-width: 100%;
            max-height: 70vh;
            border-radius: 12px;
            border: 3px solid rgba(226, 232, 240, 0.4);
            background: #fff;
            object-fit: contain;
        }

        .gcash-qr-modal-note {
            font-size: 0.9rem;
            color: rgba(226, 232, 240, 0.85);
            text-align: center;
            line-height: 1.4;
        }
    </style>


    @if(session('notice'))
        <div class="alert alert-{{ session('noticeType', 'success') }}">{{ session('notice') }}</div>
    @endif

    <div class="pos-container">
        <div class="panel-card">
            <div class="panel-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <h3 style="margin:0;">Product Catalog</h3>
                <form class="pos-search-form" method="GET" action="{{ route('pos.index') }}">
                    <div class="pos-search-input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pos-search-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" name="q" class="pos-search-field" placeholder="Search product..." value="{{ $search ?? '' }}">
                        @if(!empty($search))
                            <a href="{{ route('pos.index') }}" class="pos-clear-search" title="Reset search">&times;</a>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary pos-search-btn btn-sm">Search</button>
                </form>
            </div>
            <div class="pos-product-grid">
                @forelse($products as $product)
                    <div class="pos-product-card {{ $product->quantity <= 0 ? 'pos-card-oos' : '' }}">
                        <div>
                            <h4 class="pos-prod-name">{{ $product->name }}</h4>
                            <div class="pos-prod-stock">{{ $product->category ?: 'General' }}</div>
                            @if($product->quantity <= 0)
                                <span class="pos-oos-badge">Out of Stock</span>
                            @elseif($product->quantity <= 5)
                                <span class="pos-low-badge">Low: {{ $product->quantity }} left</span>
                            @endif
                        </div>
                        <div class="pos-prod-footer">
                            <div class="pos-prod-pricing">
                                <div class="pos-prod-price">&#8369;{{ number_format($product->price, 2) }}</div>
                                @if($product->hasBulkPricing())
                                    <div class="pos-prod-bulk">
                                        <span class="pos-bulk-tag">Bulk</span>
                                        &#8369;{{ number_format($product->bulk_price, 2) }}
                                        <span class="pos-bulk-min">at {{ $product->bulk_min_qty }}+</span>
                                    </div>
                                @endif
                            </div>
                            @if($product->quantity > 0)
                                <form method="POST" action="{{ route('pos.add') }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button type="submit" class="btn-icon-add" title="Add to cart">+</button>
                                </form>
                            @else
                                <button class="btn-icon-add" disabled style="opacity:0.4;cursor:not-allowed;">+</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="grid-column:1/-1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 24px;color:var(--color-text-muted);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:0.5;"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        <p style="font-weight:600;margin-bottom:4px;">No products match your search</p>
                        <p style="font-size:0.8rem;">Try clearing the search query to see all products.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-header">
                <h3>Cart Summary</h3>
            </div>
            @if(empty($cart))
                <div class="empty-state compact-empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5">
                        <circle cx="9" cy="21" r="1" />
                        <circle cx="20" cy="21" r="1" />
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                    </svg>
                    <p>Your cart is empty. Add products to continue.</p>
                </div>
            @else
                <div class="cart-list">
                    @foreach($cart as $item)
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <div class="cart-item-name">
                                    {{ $item['name'] }}
                                    @if(!empty($item['is_bulk']))
                                        <span class="pos-bulk-tag">Bulk</span>
                                    @endif
                                </div>
                                <div class="cart-item-price">
                                    &#8369;{{ number_format($item['price'], 2) }} each
                                    @if(!empty($item['is_bulk']) && !empty($item['retail_price']))
                                        <span class="cart-item-was">&#8369;{{ number_format($item['retail_price'], 2) }}</span>
                                    @elseif(!empty($item['bulk_min_qty']))
                                        <span class="cart-item-hint">{{ $item['bulk_min_qty'] - $item['qty'] }} more for bulk price</span>
                                    @endif
                                </div>
                            </div>
                            <div class="cart-qty-control">
                                <form method="POST" action="{{ route('pos.updateCart') }}">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $item['id'] }}">
                                    <input type="hidden" name="action" value="decrease">
                                    <button class="btn-qty" type="submit">-</button>
                                </form>
                                <div class="cart-qty-val">{{ $item['qty'] }}</div>
                                <form method="POST" action="{{ route('pos.updateCart') }}">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $item['id'] }}">
                                    <input type="hidden" name="action" value="increase">
                                    <button class="btn-qty" type="submit">+</button>
                                </form>
                            </div>
                            <div class="cart-line-actions">
                                <div class="cart-item-total">&#8369;{{ number_format($item['price'] * $item['qty'], 2) }}</div>
                                <form method="POST" action="{{ route('pos.updateCart') }}">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $item['id'] }}">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pos-summary">
                    <div class="pos-summary-row"><strong>Grand Total</strong><strong
                            class="pos-grand-total">&#8369;{{ number_format($grandTotal, 2) }}</strong></div>

                    <div class="pos-payment-tabs" role="tablist" aria-label="Payment method">
                        <button type="button" class="pos-pay-tab active" id="tabCash" onclick="switchPayment('cash')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                            </svg>
                            Cash
                        </button>
                        <button type="button" class="pos-pay-tab" id="tabGcash" onclick="switchPayment('gcash')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2" />
                                <line x1="12" y1="18" x2="12.01" y2="18" />
                            </svg>
                            GCash QR
                        </button>
                    </div>

                    <form method="POST" action="{{ route('pos.checkout') }}" id="cashCheckoutForm">
                        @csrf
                        <input type="hidden" name="payment_method" value="cash">
                        <div class="form-group" id="cashPanel">
                            <label for="cash_tendered">Cash Tendered (PHP)</label>
                            <input type="number" step="0.01" min="{{ $grandTotal }}" id="cash_tendered" name="cash_tendered"
                                class="form-control" required placeholder="Enter amount">
                            <div class="pos-change-preview" id="changePreview" style="display:none;">
                                Change: <strong id="changeAmt">&#8369;0.00</strong>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Complete Cash Checkout</button>
                    </form>

                    <div id="gcashPanel" style="display:none;">
                        <div class="gcash-qr-container">
                            <p class="gcash-instruction">Ask the customer to scan the sample QR code and pay
                                <strong>&#8369;{{ number_format($grandTotal, 2) }}</strong>.</p>
                            <button type="button" class="btn btn-primary btn-block" onclick="openGcashQrModal()">
                                View QR (Bigger)
                            </button>
                            <img src="{{ asset('images/gcash_qr.png') }}" alt="Sample GCash QR Code" class="gcash-qr-img">
                            <p class="gcash-hint">After confirming the payment on the phone, complete the checkout below.</p>
                        </div>
                        <form method="POST" action="{{ route('pos.checkout') }}" id="gcashCheckoutForm">
                            @csrf
                            <input type="hidden" name="payment_method" value="gcash">
                            <input type="hidden" name="cash_tendered" value="{{ $grandTotal }}">
                            <button type="submit" class="btn btn-primary btn-block btn-gcash">Confirm GCash Payment</button>
                        </form>
                    </div>

                    {{-- GCash QR Modal --}}
                    <div class="gcash-qr-modal-overlay" id="gcashQrModal" aria-hidden="true" onclick="closeGcashQrModalOnOverlay(event)">
                        <div class="gcash-qr-modal" role="dialog" aria-modal="true" aria-label="GCash QR Code">
                            <div class="gcash-qr-modal-header">
                                <h3>GCash QR Code</h3>
                                <button type="button" class="gcash-qr-modal-close" onclick="closeGcashQrModal()" aria-label="Close">&times;</button>
                            </div>
                            <div class="gcash-qr-modal-body">
                                <img src="{{ asset('images/gcash_qr.png') }}" alt="Sample GCash QR Code" class="gcash-qr-img gcash-qr-img-modal">
                                <p class="gcash-qr-modal-note">Scan using your phone, confirm the payment, then finish checkout.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('pos.clearCart') }}" style="margin-top:12px;">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-block">Clear Cart</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    @if($receipt)
        <div class="receipt-modal-overlay" id="receiptModal">
            <div class="receipt-modal-dialog">
                <div class="receipt-modal-header">
                    <h3>Receipt Ready</h3>
                    <p class="receipt-modal-sub">Sale #{{ $receipt['sale_id'] }} | {{ $receipt['created_at'] }}</p>
                </div>

                <!-- Receipt Print Container: two copies for printing -->
                <div class="receipt-print-container" id="printReceiptArea">
                    <!-- Customer Copy -->
                    <div class="receipt-wrapper receipt-copy customer-copy">
                        <div class="receipt-head">
                            <div class="receipt-store-name">{{ config('app.name') }}</div>
                            <div class="receipt-address">Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu</div>
                            <div style="font-weight:800;font-size:10pt;margin-top:6px;text-align:center;text-transform:uppercase;letter-spacing:0.5px;">*** CUSTOMER COPY ***</div>
                            <div class="receipt-divider">------------------------------------</div>
                            <div class="receipt-meta">Receipt #{{ $receipt['sale_id'] }} | {{ $receipt['created_at'] }}</div>
                            <div class="receipt-meta">Cashier: {{ $receipt['cashier'] }}</div>
                            <div class="receipt-divider">------------------------------------</div>
                        </div>
                        <table class="receipt-items">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Item</th>
                                    <th style="text-align:center;">Qty</th>
                                    <th style="text-align:right;">Price</th>
                                    <th style="text-align:right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receipt['items'] as $item)
                                    <tr>
                                        <td>{{ $item['name'] }}@if(!empty($item['is_bulk'])) <small>(bulk)</small>@endif</td>
                                        <td style="text-align:center;">{{ $item['qty'] }}</td>
                                        <td style="text-align:right;">&#8369;{{ number_format($item['price'], 2) }}</td>
                                        <td style="text-align:right;">&#8369;{{ number_format($item['price'] * $item['qty'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="receipt-divider">------------------------------------</div>
                        <div class="receipt-totals">
                            <div class="receipt-total-row"><span>Total</span><strong>&#8369;{{ number_format($receipt['total'], 2) }}</strong></div>
                            <div class="receipt-total-row"><span>Payment</span><span>{{ strtoupper($receipt['payment_method']) }}</span></div>
                            @if($receipt['payment_method'] === 'cash')
                                <div class="receipt-total-row"><span>Cash Tendered</span><span>&#8369;{{ number_format($receipt['cash'], 2) }}</span></div>
                                <div class="receipt-total-row receipt-change"><span>Change</span><strong>&#8369;{{ number_format($receipt['change'], 2) }}</strong></div>
                            @endif
                        </div>
                        <div class="receipt-divider">------------------------------------</div>
                        <div class="receipt-footer">Thank you for shopping at {{ config('app.name') }}!</div>
                    </div>

                    <!-- Tear Guide (Print Only) -->
                    <div class="print-only-copy receipt-tear-divider">
                        ---------------- TEAR HERE ----------------
                    </div>

                    <!-- Store Copy (Print Only) -->
                    <div class="receipt-wrapper receipt-copy store-copy print-only-copy">
                        <div class="receipt-head">
                            <div class="receipt-store-name">{{ config('app.name') }}</div>
                            <div class="receipt-address">Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu</div>
                            <div style="font-weight:800;font-size:10pt;margin-top:6px;text-align:center;text-transform:uppercase;letter-spacing:0.5px;">*** STORE COPY ***</div>
                            <div class="receipt-divider">------------------------------------</div>
                            <div class="receipt-meta">Receipt #{{ $receipt['sale_id'] }} | {{ $receipt['created_at'] }}</div>
                            <div class="receipt-meta">Cashier: {{ $receipt['cashier'] }}</div>
                            <div class="receipt-divider">------------------------------------</div>
                        </div>
                        <table class="receipt-items">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Item</th>
                                    <th style="text-align:center;">Qty</th>
                                    <th style="text-align:right;">Price</th>
                                    <th style="text-align:right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receipt['items'] as $item)
                                    <tr>
                                        <td>{{ $item['name'] }}@if(!empty($item['is_bulk'])) <small>(bulk)</small>@endif</td>
                                        <td style="text-align:center;">{{ $item['qty'] }}</td>
                                        <td style="text-align:right;">&#8369;{{ number_format($item['price'], 2) }}</td>
                                        <td style="text-align:right;">&#8369;{{ number_format($item['price'] * $item['qty'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="receipt-divider">------------------------------------</div>
                        <div class="receipt-totals">
                            <div class="receipt-total-row"><span>Total</span><strong>&#8369;{{ number_format($receipt['total'], 2) }}</strong></div>
                            <div class="receipt-total-row"><span>Payment</span><span>{{ strtoupper($receipt['payment_method']) }}</span></div>
                            @if($receipt['payment_method'] === 'cash')
                                <div class="receipt-total-row"><span>Cash Tendered</span><span>&#8369;{{ number_format($receipt['cash'], 2) }}</span></div>
                                <div class="receipt-total-row receipt-change"><span>Change</span><strong>&#8369;{{ number_format($receipt['change'], 2) }}</strong></div>
                            @endif
                        </div>
                        <div class="receipt-divider">------------------------------------</div>
                        <div class="receipt-footer">Thank you for shopping at {{ config('app.name') }}!</div>
                    </div>
                </div>

                <div class="receipt-modal-actions">
                    <button type="button" onclick="printReceipt()" class="btn btn-primary">Print Receipt</button>
                    <button type="button" onclick="document.getElementById('receiptModal').remove()"
                        class="btn btn-secondary">Close</button>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            function switchPayment(method) {
                const cashPanel = document.getElementById('cashPanel');
                const cashForm = document.getElementById('cashCheckoutForm');
                const gcashPanel = document.getElementById('gcashPanel');
                const tabCash = document.getElementById('tabCash');
                const tabGcash = document.getElementById('tabGcash');
                const cashInput = document.getElementById('cash_tendered');

                const modal = document.getElementById('gcashQrModal');
                // close modal if user switches away
                if (modal && method === 'cash' && !modal.classList.contains('open')) {
                    // no-op
                }


                if (!cashPanel || !cashForm || !gcashPanel) return;

                if (method === 'cash') {
                    cashPanel.style.display = '';
                    cashForm.style.display = '';
                    gcashPanel.style.display = 'none';
                    tabCash.classList.add('active');
                    tabGcash.classList.remove('active');
                    if (cashInput) cashInput.required = true;
                } else {
                    cashPanel.style.display = 'none';
                    cashForm.style.display = 'none';
                    gcashPanel.style.display = '';
                    tabGcash.classList.add('active');
                    tabCash.classList.remove('active');
                    if (cashInput) cashInput.required = false;
                }
            }

            function openGcashQrModal() {
                const modal = document.getElementById('gcashQrModal');
                if (!modal) return;
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeGcashQrModal() {
                const modal = document.getElementById('gcashQrModal');
                if (!modal) return;
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            function closeGcashQrModalOnOverlay(e) {
                const overlay = document.getElementById('gcashQrModal');
                if (!overlay) return;
                if (e.target === overlay) closeGcashQrModal();
            }

            function printReceipt() {
                document.body.classList.add('printing-receipt');
                window.print();
            }


            window.addEventListener('afterprint', function () {
                document.body.classList.remove('printing-receipt');
            });

            const cashInput = document.getElementById('cash_tendered');
            if (cashInput) {
                cashInput.addEventListener('input', function () {
                    const tendered = parseFloat(this.value) || 0;
                    const total = Number(@json((float) $grandTotal));
                    const change = tendered - total;
                    const preview = document.getElementById('changePreview');
                    const amount = document.getElementById('changeAmt');

                    if (tendered > 0) {
                        preview.style.display = '';
                        amount.innerHTML = '&#8369;' + (change >= 0 ? change.toFixed(2) : '0.00');
                        amount.style.color = change >= 0 ? '#10b981' : '#ef4444';
                    } else {
                        preview.style.display = 'none';
                    }
                });
            }
        </script>
    @endpush
@endsection