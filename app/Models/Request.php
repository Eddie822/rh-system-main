<?php

namespace App\Models;

use Carbon\Carbon;
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
            'expires_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function syncExpiration(): void
    {
        $firstDay = $this->days()->min('day_date');
        $this->expires_at = $firstDay ? Carbon::parse($firstDay)->startOfDay() : null;
        $this->saveQuietly();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending_area_manager', 'pending_hr_manager', 'pending_plant_manager']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->pending()->where('expires_at', '<=', now());
    }

    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->pending()->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addHours(config('requests.expiration_notice_hours')));
    }

    public function expirationAlert(): ?string
    {
        if (! $this->expires_at || ! in_array($this->status, ['pending_area_manager', 'pending_hr_manager', 'pending_plant_manager'], true)) {
            return null;
        }
        if ($this->expires_at->lte(now())) {
            return 'overdue';
        }

        return $this->expires_at->lte(now()->addHours(config('requests.expiration_notice_hours'))) ? 'soon' : null;
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
