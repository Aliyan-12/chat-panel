<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\Api\UserController as ApiUserController;
use App\Http\Controllers\Api\GroupController as ApiGroupController;
use App\Http\Controllers\Api\ConversationController as ApiConversationController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/chat', function () {
        return Inertia::render('Chat');
    })->name('chat');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
});

Route::middleware('auth')->group(function () {
    Route::resource('groups', GroupController::class);
    Route::resource('conversations', ConversationController::class);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/users', [ApiUserController::class, 'index']);
    Route::get('/search', [ApiUserController::class, 'search']);
    
    Route::post('/groups/create-or-get', [ApiGroupController::class, 'createOrGet']);

    Route::get('/conversations/{conversation}', [ApiConversationController::class, 'index']);
    Route::post('/conversations', [ApiConversationController::class, 'store']); 
});

require __DIR__.'/auth.php';
