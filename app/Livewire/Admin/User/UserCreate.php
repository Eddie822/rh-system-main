<?php

namespace App\Livewire\Admin\User;

use App\Models\Area;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class UserCreate extends Component
{
    public string $employee_number = '';

    public string $name = '';

    public string $last_name = '';

    public string $role = 'worker';

    public string $area_id = '';

    public string $group = '';

    public string $supervisor_id = '';

    public string $area_manager_id = '';

    public string $direct_manager_number = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function save()
    {
        abort_unless(auth()->user()?->canAccessAdminPanel(), 403);
        $this->employee_number = trim($this->employee_number);
        $this->name = trim($this->name);
        $this->last_name = trim($this->last_name);
        $this->direct_manager_number = trim($this->direct_manager_number);
        $bossRole = match ($this->role) {
            'worker' => ['supervisor', 'area_manager'],
            'supervisor' => ['area_manager'],
            'plant_manager' => null,
            default => ['plant_manager'],
        };
        $validated = $this->validate([
            'employee_number' => ['required', 'string', 'max:50', Rule::unique('users', 'employee_number')],
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['worker', 'supervisor', 'area_manager', 'hr_manager', 'plant_manager'])],
            'area_id' => [in_array($this->role, ['worker', 'supervisor', 'area_manager'], true) ? 'required' : 'nullable', 'exists:areas,id'],
            'group' => ['nullable', 'string', 'max:255'],
            'supervisor_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'supervisor')],
            'area_manager_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'area_manager')],
            'direct_manager_number' => ['string', 'max:50', in_array($this->role, ['area_manager', 'hr_manager', 'plant_manager'], true) ? 'nullable' : 'required', Rule::exists('users', 'employee_number')->whereIn('role', $bossRole ?? ['plant_manager'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'direct_manager_number.required' => 'La nómina del jefe directo es obligatoria.',
            'direct_manager_number.exists' => 'La nómina debe corresponder a un jefe existente con el rol permitido.',
            'direct_manager_number.max' => 'La nómina del jefe directo admite máximo 50 caracteres.',
        ]);
        $validated['direct_manager_id'] = $this->direct_manager_number === '' ? null
            : User::where('employee_number', $this->direct_manager_number)->value('id');
        unset($validated['direct_manager_number']);
        if ($bossRole === null) {
            $validated['direct_manager_id'] = null;
        } elseif (in_array($validated['role'], ['worker', 'supervisor'], true)) {
            $boss = User::findOrFail($validated['direct_manager_id']);
            if ($boss->area_id !== (int) $validated['area_id']) {
                throw ValidationException::withMessages(['direct_manager_number' => 'El jefe directo debe pertenecer a la misma área.']);
            }
        }
        if ($validated['role'] === 'plant_manager' && User::where('role', 'plant_manager')->exists()) {
            throw ValidationException::withMessages(['role' => 'Ya existe un gerente de planta.']);
        }
        if ($validated['role'] === 'hr_manager' && User::where('role', 'hr_manager')->exists()) {
            throw ValidationException::withMessages(['role' => 'Ya existe un gerente de RH.']);
        }
        if ($validated['role'] === 'area_manager' && User::where('role', 'area_manager')->where('area_id', $validated['area_id'])->exists()) {
            throw ValidationException::withMessages(['area_id' => 'Esta área ya tiene un gerente de área.']);
        }
        if ($validated['role'] === 'worker') {
            $validated['supervisor_id'] = $boss->role === 'supervisor' ? $boss->id : null;
            $managers = User::where('role', 'area_manager')->where('area_id', $validated['area_id'])->pluck('id');
            if ($managers->count() !== 1) {
                throw ValidationException::withMessages(['area_id' => 'El área debe tener exactamente un gerente de área.']);
            }
            $validated['area_manager_id'] = $managers->first();
        } elseif ($validated['role'] === 'supervisor') {
            $validated['supervisor_id'] = null;
            $validated['area_manager_id'] = $validated['direct_manager_id'];
        } else {
            $validated['supervisor_id'] = null;
            $validated['area_manager_id'] = null;
        }
        foreach (['area_id', 'supervisor_id', 'area_manager_id', 'direct_manager_id'] as $field) {
            $validated[$field] = $validated[$field] ?: null;
        }
        $validated['must_change_password'] = true;
        $user = User::create($validated);
        session()->flash('success', 'Usuario creado correctamente. Debe cambiar su contraseña al iniciar sesión.');

        return redirect()->route('admin.users.edit', $user);
    }

    public function render()
    {
        abort_unless(auth()->user()?->canAccessAdminPanel(), 403);

        return view('livewire.admin.user.user-create', [
            'areas' => Area::orderBy('name')->get(),
            'supervisors' => User::where('role', 'supervisor')->orderBy('name')->get(),
            'areaManagers' => User::where('role', 'area_manager')->orderBy('name')->get(),
            'directManagers' => User::whereIn('role', match ($this->role) {
                'worker' => ['supervisor', 'area_manager'],
                'supervisor' => ['area_manager'],
                default => ['plant_manager'],
            })->when(in_array($this->role, ['worker', 'supervisor'], true) && $this->area_id !== '', fn ($query) => $query->where('area_id', $this->area_id))->orderBy('name')->get(),
            'roles' => [
                'worker' => 'Trabajador', 'supervisor' => 'Supervisor',
                'area_manager' => 'Gerente de Área', 'hr_manager' => 'Gerente de RH',
                'plant_manager' => 'Gerente de Planta',
            ],
        ]);
    }
}
