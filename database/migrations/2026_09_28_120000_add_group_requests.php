<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::table('requests', function (Blueprint $table) {
            $table->boolean('is_group')->default(false);
        });

        Schema::create('request_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->unique(['request_id', 'employee_id']);
        });

        Schema::table('request_days', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->unique(['request_id', 'employee_id', 'day_date']);
        });
    }

    public function down(): void
    {
        Schema::table('request_days', function (Blueprint $table) {
            $table->dropUnique(['request_id', 'employee_id', 'day_date']);
            $table->dropConstrainedForeignId('employee_id');
        });
        Schema::dropIfExists('request_employees');
        Schema::table('requests', fn (Blueprint $table) => $table->dropColumn('is_group'));
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('supervisor_id'));
    }
};
