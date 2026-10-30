<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DeleteUsers;
use App\Http\Controllers\Controller;
use App\Models\User;

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
        $result = app(DeleteUsers::class)->handle([$user->id]);
        if ($result['blocked']) {
            return back()->with('error', $result['blocked'][0]);
        }

        return redirect()->route('admin.users.index')->with('deleted', 'Usuario eliminado correctamente.');
    }
}
