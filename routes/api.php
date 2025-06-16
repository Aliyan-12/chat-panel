<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\ConversationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/users', [UserController::class, 'index']);
    
    Route::post('/groups/create-or-get', [GroupController::class, 'createOrGet']);
    
    Route::get('/conversations/{group}', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
});
