<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
            $table->index(['area_id', 'created_at']);
            $table->index('week');
        });

        Schema::table('request_days', function (Blueprint $table) {
            $table->index(['day_date', 'request_id']);
        });

        Schema::table('authorizations', function (Blueprint $table) {
            $table->index(['request_id', 'authorization_role', 'action']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'area_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'area_id']);
        });

        Schema::table('authorizations', function (Blueprint $table) {
            $table->dropIndex(['request_id', 'authorization_role', 'action']);
        });

        Schema::table('request_days', function (Blueprint $table) {
            $table->dropIndex(['day_date', 'request_id']);
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex(['week']);
            $table->dropIndex(['area_id', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
        });
    }
};

