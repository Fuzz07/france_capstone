<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $dailySales = Sale::whereDate('created_at', now()->toDateString())->sum('total');
        $weeklySales = Sale::where('created_at', '>=', now()->subDays(6)->startOfDay())->sum('total');
        $monthlySales = Sale::where('created_at', '>=', now()->startOfMonth())->sum('total');
        $totalRevenue = Sale::sum('total');
        $totalProducts = Product::count();
        $lowStockCount = Product::where('quantity', '<', 5)->count();

        $recentSales = Sale::with('user')
            ->latest()
            ->limit(5)
            ->get();

        $lowStockList = Product::where('quantity', '<', 5)
            ->orderBy('quantity')
            ->limit(5)
            ->get();

        $chartStart = now()->subDays(6)->startOfDay();
        $salesByDate = Sale::where('created_at', '>=', $chartStart)
            ->get(['total', 'created_at'])
            ->groupBy(fn ($sale) => $sale->created_at->format('Y-m-d'))
            ->map(fn ($rows) => (float) $rows->sum('total'));

        $salesTrendLabels = [];
        $salesTrendTotals = [];
        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = now()->subDays($daysAgo);
            $key = $date->format('Y-m-d');
            $salesTrendLabels[] = $date->format('M d');
            $salesTrendTotals[] = round((float) ($salesByDate[$key] ?? 0), 2);
        }

        $topProducts = SaleItem::select('products.name', DB::raw('SUM(sale_items.qty) as units_sold'))
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get();

        $stockByCategory = Product::get(['category', 'quantity'])
            ->groupBy(fn ($product) => $product->category ?: 'General')
            ->map(fn ($products) => (int) $products->sum('quantity'))
            ->sortDesc()
            ->take(6);

        $chartData = [
            'salesTrend' => [
                'labels' => $salesTrendLabels,
                'totals' => $salesTrendTotals,
            ],
            'topProducts' => [
                'labels' => $topProducts->pluck('name')->values(),
                'units' => $topProducts->pluck('units_sold')->map(fn ($value) => (int) $value)->values(),
            ],
            'stockByCategory' => [
                'labels' => $stockByCategory->keys()->values(),
                'stock' => $stockByCategory->values(),
            ],
        ];

        return view('dashboard.index', compact(
            'dailySales',
            'weeklySales',
            'monthlySales',
            'totalRevenue',
            'totalProducts',
            'lowStockCount',
            'recentSales',
            'lowStockList',
            'chartData'
        ));
    }
}