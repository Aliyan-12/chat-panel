<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $groups = $user->groups()->with('users')->get();
        
        return response()->json($groups);
    }

    /**
     * Create a new group or get an existing one-on-one group
     */
    public function createOrGet(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'sometimes|exists:users,id',
            'user_id' => 'sometimes|exists:users,id',
        ]);
        
        $currentUserId = Auth::id();
        
        // Case 1: Creating a group chat with multiple users
        if ($request->has('user_ids')) {
            // Make sure we have a group name
            if (!$request->has('name') || empty($request->name)) {
                return response()->json(['error' => 'Group name is required'], 422);
            }
            
            // Add the current user to the list if not already included
            $userIds = collect($request->user_ids);
            if (!$userIds->contains($currentUserId)) {
                $userIds->push($currentUserId);
            }
            
            // Create the group
            $group = Group::create([
                'name' => $request->name,
            ]);
            
            // Add users to the group
            $group->users()->attach($userIds);
            
            // Create a conversation for this group
            $conversation = Conversation::create([
                'group_id' => $group->id,
            ]);
            
            // Load the users relationship
            $group->load('users');
            
            return response()->json([
                'group' => $group,
                'conversation' => $conversation
            ]);
        }
        
        // Case 2: Creating or getting a one-on-one chat
        if ($request->has('user_id')) {
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
                $existingGroup = Group::with('users')->findOrFail($group->group_id);
                return response()->json($existingGroup);
            }
            
            // Create a new group for one-on-one chat
            $newGroup = Group::create([
                'name' => 'Chat',
            ]);
            
            // Add both users to the group
            $newGroup->users()->attach([$currentUserId, $otherUserId]);
            
            // Create a conversation for this group
            $conversation = Conversation::create([
                'group_id' => $newGroup->id,
            ]);
            
            return response()->json($newGroup->load('users'));
        }
        
        return response()->json(['error' => 'Invalid request parameters'], 422);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);
        
        $currentUserId = Auth::id();
        
        // Add the current user to the list if not already included
        $userIds = collect($request->user_ids);
        if (!$userIds->contains($currentUserId)) {
            $userIds->push($currentUserId);
        }
        
        // Create the group
        $group = Group::create([
            'name' => $request->name,
        ]);
        
        // Add users to the group
        $group->users()->attach($userIds);
        
        // Load the users relationship
        $group->load('users');
        
        return response()->json($group);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $group = Group::with('users')->findOrFail($id);
        
        // Make sure the current user is part of this group
        if (!$group->users->contains(Auth::id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return response()->json($group);
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
        $group = Group::findOrFail($id);
        
        // Make sure the current user is part of this group
        if (!$group->users->contains(Auth::id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'exists:users,id',
        ]);
        
        if ($request->has('name')) {
            $group->name = $request->name;
            $group->save();
        }
        
        if ($request->has('user_ids')) {
            // Make sure the current user stays in the group
            $userIds = collect($request->user_ids);
            if (!$userIds->contains(Auth::id())) {
                $userIds->push(Auth::id());
            }
            
            // Update the users in the group
            $group->users()->sync($userIds);
        }
        
        // Load the users relationship
        $group->load('users');
        
        return response()->json($group);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $group = Group::findOrFail($id);
        
        // Make sure the current user is part of this group
        if (!$group->users->contains(Auth::id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Delete the group
        $group->delete();
        
        return response()->json(['message' => 'Group deleted successfully']);
    }
}
