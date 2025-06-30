<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * Get individual conversation with a user or list all conversations
     */
    public function individualOrList(Request $request)
    {
        $currentUserId = Auth::id();
        
        // If user_id is provided, get or create a conversation with that user
        if ($request->has('user_id')) {
            $otherUserId = $request->user_id;
            
            // Check if a conversation already exists between these two users
            $conversation = Conversation::where(function($query) use ($currentUserId, $otherUserId) {
                $query->where('sender_id', $currentUserId)
                      ->where('reciever_id', $otherUserId);
            })->orWhere(function($query) use ($currentUserId, $otherUserId) {
                $query->where('sender_id', $otherUserId)
                      ->where('reciever_id', $currentUserId);
            })->first();
            
            // If no conversation exists, create one
            if (!$conversation) {
                $conversation = Conversation::create([
                    'sender_id' => $currentUserId,
                    'reciever_id' => $otherUserId,
                ]);
            }
            
            // Get messages for this conversation
            $messages = Message::where('conversation_id', $conversation->id)
                ->with('user')
                ->orderBy('created_at')
                ->get();
            
            // Get the other user's details
            $otherUser = User::find($otherUserId);
            
            return response()->json([
                'conversation' => $conversation,
                'messages' => $messages,
                'other_user' => $otherUser
            ]);
        }
        
        // Otherwise, return all conversations for the current user
        $conversations = Conversation::where('sender_id', $currentUserId)
            ->orWhere('reciever_id', $currentUserId)
            ->with(['sender', 'reciever'])
            ->get()
            ->map(function($conversation) use ($currentUserId) {
                // Determine the other user in the conversation
                $otherUser = $conversation->sender_id == $currentUserId 
                    ? $conversation->reciever 
                    : $conversation->sender;
                
                // Get the latest message
                $latestMessage = Message::where('conversation_id', $conversation->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                return [
                    'id' => $conversation->id,
                    'other_user' => $otherUser,
                    'latest_message' => $latestMessage,
                    'created_at' => $conversation->created_at,
                    'updated_at' => $conversation->updated_at,
                ];
            });
        
        return response()->json($conversations);
    }
    
    /**
     * Get conversation for a group
     */
    public function groupConversation($groupId)
    {
        $group = Group::with('users')->findOrFail($groupId);
        
        // Make sure the current user is part of this group
        if (!$group->users->contains(Auth::id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Get the conversation for this group
        $conversation = Conversation::where('group_id', $groupId)->first();
        
        // If no conversation exists for this group, create one
        if (!$conversation) {
            $conversation = Conversation::create([
                'group_id' => $groupId
            ]);
        }
        
        // Get messages for this conversation
        $messages = Message::where('conversation_id', $conversation->id)
            ->with('user')
            ->orderBy('created_at')
            ->get();
        
        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
            'group' => $group
        ]);
    }

    /**
     * Store a newly created message
     */
    public function store(Request $request)
    {
        \Log::info('Web Controller - Request data:', $request->all());
        \Log::info('Web Controller - Has file:', ['hasFile' => $request->hasFile('file')]);
        
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'nullable',  // Removed string validation to allow HTML content
            'file' => 'nullable|file|max:10240', // max 10MB
        ]);
        
        // Ensure at least one of message or file is present
        if (!$request->has('message') && !$request->hasFile('file')) {
            return response()->json(['message' => 'Either message or file is required'], 422);
        }
        
        $conversation = Conversation::findOrFail($request->conversation_id);
        $currentUserId = Auth::id();
        
        // Check if user is authorized to send messages in this conversation
        if ($conversation->group_id) {
            // For group conversations, check if user is part of the group
            $group = Group::findOrFail($conversation->group_id);
            if (!$group->users->contains($currentUserId)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            // For individual conversations, check if user is sender or receiver
            if ($conversation->sender_id != $currentUserId && $conversation->reciever_id != $currentUserId) {
                return response()->json(['error' => 'Unauthorized'], 403);
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
        
        // Create the message
        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'user_id' => $currentUserId,
            'message' => $originalName ?? $messageText,
            'type' => $type,
            'file_path' => $filePath,
        ]);
        
        // Load the user relationship
        $message->load('user');
        
        // Update the conversation's updated_at timestamp
        $conversation->touch();
        
        // Broadcast the new message event
        broadcast(new NewMessage($message))->toOthers();
        
        return response()->json($message);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
