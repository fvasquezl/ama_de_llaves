<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteEnfermeria extends Model
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
            'enfermera_id' => 'integer',
            'firmado_at' => 'datetime',
        ];
    }

    public function rondaEnfermeria(): BelongsTo
    {
        return $this->belongsTo(RondaEnfermeria::class);
    }

    public function enfermera(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
