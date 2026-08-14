<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Product Routes (/api/products)
|--------------------------------------------------------------------------
*/

Route::get('/', [ProductController::class, 'index']);