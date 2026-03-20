<?php

use App\Http\Controllers\Api\PacketController;
use Illuminate\Support\Facades\Route;

Route::post('/packets', [PacketController::class, 'store']);
Route::put('/packets/{packet}/status', [PacketController::class, 'updateStatus']);
