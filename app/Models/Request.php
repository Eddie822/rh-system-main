<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'is_group',
        'area_id',
        'group',
        'reason',
        'status',
        'week',
        'request_date',
    ];

    protected function casts(): array
    {
        return [
            'is_group' => 'boolean',
            'request_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // For group requests employee_id identifies the submitting supervisor.
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'request_employees', 'request_id', 'employee_id');
    }

    public function scopeForEmployee(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where('employee_id', $user->id)
            ->orWhereHas('participants', fn (Builder $query) => $query->where('users.id', $user->id)));
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
