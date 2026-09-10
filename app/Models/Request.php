<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'area_id',
        'group',
        'reason',
        'status',
        'week',
        'request_date',
        'employee_signature',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(RequestDay::class);
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(Authorization::class);
    }


}
