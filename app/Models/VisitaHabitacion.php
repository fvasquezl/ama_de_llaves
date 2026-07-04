<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitaHabitacion extends Model
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
            'habitacion_id' => 'integer',
            'residente_id' => 'integer',
            'nfc_verificado' => 'boolean',
            'nfc_escaneado_at' => 'datetime',
        ];
    }

    public function rondaEnfermeria(): BelongsTo
    {
        return $this->belongsTo(RondaEnfermeria::class);
    }

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class);
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }

    public function checklistEnfermeriaItems(): HasMany
    {
        return $this->hasMany(ChecklistEnfermeriaItem::class);
    }
}
