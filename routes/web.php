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
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
    return redirect()->to('login');
});

Route::get('/dashboard', function () {
    // dd(Auth::user()->hasRole('admin'));
    return Inertia::render('Dashboard', [
        'isAdmin' => Auth::user()->hasRole('admin'),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/chat', function () {
        return Inertia::render('Chat');
    })->name('chat');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Chat API routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Users
    Route::get('/users', function (Request $request) {
        $currentUserId = $request->user()->id;
        $users = App\Models\User::where('id', '!=', $currentUserId)->get();
        return response()->json(['users' => $users]);
    });
    
    // Search for users and groups
    Route::get('/search', function (Request $request) {
        $currentUser = $request->user();
        
        // Get all users except current user
        $users = App\Models\User::where('id', '!=', $currentUser->id)
            ->select('id', 'name', 'email')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'type' => 'user'
                ];
            });
        
        // Get all groups the current user belongs to
        $groups = $currentUser->groups()
            ->with('users')
            ->get()
            ->map(function ($group) {
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
    });
    
    // Groups
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups/create-or-get', [GroupController::class, 'createOrGet']);
    Route::resource('groups', GroupController::class)->except(['index', 'create', 'edit']);
    
    // Conversations
    Route::get('/conversations', [ApiConversationController::class, 'individualOrList']);
    Route::get('/conversations/{group}', [ApiConversationController::class, 'groupConversation']);
    Route::post('/conversations', [ApiConversationController::class, 'store']);
    Route::post('/notifications', [ApiConversationController::class, 'notifications']);
    Route::post('/conversations/read', [ApiConversationController::class, 'markAsRead']);
});

require __DIR__.'/auth.php';
