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

        Schema::create('habitacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('numero');
            $table->enum('tipo', ['individual', 'doble', 'suite']);
            $table->unsignedInteger('piso');
            $table->unsignedInteger('capacidad')->default(1);
            $table->enum('estado', ['disponible', 'ocupada', 'sucia', 'en_limpieza', 'limpia', 'inspeccionada', 'fuera_de_servicio'])->default('disponible');
            $table->string('nfc_tag_uid')->nullable()->unique();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habitacions');
    }
};
