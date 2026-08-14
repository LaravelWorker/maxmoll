<?php

use App\Http\Controllers\Api\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StockMovementController::class, 'index']);