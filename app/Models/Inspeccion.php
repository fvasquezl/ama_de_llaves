<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inspeccion extends Model
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
            'tarea_limpieza_id' => 'integer',
            'supervisora_id' => 'integer',
            'puntaje' => 'integer',
        ];
    }

    public function tareaLimpieza(): BelongsTo
    {
        return $this->belongsTo(TareaLimpieza::class);
    }

    public function supervisora(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
