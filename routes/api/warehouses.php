<?php

use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Warehouse Routes (/api/warehouses)
|--------------------------------------------------------------------------
*/

Route::get('/', [WarehouseController::class, 'index']);