<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Managers\Approval\ApprovalController;
use App\Http\Controllers\Users\RequestsController;
use App\Models\Request;
use Illuminate\Support\Facades\Gate;
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
        if (auth()->user()?->cannot('create', Request::class)) {
            return redirect()->route('requests.index');
        }

        return view('request');
    })->name('request');

    Route::get('/requests/group/create', function () {
        Gate::authorize('createGroup', Request::class);

        return view('users.requests.group');
    })->name('requests.group.create');

    Route::get('/requests-search', function () {
        return view('users.requests.search');
    })->name('requests.search');

    Route::get('/requests-search/{request}', function (Request $request) {
        $request->load(['employee', 'area', 'assignedAreaManager', 'authorizations.user', 'days.employee']);
        $approverId = $request->areaApproverId();
        $areaApprover = ($approverId && $request->assigned_area_manager_id === $approverId)
            ? $request->assignedAreaManager
            : ($approverId ? \App\Models\User::find($approverId) : null);
        $missingApprovals = $request->missingApprovalRoles();

        return view('users.requests.search-show', compact('request', 'areaApprover', 'missingApprovals'));
    })->name('requests.search.show');

    Route::resource('requests', RequestsController::class);

    Route::resource('approvals', ApprovalController::class);
});
