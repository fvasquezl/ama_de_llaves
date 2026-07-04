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

        Schema::create('alerta_rondas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ronda_enfermeria_id')->constrained('ronda_enfermerias')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('visita_habitacion_id')->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('tipo', ['visita_tardia', 'visita_omitida', 'turno_incompleto']);
            $table->boolean('atendido')->default(false);
            $table->foreignUuid('atendido_por_id')->nullable()->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerta_rondas');
    }
};
