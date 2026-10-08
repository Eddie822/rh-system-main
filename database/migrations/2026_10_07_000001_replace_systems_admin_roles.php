<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $manager = DB::table('users')->where('employee_number', '0356')->where('role', 'admin')->first();
            if (! $manager) {
                return;
            }
            $area = DB::table('areas')->where('id', $manager->area_id)->first();
            if (mb_strtolower(trim($area->name ?? '')) !== 'sistemas') {
                throw new RuntimeException('La nómina 0356 debe pertenecer a Sistemas antes de convertir su rol.');
            }
            $oldManager = DB::table('users')->where('employee_number', '0103')->where('area_id', $manager->area_id)->first();
            $otherManagers = DB::table('users')->where('area_id', $manager->area_id)->where('role', 'area_manager')
                ->when($oldManager, fn ($query) => $query->where('id', '!=', $oldManager->id))->exists();
            if ($otherManagers) {
                throw new RuntimeException('Sistemas tiene otro gerente de área; revisa la asignación antes de convertir sus roles.');
            }
            DB::table('users')->where('id', $manager->id)->update([
                'role' => 'area_manager', 'supervisor_id' => null, 'area_manager_id' => null,
            ]);
            if ($oldManager) {
                DB::table('users')->where('area_manager_id', $oldManager->id)->update(['area_manager_id' => $manager->id]);
                DB::table('users')->where('direct_manager_id', $oldManager->id)->update(['direct_manager_id' => $manager->id]);
            }
            DB::table('users')->where('area_id', $manager->area_id)->whereIn('employee_number', ['0094', '0103'])
                ->update([
                    'role' => 'worker', 'supervisor_id' => null,
                    'area_manager_id' => $manager->id, 'direct_manager_id' => $manager->id,
                ]);
        });
    }

    public function down(): void
    {
        // Keep confirmed job assignments and historical references on rollback.
    }
};
