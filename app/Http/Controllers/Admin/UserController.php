<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index');
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function destroy(User $user)
    {
        Gate::authorize('deleteUsers');

        if ($user->is(auth()->user())) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta desde el panel.');
        }

        $hasHistory = $user->requests()->exists()
            || $user->groupRequests()->exists()
            || $user->authorizations()->exists()
            || DB::table('request_days')->where('employee_id', $user->id)->exists();
        $hasReports = User::where('supervisor_id', $user->id)
            ->orWhere('area_manager_id', $user->id)
            ->orWhere('direct_manager_id', $user->id)
            ->exists();

        if ($hasHistory || $hasReports) {
            return back()->with('error', 'No se puede eliminar este usuario porque tiene solicitudes, autorizaciones o personal a cargo.');
        }

        $user->deleteProfilePhoto();
        $user->tokens()->delete();
        $user->delete();

        return redirect()->route('admin.users.index')->with('deleted', 'Usuario eliminado correctamente.');
    }
}
