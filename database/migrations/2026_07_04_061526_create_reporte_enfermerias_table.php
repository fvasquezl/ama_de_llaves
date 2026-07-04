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

        Schema::create('reporte_enfermerias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ronda_enfermeria_id')->constrained('ronda_enfermerias')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('enfermera_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('incidencias')->nullable();
            $table->text('observaciones')->nullable();
            $table->dateTime('firmado_at')->nullable();
            $table->enum('estado', ['borrador', 'firmado'])->default('borrador');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_enfermerias');
    }
};
