<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\ConversationController;

// Test route to check if API is working
Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/search', [UserController::class, 'searchByQuery']);
    Route::get('/search', [UserController::class, 'search']);
    
    Route::post('/groups/create-or-get', [GroupController::class, 'createOrGet']);

    // Individual conversation (with user_id param)
    Route::get('/conversations', [ConversationController::class, 'individualOrList']);
    // Group conversation
    Route::get('/conversations/{group}', [ConversationController::class, 'groupConversation']);
    // Send message
    Route::post('/conversations', [ConversationController::class, 'store']);
    // Mark messages as read
    Route::post('/conversations/read', [ConversationController::class, 'markAsRead']);
});
