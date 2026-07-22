<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Mail\InquiryRepliedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('notice', 'Please login or register first to submit an inquiry.')
                ->with('noticeType', 'danger');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:150',
            'customer_email' => 'required|email|max:150',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if (session()->has('fcm_token')) {
            $validated['fcm_token'] = session('fcm_token');
        }

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

        // 1. Send Email Notification
        try {
            if (!empty($inquiry->customer_email)) {
                Mail::to($inquiry->customer_email)->send(new InquiryRepliedMail($inquiry));
            }
        } catch (\Throwable $e) {
            \Log::error("Failed to send inquiry reply email: " . $e->getMessage());
        }

        // 2. Send Push Notification (FCM)
        try {
            if (!empty($inquiry->fcm_token)) {
                $this->sendFcmPush(
                    $inquiry->fcm_token,
                    "Inquiry Replied: " . $inquiry->subject,
                    $validated['response']
                );
            }
        } catch (\Throwable $e) {
            \Log::error("Failed to send inquiry FCM push notification: " . $e->getMessage());
        }

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

    public function profile()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $inquiries = Inquiry::where('customer_email', Auth::user()->email)
            ->latest()
            ->get();

        return view('profile.index', [
            'inquiries' => $inquiries,
            'user' => Auth::user(),
            'hideStaffLinks' => true,
            'hideAppDownload' => true,
        ]);
    }

    public function notifications()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $notifications = Inquiry::where('customer_email', Auth::user()->email)
            ->where('status', 'responded')
            ->latest()
            ->get();

        return view('profile.notifications', [
            'notifications' => $notifications,
            'hideStaffLinks' => true,
            'hideAppDownload' => true,
        ]);
    }

    private function sendFcmPush($token, $title, $body)
    {
        $serverKey = env('FCM_SERVER_KEY');
        if (!$serverKey) {
            \Log::warning("FCM_SERVER_KEY is not configured in .env. Skipping push notification.");
            return;
        }

        $url = 'https://fcm.googleapis.com/fcm/send';

        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => [
                'title' => $title,
                'body' => $body,
            ],
            'priority' => 'high',
        ];

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            \Log::error("FCM cURL error: " . $err);
        } else {
            \Log::info("FCM response: " . $response);
        }
    }
}
