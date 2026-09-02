<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $query = ActivityLog::query();

        // Filtering by role: admin logs, customer logs, guest logs
        $filter = $request->query('filter', 'all');
        if ($filter === 'admin') {
            $query->where('user_role', 'admin');
        } elseif ($filter === 'customer') {
            $query->whereIn('user_role', ['user', 'staff']);
        } elseif ($filter === 'guest') {
            $query->where('user_role', 'guest');
        }

        // Searching logs
        $search = $request->query('q');
        if (!empty($search)) {
            $searchTerms = '%' . $search . '%';
            $query->where(function ($sub) use ($searchTerms) {
                $sub->where('user_name', 'like', $searchTerms)
                    ->orWhere('action', 'like', $searchTerms)
                    ->orWhere('description', 'like', $searchTerms)
                    ->orWhere('ip_address', 'like', $searchTerms);
            });
        }

        // Fetch logs with 6 items per page
        $logs = $query->orderByDesc('id')->paginate(6)->withQueryString();

        // Stats for metrics card
        $totalLogsCount = ActivityLog::count();
        $adminLogsCount = ActivityLog::where('user_role', 'admin')->count();
        $customerLogsCount = ActivityLog::whereIn('user_role', ['user', 'staff'])->count();
        $guestLogsCount = ActivityLog::where('user_role', 'guest')->count();

        return view('settings.logs', compact(
            'logs', 
            'filter', 
            'search', 
            'totalLogsCount', 
            'adminLogsCount', 
            'customerLogsCount', 
            'guestLogsCount'
        ));
    }
}
