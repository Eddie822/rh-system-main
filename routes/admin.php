<?php

use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('admin.dashboard');
})->name('dashboard');

Route::resource('users', UserController::class);

Route::get('/reportes', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reportes/excel', [ReportController::class, 'export'])->name('reports.export');
