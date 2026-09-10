<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Authorization;
use App\Models\Request;
use App\Models\RequestDay;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Áreas
        |--------------------------------------------------------------------------
        */

        $production = Area::firstOrCreate([
            'name' => 'Producción',
        ]);
        $hr = Area::firstOrCreate([
            'name' => 'Recursos Humanos',
        ]);
        $finance = Area::firstOrCreate([
            'name' => 'Finanzas',
        ]);
        $systems = Area::firstOrCreate([
            'name' => 'Sistemas',
        ]);
        $plant_manager = Area::firstOrCreate([
            'name' => 'Gerencia de planta',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Usuarios
        |--------------------------------------------------------------------------
        */

        // Gerente de planta
        $plantManager = User::updateOrCreate(
            ['employee_number' => '0005'],
            [
                'name' => 'Ricardo Cristopher',
                'last_name' => 'Rincon Gonzalez',
                'password' => Hash::make('password'),
                'must_change_password' => true,
                'role' => 'plant_manager',
                'area_id' => $plant_manager->id,
                'group' => null,
                'supervisor_id' => null,
            ]
        );

        // Gerente de RH
        $hr = User::updateOrCreate(
            ['employee_number' => '0004'],
            [
                'name' => 'Uriel',
                'last_name' => 'Mendez Gonzalez',
                'password' => Hash::make('password'),
                'must_change_password' => true,
                'role' => 'hr',
                'area_id' => $hr->id,
                'group' => null,
                'supervisor_id' => null,
            ]
        );

        // Supervisor
        $supervisor = User::updateOrCreate(
            ['employee_number' => '0002'],
            [
                'name' => 'Supervisor',
                'last_name' => 'Prueba',
                'password' => Hash::make('password'),
                'must_change_password' => true,
                'role' => 'supervisor',
                'area_id' => $production->id,
                'group' => 'A',
                'supervisor_id' => $plantManager->id,
            ]
        );

        // Gerente del área (finanazas)
        $areaManager = User::updateOrCreate(
            ['employee_number' => '0003'],
            [
                'name' => 'Carlos Eduardo',
                'last_name' => 'Martinez Palacios',
                'password' => Hash::make('password'),
                'must_change_password' => true,
                'role' => 'manager',
                'area_id' => $finance->id,
                'group' => null,
                'supervisor_id' => null,
            ]
        );


        // Empleado
        $worker = User::updateOrCreate(
            ['employee_number' => '0001'],
            [
                'name' => 'Empleado',
                'last_name' => 'Prueba',
                'password' => Hash::make('password'),
                'must_change_password' => true,
                'role' => 'worker',
                'area_id' => $production->id,
                'group' => 'A',
                'supervisor_id' => null,
            ]
        );
        /*
        |--------------------------------------------------------------------------
        | Relación empleado → supervisor
        |--------------------------------------------------------------------------
        */

        $worker->update([
            'supervisor_id' => $supervisor->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Limpiar solicitudes anteriores
        |--------------------------------------------------------------------------
        */
        Authorization::query()->delete();
        RequestDay::query()->delete();
        Request::query()->delete();

        /*
        |--------------------------------------------------------------------------
        | Solicitud 1 - Pendiente de Supervisor
        |--------------------------------------------------------------------------
        */
        $request1 = Request::create([
            'employee_id' => $worker->id,
            'area_id' => $production->id,
            'group' => 'A',
            'reason' => 'Employee to cover',
            'status' => 'pending_supervisor',
            'week' => now()->weekOfYear,
            'employee_signature' => null,
        ]);

        RequestDay::create([
            'request_id' => $request1->id,
            'day_name' => 'Jueves',
            'day_date' => now()->addDay()->toDateString(),
            'hours' => 4.00,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Solicitud 2 - Pendiente de Gerente del Área
        |--------------------------------------------------------------------------
        */
        $request2 = Request::create([
            'employee_id' => $worker->id,
            'area_id' => $production->id,
            'group' => 'A',
            'reason' => 'Vacancy',
            'status' => 'pending_area_manager',
            'week' => now()->weekOfYear,
            'employee_signature' => null,
        ]);

        RequestDay::create([
            'request_id' => $request2->id,
            'day_name' => 'Miércoles',
            'day_date' => now()->toDateString(),
            'hours' => 3.50,
        ]);

        Authorization::create([
            'request_id' => $request2->id,
            'user_id' => $supervisor->id,
            'authorization_role' => 'supervisor',
            'action' => 'approved',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Solicitud 3 - Pendiente de RH
        |--------------------------------------------------------------------------
        */
        $request3 = Request::create([
            'employee_id' => $worker->id,
            'area_id' => $production->id,
            'group' => 'A',
            'reason' => 'Other',
            'status' => 'pending_hr',
            'week' => now()->weekOfYear,
            'employee_signature' => null,
        ]);

        RequestDay::create([
            'request_id' => $request3->id,
            'day_name' => 'Martes',
            'day_date' => now()->subDay()->toDateString(),
            'hours' => 2.00,
        ]);

        Authorization::create([
            'request_id' => $request3->id,
            'user_id' => $supervisor->id,
            'authorization_role' => 'supervisor',
            'action' => 'approved',
        ]);

        Authorization::create([
            'request_id' => $request3->id,
            'user_id' => $areaManager->id,
            'authorization_role' => 'area_manager',
            'action' => 'approved',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Solicitud 4 - Pendiente de Gerente de Planta
        |--------------------------------------------------------------------------
        */
        $request4 = Request::create([
            'employee_id' => $worker->id,
            'area_id' => $production->id,
            'group' => 'A',
            'reason' => 'Vacation',
            'status' => 'pending_plant_manager',
            'week' => now()->weekOfYear,
            'employee_signature' => null,
        ]);

        RequestDay::create([
            'request_id' => $request4->id,
            'day_name' => 'Miércoles',
            'day_date' => now()->addDays(2)->toDateString(),
            'hours' => 5.00,
        ]);

        Authorization::create([
            'request_id' => $request4->id,
            'user_id' => $supervisor->id,
            'authorization_role' => 'supervisor',
            'action' => 'approved',
        ]);

        Authorization::create([
            'request_id' => $request4->id,
            'user_id' => $areaManager->id,
            'authorization_role' => 'area_manager',
            'action' => 'approved',
        ]);

        Authorization::create([
            'request_id' => $request4->id,
            'user_id' => $hr->id,
            'authorization_role' => 'hr',
            'action' => 'approved',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Solicitud 5 - Completamente aprobada
        |--------------------------------------------------------------------------
        */
        $request5 = Request::create([
            'employee_id' => $worker->id,
            'area_id' => $production->id,
            'group' => 'A',
            'reason' => 'Sick leave',
            'status' => 'approved',
            'week' => now()->weekOfYear,
            'employee_signature' => null,
        ]);

        RequestDay::create([
            'request_id' => $request5->id,
            'day_name' => 'Jueves',
            'day_date' => now()->addDays(4)->toDateString(),
            'hours' => 4.00,
        ]);

        Authorization::create([
            'request_id' => $request5->id,
            'user_id' => $supervisor->id,
            'authorization_role' => 'supervisor',
            'action' => 'approved',
        ]);

        Authorization::create([
            'request_id' => $request5->id,
            'user_id' => $areaManager->id,
            'authorization_role' => 'area_manager',
            'action' => 'approved',
        ]);
        /*
        |--------------------------------------------------------------------------
        | Información en consola
        |--------------------------------------------------------------------------
        */

        $this->command->newLine();

        $this->command->info('Usuarios de prueba creados:');

        $this->command->table(
            ['Employee Number', 'Nombre', 'Rol', 'Password'],
            [
                ['EMP001', 'Empleado Prueba', 'worker', 'password'],
                ['SUP001', 'Supervisor Prueba', 'supervisor', 'password'],
                ['MGR001', 'Carlos Martinez', 'manager', 'password'],
                ['HR001', 'Uriel', 'hr', 'password'],
                ['MGR002', 'Ricardo', 'manager', 'password'],
            ]
        );

        $this->command->newLine();

        $this->command->info('Solicitudes de prueba creadas:');

        $this->command->table(
            ['ID', 'Estado', 'Descripción'],
            [
                [$request1->id, 'pending_supervisor', 'Pendiente de supervisor'],
                [$request2->id, 'pending_area_manager', 'Pendiente de Carlos Martinez'],
                [$request3->id, 'pending_hr', 'Pendiente de Uriel'],
                [$request4->id, 'pending_plant_manager', 'Pendiente de Ricardo'],
                [$request5->id, 'approved', 'Completamente aprobada'],
            ]
        );
    }
}
