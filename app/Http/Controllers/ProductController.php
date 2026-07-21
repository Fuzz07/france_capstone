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
            'quantity' => 'required|integer|min:0',
        ]);

        if ($request->input('action') === 'edit') {
            $product = Product::findOrFail($request->input('id'));
            $product->update($data);
            return redirect()->route('products.index')->with('notice', 'Product updated successfully.')->with('noticeType', 'success');
        }

        Product::create($data);
        return redirect()->route('products.index')->with('notice', 'Product added successfully.')->with('noticeType', 'success');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('notice', 'Product successfully deleted.')->with('noticeType', 'success');
    }
}
