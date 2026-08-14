<?php

use App\Http\Controllers\Api\SupplyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Supply Routes (/api/supplies)
|--------------------------------------------------------------------------
*/

Route::get('/', [SupplyController::class, 'index']);
Route::post('/', [SupplyController::class, 'store']);