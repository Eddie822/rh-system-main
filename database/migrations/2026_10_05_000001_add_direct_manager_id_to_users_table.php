<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('direct_manager_id')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('users')->where('role', 'worker')->whereNotNull('supervisor_id')
            ->update(['direct_manager_id' => DB::raw('supervisor_id')]);
        DB::table('users')->where('role', 'supervisor')->whereNotNull('area_manager_id')
            ->update(['direct_manager_id' => DB::raw('area_manager_id')]);
        $plantManagers = DB::table('users')->where('role', 'plant_manager')->pluck('id');
        if ($plantManagers->count() === 1) {
            DB::table('users')->whereIn('role', ['area_manager', 'hr_manager', 'admin'])
                ->whereNull('direct_manager_id')->update(['direct_manager_id' => $plantManagers->first()]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('direct_manager_id');
        });
    }
};
