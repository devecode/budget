<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\EgressController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\AuthController;

// Ruta para obtener el usuario autenticado
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:api');

// Rutas de autenticación
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');

// Rutas protegidas
Route::middleware('auth:api')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('egresses', EgressController::class);
    Route::apiResource('incomes', IncomeController::class);
    Route::apiResource('payment-methods', PaymentController::class);
    Route::get('/top-categories', [EgressController::class, 'topCategories']);
    Route::get('/categories-with-spent', [EgressController::class, 'allCategoriesWithSpent']);
    Route::get('/cuentas-por-pagar', [EgressController::class, 'cuentasPorPagar']);
    Route::get('/export-cuentas-por-pagar', [EgressController::class, 'exportCuentasPorPagar']);
    Route::get('/export-payment-method-view', [EgressController::class, 'exportPayMethodView']);
    Route::get('/get-egresses', [EgressController::class, 'getEgresses']);
    Route::get('/income-vs-egress', [ReportController::class, 'incomeVsEgress']);
    Route::get('/spending-by-payment-method', [EgressController::class, 'getSpendingByPaymentMethod']);
    Route::get('/payment-method-view', [EgressController::class, 'getPayMethodView']);
    Route::get('/accounts-payable', [EgressController::class, 'accountsPayable']);
});
