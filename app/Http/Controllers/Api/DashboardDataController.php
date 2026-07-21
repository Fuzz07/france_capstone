<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardDataController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'daily');
        if (!in_array($filter, ['daily', 'weekly', 'monthly'])) {
            $filter = 'daily';
        }

        $revenueLabels = [];
        $revenueData = [];
        $popularLabels = [];
        $popularData = [];

        if ($filter === 'daily') {
            $dates = collect();
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $dates->put($date->format('Y-m-d'), [
                    'label' => $date->format('M d'),
                    'revenue' => 0,
                ]);
            }

            $sales = Sale::selectRaw('DATE(created_at) as sale_date, SUM(total) as total_revenue')
                ->where('created_at', '>=', Carbon::today()->subDays(29))
                ->groupBy('sale_date')
                ->get();

            foreach ($sales as $row) {
                if ($dates->has($row->sale_date)) {
                    $dates[$row->sale_date]['revenue'] = $row->total_revenue;
                }
            }

            foreach ($dates as $day) {
                $revenueLabels[] = $day['label'];
                $revenueData[] = $day['revenue'];
            }

            $popular = SaleItem::select('products.name as item_name', DB::raw('SUM(qty) as total_qty'))
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.created_at', '>=', Carbon::today()->subDays(29))
                ->groupBy('sale_items.product_id')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get();
        } elseif ($filter === 'weekly') {
            $weeks = collect();
            for ($i = 11; $i >= 0; $i--) {
                $monday = Carbon::today()->subWeeks($i)->startOfWeek();
                $weeks->put($monday->format('Y-m-d'), [
                    'label' => 'Wk of ' . $monday->format('M d'),
                    'start' => $monday->copy(),
                    'end' => $monday->copy()->endOfWeek(),
                    'revenue' => 0,
                ]);
            }

            $sales = Sale::where('created_at', '>=', Carbon::today()->subWeeks(11)->startOfWeek())->get();
            foreach ($sales as $sale) {
                foreach ($weeks as $key => $week) {
                    if ($sale->created_at->between($week['start'], $week['end'])) {
                        $weeks[$key]['revenue'] += $sale->total;
                        break;
                    }
                }
            }

            foreach ($weeks as $week) {
                $revenueLabels[] = $week['label'];
                $revenueData[] = $week['revenue'];
            }

            $popular = SaleItem::select('products.name as item_name', DB::raw('SUM(qty) as total_qty'))
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.created_at', '>=', Carbon::today()->subWeeks(11)->startOfWeek())
                ->groupBy('sale_items.product_id')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get();
        } else {
            $months = collect();
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::today()->subMonths($i)->startOfMonth();
                $months->put($month->format('Y-m'), [
                    'label' => $month->format('M Y'),
                    'revenue' => 0,
                ]);
            }

            $sales = Sale::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as yr_mo, SUM(total) as total_revenue")
                ->where('created_at', '>=', Carbon::today()->subMonths(11)->startOfMonth())
                ->groupBy('yr_mo')
                ->get();

            foreach ($sales as $row) {
                if ($months->has($row->yr_mo)) {
                    $months[$row->yr_mo]['revenue'] = $row->total_revenue;
                }
            }

            foreach ($months as $month) {
                $revenueLabels[] = $month['label'];
                $revenueData[] = $month['revenue'];
            }

            $popular = SaleItem::select('products.name as item_name', DB::raw('SUM(qty) as total_qty'))
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.created_at', '>=', Carbon::today()->subMonths(11)->startOfMonth())
                ->groupBy('sale_items.product_id')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get();
        }

        if ($popular->isEmpty()) {
            $popularLabels = ['No sales recorded'];
            $popularData = [0];
        } else {
            foreach ($popular as $row) {
                $popularLabels[] = $row->item_name;
                $popularData[] = (int) $row->total_qty;
            }
        }

        return response()->json([
            'revenue' => [
                'labels' => $revenueLabels,
                'data' => $revenueData,
            ],
            'most_bought' => [
                'labels' => $popularLabels,
                'data' => $popularData,
            ],
        ]);
    }
}
