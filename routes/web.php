<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BudgetController;

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
