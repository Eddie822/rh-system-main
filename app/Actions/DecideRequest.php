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
            $user = $user->fresh();
            Gate::forUser($user)->authorize('approve', $locked);
            $roles = array_intersect($locked->approvalRolesFor($user), $locked->missingApprovalRoles());
            foreach ($roles as $role) {
                $locked->authorizations()->create([
                    'user_id' => $user->id,
                    'authorization_role' => $role,
                    'action' => $action,
                    'reason' => $action === 'rejected' ? $reason : null,
                ]);
            }
            $missing = $locked->missingApprovalRoles();
            $locked->update([
                'assigned_area_manager_id' => $locked->areaApproverId(),
                'status' => $action === 'rejected' ? 'rejected' : ($missing ? 'pending_'.$missing[0] : 'approved'),
            ]);
        });
    }
}
