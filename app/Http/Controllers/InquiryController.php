<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inquiry::query()->with('respondent');

        if ($request->filled('status') && in_array($request->status, ['pending', 'responded'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($sub) use ($search) {
                $sub->where('customer_name', 'like', $search)
                    ->orWhere('customer_email', 'like', $search)
                    ->orWhere('subject', 'like', $search)
                    ->orWhere('message', 'like', $search)
                    ->orWhere('response', 'like', $search);
            });
        }

        $inquiries = $query->latest()->get();

        return view('inquiries.index', [
            'inquiries' => $inquiries,
            'statusFilter' => $request->status ?? 'all',
            'search' => $request->search ?? '',
            'notice' => session('notice'),
            'noticeType' => session('noticeType', 'success'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:150',
            'customer_email' => 'required|email|max:150',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        Inquiry::create($validated);

        $redirectRoute = $request->input('source') === 'mobile' ? 'mobile.home' : 'home';

        return redirect()->route($redirectRoute)->with('notice', 'Inquiry submitted successfully.')->with('noticeType', 'success');
    }

    public function respond(Request $request, Inquiry $inquiry)
    {
        $validated = $request->validate([
            'response' => 'required|string|max:5000',
        ]);

        $inquiry->update([
            'response' => $validated['response'],
            'status' => 'responded',
            'responded_at' => now(),
            'responded_by' => Auth::id(),
        ]);

        return back()->with('notice', 'Customer response saved and inquiry marked as responded.')->with('noticeType', 'success');
    }

    public function toggle(Inquiry $inquiry)
    {
        if ($inquiry->status === 'pending') {
            $inquiry->status = 'responded';
            $inquiry->responded_at = $inquiry->responded_at ?: now();
            $inquiry->responded_by = $inquiry->responded_by ?: Auth::id();
        } else {
            $inquiry->status = 'pending';
            $inquiry->responded_at = null;
            $inquiry->responded_by = null;
        }

        $inquiry->save();

        return back()->with('notice', 'Inquiry status updated successfully.')->with('noticeType', 'success');
    }

    public function destroy(Inquiry $inquiry)
    {
        $inquiry->delete();

        return redirect()->route('inquiries.index')->with('notice', 'Inquiry deleted successfully.');
    }
}