<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        $messages = Message::orderByDesc('id')->limit(100)->get()->reverse()->values();
        $supportInquiries = Inquiry::with('respondent')->latest()->limit(8)->get();

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
        $messages = Message::orderByDesc('id')->limit(100)->get()->reverse()->values();

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
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $msg = Message::create([
            'user_name' => auth()->user()->name,
            'message' => $request->input('message'),
        ]);

        \App\Models\ActivityLog::log('chat_message', 'Sent a team chat message: "' . \Illuminate\Support\Str::limit($msg->message, 50) . '"');

        return redirect()->route('chat.index')->with('notice', 'Message sent.');
    }
}