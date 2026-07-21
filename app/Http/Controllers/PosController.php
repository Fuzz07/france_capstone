<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    protected function cart(Request $request)
    {
        return $request->session()->get('cart', []);
    }

    public function index(Request $request)
    {
        $search = $request->input('q', '');
        $products = Product::orderBy('name')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->limit(500)
            ->get();
        $cart = $this->cart($request);
        $grandTotal = collect($cart)->sum(function ($item) {
            return $item['price'] * $item['qty'];
        });

        $receipt = $request->session()->pull('receipt');

        return view('pos.index', compact('products', 'cart', 'grandTotal', 'receipt', 'search'));
    }


    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'qty' => 'nullable|integer|min:1',
        ]);

        $qty = max(1, $request->input('qty', 1));
        $product = Product::findOrFail($request->product_id);
        $cart = $request->session()->get('cart', []);

        $existingQty = $cart[$product->id]['qty'] ?? 0;
        $newQty = min($existingQty + $qty, $product->quantity);

        if ($newQty <= 0) {
            return back()->with('notice', 'Product is out of stock!')->with('noticeType', 'danger');
        }

        $cart[$product->id] = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'qty' => $newQty,
        ];

        $request->session()->put('cart', $cart);

        return redirect()->route('pos.index');
    }

    public function updateCart(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'action' => 'required|in:increase,decrease,remove',
        ]);

        $cart = $request->session()->get('cart', []);
        $productId = $request->input('id');

        if (!isset($cart[$productId])) {
            return redirect()->route('pos.index');
        }

        $product = Product::find($productId);

        if ($request->action === 'increase' && $product) {
            $cart[$productId]['qty'] = min($cart[$productId]['qty'] + 1, $product->quantity);
        } elseif ($request->action === 'decrease') {
            $cart[$productId]['qty']--;
            if ($cart[$productId]['qty'] <= 0) {
                unset($cart[$productId]);
            }
        } elseif ($request->action === 'remove') {
            unset($cart[$productId]);
        }

        $request->session()->put('cart', $cart);

        return redirect()->route('pos.index');
    }

    public function clearCart(Request $request)
    {
        $request->session()->forget('cart');
        return redirect()->route('pos.index');
    }

    public function checkout(Request $request)
    {
        $cart = $this->cart($request);
        if (empty($cart)) {
            return redirect()->route('pos.index')->with('notice', 'Cart is empty!')->with('noticeType', 'danger');
        }

        $validated = $request->validate([
            'cash_tendered' => 'required|numeric|min:0',
            'payment_method' => 'nullable|in:cash,gcash',
        ]);

        $grand = collect($cart)->sum(fn($item) => $item['price'] * $item['qty']);
        $paymentMethod = $validated['payment_method'] ?? 'cash';

        if ($paymentMethod === 'cash' && (float) $validated['cash_tendered'] < $grand) {
            return redirect()->route('pos.index')->with('notice', 'Insufficient cash tendered.')->with('noticeType', 'danger');
        }

        $saleId = null;
        DB::transaction(function () use ($cart, $grand, &$saleId) {
            $sale = Sale::create([
                'user_id' => Auth::id(),
                'total' => $grand,
            ]);
            $saleId = $sale->id;

            foreach ($cart as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                ]);

                Product::where('id', $item['id'])->decrement('quantity', $item['qty']);
            }
        });

        $cashTendered = (float) $validated['cash_tendered'];
        $change = $paymentMethod === 'gcash' ? 0.0 : max(0.0, $cashTendered - $grand);

        $request->session()->forget('cart');
        $request->session()->put('receipt', [
            'sale_id' => $saleId,
            'items' => array_values($cart),
            'total' => $grand,
            'cash' => $cashTendered,
            'change' => $change,
            'payment_method' => $paymentMethod,
            'cashier' => Auth::user()->name,
            'created_at' => now()->format('M d, Y h:i A'),
        ]);

        return redirect()->route('pos.index')->with('notice', 'Checkout complete!')->with('noticeType', 'success');
    }

    public function printSale(Request $request, $sale)
    {
        $saleModel = Sale::with(['items.product', 'user'])->findOrFail($sale);

        $items = $saleModel->items->map(function ($item) {
            return [
                'name'  => $item->product->name ?? 'Deleted Product',
                'qty'   => $item->qty,
                'price' => $item->price,
            ];
        })->toArray();

        $receipt = [
            'sale_id'        => $saleModel->id,
            'items'          => $items,
            'total'          => $saleModel->total,
            'cash'           => $saleModel->total,
            'change'         => 0,
            'payment_method' => 'cash',
            'cashier'        => $saleModel->user->name ?? 'System',
            'created_at'     => $saleModel->created_at->format('M d, Y h:i A'),
        ];

        return view('pos.print', compact('receipt'));
    }
}

