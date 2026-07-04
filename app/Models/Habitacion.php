<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Habitacion extends Model
{
    use HasFactory, SoftDeletes;

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
            'sucursal_id' => 'integer',
            'piso' => 'integer',
            'capacidad' => 'integer',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function estancias(): HasMany
    {
        return $this->hasMany(Estancia::class);
    }

    public function tareaLimpiezas(): HasMany
    {
        return $this->hasMany(TareaLimpieza::class);
    }

    public function reporteMantenimientos(): HasMany
    {
        return $this->hasMany(ReporteMantenimiento::class);
    }

    public function visitaHabitacions(): HasMany
    {
        return $this->hasMany(VisitaHabitacion::class);
    }
}
