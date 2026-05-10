<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MedicineController;

use App\Http\Controllers\OrderController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public medicine routes
Route::get('/medicines', [MedicineController::class, 'index']);
Route::get('/medicines/categories', [MedicineController::class, 'categories']);
Route::get('/medicines/{id}', [MedicineController::class, 'show']);

// Chatbot route
Route::get('/chatbot', [\App\Http\Controllers\ChatbotController::class, 'query']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Protected medicine routes
    Route::post('/medicines', [MedicineController::class, 'store']);
    Route::put('/medicines/{id}', [MedicineController::class, 'update']);
    Route::delete('/medicines/{id}', [MedicineController::class, 'destroy']);
    
    // Order routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    
    // Prescription routes
    Route::post('/prescriptions', [\App\Http\Controllers\PrescriptionController::class, 'store']);
    
    // Delivery routes
    Route::get('/deliveries', [\App\Http\Controllers\DeliveryController::class, 'index']);
    Route::post('/deliveries/assign', [\App\Http\Controllers\DeliveryController::class, 'assign']);
    Route::put('/deliveries/{id}/status', [\App\Http\Controllers\DeliveryController::class, 'updateStatus']);
    Route::post('/delivery/location', [\App\Http\Controllers\DeliveryController::class, 'updateLocation']);
    Route::get('/delivery/location', [\App\Http\Controllers\DeliveryController::class, 'getLocation']);
    
    // Example protected route for user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
