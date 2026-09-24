<?php

namespace App\Livewire\Admin\User;

use App\Models\Area;
use App\Models\User;
use Livewire\Component;
use Illuminate\Validation\Rule;

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
        $this->group = $user->group;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',           
             'employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'employee_number')->ignore($this->user->id),
            ],
            'role' => ['required', Rule::in(['worker', 'area_manager', 'hr_manager', 'plant_manager', 'admin'])],
            'area_id' => ['nullable', 'exists:areas,id'],
            'area_manager_id' => [
                'nullable',
                'exists:users,id',
                Rule::notIn([$this->user->id]), // un usuario no puede ser su propio gerente
            ],
            'group' => ['nullable', 'string', 'max:255'],

        ];
    }

    protected function messages(): array
    {
        return [
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

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $validated = $this->validate();

        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        unset($validated['password_confirmation']);

        $this->user->update($validated);

        $this->password = null;
        $this->password_confirmation = null;
        $this->showSuccess = true;

        $this->dispatch('user-updated', userId: $this->user->id);
    }

    public function render()
    {
        return view('livewire.admin.user.user-edit', [
            'areas' => Area::orderBy('name')->get(),
            'areaManagers' => User::where('id', '!=', $this->user->id)
                ->where('role', 'area_manager')
                ->orderBy('name')
                ->get(),
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