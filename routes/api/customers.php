<?php

use App\Http\Controllers\Api\CustomerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Routes (/api/customers)
|--------------------------------------------------------------------------
*/

Route::get('/', [CustomerController::class, 'index']);
Route::post('/', [CustomerController::class, 'store']);
Route::match(['put', 'patch'], '/{customer}', [CustomerController::class, 'update']);