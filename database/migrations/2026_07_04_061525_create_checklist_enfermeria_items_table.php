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

        Schema::create('checklist_enfermeria_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visita_habitacion_id')->constrained('visita_habitacions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('descripcion');
            $table->boolean('completado')->default(false);
            $table->string('valor')->nullable();
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
        Schema::dropIfExists('checklist_enfermeria_items');
    }
};
