<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'date',
        'hours',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours' => 'decimal:2',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->BelongsTo(Request::class);
    }
}
