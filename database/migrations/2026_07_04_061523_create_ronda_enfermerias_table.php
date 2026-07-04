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

        Schema::create('ronda_enfermerias', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('enfermera_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('turno', ['matutino', 'vespertino', 'nocturno']);
            $table->date('fecha');
            $table->time('hora_inicio_programada');
            $table->time('hora_fin_programada');
            $table->time('hora_inicio_real')->nullable();
            $table->time('hora_fin_real')->nullable();
            $table->enum('estado', ['pendiente', 'en_curso', 'completada', 'incompleta'])->default('pendiente');
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
        Schema::dropIfExists('ronda_enfermerias');
    }
};
