<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Get all users (except the authenticated user) and all groups
     */
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $users = User::where('id', '!=', auth()->id())
            ->select('id', 'name', 'email')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                    'type' => 'user'
                ];
            });
        $groups = Group::with('users')->get()->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'users' => $group->users,
                'type' => 'group'
            ];
        });
        return [
            'users' => $users,
            'groups' => $groups
        ];
    }
    
    /**
     * Get all users (except the authenticated user) and groups the user belongs to
     */
    public function search(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $currentUser = auth()->user();
        $users = User::where('id', '!=', $currentUser->id)
            ->select('id', 'name', 'email')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                    'type' => 'user'
                ];
            });
        $groups = $currentUser->groups()->with('users')->get()->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'users' => $group->users,
                'type' => 'group'
            ];
        });
        return [
            'users' => $users,
            'groups' => $groups
        ];
    }
} 