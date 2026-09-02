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
            \App\Models\ActivityLog::log('reset_sales', 'Admin cleared sales transaction history.');
            return redirect()->route('settings.index')->with('notice', 'Sales transaction history has been cleared.')->with('noticeType', 'success');
        }

        if ($action === 'reset_chat') {
            DB::table('messages')->truncate();
            \App\Models\ActivityLog::log('reset_chat', 'Admin cleared chat history.');
            return redirect()->route('settings.index')->with('notice', 'Chat history has been cleared.')->with('noticeType', 'success');
        }

        if ($action === 'seed_products') {
            // Disable FK checks to allow truncating tables with foreign-key relationships.
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            } elseif (DB::getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF');
            }

            DB::table('sale_items')->truncate();
            DB::table('sales')->truncate();
            DB::table('products')->truncate();

            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } elseif (DB::getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            }

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
                ['SCH-SCISSORS', 'Stainless Scissors 6-inch', 'School Supplies', 45.00, 75],
                ['SCH-COMPASS', 'Mathematical Drawing Compass', 'School Supplies', 60.00, 40],
                ['SCH-STAPLER', 'Mini Stapler with Staples', 'School Supplies', 75.00, 35],
                ['SCH-PENCILCASE', 'Plastic Pencil Case Organizer', 'School Supplies', 35.00, 80],
                ['SCH-CALCULATOR', 'Scientific Calculator 240 Functions', 'School Supplies', 350.00, 25],       
                ['SCH-GEOMETRY', 'Mathematical Geometry Box Set', 'School Supplies', 120.00, 30],
                ['SCH-MARKER-BLK', 'Permanent Marker Black (Pack of 3)', 'School Supplies', 45.00, 60],
                ['SCH-STCKY-3X3', 'Sticky Notes 3x3 (Pack of 3)', 'School Supplies', 45.00, 100],
                ['SCH-SHARPENER', 'Dual-Hole Pencil Sharpener', 'School Supplies', 15.00, 150],
                ['SCH-CLIP-PND', 'Binder Clips 19mm (Box of 12)', 'School Supplies', 35.00, 80],
                ['SCH-REFTAPE', 'Correction Tape 5mm x 8m', 'School Supplies', 25.00, 120],
                ['SCH-DRAW-PAD', 'Sketchbook/Drawing Pad A4', 'School Supplies', 95.00, 50],
                ['SCH-PAINT-WTR', 'Water Color Paint Set 12 Colors', 'School Supplies', 110.00, 45],
                ['SCH-CLIP-BRD', 'A4 Wooden Clipboard', 'School Supplies', 45.00, 70],
                ['SCH-INDEX-CRD', 'Ruled Index Cards 3x5 (Pack of 100)', 'School Supplies', 35.00, 110],
                ['SCH-PROTRACTOR', 'Clear Plastic Protractor 180 Degrees', 'School Supplies', 12.00, 150],
                ['SCH-PUNCHER', 'Two-Hole Paper Puncher Heavy Duty', 'School Supplies', 160.00, 30],
                ['SCH-REFILL-GEL', 'Blue Gel Pen Refill 0.5mm (Pack of 5)', 'School Supplies', 40.00, 85],

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
                ['FAB-POLYESTER', 'Polyester Lining Fabric (Yard)', 'Fabric', 80.00, 60],
                ['FAB-FLANNEL', 'Soft Cotton Flannel (Yard)', 'Fabric', 150.00, 35],
                ['FAB-ORGANZA', 'Sheer Organza Fabric (Yard)', 'Fabric', 110.00, 45],
                ['FAB-CANVAS', 'Heavy Duty Canvas Fabric (Yard)', 'Fabric', 195.00, 20],
                ['FAB-FLEECE', 'Warm Polar Fleece (Yard)', 'Fabric', 185.00, 15],
                ['FAB-CORDUROY', 'Premium Corduroy Fabric (Yard)', 'Fabric', 210.00, 25],
                ['FAB-BROCADE', 'Elegant Brocade Fabric (Yard)', 'Fabric', 280.00, 15],
                ['FAB-WOOL', 'Premium Merino Wool Fabric (Yard)', 'Fabric', 450.00, 10],
                ['FAB-SILK', 'Pure Mulberry Silk Fabric (Yard)', 'Fabric', 550.00, 8],
                ['FAB-JERSEY', 'Stretch Cotton Jersey Fabric (Yard)', 'Fabric', 160.00, 25],
                ['FAB-NYLON', 'Ripstop Nylon Fabric (Yard)', 'Fabric', 110.00, 40],
                ['FAB-TWEED', 'Classic Tweed Fabric (Yard)', 'Fabric', 290.00, 15],
                ['FAB-RAYON', 'Soft Rayon Challis Fabric (Yard)', 'Fabric', 130.00, 32],
                ['FAB-BROAD', 'Polyester Cotton Broadcloth (Yard)', 'Fabric', 90.00, 50],
                ['FAB-TAFFETA', 'Crisp Polyester Taffeta Fabric (Yard)', 'Fabric', 140.00, 24],
                ['FAB-MUSLIN', 'Unbleached Cotton Muslin Fabric (Yard)', 'Fabric', 85.00, 60],
                ['FAB-SEERSUCKER', 'Striped Cotton Seersucker (Yard)', 'Fabric', 175.00, 18],
                ['FAB-GEORGETTE', 'Premium Georgette Fabric (Yard)', 'Fabric', 155.00, 22],

                // --- General Merchandise ---
                ['GEN-UMBRELLA', 'Compact Foldable Umbrella', 'General Merchandise', 180.00, 50],
                ['GEN-BATTERY-AA', 'AA Alkaline Batteries (Pack of 4)', 'General Merchandise', 120.00, 100],    
                ['GEN-MUG-CERAMIC', 'Classic White Ceramic Mug 11oz', 'General Merchandise', 65.00, 60],        
                ['GEN-FLASHLIGHT', 'LED Waterproof Flashlight', 'General Merchandise', 150.00, 30],
                ['GEN-WALLCLOCK', 'Minimalist Silent Wall Clock', 'General Merchandise', 240.00, 15],
                ['GEN-LOCK-30', 'Brass Padlock 30mm with 3 Keys', 'General Merchandise', 85.00, 40],
                ['GEN-EXTENSION', 'Power Extension Strip 4-Outlet (2m)', 'General Merchandise', 195.00, 25],    
                ['GEN-WATERBTL', 'Stainless Steel Water Bottle 1L', 'General Merchandise', 280.00, 30],
                ['GEN-KEYCHAIN', 'Leather Key Organizer Holder', 'General Merchandise', 90.00, 50],
                ['GEN-KITCHEN', 'Digital Kitchen Scale 5kg', 'General Merchandise', 220.00, 15],
                ['GEN-SACK-TRG', 'Reusable Shopping Tote Bag', 'General Merchandise', 40.00, 200],
                ['GEN-PHONE-STND', 'Adjustable Desktop Phone Stand', 'General Merchandise', 110.00, 45],
                ['GEN-MUG-TRAVEL', 'Insulated Stainless Travel Mug 500ml', 'General Merchandise', 260.00, 40],
                ['GEN-BATTERY-AAA', 'AAA Alkaline Batteries (Pack of 4)', 'General Merchandise', 120.00, 95],
                ['GEN-CALCULATOR-DESK', 'Desktop Electronic Calculator 12-Digit', 'General Merchandise', 185.00, 30],
                ['GEN-PLUG-ADAPTER', 'Universal Travel Plug Adapter', 'General Merchandise', 150.00, 50],
                ['GEN-HANGER-WD', 'Wooden Clothes Hangers (Set of 5)', 'General Merchandise', 135.00, 60],
                ['GEN-SCISSORS-KT', 'Heavy Duty Kitchen Shears', 'General Merchandise', 110.00, 40],
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

            \App\Models\ActivityLog::log('seed_products', 'Admin seeded demo inventory and reset sales history.');

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

    public function usersIndex(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $search = $request->query('search');
        $query = User::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('role', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(8)->withQueryString();

        return view('settings.users', [
            'users' => $users,
            'search' => $search,
            'totalUsers' => User::count(),
            'adminCount' => User::where('role', 'admin')->count(),
            'customerCount' => User::where('role', 'user')->count(),
        ]);
    }

    public function usersStore(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:user,admin',
        ]);

        if ($data['role'] === 'admin' && User::where('role', 'admin')->exists()) {
            return redirect()->route('users.index')->with('notice', 'There can only be one administrator in the system.')->with('noticeType', 'danger');
        }

        $newUser = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        \App\Models\ActivityLog::log('create_user', 'Admin created new user: ' . $newUser->name . ' (' . $newUser->email . ', Role: ' . $newUser->role . ')');

        return redirect()->route('users.index')->with('notice', 'User ' . $data['name'] . ' created successfully.')->with('noticeType', 'success');
    }

    public function usersUpdateRole(Request $request, User $user)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('notice', 'You cannot change your own role.')->with('noticeType', 'danger');
        }

        $data = $request->validate([
            'role' => 'required|string|in:user,admin',
        ]);

        if ($data['role'] === 'admin') {
            $existingAdmin = User::where('role', 'admin')->first();
            if ($existingAdmin && $existingAdmin->id !== $user->id) {
                return redirect()->route('users.index')->with('notice', 'There can only be one administrator in the system.')->with('noticeType', 'danger');
            }
        }

        $oldRole = $user->role;
        $user->update([
            'role' => $data['role'],
        ]);

        \App\Models\ActivityLog::log('update_user_role', 'Admin updated user ' . $user->name . ' role from ' . $oldRole . ' to ' . $user->role);

        return redirect()->route('users.index')->with('notice', 'User ' . $user->name . ' role updated to ' . $data['role'] . '.')->with('noticeType', 'success');
    }

    public function usersDestroy(User $user)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('notice', 'You cannot delete yourself!')->with('noticeType', 'danger');
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $user->delete();

        \App\Models\ActivityLog::log('delete_user', 'Admin deleted user ' . $userName . ' (' . $userEmail . ')');

        return redirect()->route('users.index')->with('notice', 'User ' . $userName . ' deleted successfully.')->with('noticeType', 'success');
    }

    public function adminChangePassword(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!\Illuminate\Support\Facades\Hash::check($data['current_password'], $user->password)) {
            return redirect()->route('settings.index')
                ->with('notice', 'The current password you entered is incorrect.')
                ->with('noticeType', 'danger');
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($data['new_password']),
        ]);

        \App\Models\ActivityLog::log('change_admin_password', 'Admin changed their password.');

        return redirect()->route('settings.index')
            ->with('notice', 'Administrator password updated successfully!')
            ->with('noticeType', 'success');
    }
}
