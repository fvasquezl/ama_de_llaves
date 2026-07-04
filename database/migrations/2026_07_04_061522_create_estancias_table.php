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
        Schema::disableForeignKeyConstraints();

        Schema::create('estancias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residente_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('habitacion_id')->constrained('habitacions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('fecha_ingreso');
            $table->date('fecha_egreso')->nullable();
            $table->enum('estado', ['activa', 'alta', 'traslado', 'fallecimiento'])->default('activa');
            $table->text('notas_medicas')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estancias');
    }
};
