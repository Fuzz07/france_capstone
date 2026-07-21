@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<style>
    /* ── Inventory-specific styles ── */
    .inv-layout {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
        align-items: start;
    }

    /* Search bar */
    .inv-search-wrapper {
        position: relative;
        flex: 1;
        max-width: 340px;
    }
    .inv-search-icon {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--color-text-subtle);
        pointer-events: none;
        width: 15px;
        height: 15px;
    }
    .inv-search-input {
        padding-left: 34px !important;
        padding-right: 32px !important;
        height: 38px !important;
        padding-block: 0 !important;
        border-radius: var(--radius-sm) !important;
        font-size: 0.875rem !important;
        border: 1.5px solid #e2e8f0 !important;
        background: #f8fafc !important;
        width: 100%;
        outline: none;
        transition: var(--transition-fast);
    }
    .inv-search-input:focus {
        background: #fff !important;
        border-color: var(--color-primary) !important;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1) !important;
    }
    .inv-search-clear {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 17px;
        height: 17px;
        border-radius: 50%;
        background: rgba(148,163,184,0.2);
        border: none;
        color: var(--color-text-muted);
        font-size: 0.65rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: var(--transition-fast);
        cursor: pointer;
    }
    .inv-search-clear:hover { background: var(--color-danger); color: #fff; }

    /* Table */
    .inv-sku-badge {
        display: inline-block;
        padding: 2px 7px;
        background: #f1f5f9;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-size: 0.72rem;
        color: #475569;
        border: 1px solid #e2e8f0;
        letter-spacing: 0.3px;
    }
    .stock-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        margin-right: 5px;
        vertical-align: middle;
    }
    .stock-ok   { background: #10b981; }
    .stock-low  { background: #f59e0b; }
    .stock-zero { background: #ef4444; }

    .badge-stock-ok   { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
    .badge-stock-low  { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
    .badge-stock-zero { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
    .badge-stock {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    /* SVG action buttons */
    .action-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 7px;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        color: var(--color-text-muted);
        cursor: pointer;
        text-decoration: none;
        transition: var(--transition-fast);
    }
    .action-icon-btn:hover { border-color: var(--color-primary); color: var(--color-primary); background: var(--color-primary-light); }
    .action-icon-btn.del:hover { border-color: var(--color-danger); color: var(--color-danger); background: #fee2e2; }

    /* Add/Edit form card */
    .inv-form-card { position: sticky; top: 24px; }
    .inv-form-title { font-size: 1rem; font-weight: 700; color: var(--color-secondary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .inv-form-group { margin-bottom: 14px; }
    .inv-form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--color-text-muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px; }
    .inv-form-group input, .inv-form-group select {
        width: 100%;
        border: 1.5px solid #e2e8f0;
        border-radius: var(--radius-sm);
        padding: 8px 12px;
        font-size: 0.875rem;
        background: #f8fafc;
        outline: none;
        transition: var(--transition-fast);
        color: var(--color-secondary);
    }
    .inv-form-group input:focus, .inv-form-group select:focus {
        border-color: var(--color-primary);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }
    .inv-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .inv-form-actions { display: flex; gap: 8px; margin-top: 4px; }

    /* Empty state */
    .inv-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 24px; color: var(--color-text-muted); }
    .inv-empty svg { opacity: 0.35; margin-bottom: 14px; }
    .inv-empty p { font-weight: 600; font-size: 0.95rem; color: var(--color-secondary); margin-bottom: 4px; }
    .inv-empty small { font-size: 0.8rem; }

    @media (max-width: 900px) {
        .inv-layout { grid-template-columns: 1fr; }
        .inv-form-card { position: static; }
    }
</style>

<div class="page-header">
    <div class="page-title">
        <h2>Inventory Management</h2>
        <p>Manage your product catalog, stock levels, and pricing.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('products.index') }}" class="btn btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3"/></svg>
            Refresh
        </a>
    </div>
</div>

@if(session('notice'))
    <div class="alert alert-{{ session('noticeType', 'success') }}">{{ session('notice') }}</div>
@endif

<div class="inv-layout">
    <!-- ── Left: Product List ── -->
    <div class="panel-card">
        <div class="panel-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div>
                <h3 style="margin:0;">Product List</h3>
                <p style="margin:0;font-size:0.8rem;color:var(--color-text-muted);">{{ $products->count() }} product(s) found</p>
            </div>
            <form method="GET" action="{{ route('products.index') }}" style="display:flex;gap:8px;align-items:center;">
                <div class="inv-search-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" class="inv-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="q" class="inv-search-input" placeholder="Search name, SKU, category..." value="{{ $search }}">
                    @if($search)
                        <a href="{{ route('products.index') }}" class="inv-search-clear" title="Clear search">&times;</a>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="height:38px;padding:0 16px;">Search</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th style="text-align:center;">Stock</th>
                        <th style="text-align:right;">Price</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                <div style="font-weight:600;color:var(--color-secondary);margin-bottom:3px;">{{ $product->name }}</div>
                                @if($product->sku)
                                    <span class="inv-sku-badge">{{ $product->sku }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="category-pill">{{ $product->category ?: 'General' }}</span>
                            </td>
                            <td style="text-align:center;">
                                @if($product->quantity === 0)
                                    <span class="badge-stock badge-stock-zero">
                                        <span class="stock-dot stock-zero"></span>Out of Stock
                                    </span>
                                @elseif($product->quantity <= 5)
                                    <span class="badge-stock badge-stock-low">
                                        <span class="stock-dot stock-low"></span>{{ $product->quantity }} left
                                    </span>
                                @else
                                    <span class="badge-stock badge-stock-ok">
                                        <span class="stock-dot stock-ok"></span>{{ $product->quantity }} in stock
                                    </span>
                                @endif
                            </td>
                            <td style="text-align:right;font-weight:700;color:#059669;">
                                &#8369;{{ number_format($product->price, 2) }}
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex;justify-content:center;gap:6px;">
                                    <a href="{{ route('products.index', ['edit' => $product->id]) }}"
                                       class="action-icon-btn" title="Edit product">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('products.destroy', $product->id) }}" onsubmit="return confirm('Delete {{ addslashes($product->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-icon-btn del" title="Delete product">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:0;">
                                <div class="inv-empty">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="7.5 4.21 12 6.81 16.5 4.21"/><polyline points="7.5 19.79 7.5 14.6 3 12"/><polyline points="16.5 19.79 16.5 14.6 21 12"/><polyline points="12 22.08 12 16.88 12 12"/></svg>
                                    @if($search)
                                        <p>No products match "{{ $search }}"</p>
                                        <small>Try a different search term or <a href="{{ route('products.index') }}">clear the search</a>.</small>
                                    @else
                                        <p>No products in inventory yet.</p>
                                        <small>Add your first product using the form on the right.</small>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Right: Add / Edit Form ── -->
    <div class="panel-card inv-form-card">
        @if($editProduct)
            <div class="inv-form-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit Product
            </div>
            <form method="POST" action="{{ route('products.store') }}">
                @csrf
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="{{ $editProduct->id }}">
                <div class="inv-form-group">
                    <label>Product Name <span style="color:var(--color-danger);">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $editProduct->name) }}" required placeholder="e.g. Coca-Cola 1.5L">
                </div>
                <div class="inv-form-row">
                    <div class="inv-form-group">
                        <label>SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $editProduct->sku) }}" placeholder="e.g. SKU-001">
                    </div>
                    <div class="inv-form-group">
                        <label>Category</label>
                        <input type="text" name="category" value="{{ old('category', $editProduct->category) }}" placeholder="e.g. Beverages">
                    </div>
                </div>
                <div class="inv-form-row">
                    <div class="inv-form-group">
                        <label>Price (PHP) <span style="color:var(--color-danger);">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $editProduct->price) }}" required placeholder="0.00">
                    </div>
                    <div class="inv-form-group">
                        <label>Stock Qty <span style="color:var(--color-danger);">*</span></label>
                        <input type="number" min="0" name="quantity" value="{{ old('quantity', $editProduct->quantity) }}" required placeholder="0">
                    </div>
                </div>
                @if ($errors->any())
                    <div style="color:var(--color-danger);font-size:0.8rem;margin-bottom:10px;">{{ $errors->first() }}</div>
                @endif
                <div class="inv-form-actions">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        @else
            <div class="inv-form-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Add New Product
            </div>
            <form method="POST" action="{{ route('products.store') }}">
                @csrf
                <div class="inv-form-group">
                    <label>Product Name <span style="color:var(--color-danger);">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Coca-Cola 1.5L">
                </div>
                <div class="inv-form-row">
                    <div class="inv-form-group">
                        <label>SKU</label>
                        <input type="text" name="sku" value="{{ old('sku') }}" placeholder="e.g. SKU-001">
                    </div>
                    <div class="inv-form-group">
                        <label>Category</label>
                        <input type="text" name="category" value="{{ old('category') }}" placeholder="e.g. Beverages">
                    </div>
                </div>
                <div class="inv-form-row">
                    <div class="inv-form-group">
                        <label>Price (PHP) <span style="color:var(--color-danger);">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" value="{{ old('price') }}" required placeholder="0.00">
                    </div>
                    <div class="inv-form-group">
                        <label>Stock Qty <span style="color:var(--color-danger);">*</span></label>
                        <input type="number" min="0" name="quantity" value="{{ old('quantity') }}" required placeholder="0">
                    </div>
                </div>
                @if ($errors->any())
                    <div style="color:var(--color-danger);font-size:0.8rem;margin-bottom:10px;">{{ $errors->first() }}</div>
                @endif
                <div class="inv-form-actions">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Add Product</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
