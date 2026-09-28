<?php

namespace App\Policies;

use App\Models\Request;
use App\Models\User;

class RequestPolicy
{
    public function createGroup(User $user): bool
    {
        return $user->role === 'supervisor' && $user->area_id !== null;
    }

    public function view(User $user, Request $request): bool
    {
        return $request->employee_id === $user->id
            || $request->participants()->whereKey($user->id)->exists()
            || $this->viewApproval($user, $request);
    }

    public function viewApproval(User $user, Request $request): bool
    {
        return in_array($user->role, ['hr_manager', 'plant_manager'], true)
            || ($user->role === 'area_manager' && $user->area_id !== null
                && $user->area_id === $request->area_id);
    }

    public function update(User $user, Request $request): bool
    {
        return $request->employee_id === $user->id
            && $request->status === 'pending_area_manager'
            && (! $request->is_group || ($this->createGroup($user) && $user->area_id === $request->area_id));
    }

    public function approve(User $user, Request $request): bool
    {
        return $this->viewApproval($user, $request) && $request->status === match ($user->role) {
            'area_manager' => 'pending_area_manager',
            'hr_manager' => 'pending_hr_manager',
            'plant_manager' => 'pending_plant_manager',
            default => null,
        };
    }
}
