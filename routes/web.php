<?php

use App\Http\Controllers\Users\RequestsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/request', function () {
        return view('request');
    })->name('request');

    Route::get('/requests', [RequestsController::class, 'index'])
        ->name('requests.index');
});
