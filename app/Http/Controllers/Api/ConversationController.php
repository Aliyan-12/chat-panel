<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
            // Unread count for this conversation
            $unread_count = $conversation->messages()
                ->where('user_id', '!=', $authId)
                ->whereNull('read_at')
                ->count();
            return response()->json([
                'conversation' => $conversation,
                'messages' => $messages,
                'unread_count' => $unread_count
            ]);
        }
        // Return all conversations for the user with unread counts
        $conversations = Conversation::where(function($q) use ($authId) {
                $q->where('sender_id', $authId)->orWhere('reciever_id', $authId);
            })
            ->orWhereHas('group.users', function($q) use ($authId) {
                $q->where('users.id', $authId);
            })
            ->with(['sender', 'reciever', 'group.users'])
            ->get()
            ->map(function($conversation) use ($authId) {
                $isGroup = $conversation->group_id !== null;
                $lastMessage = $conversation->messages()->latest()->first();
                $unreadCount = $conversation->messages()
                    ->where('user_id', '!=', $authId)
                    ->whereNull('read_at')
                    ->count();
                return [
                    'id' => $conversation->id,
                    'type' => $isGroup ? 'group' : 'individual',
                    'user' => $isGroup ? null : ($conversation->sender_id == $authId ? $conversation->reciever : $conversation->sender),
                    'group' => $isGroup ? $conversation->group : null,
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount,
                    'updated_at' => $lastMessage ? $lastMessage->created_at : $conversation->updated_at
                ];
            })
            ->sortByDesc('updated_at')
            ->values();
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
        // Unread count for this group conversation
        $unread_count = $conversation->messages()
            ->where('user_id', '!=', $authId)
            ->whereNull('read_at')
            ->count();
        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
            'unread_count' => $unread_count
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
        // dd($request->all());
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
            // $messageText = $fileNameWithTimestamp; // Only the filename
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $authId,
            'message' => $messageText,
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

    public function markAsRead(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);
        $authId = auth()->id();
        $conversation = Conversation::findOrFail($request->conversation_id);
        // Mark all unread messages from other users as read
        $conversation->messages()
            ->where('user_id', '!=', $authId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }
} 