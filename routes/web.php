<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutoReplyController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageLogController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Auth Routes (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Logout Route (Auth Only)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes (Auth Only)
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts');
    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::post('/contacts/import', [ContactController::class, 'importCsv'])->name('contacts.import');
    Route::post('/contacts/bulk-delete', [ContactController::class, 'bulkDelete'])->name('contacts.bulk-delete');
    Route::post('/contacts/bulk-add-group', [ContactController::class, 'bulkAddGroup'])->name('contacts.bulk-add-group');
    Route::post('/contacts/bulk-broadcast', [ContactController::class, 'bulkBroadcast'])->name('contacts.bulk-broadcast');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::post('/contacts/{contact}/send-message', [ContactController::class, 'sendMessage'])->name('contacts.send-message');

    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::post('/campaigns/{campaign}/toggle', [CampaignController::class, 'toggle'])->name('campaigns.toggle');
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');

    Route::get('/reports', [MessageLogController::class, 'index'])->name('reports');
    Route::get('/reports/export', [MessageLogController::class, 'exportCsv'])->name('reports.export');

    Route::get('/auto-reply', [AutoReplyController::class, 'index'])->name('auto-reply');
    Route::post('/auto-reply/{autoReply}/toggle', [AutoReplyController::class, 'toggle'])->name('auto-reply.toggle');
    Route::post('/auto-reply', [AutoReplyController::class, 'store'])->name('auto-reply.store');
    Route::put('/auto-reply/{autoReply}', [AutoReplyController::class, 'update'])->name('auto-reply.update');
    Route::delete('/auto-reply/{autoReply}', [AutoReplyController::class, 'destroy'])->name('auto-reply.destroy');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings');
    Route::post('/settings/device', [SettingController::class, 'storeDevice'])->name('settings.device.store');
    Route::put('/settings/device/{device}', [SettingController::class, 'updateDevice'])->name('settings.device.update');
    Route::delete('/settings/device/{device}', [SettingController::class, 'deleteDevice'])->name('settings.device.delete');
    Route::delete('/settings/device/{device}/disconnect', [SettingController::class, 'disconnectDevice'])->name('settings.device.disconnect');

    Route::get('/templates', [MessageTemplateController::class, 'index'])->name('templates');
    Route::post('/templates', [MessageTemplateController::class, 'store'])->name('templates.store');
    Route::put('/templates/{template}', [MessageTemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [MessageTemplateController::class, 'destroy'])->name('templates.destroy');

    Route::get('/blacklist', [BlacklistController::class, 'index'])->name('blacklist');
    Route::post('/blacklist', [BlacklistController::class, 'store'])->name('blacklist.store');
    Route::delete('/blacklist/{blacklist}', [BlacklistController::class, 'destroy'])->name('blacklist.destroy');
});

// Webhook Routes (Public)
Route::match(['get', 'post'], '/webhook/fonnte/device', [WebhookController::class, 'device'])->name('webhook.fonnte.device');
Route::match(['get', 'post'], '/webhook/fonnte/message', [WebhookController::class, 'message'])->name('webhook.fonnte.message');
