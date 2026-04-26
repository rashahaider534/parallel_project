<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/addproduct', [ProductController::class, 'addproduct']);
    Route::delete('/removeproduct/{product_id}', [ProductController::class, 'removeproduct']);
    Route::post('/update', [ProductController::class, 'update']);

    Route::post('/storebefore', [CartController::class, 'storebefore']);
    Route::post('/storeafter', [CartController::class, 'storeafter']);
    Route::post('/storeOptimistic', [CartController::class, 'storeOptimistic']);
});
