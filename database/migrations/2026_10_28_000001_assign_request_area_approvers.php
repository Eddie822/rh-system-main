<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::withoutForeignKeyConstraints(fn () => Schema::table('requests', function (Blueprint $table) {
            $table->foreignId('assigned_area_manager_id')->nullable()->constrained('users')->restrictOnDelete();
        }));

        // Preserve the manager who already authorized historical requests. For open
        // requests, prefer the supervisor's explicit assignment over their area.
        DB::table('requests')->orderBy('id')->chunkById(500, function ($requests) {
            foreach ($requests as $request) {
                $manager = DB::table('authorizations')->where('request_id', $request->id)
                    ->where('authorization_role', 'area_manager')->orderBy('id')->value('user_id');
                $creator = DB::table('users')->where('id', $request->employee_id)->first();
                if (! $manager && $creator?->role === 'supervisor') {
                    $manager = $creator->area_manager_id ?? $creator->direct_manager_id;
                }
                $manager ??= DB::table('users')->where('role', 'area_manager')->where('area_id', $request->area_id)->value('id');
                if ($manager) {
                    DB::table('requests')->where('id', $request->id)->update(['assigned_area_manager_id' => $manager]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(fn () => Schema::table('requests', fn (Blueprint $table) => $table->dropConstrainedForeignId('assigned_area_manager_id')));
    }
};
