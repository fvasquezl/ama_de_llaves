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

        Schema::create('inspeccions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_limpieza_id')->constrained('tarea_limpiezas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('supervisora_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('resultado', ['aprobada', 'rechazada']);
            $table->unsignedInteger('puntaje')->nullable();
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
        Schema::dropIfExists('inspeccions');
    }
};
