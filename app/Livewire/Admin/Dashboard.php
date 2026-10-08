<?php

namespace App\Livewire\Admin;

use App\Models\Area;
use App\Models\Authorization;
use App\Models\Request as RequestModel;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class Dashboard extends Component
{
    public string $areaId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['areaId', 'dateFrom', 'dateTo'], true)) {
            $this->dispatch('dashboard-data-updated', data: $this->dashboardData());
        }
    }

    public function resetFilters(): void
    {
        $this->reset('areaId', 'dateFrom', 'dateTo');
        $this->dispatch('dashboard-data-updated', data: $this->dashboardData());
    }

    public function render()
    {
        abort_unless(auth()->user()?->canAccessAdminPanel(), 403);

        return view('livewire.admin.dashboard', [
            'areas' => Area::orderBy('name')->get(),
            'expiringSoon' => RequestModel::expiringSoon()->count(),
            'overdue' => RequestModel::overdue()->count(),
            'dashboardData' => $this->dashboardData(),
            'invalidDateRange' => $this->dateFrom !== '' && $this->dateTo !== '' && $this->dateFrom > $this->dateTo,
        ]);
    }

    private function filteredRequests(Builder $query): Builder
    {
        return $query
            ->when($this->areaId !== '' && ctype_digit($this->areaId), fn (Builder $query) => $query->where('area_id', (int) $this->areaId))
            ->when($this->validDate($this->dateFrom), fn (Builder $query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->validDate($this->dateTo), fn (Builder $query) => $query->whereDate('created_at', '<=', $this->dateTo));
    }

    private function validDate(string $date): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));

        return checkdate($month, $day, $year);
    }

    private function dashboardData(): array
    {
        $counts = $this->filteredRequests(RequestModel::query())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $areas = Area::query()
            ->when($this->areaId !== '' && ctype_digit($this->areaId), fn (Builder $query) => $query->whereKey((int) $this->areaId))
            ->withCount(['requests' => fn (Builder $query) => $this->filteredRequests($query)])
            ->orderByDesc('requests_count')
            ->orderBy('name')
            ->get();

        $authorizations = Authorization::query()
            ->selectRaw('authorization_role, action, COUNT(*) as total')
            ->whereIn('authorization_role', ['area_manager', 'hr_manager', 'plant_manager'])
            ->whereIn('action', ['approved', 'rejected'])
            ->whereHas('request', fn (Builder $query) => $this->filteredRequests($query))
            ->groupBy('authorization_role', 'action')
            ->get();

        $decisions = [];
        foreach (['area_manager', 'hr_manager', 'plant_manager'] as $role) {
            $decisions[$role] = [
                'approved' => (int) ($authorizations->first(fn ($row) => $row->authorization_role === $role && $row->action === 'approved')?->total ?? 0),
                'rejected' => (int) ($authorizations->first(fn ($row) => $row->authorization_role === $role && $row->action === 'rejected')?->total ?? 0),
            ];
        }

        return [
            'indicators' => [
                'approved' => (int) ($counts['approved'] ?? 0),
                'rejected' => (int) ($counts['rejected'] ?? 0),
                'pending_area_manager' => (int) ($counts['pending_area_manager'] ?? 0),
                'pending_hr_manager' => (int) ($counts['pending_hr_manager'] ?? 0),
                'pending_plant_manager' => (int) ($counts['pending_plant_manager'] ?? 0),
            ],
            'areas' => [
                'labels' => $areas->pluck('name')->all(),
                'values' => $areas->pluck('requests_count')->map(fn ($count) => (int) $count)->all(),
            ],
            'decisions' => $decisions,
        ];
    }
}
