<?php

namespace App\Livewire\Admin\User;

use App\Models\Area;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class UserEdit extends Component
{
    public User $user;

    public string $name = '';

    public string $last_name = '';

    public string $employee_number = '';

    public ?string $password = null;

    public ?string $password_confirmation = null;

    public string $role = '';

    public ?int $area_id = null;

    public ?int $area_manager_id = null;

    public ?int $supervisor_id = null;

    public ?int $direct_manager_id = null;

    public string $direct_manager_number = '';

    public ?string $group = null;

    public bool $showSuccess = false;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->last_name = $user->last_name;
        $this->employee_number = $user->employee_number;
        $this->role = $user->role;
        $this->area_id = $user->area_id;
        $this->area_manager_id = $user->area_manager_id;
        $this->supervisor_id = $user->supervisor_id;
        $this->direct_manager_id = $user->direct_manager_id ?? ($user->role === 'supervisor' ? $user->area_manager_id : null);
        $this->direct_manager_number = User::find($this->direct_manager_id)?->employee_number ?? '';
        $this->group = $user->group;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'employee_number')->ignore($this->user->id),
            ],
            'role' => ['required', Rule::in(['worker', 'supervisor', 'area_manager', 'hr_manager', 'plant_manager'])],
            'supervisor_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'supervisor'), Rule::notIn([$this->user->id])],
            'area_id' => ['nullable', 'exists:areas,id'],
            'area_manager_id' => [
                'nullable',
                'exists:users,id',
                Rule::notIn([$this->user->id]), // un usuario no puede ser su propio gerente
            ],
            'direct_manager_id' => ['nullable', 'exists:users,id', Rule::notIn([$this->user->id])],
            'direct_manager_number' => ['nullable', 'string', 'max:50', 'exists:users,employee_number', Rule::notIn([$this->employee_number])],
            'group' => ['nullable', 'string', 'max:255'],

        ];
    }

    protected function messages(): array
    {
        return [
            'direct_manager_number.exists' => 'No existe un usuario con esa nómina.',
            'direct_manager_number.not_in' => 'El usuario no puede ser su propio jefe.',
            'direct_manager_number.max' => 'La nómina del jefe directo admite máximo 50 caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'employee_number.required' => 'El número de nómina es obligatorio.',
            'employee_number.unique' => 'Ese número de nómina ya está registrado.',
            'role.required' => 'Selecciona un rol.',
            'role.in' => 'El rol seleccionado no es válido.',
            'area_manager_id.not_in' => 'Un usuario no puede ser su propio jefe inmediato.',
        ];
    }

    public function updatedSupervisorId($value): void
    {
        $this->direct_manager_id = $value ?: null;
        $this->direct_manager_number = User::find($this->direct_manager_id)?->employee_number ?? '';
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        abort_unless(auth()->user()?->canAccessAdminPanel(), 403);
        $this->direct_manager_number = trim($this->direct_manager_number);
        $this->validateOnly('direct_manager_number');
        $this->direct_manager_id = $this->direct_manager_number === '' ? null
            : User::where('employee_number', $this->direct_manager_number)->value('id');
        $validated = $this->validate();
        unset($validated['direct_manager_number']);

        if (in_array($validated['role'], ['plant_manager', 'hr_manager'], true)
            && User::where('role', $validated['role'])->whereKeyNot($this->user->id)->exists()) {
            throw ValidationException::withMessages(['role' => 'Ya existe un usuario con este cargo único.']);
        }
        if ($validated['role'] === 'area_manager'
            && User::where('role', 'area_manager')->where('area_id', $validated['area_id'])->whereKeyNot($this->user->id)->exists()) {
            throw ValidationException::withMessages(['area_id' => 'Esta área ya tiene un gerente de área.']);
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        unset($validated['password_confirmation']);
        if ($validated['role'] !== 'worker') {
            $validated['supervisor_id'] = null;
        }
        if (! in_array($validated['role'], ['worker', 'supervisor'], true)) {
            $validated['area_manager_id'] = null;
        }

        $expectedBossRole = match ($validated['role']) {
            'worker' => ['supervisor', 'area_manager'],
            'supervisor' => ['area_manager'],
            'plant_manager' => null,
            default => ['plant_manager'],
        };
        if ($expectedBossRole === null) {
            $validated['direct_manager_id'] = null;
        } else {
            $bossId = $validated['direct_manager_id'];
            if ($bossId) {
                $boss = User::findOrFail($bossId);
                if (! in_array($boss->role, $expectedBossRole, true) || (in_array($validated['role'], ['worker', 'supervisor'], true) && $boss->area_id !== $validated['area_id'])) {
                    throw ValidationException::withMessages(['direct_manager_number' => 'El jefe directo debe tener el rol correcto y pertenecer a la misma área.']);
                }
            }
            $validated['direct_manager_id'] = $bossId;
            if ($validated['role'] === 'worker' && $bossId) {
                $validated['supervisor_id'] = $boss->role === 'supervisor' ? $bossId : null;
                $validated['area_manager_id'] = User::where('role', 'area_manager')->where('area_id', $validated['area_id'])->value('id');
            }
            if ($validated['role'] === 'supervisor') {
                $validated['area_manager_id'] = $bossId;
            }
        }

        $this->user->update($validated);

        $this->password = null;
        $this->password_confirmation = null;
        $this->showSuccess = true;

        $this->dispatch('user-updated', userId: $this->user->id);
    }

    public function render()
    {
        abort_unless(auth()->user()?->canAccessAdminPanel(), 403);

        return view('livewire.admin.user.user-edit', [
            'areas' => Area::orderBy('name')->get(),
            'supervisors' => User::where('role', 'supervisor')->whereKeyNot($this->user->id)->orderBy('name')->get(),
            'areaManagers' => User::where('id', '!=', $this->user->id)
                ->where('role', 'area_manager')
                ->orderBy('name')
                ->get(),
            'directManagers' => User::where('id', '!=', $this->user->id)
                ->whereIn('role', match ($this->role) {
                    'worker' => ['supervisor', 'area_manager'],
                    'supervisor' => ['area_manager'],
                    default => ['plant_manager'],
                })->orderBy('name')->get(),
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
