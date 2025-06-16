<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\GroupController;
use App\Models\Conversation;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::resource('groups', GroupController::class);
    Route::resource('conversations', ConversationController::class);

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
