<?php

namespace App\Actions;

use App\Models\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteUsers
{
    public function handle(array $ids): array
    {
        Gate::authorize('deleteUsers');

        return DB::transaction(function () use ($ids) {
            $deleted = 0;
            $blocked = [];
            $users = User::whereKey($ids)
                ->withExists(['requests', 'groupRequests', 'authorizations'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $userIds = $users->modelKeys();
            $dayEmployeeIds = $userIds === [] ? [] : DB::table('request_days')
                ->whereIn('employee_id', $userIds)
                ->distinct()
                ->pluck('employee_id')
                ->flip()
                ->all();
            $assignedApproverIds = $userIds === [] ? [] : Request::whereIn('assigned_area_manager_id', $userIds)
                ->distinct()
                ->pluck('assigned_area_manager_id')
                ->flip()
                ->all();
            $managerIdsWithReports = $userIds === [] ? [] : User::where(function ($query) use ($userIds) {
                $query->whereIn('area_manager_id', $userIds)
                    ->orWhereIn('direct_manager_id', $userIds);
            })
                ->get(['area_manager_id', 'direct_manager_id'])
                ->flatMap(fn (User $u) => array_filter([$u->area_manager_id, $u->direct_manager_id]))
                ->flip()
                ->all();

            $authUser = auth()->user();
            $deletableIds = [];
            foreach ($users as $user) {
                if ($authUser && $user->is($authUser)) {
                    $blocked[] = "{$user->employee_number} — {$user->full_name}: No puedes eliminar tu propia cuenta desde el panel.";

                    continue;
                }
                $hasHistory = $user->requests_exists
                    || $user->group_requests_exists
                    || $user->authorizations_exists
                    || isset($dayEmployeeIds[$user->id])
                    || isset($assignedApproverIds[$user->id]);
                $hasReports = isset($managerIdsWithReports[$user->id]);

                if ($hasHistory || $hasReports) {
                    $blocked[] = "{$user->employee_number} — {$user->full_name}: No se puede eliminar porque tiene solicitudes, autorizaciones o personal a cargo.";

                    continue;
                }

                $user->deleteProfilePhoto();
                $deletableIds[] = $user->id;
                $deleted++;
            }

            if ($deletableIds !== []) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereIn('tokenable_id', $deletableIds)
                    ->delete();
                User::whereKey($deletableIds)->delete();
            }

            return compact('deleted', 'blocked');
        });
    }
}
