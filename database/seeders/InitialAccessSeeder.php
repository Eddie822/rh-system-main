<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InitialAccessSeeder extends Seeder
{
    public function run(): void
    {
        $number = trim((string) config('rh.initial_access.employee_number'));
        $password = config('rh.initial_access.password');
        validator(['employee_number' => $number], [
            'employee_number' => ['required', 'string', 'max:50'],
        ])->validate();

        if ($existing = User::where('employee_number', $number)->first()) {
            $this->command?->info("La nómina {$number} ya existe. Se conservaron sus datos y su contraseña.");
            if (! $existing->canAccessAdminPanel()) {
                $this->command?->warn('Esta cuenta no pertenece a Sistemas o RH. Configura otra nómina en RH_INITIAL_EMPLOYEE_NUMBER para crear el acceso inicial.');
            }

            return;
        }

        $password = $password ?: Str::password(20);
        validator(['password' => $password], ['password' => ['required', 'string', 'min:8']])->validate();

        DB::transaction(function () use ($number, $password) {
            $area = Area::all()->first(fn (Area $area) => Str::lower(Str::ascii(trim($area->name))) === 'sistemas')
                ?? Area::create(['name' => 'Sistemas']);
            // Administration is granted by area, not by an obsolete "admin" role.
            User::create([
                'employee_number' => $number,
                'name' => 'Administrador',
                'apellidos_pat' => 'Sistemas',
                'apellido_mat' => null,
                'puesto' => 'Responsable de Sistemas',
                'area_id' => $area->id,
                'role' => User::where('area_id', $area->id)->where('role', 'area_manager')->exists()
                    ? 'worker' : 'area_manager',
                'direct_manager_id' => null,
                'area_manager_id' => null,
                'password' => $password,
                'must_change_password' => true,
            ]);
        });

        $this->command?->info('Acceso inicial creado. Ingresa en /login con estas credenciales:');
        $this->command?->table(['Número de nómina', 'Contraseña temporal'], [[$number, $password]]);
        $this->command?->info('Deberás cambiar la contraseña al iniciar sesión. Después podrás abrir el panel de administrador e importar a los empleados.');
    }
}
