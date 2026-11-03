<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/agenda', [DashboardController::class, 'store'])->name('agenda.store');
Route::patch('/agenda/{agenda}/review', [DashboardController::class, 'review'])->name('agenda.review');
