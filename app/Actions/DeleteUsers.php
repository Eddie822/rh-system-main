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
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $authUser = auth()->user();
            $deletableIds = [];
            foreach ($users as $user) {
                if ($authUser && $user->is($authUser)) {
                    $blocked[] = "{$user->employee_number} — {$user->full_name}: No puedes eliminar tu propia cuenta desde el panel.";

                    continue;
                }

                $user->deleteProfilePhoto();
                $deletableIds[] = $user->id;
                $deleted++;
            }

            if ($deletableIds !== []) {
                DB::table('request_days')
                    ->whereIn('employee_id', $deletableIds)
                    ->delete();

                DB::table('request_employees')
                    ->whereIn('employee_id', $deletableIds)
                    ->delete();

                DB::table('authorizations')
                    ->whereIn('user_id', $deletableIds)
                    ->delete();

                Request::whereIn('assigned_area_manager_id', $deletableIds)
                    ->update(['assigned_area_manager_id' => null]);

                Request::whereIn('employee_id', $deletableIds)
                    ->delete();

                Request::where('is_group', true)
                    ->whereDoesntHave('participants')
                    ->delete();

                User::whereIn('area_manager_id', $deletableIds)
                    ->update(['area_manager_id' => null]);

                User::whereIn('direct_manager_id', $deletableIds)
                    ->update(['direct_manager_id' => null]);

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
