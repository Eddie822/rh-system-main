<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('request_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                ->nullable()
                ->constrained('requests')
                ->cascadeOnDelete();
            $table->string('day_name')->nullable();   // Ej: "Lunes"
            $table->date('day_date')->nullable();    // Fecha real del día
            $table->decimal('hours', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_days');
    }
};
