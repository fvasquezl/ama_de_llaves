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

        Schema::create('reporte_mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habitacion_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('reportado_por_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('descripcion');
            $table->enum('prioridad', ['baja', 'normal', 'alta', 'urgente'])->default('normal');
            $table->enum('estado', ['pendiente', 'en_proceso', 'resuelto'])->default('pendiente');
            $table->string('foto_path')->nullable();
            $table->text('notas_resolucion')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_mantenimientos');
    }
};
