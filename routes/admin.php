<?php

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('admin.dashboard');
})->name('dashboard');

Route::get('users/import', [UserImportController::class, 'index'])->name('users.import.index');
Route::get('users/import/template', [UserImportController::class, 'template'])->name('users.import.template');
Route::get('users/import/roster', [UserImportController::class, 'roster'])->name('users.import.roster');
Route::post('users/import', [UserImportController::class, 'store'])->name('users.import.store');
Route::resource('users', UserController::class)->only(['index', 'create', 'show', 'edit', 'destroy']);
Route::resource('areas', AreaController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

Route::get('/reportes', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reportes/excel', [ReportController::class, 'export'])->name('reports.export');
