<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ExpenseController;

// Ruta para obtener el usuario autenticado
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

// Rutas de recursos para BudgetController
Route::apiResource('budgets', BudgetController::class);

// Rutas de recursos para CategoryController
Route::apiResource('categories', CategoryController::class);

// Rutas de recursos para ExpenseController
Route::apiResource('expenses', ExpenseController::class);
