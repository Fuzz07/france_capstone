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
            $guestMessages = collect([
                (object)[
                    'user_name' => "Mera's Support Bot",
                    'message' => "Hello! 👋 Welcome to Mera's Store support assistant. How can I help you today? Ask me about products, store hours, location, or payment options!",
                    'created_at' => now(),
                ]
            ]);

            return view('chat.index', [
                'messages' => $guestMessages,
                'supportInquiries' => collect(),
                'messagesToday' => 0,
                'totalMessages' => 0,
                'pendingInquiries' => 0,
                'respondedToday' => 0,
            ]);
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
     * AJAX Endpoint for Chatbot Widget (Supports Guest & Authenticated Users)
     */
    public function botResponse(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $userMessage = trim($request->input('message'));
        $userName = auth()->check() ? auth()->user()->name : 'Guest Customer';

        // Log message if user is authenticated
        if (auth()->check()) {
            Message::create([
                'user_name' => $userName,
                'message' => $userMessage,
            ]);
            \App\Models\ActivityLog::log('chat_message', 'Sent chatbot message: "' . \Illuminate\Support\Str::limit($userMessage, 50) . '"');
        }

        $botData = $this->getDetailedBotResponse($userMessage);

        // Save bot response to conversation if user is logged in
        if (auth()->check()) {
            Message::create([
                'user_name' => "Mera's Support Bot",
                'message' => $botData['reply'],
            ]);
        }

        return response()->json([
            'success' => true,
            'user_name' => $userName,
            'reply' => $botData['reply'],
            'suggestions' => $botData['suggestions'],
            'products' => $botData['products'] ?? [],
        ]);
    }

    /**
     * Generate an intelligent, helpful bot response based on customer keywords and store inventory.
     */
    private function getBotResponse($message)
    {
        $data = $this->getDetailedBotResponse($message);
        return $data['reply'];
    }

    /**
     * Generate structured bot response with reply, product matches, and suggested chips.
     */
    private function getDetailedBotResponse($message)
    {
        $lowerMsg = strtolower(trim($message));
        $defaultSuggestions = ["Store Hours 🕒", "Location 📍", "Check Products 🛍️", "Payment Methods 💳"];
        
        // Greeting intents
        if (preg_match('/\b(hi|hello|hey|greetings|good morning|good afternoon|good evening|hello bot|help|start)\b/', $lowerMsg)) {
            return [
                'reply' => "Hello! 👋 Welcome to Mera's Merchandise support assistant. How can I help you today? You can ask me about our products, store hours, location, or payment options.",
                'suggestions' => $defaultSuggestions,
            ];
        }
        
        // Store hours intents
        if (str_contains($lowerMsg, 'hour') || str_contains($lowerMsg, 'time') || str_contains($lowerMsg, 'open') || str_contains($lowerMsg, 'schedule') || str_contains($lowerMsg, 'close') || str_contains($lowerMsg, 'when')) {
            return [
                'reply' => "🕒 Store Hours:\nWe are open Monday to Saturday, from 8:00 AM to 6:00 PM. We are closed on Sundays to restock our goods!",
                'suggestions' => ["Location 📍", "Check Products 🛍️", "Contact Details 📞"],
            ];
        }
        
        // Location/address intents
        if (str_contains($lowerMsg, 'where') || str_contains($lowerMsg, 'location') || str_contains($lowerMsg, 'address') || str_contains($lowerMsg, 'find') || str_contains($lowerMsg, 'stall') || str_contains($lowerMsg, 'place') || str_contains($lowerMsg, 'direction')) {
            return [
                'reply' => "📍 Our Location:\nYou can find us at Stall No. 18, Bantayan Public Market, Suba, Bantayan, Cebu 6052. Drop by and say hello! 😊",
                'suggestions' => ["Store Hours 🕒", "Check Products 🛍️", "Payment Methods 💳"],
            ];
        }
        
        // Payment intents
        if (str_contains($lowerMsg, 'pay') || str_contains($lowerMsg, 'payment') || str_contains($lowerMsg, 'gcash') || str_contains($lowerMsg, 'cash')) {
            return [
                'reply' => "💳 Payment Methods:\nWe accept Cash in store and GCash payments. For GCash, we have a QR code at the checkout counter. You can scan and pay securely!",
                'suggestions' => ["Store Hours 🕒", "Location 📍", "Check Products 🛍️"],
            ];
        }

        // Contact details
        if (str_contains($lowerMsg, 'contact') || str_contains($lowerMsg, 'phone') || str_contains($lowerMsg, 'call') || str_contains($lowerMsg, 'number') || str_contains($lowerMsg, 'email') || str_contains($lowerMsg, 'fb') || str_contains($lowerMsg, 'facebook')) {
            return [
                'reply' => "📞 Contact Info:\nEmail: support@meras-merchandise.com\nFacebook: fb.com/meras.merchandise\nYou can also submit an inquiry through our Contact page or Inquiry form.",
                'suggestions' => ["Location 📍", "Check Products 🛍️", "Store Hours 🕒"],
            ];
        }

        // Product inventory search
        if (str_contains($lowerMsg, 'product') || str_contains($lowerMsg, 'item') || str_contains($lowerMsg, 'price') || str_contains($lowerMsg, 'stock') || str_contains($lowerMsg, 'inventory') || str_contains($lowerMsg, 'buy') || str_contains($lowerMsg, 'sell') || str_contains($lowerMsg, 'fabric') || str_contains($lowerMsg, 'bag') || str_contains($lowerMsg, 'supply') || str_contains($lowerMsg, 'notebook') || str_contains($lowerMsg, 'pen')) {
            $products = Product::all();
            $matchedProducts = [];
            
            foreach ($products as $p) {
                if (str_contains($lowerMsg, strtolower($p->name)) || (isset($p->category) && str_contains($lowerMsg, strtolower($p->category)))) {
                    $matchedProducts[] = $p;
                }
            }

            if (count($matchedProducts) > 0) {
                $productListMsg = "🛍️ Matching Products Found:\n";
                $productItems = [];
                foreach (array_slice($matchedProducts, 0, 4) as $p) {
                    $status = $p->quantity > 10 ? "🟢 In Stock" : ($p->quantity > 0 ? "🟡 Low Stock ({$p->quantity} left)" : "🔴 Out of Stock");
                    $productListMsg .= "• {$p->name} — ₱" . number_format($p->price, 2) . " ({$status})\n";
                    $productItems[] = [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => '₱' . number_format($p->price, 2),
                        'quantity' => $p->quantity,
                        'status' => $status,
                        'category' => $p->category ?? 'Merchandise',
                    ];
                }
                $productListMsg .= "\nFeel free to ask about another item or view all items in our catalog!";
                
                return [
                    'reply' => $productListMsg,
                    'suggestions' => ["Store Hours 🕒", "Location 📍", "Payment Methods 💳"],
                    'products' => $productItems,
                ];
            }

            // Featured items if no direct search match
            $featured = Product::where('quantity', '>', 0)->limit(4)->get();
            $featuredMsg = "🛍️ Featured Store Products:\n";
            $productItems = [];
            foreach ($featured as $f) {
                $status = "🟢 In Stock (" . $f->quantity . " left)";
                $featuredMsg .= "• {$f->name} — ₱" . number_format($f->price, 2) . "\n";
                $productItems[] = [
                    'id' => $f->id,
                    'name' => $f->name,
                    'price' => '₱' . number_format($f->price, 2),
                    'quantity' => $f->quantity,
                    'status' => $status,
                    'category' => $f->category ?? 'Merchandise',
                ];
            }
            $featuredMsg .= "\nYou can also search for a specific product by name!";

            return [
                'reply' => $featuredMsg,
                'suggestions' => ["Store Hours 🕒", "Location 📍", "Payment Methods 💳"],
                'products' => $productItems,
            ];
        }

        // Default fallback
        return [
            'reply' => "🤖 Support Assistant:\nThank you for your message! If you need specific assistance, you can ask about our products, store hours, location, or payment options.\n\nOur staff has also been notified and will be happy to assist you!",
            'suggestions' => $defaultSuggestions,
        ];
    }
}
