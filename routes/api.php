<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.token')->group(function () {
    Route::get('/contacts', [ApiController::class, 'contacts']);
    Route::get('/campaigns', [ApiController::class, 'campaigns']);
    Route::get('/devices', [ApiController::class, 'devices']);
    Route::get('/stats', [ApiController::class, 'stats']);
    Route::post('/send', [ApiController::class, 'sendMessage']);
});
