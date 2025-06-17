<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Group;
use App\Models\Conversation;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('groups.{group}', function ($user, Group $group) {
    return $group->users()->where('user_id', $user->id)->exists();
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::findOrFail($conversationId);
    
    // For group conversations
    if ($conversation->group_id) {
        $group = Group::findOrFail($conversation->group_id);
        return $group->users()->where('user_id', $user->id)->exists();
    }
    
    // For direct message conversations
    return $conversation->sender_id == $user->id || $conversation->reciever_id == $user->id;
});
