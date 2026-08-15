<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Routes (/api/orders)
|--------------------------------------------------------------------------
*/

Route::get('/', [OrderController::class, 'index']);
Route::post('/', [OrderController::class, 'store']);
Route::match(['put', 'patch'], '/{order}', [OrderController::class, 'update']);
Route::delete('/{order}', [OrderController::class, 'destroy']);

// Экшены смены статуса
Route::post('/{order}/complete', [OrderController::class, 'complete']);
Route::post('/{order}/cancel', [OrderController::class, 'cancel']);
Route::post('/{order}/restore', [OrderController::class, 'restore']);