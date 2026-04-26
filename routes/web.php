<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\RecipientController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Events
    Route::resource('events', EventController::class)->except(['show']);
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::post('events/{event}/recipients/{recipient}', [EventController::class, 'attachRecipient'])->name('events.recipients.attach');
    Route::delete('events/{event}/recipients/{recipient}', [EventController::class, 'detachRecipient'])->name('events.recipients.detach');

    // Recipients
    Route::resource('recipients', RecipientController::class)->except(['show']);

    // AI Agent
    Route::get('/agent', [AgentController::class, 'index'])->name('agent.index');
    Route::post('/agent/conversation', [AgentController::class, 'conversation'])->name('agent.conversation');
    Route::post('/agent/stream', [AgentController::class, 'stream'])->name('agent.stream');
});

require __DIR__.'/auth.php';
