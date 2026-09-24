<?php

namespace App\Livewire\Admin\User;

use App\Models\Area;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class UserList extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

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

    public function render()
    {
        $users = User::query()
            ->with('area')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%')
                      ->orWhere('employee_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, fn ($query) =>
                $query->where('role', $this->roleFilter)
            )
            ->when($this->areaFilter, fn ($query) =>
                $query->where('area_id', $this->areaFilter)
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.admin.user.user-list', [
            'users' => $users,
            'areas' => Area::orderBy('name')->get(),
            'roles' => [
                'worker' => 'Trabajador',
                'area_manager' => 'Gerente de Área',
                'hr_manager' => 'Gerente de RH',
                'plant_manager' => 'Gerente de Planta',
                'admin' => 'Administrador',
            ],
        ]);
    }
}