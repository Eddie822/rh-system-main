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
        // Keep legacy surnames intact: compound surnames cannot be split reliably.
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('last_name', 'apellidos_pat');
            $table->renameColumn('group', 'puesto');
        });
        Schema::withoutForeignKeyConstraints(fn () => Schema::table('users', function (Blueprint $table) {
            $table->string('apellido_mat')->nullable();
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn('supervisor_id');
        }));
        DB::table('users')->where('role', 'worker')->update(['direct_manager_id' => null]);
    }

    public function down(): void
    {
        // Preserve both surnames if returning to the legacy schema.
        DB::table('users')->whereNotNull('apellido_mat')->orderBy('id')->chunkById(500, function ($users) {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update([
                    'apellidos_pat' => trim($user->apellidos_pat.' '.$user->apellido_mat),
                ]);
            }
        });
        Schema::withoutForeignKeyConstraints(fn () => Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('apellido_mat');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->renameColumn('apellidos_pat', 'last_name');
            $table->renameColumn('puesto', 'group');
        }));
    }
};
