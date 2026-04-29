<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FamilyGroupController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PrayerController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\UpgradeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return view('welcome'); });

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
Route::get('/upgrade', [UpgradeController::class, 'index'])->middleware('auth')->name('upgrade');

// Payment
Route::middleware(['auth'])->group(function () {
    Route::post('/payment', [PaymentController::class, 'create'])->name('payment.create');
    Route::get('/payment/{order}', [PaymentController::class, 'show'])->name('payment.show');
    Route::get('/payment/{order}/status', [PaymentController::class, 'status'])->name('payment.status');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    // Import từ ảnh
    Route::get('events/import', [ImportController::class, 'index'])->name('events.import');
    Route::post('events/import/preview', [ImportController::class, 'preview'])->name('events.import.preview')->middleware('throttle:5,1');
    Route::post('events/import/confirm', [ImportController::class, 'confirm'])->name('events.import.confirm');

    // Events
    Route::resource('events', EventController::class)->except(['show']);
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');

    // Người nhận thông báo — quản lý trong màn hình ngày giỗ
    Route::resource('recipients', RecipientController::class)->except(['show']);

    // Prayers — CRUD + API lưu từ chat
    Route::get('prayers', [PrayerController::class, 'index'])->name('prayers.index');
    Route::get('prayers/{prayer}', [PrayerController::class, 'show'])->name('prayers.show');
    Route::get('prayers/{prayer}/edit', [PrayerController::class, 'edit'])->name('prayers.edit');
    Route::put('prayers/{prayer}', [PrayerController::class, 'update'])->name('prayers.update');
    Route::delete('prayers/{prayer}', [PrayerController::class, 'destroy'])->name('prayers.destroy');
    Route::post('prayers/from-chat', [PrayerController::class, 'storeFromChat'])->name('prayers.from-chat');

    // Tài liệu gia đình (RAG)
    Route::get('agent/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('agent/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::delete('agent/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('agent/documents/{document}/reprocess', [DocumentController::class, 'reprocess'])->name('documents.reprocess');

    // AI Agent
    Route::get('agent', [AgentController::class, 'index'])->name('agent.index');
    Route::get('agent/history', [AgentController::class, 'history'])->name('agent.history');
    Route::post('agent/conversation', [AgentController::class, 'conversation'])->name('agent.conversation');
    Route::post('agent/stream', [AgentController::class, 'stream'])->name('agent.stream');

    // Family groups — switch + CRUD
    Route::post('family-groups/switch', [FamilyGroupController::class, 'switch'])->name('family-groups.switch');
    Route::post('family-groups/toggle-reminder', [FamilyGroupController::class, 'toggleReminder'])->name('family-groups.toggle-reminder');
    Route::post('family-groups', [FamilyGroupController::class, 'store'])->name('family-groups.store');
    Route::put('family-groups/{familyGroup}', [FamilyGroupController::class, 'update'])->name('family-groups.update');
    Route::delete('family-groups/{familyGroup}', [FamilyGroupController::class, 'destroy'])->name('family-groups.destroy');

    // Gia phả
    Route::resource('genealogy', FamilyMemberController::class)->except(['show']);
    Route::get('genealogy-data', [FamilyMemberController::class, 'treeData'])->name('genealogy.data');
    Route::post('genealogy-save', [FamilyMemberController::class, 'treeSave'])->name('genealogy.save');
    Route::post('genealogy/{genealogy}/add-relative', [FamilyMemberController::class, 'addRelative'])->name('genealogy.add-relative');

    // Share — toggle link của group đang active (JSON API, gọi từ chat UI)
    Route::post('share/enable', [ShareController::class, 'enable'])->name('share.enable');
    Route::post('share/disable', [ShareController::class, 'disable'])->name('share.disable');
});

// Share link public — không cần auth
Route::get('s/{token}', [ShareController::class, 'show'])->name('share.public');
Route::post('s/{token}/stream', [ShareController::class, 'stream'])->name('share.stream');
Route::get('s/{token}/history', [ShareController::class, 'history'])->name('share.history');

require __DIR__.'/auth.php';
