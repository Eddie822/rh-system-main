<?php

namespace App\Livewire\Admin\User;

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
    $requests = $this->user->requests()
        ->withCount('days')
        ->with(['days' => fn ($q) => $q->orderBy('day_date')])
        ->latest('created_at')
        ->paginate(10);

        return view('livewire.admin.user.user-show', [
            'requests' => $requests,
            'roles' => [
                'worker' => 'Trabajador',
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