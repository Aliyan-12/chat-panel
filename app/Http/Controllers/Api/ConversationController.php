<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Group;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Get all conversations for a specific group
     */
    public function index(Group $group)
    {
        // Check if the authenticated user is a member of the group
        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return Conversation::with('user')
            ->where('group_id', $group->id)
            ->orderBy('created_at')
            ->get();
    }
    
    /**
     * Store a new conversation message
     */
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'group_id' => 'required|exists:groups,id',
        ]);
        
        // Check if the authenticated user is a member of the group
        $group = Group::findOrFail($request->group_id);
        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $conversation = Conversation::create([
            'message' => $request->message,
            'group_id' => $request->group_id,
            'user_id' => auth()->id(),
        ]);
        
        $conversation->load('user');
        
        broadcast(new NewMessage($conversation))->toOthers();
        
        return $conversation;
    }
} 