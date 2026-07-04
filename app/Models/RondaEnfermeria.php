<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RondaEnfermeria extends Model
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
            'enfermera_id' => 'integer',
            'fecha' => 'date',
            'user_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enfermera(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visitaHabitacions(): HasMany
    {
        return $this->hasMany(VisitaHabitacion::class);
    }

    public function alertaRondas(): HasMany
    {
        return $this->hasMany(AlertaRonda::class);
    }

    public function reporteEnfermeria(): HasOne
    {
        return $this->hasOne(ReporteEnfermeria::class);
    }
}
