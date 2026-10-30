<?php

namespace App\Policies;

use App\Models\Request;
use App\Models\User;

class RequestPolicy
{
    public function create(User $user): bool
    {
        return $this->createGroup($user);
    }

    public function createGroup(User $user): bool
    {
        return in_array($user->role, ['supervisor', 'area_manager', 'hr_manager', 'plant_manager'], true)
            && ($user->area_id !== null || in_array($user->role, ['hr_manager', 'plant_manager'], true));
    }

    public function view(User $user, Request $request): bool
    {
        $isParticipant = $request->relationLoaded('participants')
            ? $request->participants->contains('id', $user->id)
            : $request->participants()->whereKey($user->id)->exists();

        return $request->employee_id === $user->id
            || $isParticipant
            || $this->viewApproval($user, $request);
    }

    public function viewApproval(User $user, Request $request): bool
    {
        return $request->approvalRolesFor($user) !== [];
    }

    public function update(User $user, Request $request): bool
    {
        $hasAuthorizations = $request->authorizations_exists
            ?? ($request->relationLoaded('authorizations')
                ? $request->authorizations->isNotEmpty()
                : $request->authorizations()->exists());

        return $user->role !== 'worker'
            && $request->employee_id === $user->id
            && $request->status === 'pending_area_manager'
            && ! $hasAuthorizations
            && (! $request->is_group || ($this->createGroup($user) && $user->area_id === $request->area_id));
    }

    public function approve(User $user, Request $request): bool
    {
        return in_array($request->status, ['pending_area_manager', 'pending_hr_manager', 'pending_plant_manager'], true)
            && array_intersect($request->approvalRolesFor($user), $request->missingApprovalRoles()) !== [];
    }
}
