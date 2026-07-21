<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Message;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $tableCounts = [
            'products' => Product::count(),
            'sales' => Sale::count(),
            'sale_items' => SaleItem::count(),
            'inquiries' => Inquiry::count(),
            'messages' => Message::count(),
            'users' => User::count(),
        ];

        return view('settings.index', [
            'tableCounts' => $tableCounts,
            'totalRevenue' => Sale::sum('total'),
            'adminCount' => User::where('role', 'admin')->count(),
            'lastSaleAt' => Sale::max('created_at'),
            'currentUser' => Auth::user(),
        ]);
    }

    public function action(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $action = $request->input('action');

        if ($action === 'reset_sales') {
            DB::table('sale_items')->truncate();
            DB::table('sales')->truncate();
            return redirect()->route('settings.index')->with('notice', 'Sales transaction history has been cleared.')->with('noticeType', 'success');
        }

        if ($action === 'reset_chat') {
            DB::table('messages')->truncate();
            return redirect()->route('settings.index')->with('notice', 'Chat history has been cleared.')->with('noticeType', 'success');
        }

        if ($action === 'seed_products') {
            // Disable FK checks to allow truncating tables with foreign-key relationships.
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('sale_items')->truncate();
            DB::table('sales')->truncate();
            DB::table('products')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $demoProducts = [
                // --- School Supplies ---
                ['SCH-NOTE-80', 'Spiral Notebook 80 Pages', 'School Supplies', 25.00, 100],
                ['SCH-GELPEN', 'Black Gel Pen 0.5mm', 'School Supplies', 15.00, 150],
                ['SCH-PENCILS', 'No. 2 HB Pencil (Set of 12)', 'School Supplies', 85.00, 50],
                ['SCH-PAD-YLW', 'Yellow Pad Paper 80 Leaves', 'School Supplies', 45.00, 90],
                ['SCH-CRAYON24', 'Crayons 24 Colors', 'School Supplies', 65.00, 60],
                ['SCH-HIGHLIGHT', 'Highlighter Marker Set (4 Colors)', 'School Supplies', 55.00, 80],
                ['SCH-ERASER', 'Eraser (Pack of 2)', 'School Supplies', 20.00, 120],
                ['SCH-RULER-30', 'Plastic Ruler 30cm', 'School Supplies', 18.00, 140],
                ['SCH-GLUESTICK', 'Glue Stick (20g)', 'School Supplies', 30.00, 100],
                ['SCH-TAPE', 'Scotch Tape 1 inch (Roll)', 'School Supplies', 28.00, 110],

                // --- Fabric ---
                ['FAB-COTTON', 'Printed Cotton Fabric (Yard)', 'Fabric', 120.00, 40],
                ['FAB-LINEN', 'Premium Plain Linen (Yard)', 'Fabric', 180.00, 30],
                ['FAB-SATIN', 'Smooth Satin Silk (Yard)', 'Fabric', 250.00, 20],
                ['FAB-DENIM', 'Classic Denim Fabric (Yard)', 'Fabric', 220.00, 18],
                ['FAB-CHIFFON', 'Chiffon Fabric (Yard)', 'Fabric', 165.00, 25],
                ['FAB-TWILL', 'Twill Cotton Fabric (Yard)', 'Fabric', 145.00, 22],
                ['FAB-VELVET', 'Soft Velvet Fabric (Yard)', 'Fabric', 310.00, 12],
                ['FAB-GINGHAM', 'Gingham Plaid Fabric (Yard)', 'Fabric', 135.00, 28],
                ['FAB-LACE', 'Lace Fabric Trim (Per Yard)', 'Fabric', 95.00, 35],
            ];

            foreach ($demoProducts as $product) {
                Product::create([
                    'sku' => $product[0],
                    'name' => $product[1],
                    'category' => $product[2],
                    'price' => $product[3],
                    'quantity' => $product[4],
                ]);
            }

            return redirect()->route('settings.index')->with('notice', 'Demo inventory loaded and sales history reset.')->with('noticeType', 'success');
        }

        return redirect()->route('settings.index');
    }

    public function export(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $exportKey = $request->query('export', 'all');
        $definitions = [
            'products' => [
                'label' => 'Products',
                'sql' => Product::select('id', 'sku', 'name', 'category', 'price', 'quantity', 'created_at')->toSql(),
            ],
            'sales' => [
                'label' => 'Sales',
                'sql' => Sale::select('id', 'user_id', 'total', 'created_at')->toSql(),
            ],
            'sale_items' => [
                'label' => 'Sale Items',
                'sql' => SaleItem::select('id', 'sale_id', 'product_id', 'qty', 'price')->toSql(),
            ],
            'inquiries' => [
                'label' => 'Inquiries',
                'sql' => Inquiry::select('id', 'customer_name', 'customer_email', 'subject', 'message', 'status', 'created_at')->toSql(),
            ],
            'messages' => [
                'label' => 'Team Messages',
                'sql' => Message::select('id', 'user_name', 'message', 'created_at')->toSql(),
            ],
            'users' => [
                'label' => 'Users',
                'sql' => User::select('id', 'name', 'email', 'role', 'created_at')->toSql(),
            ],
        ];

        $filename = 'meras_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($exportKey, $definitions) {
            $output = fopen('php://output', 'w');
            fputs($output, pack('CCC', 0xEF, 0xBB, 0xBF));

            if ($exportKey === 'all') {
                foreach ($definitions as $key => $definition) {
                    fputcsv($output, [$definition['label']]);
                    $rows = DB::table($key)->get();
                    if ($rows->count()) {
                        fputcsv($output, array_keys((array) $rows->first()));
                        foreach ($rows as $row) {
                            fputcsv($output, (array) $row);
                        }
                    }
                    fputcsv($output, []);
                }
            } elseif (isset($definitions[$exportKey])) {
                $rows = DB::table($exportKey)->get();
                if ($rows->count()) {
                    fputcsv($output, array_keys((array) $rows->first()));
                    foreach ($rows as $row) {
                        fputcsv($output, (array) $row);
                    }
                }
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
