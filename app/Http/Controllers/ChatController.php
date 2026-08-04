<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $supportInquiries = Inquiry::with('respondent')->latest()->limit(8)->get();

        if ($user->role === 'user') {
            // Customer only sees their own messages, bot messages, and messages from staff/admin
            $staffNames = User::where('role', 'admin')->pluck('name')->toArray();
            $allowedNames = array_merge([$user->name, "Mera's Support Bot", "Mera's Support Assistant"], $staffNames);
            
            $messages = Message::whereIn('user_name', $allowedNames)
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->reverse()
                ->values();
        } else {
            // Admins/Staff see all messages
            $messages = Message::orderByDesc('id')->limit(100)->get()->reverse()->values();
        }

        return view('chat.index', [
            'messages' => $messages,
            'supportInquiries' => $supportInquiries,
            'messagesToday' => Message::whereDate('created_at', today())->count(),
            'totalMessages' => Message::count(),
            'pendingInquiries' => Inquiry::where('status', 'pending')->count(),
            'respondedToday' => Inquiry::where('status', 'responded')->whereDate('responded_at', today())->count(),
        ]);
    }

    public function messages()
    {
        if (!auth()->check()) {
            return response()->json([]);
        }

        $user = auth()->user();

        if ($user->role === 'user') {
            $staffNames = User::where('role', 'admin')->pluck('name')->toArray();
            $allowedNames = array_merge([$user->name, "Mera's Support Bot", "Mera's Support Assistant"], $staffNames);
            
            $messages = Message::whereIn('user_name', $allowedNames)
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->reverse()
                ->values();
        } else {
            $messages = Message::orderByDesc('id')->limit(100)->get()->reverse()->values();
        }

        return response()->json($messages->map(function (Message $message) {
            return [
                'user_name' => $message->user_name,
                'message' => $message->message,
                'created_at' => optional($message->created_at)->diffForHumans(),
            ];
        }));
    }

    public function store(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $user = auth()->user();

        $msg = Message::create([
            'user_name' => $user->name,
            'message' => $request->input('message'),
        ]);

        \App\Models\ActivityLog::log('chat_message', 'Sent a chat message: "' . \Illuminate\Support\Str::limit($msg->message, 50) . '"');

        // Automated Chatbot Response for Customers!
        if ($user->role === 'user') {
            $userMessage = $request->input('message');
            $botReply = $this->getBotResponse($userMessage);
            
            if ($botReply) {
                Message::create([
                    'user_name' => "Mera's Support Bot",
                    'message' => $botReply,
                ]);
            }
        }

        return redirect()->route('chat.index')->with('notice', 'Message sent.');
    }

    /**
     * Generate an intelligent, helpful bot response based on customer keywords and store inventory.
     */
    private function getBotResponse($message)
    {
        $lowerMsg = strtolower(trim($message));
        
        // Greeting intents
        if (preg_match('/\b(hi|hello|hey|greetings|good morning|good afternoon|good evening|hello bot)\b/', $lowerMsg)) {
            return "Hello! 👋 Welcome to Mera's Store support assistant. How can I help you today? You can ask me about our products, store hours, location, or payment options.";
        }
        
        // Store hours intents
        if (str_contains($lowerMsg, 'hour') || str_contains($lowerMsg, 'time') || str_contains($lowerMsg, 'open') || str_contains($lowerMsg, 'schedule') || str_contains($lowerMsg, 'close') || str_contains($lowerMsg, 'when')) {
            return "🕒 Store Hours:\nWe are open Monday to Saturday, from 8:00 AM to 6:00 PM. We are closed on Sundays to restock our amazing goods!";
        }
        
        // Location/address intents
        if (str_contains($lowerMsg, 'where') || str_contains($lowerMsg, 'location') || str_contains($lowerMsg, 'address') || str_contains($lowerMsg, 'find') || str_contains($lowerMsg, 'stall') || str_contains($lowerMsg, 'place') || str_contains($lowerMsg, 'direction')) {
            return "📍 Our Location:\nYou can find us at Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu 6052. Drop by and say hello! 😊";
        }
        
        // Payment intents
        if (str_contains($lowerMsg, 'pay') || str_contains($lowerMsg, 'payment') || str_contains($lowerMsg, 'gcash') || str_contains($lowerMsg, 'cash')) {
            return "💳 Payment Methods:\nWe accept Cash in store and GCash payments. For GCash, we have a QR code at the checkout counter. You can scan and pay securely!";
        }

        // Contact details
        if (str_contains($lowerMsg, 'contact') || str_contains($lowerMsg, 'phone') || str_contains($lowerMsg, 'call') || str_contains($lowerMsg, 'number') || str_contains($lowerMsg, 'email') || str_contains($lowerMsg, 'fb') || str_contains($lowerMsg, 'facebook')) {
            return "📞 Contact Info:\nEmail: support@meras-merchandise.com\nFacebook: fb.com/meras.merchandise\nYou can also submit an official inquiry through the 'Inquiry' tab in your app menu.";
        }

        // Product inventory search
        if (str_contains($lowerMsg, 'product') || str_contains($lowerMsg, 'item') || str_contains($lowerMsg, 'price') || str_contains($lowerMsg, 'stock') || str_contains($lowerMsg, 'inventory') || str_contains($lowerMsg, 'buy') || str_contains($lowerMsg, 'sell')) {
            // Check if there is a specific product name mentioned
            $products = Product::all();
            $matchedProduct = null;
            foreach ($products as $p) {
                if (str_contains($lowerMsg, strtolower($p->name))) {
                    $matchedProduct = $p;
                    break;
                }
            }
            
            if ($matchedProduct) {
                $stockStatus = $matchedProduct->quantity > 0 ? "🟢 In Stock ({$matchedProduct->quantity} left)" : "🔴 Out of Stock";
                return "🛍️ Product Found:\nName: " . $matchedProduct->name . "\nPrice: ₱" . number_format($matchedProduct->price, 2) . "\nStatus: " . $stockStatus . "\nDescription: " . ($matchedProduct->description ?: 'Premium merchandise item') . "\n\nYou can view and purchase this in our catalog under the 'Products' tab!";
            }
            
            // Otherwise show a few in-stock items
            $featured = Product::where('quantity', '>', 0)->limit(3)->get();
            if ($featured->count() > 0) {
                $list = "";
                foreach ($featured as $f) {
                    $list .= "• " . $f->name . " (₱" . number_format($f->price, 2) . ")\n";
                }
                return "🛍️ Store Products:\nWe have some great merchandise available! Here are a few featured items:\n" . $list . "\nBrowse our full list on the 'Products' tab inside the app!";
            }
            
            return "🛍️ Products:\nWe offer a selection of premium bags, apparel, and merchandise. You can check the 'Products' tab for pricing and stock availability!";
        }
        
        // Help or default fallback
        return "🤖 Support Assistant:\nThank you for your message! If you need live assistance, our staff has been notified and will reply here soon.\n\nIn the meantime, feel free to ask me about:\n- 🕒 Store Hours\n- 📍 Location / Address\n- 🛍️ Products / Stock\n- 💳 Payment options";
    }
}
