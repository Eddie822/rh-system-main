<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Managers\Approval\ApprovalController;
use App\Http\Controllers\Users\RequestsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {

    Route::get('/change-password', function () {
        return view('auth.change-password');
    })->name('password.change');

    Route::post('/change-password', [PasswordController::class, 'update'])
        ->name('password.update');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'force.password.change',
])->group(function () {

    Route::get('/request', function () {
        return view('request');
    })->name('request');

    Route::resource('requests', RequestsController::class);

    Route::resource('approvals', ApprovalController::class);
});     