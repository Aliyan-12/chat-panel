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
        \Log::info('Request data:', $request->all());
        \Log::info('Has file:', ['hasFile' => $request->hasFile('file')]);
        
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // max 10MB
        ]);
        
        // Ensure at least one of message or file is present
        if (!$request->has('message') && !$request->hasFile('file')) {
            return response()->json(['message' => 'Either message or file is required'], 422);
        }
        
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

        $type = 'text';
        $filePath = null;
        $messageText = $request->message;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileNameWithTimestamp = date('YmdHis') . '_' . pathinfo($originalName, PATHINFO_FILENAME) . '.' . $extension;
            $filePath = $file->storeAs('chat_files', $fileNameWithTimestamp, 'public');
            $type = 'file';
            $messageText = $fileNameWithTimestamp; // Only the filename
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $authId,
            'message' => $originalName ?? $messageText,
            'type' => $type,
            'file_path' => $filePath,
        ]);
        $message->load('user');
        broadcast(new NewMessage($message))->toOthers();

        // Send notification to recipient(s)
        if ($conversation->group_id) {
            // Group chat: notify all group members except sender
            $groupUsers = $conversation->group->users()->where('users.id', '!=', $authId)->get();
            // dd($groupUsers);
            foreach ($groupUsers as $user) {
                $user->notify(new \App\Notifications\NewMessageNotification($message));
            }
        } else {
            // One-to-one chat: notify the other user
            $recipientId = $conversation->sender_id == $authId ? $conversation->reciever_id : $conversation->sender_id;
            $recipient = \App\Models\User::find($recipientId);
            if ($recipient) {
                $recipient->notify(new \App\Notifications\NewMessageNotification($message));
            }
        }

        return $message;
    }

    public function notifications()
    {
        $notifications = auth()->user()->notifications;
        return response()->json($notifications);
    }
} 