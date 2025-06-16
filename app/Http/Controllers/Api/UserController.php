<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get all users except the authenticated user
     */
    public function index()
    {
        return User::where('id', '!=', auth()->id())->get();
    }
} 