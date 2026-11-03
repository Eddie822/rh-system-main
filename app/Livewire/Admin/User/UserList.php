<?php

namespace App\Livewire\Admin\User;

use App\Actions\DeleteUsers;
use App\Models\Area;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public array $selected = [];

    public array $deletionBlocked = [];

    public string $deletionSummary = '';

    #[Url(history: true)]
    public string $roleFilter = '';

    #[Url(history: true)]
    public string $areaFilter = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingAreaFilter()
    {
        $this->resetPage();
    }

    public function sortBy(string $field)
    {
        if (! in_array($field, ['name', 'employee_number'], true)) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'roleFilter', 'areaFilter']);
        $this->resetPage();
    }

    private function baseUsersQuery()
    {
        $sortField = in_array($this->sortField, ['name', 'employee_number'], true) ? $this->sortField : 'name';
        $sortDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $search = trim($this->search);

        $query = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('apellidos_pat', 'like', '%'.$search.'%')
                        ->orWhere('apellido_mat', 'like', '%'.$search.'%')
                        ->orWhere('employee_number', 'like', '%'.$search.'%');
                });
            })
            ->when($this->roleFilter, fn ($query) => $query->where('role', $this->roleFilter)
            )
            ->when($this->areaFilter, fn ($query) => $query->where('area_id', $this->areaFilter)
            );

        if ($search !== '') {
            $numericSearch = ctype_digit($search) ? (int) (ltrim($search, '0') ?: '0') : -1;
            $query->orderByRaw(
                'CASE WHEN employee_number = ? THEN 0 WHEN CAST(employee_number AS UNSIGNED) = ? THEN 1 WHEN employee_number LIKE ? THEN 2 WHEN employee_number LIKE ? THEN 3 ELSE 4 END ASC, CAST(employee_number AS UNSIGNED) ASC, LENGTH(employee_number) ASC',
                [$search, $numericSearch, $search.'%', '%'.$search.'%']
            );
        }

        if ($sortField === 'employee_number') {
            return $query->orderByRaw(
                "CAST(employee_number AS UNSIGNED) {$sortDirection}, LENGTH(employee_number) {$sortDirection}, employee_number {$sortDirection}"
            );
        }

        return $query->orderBy($sortField, $sortDirection);
    }

    private function usersQuery()
    {
        return $this->baseUsersQuery()
            ->with(['area', 'areaManager', 'directManager']);
    }

    public function selectPage(): void
    {
        Gate::authorize('deleteUsers');
        $ids = $this->baseUsersQuery()
            ->forPage($this->getPage(), 15)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $this->selected = array_values(array_unique(array_merge($this->selected, $ids)));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->resetValidation('selected');
    }

    public function deleteSelected(): void
    {
        Gate::authorize('deleteUsers');
        $this->validate([
            'selected' => ['required', 'array', 'min:1', 'max:500'],
            'selected.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);
        $result = app(DeleteUsers::class)->handle($this->selected);
        $this->deletionBlocked = $result['blocked'];
        $this->deletionSummary = $result['deleted'].' usuario(s) eliminado(s); '.count($result['blocked']).' conservado(s).';
        $this->selected = [];
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth()->user()?->canAccessAdminPanel(), 403);
        $users = $this->usersQuery()->paginate(15);

        return view('livewire.admin.user.user-list', [
            'users' => $users,
            'areas' => Area::orderBy('name')->get(),
            'roles' => [
                'worker' => 'Trabajador',
                'supervisor' => 'Supervisor',
                'area_manager' => 'Gerente de Área',
                'hr_manager' => 'Gerente de RH',
                'plant_manager' => 'Gerente de Planta',
            ],
        ]);
    }
}
