<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ReportController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/simulate-lb', [ProductController::class, 'simulateLoadBalancer']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/report/before', [ReportController::class, 'before']);
    Route::get('/report/run', [ReportController::class, 'run']);
    Route::get('/report/after', [ReportController::class, 'after']);
    
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