<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('q');
        $query = Product::query();

        if ($search) {
            $searchTerms = '%' . $search . '%';
            $query->where(function ($sub) use ($searchTerms) {
                $sub->where('name', 'like', $searchTerms)
                    ->orWhere('sku', 'like', $searchTerms)
                    ->orWhere('category', 'like', $searchTerms);
            });
        }

        $products = $query->orderByDesc('id')->limit(200)->get();

        $editProduct = null;
        if ($request->filled('edit')) {
            $editProduct = Product::find($request->query('edit'));
        }

        return view('inventory.index', compact('products', 'search', 'editProduct'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'sku' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            // Bulk pricing is optional, but a bulk price only takes effect in the
            // POS together with the quantity that unlocks it, so require the pair.
            'bulk_price' => 'nullable|numeric|min:0|required_with:bulk_min_qty',
            'bulk_min_qty' => 'nullable|integer|min:2|required_with:bulk_price',
            'quantity' => 'required|integer|min:0',
        ]);

        // Blank inputs must clear the columns rather than store 0.
        $data['bulk_price'] = $data['bulk_price'] ?? null;
        $data['bulk_min_qty'] = $data['bulk_min_qty'] ?? null;

        if ($request->input('action') === 'edit') {
            $product = Product::findOrFail($request->input('id'));
            $oldQuantity = $product->quantity;
            $product->update($data);
            \App\Models\ActivityLog::log('update_product', 'Updated product: ' . $product->name . ' (SKU: ' . $product->sku . ', Qty: ' . $oldQuantity . ' -> ' . $product->quantity . ')');
            return redirect()->route('products.index')->with('notice', 'Product updated successfully.')->with('noticeType', 'success');
        }

        $product = Product::create($data);
        \App\Models\ActivityLog::log('create_product', 'Created new product: ' . $product->name . ' (SKU: ' . $product->sku . ', Price: PHP ' . number_format($product->price, 2) . ', Qty: ' . $product->quantity . ')');
        return redirect()->route('products.index')->with('notice', 'Product added successfully.')->with('noticeType', 'success');
    }

    public function destroy(Product $product)
    {
        $name = $product->name;
        $sku = $product->sku;
        $product->delete();
        \App\Models\ActivityLog::log('delete_product', 'Deleted product: ' . $name . ' (SKU: ' . $sku . ')');
        return redirect()->route('products.index')->with('notice', 'Product successfully deleted.')->with('noticeType', 'success');
    }
}
