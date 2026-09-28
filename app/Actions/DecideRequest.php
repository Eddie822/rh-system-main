<?php

namespace App\Actions;

use App\Models\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DecideRequest
{
    public function handle(Request $request, User $user, string $action, ?string $reason = null): void
    {
        if (! in_array($action, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['action' => 'Decisión no válida.']);
        }

        if ($action === 'rejected') {
            validator(['reason' => $reason], ['reason' => 'required|string|min:5|max:500'])->validate();
        }

        DB::transaction(function () use ($request, $user, $action, $reason) {
            $locked = Request::lockForUpdate()->findOrFail($request->id);
            Gate::forUser($user)->authorize('approve', $locked);

            $locked->authorizations()->create([
                'user_id' => $user->id,
                'authorization_role' => $user->role,
                'action' => $action,
                'reason' => $action === 'rejected' ? $reason : null,
            ]);

            $locked->update(['status' => $action === 'rejected' ? 'rejected' : match ($user->role) {
                'area_manager' => 'pending_hr_manager',
                'hr_manager' => 'pending_plant_manager',
                'plant_manager' => 'approved',
            }]);
        });
    }
}
