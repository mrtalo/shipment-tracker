<?php

use App\Http\Controllers\Api\CarrierWebhookController;
use App\Http\Controllers\Api\PacketController;
use Illuminate\Support\Facades\Route;

Route::post('/packets', [PacketController::class, 'store']);
Route::get('/packets', [PacketController::class, 'index']);
Route::get('/packets/{id}', [PacketController::class, 'show']);
Route::put('/packets/{packet}/status', [PacketController::class, 'updateStatus']);

Route::post('/webhooks/carrier', [CarrierWebhookController::class, 'handle']);
