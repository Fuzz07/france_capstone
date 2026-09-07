<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /** Live Messenger account customers are handed off to when the bot is not enough. */
    private const MESSENGER_URL = 'https://web.facebook.com/profile.php?id=100063831981795&rdid=J0BCP6eqTK2B8p6d&share_url=https%3A%2F%2Fweb.facebook.com%2Fshare%2F1Bqoxp5CiZ%2F%3F_rdc%3D1%26_rdr#';
    private const MESSENGER_NAME = "Mera's Merchandise";

    /** Number of customer messages after which the Messenger handoff is offered. */
    private const HANDOFF_AFTER_MESSAGES = 3;

    /**
     * Count the customer's messages and decide whether it is time to offer a live
     * Messenger handoff. Offered once per session; the bot keeps answering after it.
     */
    private function resolveHandoff(Request $request): ?array
    {
        $sent = $request->session()->increment('chatbot_customer_messages');

        if ($sent < self::HANDOFF_AFTER_MESSAGES || $request->session()->get('chatbot_handoff_offered')) {
            return null;
        }

        $request->session()->put('chatbot_handoff_offered', true);

        return [
            'name' => self::MESSENGER_NAME,
            'url' => self::MESSENGER_URL,
            'label' => 'Chat live on Messenger',
            'text' => "💬 Want to talk to a real person?\nYou have asked a few questions already, so " . self::MESSENGER_NAME . " can help you live on Messenger for anything I miss.",
        ];
    }

    public function index()
    {
        if (!auth()->check()) {
            $guestMessages = collect([
                (object) [
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

            if ($handoff = $this->resolveHandoff($request)) {
                Message::create([
                    'user_name' => "Mera's Support Bot",
                    'message' => $handoff['text'] . "\n" . $handoff['url'],
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
        $handoff = $this->resolveHandoff($request);

        // Save bot response to conversation if user is logged in
        if (auth()->check()) {
            Message::create([
                'user_name' => "Mera's Support Bot",
                'message' => $botData['reply'],
            ]);

            if ($handoff) {
                Message::create([
                    'user_name' => "Mera's Support Bot",
                    'message' => $handoff['text'] . "\n" . $handoff['url'],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'user_name' => $userName,
            'reply' => $botData['reply'],
            'suggestions' => $botData['suggestions'],
            'products' => $botData['products'] ?? [],
            'handoff' => $handoff,
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
        $hasProductIntent = str_contains($lowerMsg, 'product') || str_contains($lowerMsg, 'item') || str_contains($lowerMsg, 'price')
            || str_contains($lowerMsg, 'stock') || str_contains($lowerMsg, 'quantity') || str_contains($lowerMsg, 'qty')
            || str_contains($lowerMsg, 'how much') || str_contains($lowerMsg, 'how many') || str_contains($lowerMsg, 'cost')
            || str_contains($lowerMsg, 'available') || str_contains($lowerMsg, 'availability') || str_contains($lowerMsg, 'left')
            || str_contains($lowerMsg, 'inventory') || str_contains($lowerMsg, 'buy') || str_contains($lowerMsg, 'sell')
            || str_contains($lowerMsg, 'fabric') || str_contains($lowerMsg, 'bag') || str_contains($lowerMsg, 'supply')
            || str_contains($lowerMsg, 'notebook') || str_contains($lowerMsg, 'pen') || str_contains($lowerMsg, 'order')
            || str_contains($lowerMsg, 'eraser') || str_contains($lowerMsg, 'pencil') || str_contains($lowerMsg, 'faber');

        $allProducts = Product::all();
        $matchedProducts = $this->searchProductsByRelevance($lowerMsg, $allProducts);

        if ($hasProductIntent || count($matchedProducts) > 0) {
            if (count($matchedProducts) > 0) {
                $productItems = [];
                $isSingle = count($matchedProducts) === 1;

                if ($isSingle) {
                    $p = $matchedProducts[0];
                    $unit = $p->unit ?: 'pcs';
                    $status = $p->quantity > 10 ? "🟢 In Stock" : ($p->quantity > 0 ? "🟡 Low Stock ({$p->quantity} left)" : "🔴 Out of Stock");

                    $productListMsg = "🛍️ {$p->name}:\n"
                        . "• Price: ₱" . number_format($p->price, 2) . " / {$unit}\n"
                        . "• Quantity: {$p->quantity} {$unit} ({$status})\n";

                    if ($p->hasBulkPricing()) {
                        $productListMsg .= "• Bulk Price: ₱" . number_format($p->bulk_price, 2) . " (at {$p->bulk_min_qty}+ {$unit})\n";
                    }
                    $productListMsg .= "\n👉 Click on the product card below to view and purchase this item directly in the catalog!";

                    $productItems[] = [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => '₱' . number_format($p->price, 2),
                        'quantity' => $p->quantity,
                        'unit' => $unit,
                        'status' => $status,
                        'category' => $p->category ?? 'Merchandise',
                        'url' => route('home') . '#product-' . $p->id,
                    ];
                } else {
                    $productListMsg = "🛍️ Matching Products Found:\n";
                    foreach (array_slice($matchedProducts, 0, 4) as $p) {
                        $unit = $p->unit ?: 'pcs';
                        $status = $p->quantity > 10 ? "🟢 In Stock" : ($p->quantity > 0 ? "🟡 Low Stock ({$p->quantity} left)" : "🔴 Out of Stock");
                        $productListMsg .= "• {$p->name} — ₱" . number_format($p->price, 2) . " ({$p->quantity} {$unit} left, {$status})\n";
                        $productItems[] = [
                            'id' => $p->id,
                            'name' => $p->name,
                            'price' => '₱' . number_format($p->price, 2),
                            'quantity' => $p->quantity,
                            'unit' => $unit,
                            'status' => $status,
                            'category' => $p->category ?? 'Merchandise',
                            'url' => route('home') . '#product-' . $p->id,
                        ];
                    }
                    $productListMsg .= "\n👉 Click on any item below to view it directly in our catalog!";
                }

                return [
                    'reply' => $productListMsg,
                    'suggestions' => ["Store Hours 🕒", "Location 📍", "Payment Methods 💳"],
                    'products' => $productItems,
                ];
            }

            // Featured items if general product intent without specific match
            $featured = Product::where('quantity', '>', 0)->limit(4)->get();
            $featuredMsg = "🛍️ Featured Store Products:\n";
            $productItems = [];
            foreach ($featured as $f) {
                $unit = $f->unit ?: 'pcs';
                $status = "🟢 In Stock (" . $f->quantity . " " . $unit . " left)";
                $featuredMsg .= "• {$f->name} — ₱" . number_format($f->price, 2) . "\n";
                $productItems[] = [
                    'id' => $f->id,
                    'name' => $f->name,
                    'price' => '₱' . number_format($f->price, 2),
                    'quantity' => $f->quantity,
                    'unit' => $unit,
                    'status' => $status,
                    'category' => $f->category ?? 'Merchandise',
                    'url' => route('home') . '#product-' . $f->id,
                ];
            }
            $featuredMsg .= "\n👉 Click on any product below to view it, or ask about a specific item by name!";

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

    /**
     * Search products using intelligent multi-term relevance scoring and typo tolerance.
     * Accurately ranks specific items (e.g. "Faber-Castell Eraser" for "faber castel eraser")
     * first on the list ahead of general brand or category matches. Applicable to all queries.
     */
    private function searchProductsByRelevance(string $message, $allProducts): array
    {
        $normalizedMsg = strtolower(trim($message));
        $cleanMsg = preg_replace('/[^a-z0-9 ]+/', ' ', $normalizedMsg);
        $cleanMsg = preg_replace('/\s+/', ' ', trim($cleanMsg ?? ''));

        if (empty($cleanMsg)) {
            return [];
        }

        // Noise / Stop words to exclude from keyword comparison
        $stopWords = [
            'do', 'you', 'have', 'are', 'there', 'any', 'is', 'the', 'a', 'an', 'in', 'on', 'at',
            'to', 'for', 'of', 'with', 'and', 'or', 'can', 'i', 'get', 'show', 'me', 'please',
            'want', 'looking', 'find', 'buy', 'sell', 'how', 'much', 'many', 'cost', 'price',
            'pricing', 'available', 'availability', 'stock', 'store', 'product', 'products',
            'item', 'items', 'merchandise', 'what', 'which', 'who', 'where', 'when', 'why',
            'check', 'search', 'give', 'tell', 'about', 'need', 'would', 'like'
        ];
        $stopWordsLookup = array_flip($stopWords);

        $rawTokens = explode(' ', $cleanMsg);
        $queryTokens = [];
        foreach ($rawTokens as $tok) {
            $tok = trim($tok);
            if (strlen($tok) >= 2 && !isset($stopWordsLookup[$tok])) {
                $queryTokens[] = $tok;
            }
        }

        if (empty($queryTokens)) {
            return [];
        }

        $totalTokens = count($queryTokens);
        $scored = [];

        foreach ($allProducts as $p) {
            $pNameNorm = preg_replace('/[^a-z0-9 ]+/', ' ', strtolower($p->name));
            $pNameNorm = preg_replace('/\s+/', ' ', trim($pNameNorm ?? ''));
            $pNameWords = explode(' ', $pNameNorm);

            $pSkuNorm = preg_replace('/[^a-z0-9 ]+/', ' ', strtolower($p->sku ?? ''));
            $pCatNorm = preg_replace('/[^a-z0-9 ]+/', ' ', strtolower($p->category ?? ''));
            $pCatWords = explode(' ', trim($pCatNorm ?? ''));

            $score = 0;
            $matchedTokensCount = 0;

            // 1. Exact / Substring phrase match bonus
            if (!empty($pNameNorm) && str_contains($pNameNorm, $cleanMsg)) {
                $score += 150;
            } elseif (!empty($cleanMsg) && str_contains($cleanMsg, $pNameNorm)) {
                $score += 100;
            }

            // 2. Token-by-token matching
            foreach ($queryTokens as $qToken) {
                $qLen = strlen($qToken);
                $tokenMatched = false;
                $tokenScore = 0;

                // Match against Product Name words
                foreach ($pNameWords as $pWord) {
                    if (empty($pWord)) continue;

                    if ($qToken === $pWord) {
                        // Exact word match in product name
                        $tokenScore = max($tokenScore, 40);
                        $tokenMatched = true;
                        break;
                    } elseif (str_starts_with($pWord, $qToken) || str_starts_with($qToken, $pWord)) {
                        // Prefix match
                        $tokenScore = max($tokenScore, 28);
                        $tokenMatched = true;
                    } elseif ($qLen >= 4) {
                        // Typo tolerance: Levenshtein distance <= 1 for 4-6 char words, <= 2 for 7+ chars
                        $dist = levenshtein($qToken, $pWord);
                        $maxDist = ($qLen >= 7) ? 2 : 1;
                        if ($dist <= $maxDist) {
                            $tokenScore = max($tokenScore, 26);
                            $tokenMatched = true;
                        }
                    }
                }

                // Match against SKU if not matched in name
                if (!$tokenMatched && !empty($pSkuNorm)) {
                    if (str_contains($pSkuNorm, $qToken)) {
                        $tokenScore = max($tokenScore, 20);
                        $tokenMatched = true;
                    }
                }

                // Match against Category if not matched in name or SKU
                if (!$tokenMatched && !empty($pCatWords)) {
                    foreach ($pCatWords as $cWord) {
                        if (empty($cWord)) continue;
                        if ($qToken === $cWord || str_starts_with($cWord, $qToken)) {
                            $tokenScore = max($tokenScore, 14);
                            $tokenMatched = true;
                            break;
                        }
                    }
                }

                if ($tokenMatched) {
                    $matchedTokensCount++;
                    $score += $tokenScore;
                }
            }

            if ($matchedTokensCount > 0) {
                // Completeness multiplier: reward items that match all or more of the search terms
                $coverage = $matchedTokensCount / $totalTokens;
                $score = $score * (0.4 + 0.6 * $coverage);

                if ($matchedTokensCount === $totalTokens) {
                    $score += 90; // All requested keywords are present!
                }

                // In-stock preference
                if ($p->quantity > 0) {
                    $score += 3;
                }

                if ($score >= 18) {
                    $scored[] = [
                        'product' => $p,
                        'score' => $score,
                        'matched_count' => $matchedTokensCount,
                    ];
                }
            }
        }

        // Sort descending by score, then matched token count, then quantity
        usort($scored, function ($a, $b) {
            if ($b['score'] != $a['score']) {
                return ($b['score'] > $a['score']) ? 1 : -1;
            }
            if ($b['matched_count'] != $a['matched_count']) {
                return $b['matched_count'] - $a['matched_count'];
            }
            return $b['product']->quantity - $a['product']->quantity;
        });

        return array_map(fn($item) => $item['product'], $scored);
    }
}
