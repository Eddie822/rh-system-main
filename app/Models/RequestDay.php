<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'day_name',   // Ej: "Monday"
        'day_date',   // Ej: "2026-09-07"
        'hours',      // Ej: 8.5 (decimal)
    ];

    protected function casts(): array
    {
        return [
            'day_date' => 'date',
            'hours' => 'float',
        ];
    }

    public function getDayDateFormattedAttribute(): string
    {
        return $this->day_date
            ? $this->day_date->format('d-m-Y')
            : '';
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }
}
