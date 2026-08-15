<?php

use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Warehouse Routes (/api/warehouses)
|--------------------------------------------------------------------------
*/

Route::get('/', [WarehouseController::class, 'index']);
Route::get('/products', [WarehouseController::class, 'products']);
Route::get('/stocks', [WarehouseController::class, 'stocks']);
Route::post('/transfers', [WarehouseController::class, 'storeTransfer']);