<?php

namespace App\Livewire\Admin\User;

use App\Models\Request;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserShow extends Component
{
    use WithPagination;

    public User $user;

    protected $paginationTheme = 'tailwind';

    public function mount(User $user)
    {
        $this->user = $user;
    }

    public function render()
    {
        $days = fn ($query) => $query->where(fn ($query) => $query
            ->whereNull('employee_id')->orWhere('employee_id', $this->user->id)
            ->orWhereHas('request', fn ($query) => $query->where('employee_id', $this->user->id)));
        $requests = Request::forEmployee($this->user)
            ->withCount(['days' => $days])
            ->with(['days' => fn ($query) => $days($query)->orderBy('day_date')])
            ->latest('created_at')
            ->paginate(10);

        return view('livewire.admin.user.user-show', [
            'requests' => $requests,
            'roles' => [
                'worker' => 'Trabajador',
                'supervisor' => 'Supervisor',
                'area_manager' => 'Gerente de Área',
                'hr_manager' => 'Gerente de RH',
                'plant_manager' => 'Gerente de Planta',
                'admin' => 'Administrador',
            ],
            'statusLabels' => [
                'pending_area_manager' => 'Pendiente - Gerente de Área',
                'pending_hr_manager' => 'Pendiente - RH',
                'pending_plant_manager' => 'Pendiente - Gerente de Planta',
                'approved' => 'Aprobada',
                'rejected' => 'Rechazada',
            ],
        ]);
    }
}
