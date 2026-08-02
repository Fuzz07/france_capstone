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

        $inq = Inquiry::create($validated);
        \App\Models\ActivityLog::log('submit_inquiry', 'Customer submitted inquiry: "' . $inq->subject . '"', Auth::user());

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

        \App\Models\ActivityLog::log('reply_inquiry', 'Admin responded to inquiry #' . $inquiry->id . ' from ' . $inquiry->customer_name);

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
        \App\Models\ActivityLog::log('update_inquiry_status', 'Updated status of inquiry #' . $inquiry->id . ' to ' . $inquiry->status);

        return back()->with('notice', 'Inquiry status updated successfully.')->with('noticeType', 'success');
    }

    public function destroy(Inquiry $inquiry)
    {
        $id = $inquiry->id;
        $subject = $inquiry->subject;
        $customer = $inquiry->customer_name;
        $inquiry->delete();
        \App\Models\ActivityLog::log('delete_inquiry', 'Deleted inquiry #' . $id . ' ("' . $subject . '" from ' . $customer . ')');

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
        $serviceAccountPath = storage_path('app/firebase-service-account.json');
        
        // 1. Check if the modern FCM HTTP v1 Service Account JSON file is present
        if (file_exists($serviceAccountPath)) {
            try {
                $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
                if (!$serviceAccount || !isset($serviceAccount['project_id']) || !isset($serviceAccount['private_key'])) {
                    throw new \Exception("Invalid service account JSON structure.");
                }

                $accessToken = $this->getFcmAccessToken($serviceAccount);
                $projectId = $serviceAccount['project_id'];
                $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

                // FCM HTTP v1 JSON Payload
                $payload = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                    ]
                ];

                $headers = [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

                $response = curl_exec($ch);
                $err = curl_error($ch);
                curl_close($ch);

                if ($err) {
                    \Log::error("FCM HTTP v1 cURL error: " . $err);
                } else {
                    \Log::info("FCM HTTP v1 response: " . $response);
                }
                return;
            } catch (\Throwable $e) {
                \Log::error("FCM HTTP v1 failed: " . $e->getMessage() . ". Falling back to Legacy API.");
            }
        }

        // 2. Legacy API Fallback
        $serverKey = env('FCM_SERVER_KEY');
        if (!$serverKey) {
            \Log::warning("FCM_SERVER_KEY is not configured in .env and firebase-service-account.json is missing. Skipping push notification.");
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            \Log::error("Legacy FCM cURL error: " . $err);
        } else {
            \Log::info("Legacy FCM response: " . $response);
        }
    }

    private function getFcmAccessToken($serviceAccount)
    {
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $payload = json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;
        
        $privateKey = $serviceAccount['private_key'];
        if (!openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \Exception("Failed to sign JWT with private key.");
        }

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $signatureInput . "." . $base64UrlSignature;

        // Fetch access token via cURL
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \Exception("OAuth2 token request error: " . $err);
        }

        $data = json_decode($response, true);
        if (isset($data['error'])) {
            throw new \Exception("OAuth2 error: " . $data['error_description']);
        }

        return $data['access_token'];
    }
}
