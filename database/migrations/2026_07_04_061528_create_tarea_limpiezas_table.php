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

        Schema::create('tarea_limpiezas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habitacion_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('camarera_id')->nullable()->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('supervisora_id')->nullable()->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('tipo', ['salida', 'estancia', 'profunda', 'llegada']);
            $table->enum('prioridad', ['baja', 'normal', 'alta', 'urgente'])->default('normal');
            $table->enum('estado', ['pendiente', 'en_progreso', 'completada', 'inspeccionada', 'rechazada'])->default('pendiente');
            $table->date('fecha_programada');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarea_limpiezas');
    }
};
