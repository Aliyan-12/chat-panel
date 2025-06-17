<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\Message;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Get or create an individual conversation and return its messages
     */
    public function individualOrList(Request $request)
    {
        $authId = auth()->id();
        $userId = $request->query('user_id');
        if ($userId) {
            // Find or create individual conversation
            $conversation = Conversation::where(function($q) use ($authId, $userId) {
                $q->where('sender_id', $authId)->where('reciever_id', $userId);
            })->orWhere(function($q) use ($authId, $userId) {
                $q->where('sender_id', $userId)->where('reciever_id', $authId);
            })->first();
            if (!$conversation) {
                $conversation = Conversation::create([
                    'sender_id' => $authId,
                    'reciever_id' => $userId,
                ]);
            }
            $messages = $conversation->messages()->with('user')->orderBy('created_at')->get();
            return response()->json([
                'conversation' => $conversation,
                'messages' => $messages
            ]);
        }
        // Optionally: return all conversations for the user
        $conversations = Conversation::where('sender_id', $authId)
            ->orWhere('reciever_id', $authId)
            ->get();
        return response()->json(['conversations' => $conversations]);
    }

    /**
     * Get a group conversation and its messages
     */
    public function groupConversation(Group $group)
    {
        $authId = auth()->id();
        if (!$group->users()->where('user_id', $authId)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $conversation = Conversation::where('group_id', $group->id)->first();
        if (!$conversation) {
            $conversation = Conversation::create([
                'group_id' => $group->id,
            ]);
        }
        $messages = $conversation->messages()->with('user')->orderBy('created_at')->get();
        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages
        ]);
    }

    /**
     * Store a new message in a conversation (individual or group)
     */
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'conversation_id' => 'required|exists:conversations,id',
        ]);
        $conversation = Conversation::findOrFail($request->conversation_id);
        $authId = auth()->id();
        // Check if user is allowed
        if ($conversation->group_id) {
            if (!$conversation->group->users()->where('user_id', $authId)->exists()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } else {
            if ($conversation->sender_id !== $authId && $conversation->reciever_id !== $authId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $authId,
            'message' => $request->message,
        ]);
        $message->load('user');
        broadcast(new NewMessage($message))->toOthers();
        return $message;
    }
} 