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
        'assigned_area_manager_id',
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

    public const STATUS_LABELS = [
        'pending_area_manager' => 'Pendiente de gerente de área',
        'pending_hr_manager' => 'Pendiente de Recursos Humanos',
        'pending_plant_manager' => 'Pendiente de gerente de planta',
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
    ];

    public const ROLE_LABELS = [
        'area_manager' => 'Gerente de área',
        'hr_manager' => 'Gerente de RH',
        'plant_manager' => 'Gerente de planta',
    ];

    public const STATUS_TO_ROLE = [
        'pending_area_manager' => 'area_manager',
        'pending_hr_manager' => 'hr_manager',
        'pending_plant_manager' => 'plant_manager',
    ];

    public function getStatusLabelAttribute(): string
    {
        return $this->statusLabelWithManager();
    }

    public function statusLabelWithManager(?array $customLabels = null): string
    {
        $labels = $customLabels ?? self::STATUS_LABELS;
        $base = $labels[$this->status] ?? $this->status;
        $role = self::STATUS_TO_ROLE[$this->status] ?? null;

        return $role ? $base.$this->managerSuffixForRole($role) : $base;
    }

    public function roleLabelWithManager(string $role, ?string $baseLabel = null): string
    {
        $label = $baseLabel ?? (self::ROLE_LABELS[$role] ?? $role);

        return $label.$this->managerSuffixForRole($role);
    }

    public function managerSuffixForRole(string $role): string
    {
        $name = $this->managerNameForRole($role);

        return $name ? " ({$name})" : '';
    }

    public function managerNameForRole(string $role): ?string
    {
        if ($this->relationLoaded('authorizations')) {
            $auth = $this->authorizations->firstWhere('authorization_role', $role);
            if ($auth && $auth->relationLoaded('user') && $auth->user) {
                return $auth->user->full_name;
            }
        }

        if ($role === 'area_manager') {
            if ($this->assigned_area_manager_id && $this->relationLoaded('assignedAreaManager') && $this->assignedAreaManager) {
                return $this->assignedAreaManager->full_name;
            }

            $approverId = $this->areaApproverId();
            if (! $approverId) {
                return null;
            }

            if ($this->relationLoaded('assignedAreaManager') && $this->assignedAreaManager?->id === $approverId) {
                return $this->assignedAreaManager->full_name;
            }

            if ($this->relationLoaded('employee') && $this->employee?->id === $approverId) {
                return $this->employee->full_name;
            }

            return User::find($approverId)?->full_name;
        }

        if (in_array($role, ['hr_manager', 'plant_manager'], true)) {
            $names = User::where('role', $role)
                ->orderBy('id')
                ->get()
                ->map(fn (User $user) => $user->full_name)
                ->filter()
                ->join(', ');

            return $names !== '' ? $names : null;
        }

        return null;
    }

    public const APPROVAL_ROLES = ['area_manager', 'hr_manager', 'plant_manager'];

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            $request->assigned_area_manager_id ??= $request->areaApproverId();
        });
    }

    public function assignedAreaManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_area_manager_id');
    }

    public function areaApproverId(): ?int
    {
        $creator = $this->employee;
        if (in_array($creator?->role, ['area_manager', 'hr_manager', 'plant_manager'], true)) {
            return $creator->id;
        }
        if ($this->assigned_area_manager_id) {
            return $this->assigned_area_manager_id;
        }
        if ($creator && ($creator->area_manager_id || ($creator->role === 'supervisor' && $creator->direct_manager_id))) {
            return $creator->area_manager_id ?? $creator->direct_manager_id;
        }

        return $this->area_id ? User::where('role', 'area_manager')->where('area_id', $this->area_id)->value('id') : null;
    }

    public function approvalRolesFor(User $user): array
    {
        if (! $user->canApprove()) {
            return [];
        }
        $roles = $this->areaApproverId() === $user->id ? ['area_manager'] : [];
        if (in_array($user->role, ['hr_manager', 'plant_manager'], true)) {
            $roles[] = $user->role;
        }

        return array_values(array_unique($roles));
    }

    public function missingApprovalRoles(): array
    {
        $approvedRoles = $this->relationLoaded('authorizations')
            ? $this->authorizations->where('action', 'approved')->pluck('authorization_role')->all()
            : $this->authorizations()->where('action', 'approved')->pluck('authorization_role')->all();

        return array_values(array_diff(self::APPROVAL_ROLES, $approvedRoles));
    }

    public function scopeForAreaApprover(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->where('assigned_area_manager_id', $user->id)
                ->when(
                    in_array($user->role, ['area_manager', 'hr_manager', 'plant_manager'], true),
                    fn (Builder $query) => $query->orWhere('employee_id', $user->id)
                )
                ->orWhere(function (Builder $query) use ($user) {
                    $query->whereNull('assigned_area_manager_id')->where(function (Builder $query) use ($user) {
                        $query->whereHas('employee', fn (Builder $query) => $query->where('role', 'supervisor')
                            ->where(fn (Builder $query) => $query->where('area_manager_id', $user->id)
                                ->orWhere(fn (Builder $query) => $query->whereNull('area_manager_id')->where('direct_manager_id', $user->id))));
                        if ($user->role === 'area_manager' && $user->area_id !== null) {
                            $query->orWhere(fn (Builder $query) => $query->where('area_id', $user->area_id)
                                ->whereDoesntHave('employee', fn (Builder $query) => $query->whereIn('role', ['area_manager', 'hr_manager', 'plant_manager'])
                                    ->orWhere(fn (Builder $query) => $query->where('role', 'supervisor')
                                        ->where(fn (Builder $query) => $query->whereNotNull('area_manager_id')->orWhereNotNull('direct_manager_id')))));
                        }
                    });
                });
        });
    }

    public function scopeAwaitingDecisionBy(Builder $query, User $user): Builder
    {
        return $query->pending()->where(function (Builder $query) use ($user) {
            $query->where(fn (Builder $query) => $query->forAreaApprover($user)
                ->whereDoesntHave('authorizations', fn (Builder $query) => $query->where('authorization_role', 'area_manager')->where('action', 'approved')));
            if (in_array($user->role, ['hr_manager', 'plant_manager'], true)) {
                $query->orWhereDoesntHave('authorizations', fn (Builder $query) => $query->where('authorization_role', $user->role)->where('action', 'approved'));
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function syncExpiration(): void
    {
        $firstDay = $this->days()->min('day_date');
        $this->expires_at = $firstDay ? Carbon::parse($firstDay)->startOfWeek(Carbon::MONDAY)->addWeek()->addDay()->startOfDay() : null;
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
