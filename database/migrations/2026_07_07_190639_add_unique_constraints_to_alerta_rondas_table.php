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
        Schema::table('alerta_rondas', function (Blueprint $table) {
            $table->unique(
                ['ronda_enfermeria_id', 'visita_habitacion_id', 'tipo'],
                'alerta_rondas_ronda_visita_tipo_unique'
            );

            // NOTE: uses a VIRTUAL (not STORED) generated column. MySQL/InnoDB
            // rejects a STORED generated column whose base column
            // (`ronda_enfermeria_id`) carries an ON UPDATE/DELETE CASCADE
            // foreign key (error 1215: "Cannot add foreign key constraint").
            // VIRTUAL columns aren't subject to that restriction and MySQL
            // 8 supports secondary indexes on virtual generated columns.
            $table->unsignedBigInteger('turno_incompleto_key')
                ->virtualAs("IF(tipo = 'turno_incompleto', ronda_enfermeria_id, NULL)")
                ->nullable()
                ->unique('alerta_rondas_turno_incompleto_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerta_rondas', function (Blueprint $table) {
            // MySQL auto-drops the implicit index that originally backed the
            // `ronda_enfermeria_id` foreign key once `alerta_rondas_ronda_visita_tipo_unique`
            // becomes available to serve that role. Restore an explicit
            // index for it first, otherwise dropping the unique index below
            // fails with "Cannot drop index ...: needed in a foreign key constraint".
            $table->index('ronda_enfermeria_id', 'alerta_rondas_ronda_enfermeria_id_index');
            $table->dropUnique('alerta_rondas_ronda_visita_tipo_unique');
            $table->dropUnique('alerta_rondas_turno_incompleto_unique');
            $table->dropColumn('turno_incompleto_key');
        });
    }
};
