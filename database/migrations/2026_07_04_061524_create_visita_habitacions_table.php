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

        Schema::create('visita_habitacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ronda_enfermeria_id')->constrained('ronda_enfermerias')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('habitacion_id')->constrained('habitacions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('residente_id')->constrained('residentes')->cascadeOnDelete()->cascadeOnUpdate();
            $table->time('hora_programada');
            $table->boolean('nfc_verificado')->default(false);
            $table->dateTime('nfc_escaneado_at')->nullable();
            $table->enum('estado', ['pendiente', 'en_progreso', 'completada', 'omitida'])->default('pendiente');
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
        Schema::dropIfExists('visita_habitacions');
    }
};
