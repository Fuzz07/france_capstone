<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $meta = $this->periodMeta($request->query('period', 'all'));

        $stats = $this->salesQuery($meta['start'])
            ->selectRaw('COALESCE(SUM(total), 0) as revenue, COUNT(*) as count, COALESCE(AVG(total), 0) as average')
            ->first();

        $productBreakdown = $this->productBreakdown($meta['start'])->get();

        $transactions = $this->salesQuery($meta['start'])
            ->with('user')
            ->latest()
            ->limit(200)
            ->get();

        return view('reports.index', [
            'stats' => $stats,
            'productBreakdown' => $productBreakdown,
            'transactions' => $transactions,
            'dateLabel' => $meta['label'],
            'dateRange' => $meta['range'],
            'period' => $meta['period'],
        ]);
    }

    public function export(Request $request)
    {
        $meta = $this->periodMeta($request->query('period', 'all'));

        $stats = $this->salesQuery($meta['start'])
            ->selectRaw('COALESCE(SUM(total), 0) as revenue, COUNT(*) as count, COALESCE(AVG(total), 0) as average')
            ->first();

        $productBreakdown = $this->productBreakdown($meta['start'])->get();
        $transactions = $this->salesQuery($meta['start'])->with('user')->latest()->limit(500)->get();
        $filename = 'meras_sales_report_' . $meta['period'] . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($meta, $stats, $productBreakdown, $transactions) {
            $output = fopen('php://output', 'w');
            fputs($output, pack('CCC', 0xEF, 0xBB, 0xBF));

            fputcsv($output, [config('app.name') . ' Sales Report']);
            fputcsv($output, ['Report', $meta['label']]);
            fputcsv($output, ['Period', $meta['range']]);
            fputcsv($output, ['Generated', now()->format('M d, Y h:i A')]);
            fputcsv($output, []);

            fputcsv($output, ['Summary']);
            fputcsv($output, ['Total Revenue', 'Transactions', 'Average Sale']);
            fputcsv($output, [number_format((float) $stats->revenue, 2, '.', ''), $stats->count, number_format((float) $stats->average, 2, '.', '')]);
            fputcsv($output, []);

            fputcsv($output, ['Top Selling Products']);
            fputcsv($output, ['Rank', 'SKU', 'Product', 'Category', 'Units Sold', 'Revenue']);
            foreach ($productBreakdown as $index => $item) {
                fputcsv($output, [
                    $index + 1,
                    $item->sku,
                    $item->name,
                    $item->category ?: 'General',
                    $item->units_sold,
                    number_format((float) $item->line_total, 2, '.', ''),
                ]);
            }
            fputcsv($output, []);

            fputcsv($output, ['Recent Transactions']);
            fputcsv($output, ['Sale ID', 'Cashier', 'Date Time', 'Total']);
            foreach ($transactions as $sale) {
                fputcsv($output, [
                    $sale->id,
                    $sale->user->name ?? 'System',
                    optional($sale->created_at)->format('M d, Y h:i A'),
                    number_format((float) $sale->total, 2, '.', ''),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function periodMeta(string $period): array
    {
        $today = now();

        if ($period === 'weekly') {
            $start = $today->copy()->subDays(6)->startOfDay();
            return [
                'period' => 'weekly',
                'label' => 'Weekly Report',
                'range' => $start->format('M d, Y') . ' - ' . $today->format('M d, Y'),
                'start' => $start,
            ];
        }

        if ($period === 'monthly') {
            $start = $today->copy()->subDays(29)->startOfDay();
            return [
                'period' => 'monthly',
                'label' => 'Monthly Report',
                'range' => $start->format('M d, Y') . ' - ' . $today->format('M d, Y'),
                'start' => $start,
            ];
        }

        if ($period === 'yearly') {
            $start = $today->copy()->subDays(364)->startOfDay();
            return [
                'period' => 'yearly',
                'label' => 'Yearly Report',
                'range' => $start->format('M d, Y') . ' - ' . $today->format('M d, Y'),
                'start' => $start,
            ];
        }

        return [
            'period' => 'all',
            'label' => 'All Time',
            'range' => 'Since start of records',
            'start' => null,
        ];
    }

    private function salesQuery($start)
    {
        return Sale::query()
            ->when($start, function ($query) use ($start) {
                $query->where('created_at', '>=', $start);
            });
    }

    private function productBreakdown($start)
    {
        return SaleItem::select(
                'products.sku',
                'products.name',
                'products.category',
                DB::raw('SUM(sale_items.qty) as units_sold'),
                DB::raw('SUM(sale_items.qty * sale_items.price) as line_total')
            )
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->when($start, function ($query) use ($start) {
                $query->where('sales.created_at', '>=', $start);
            })
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.category')
            ->orderByDesc('line_total')
            ->limit(50);
    }
}