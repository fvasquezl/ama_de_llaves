<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaRonda extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'ronda_enfermeria_id' => 'integer',
            'visita_habitacion_id' => 'integer',
            'atendido' => 'boolean',
            'atendido_por_id' => 'integer',
        ];
    }

    public function rondaEnfermeria(): BelongsTo
    {
        return $this->belongsTo(RondaEnfermeria::class);
    }

    public function visitaHabitacion(): BelongsTo
    {
        return $this->belongsTo(VisitaHabitacion::class);
    }

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
