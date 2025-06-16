<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    /**
     * Create a new group with the authenticated user and the specified user,
     * or return an existing group if one already exists.
     */
    public function createOrGet(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $currentUserId = auth()->id();
        $otherUserId = $request->user_id;
        
        // Check if a group already exists with just these two users
        $group = DB::table('group_user')
            ->select('group_id')
            ->whereIn('user_id', [$currentUserId, $otherUserId])
            ->groupBy('group_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 2')
            ->havingRaw('COUNT(*) = 2')
            ->first();
        
        if ($group) {
            return Group::with('users')->findOrFail($group->group_id);
        }
        
        // Create a new group
        $newGroup = Group::create([
            'name' => 'Chat',
        ]);
        
        // Add both users to the group
        $newGroup->users()->attach([$currentUserId, $otherUserId]);
        
        return $newGroup->load('users');
    }
} 