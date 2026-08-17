<?php

use App\Http\Controllers\Api\TransferController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Transfer Routes (/api/transfers)
|--------------------------------------------------------------------------
*/

Route::post('/', [TransferController::class, 'store']);
