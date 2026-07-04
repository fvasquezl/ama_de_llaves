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

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_limpieza_id')->constrained('tarea_limpiezas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('descripcion');
            $table->boolean('completado')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
