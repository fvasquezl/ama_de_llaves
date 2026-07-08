<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TareaLimpieza extends Model
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
            'habitacion_id' => 'integer',
            'camarera_id' => 'string',
            'supervisora_id' => 'string',
            'fecha_programada' => 'date',
        ];
    }

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class);
    }

    public function camarera(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supervisora(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

    public function inspeccions(): HasMany
    {
        return $this->hasMany(Inspeccion::class);
    }
}
