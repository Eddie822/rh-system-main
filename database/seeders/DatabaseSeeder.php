<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Authorization;
use App\Models\Request;
use App\Models\RequestDay;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Carbon\Carbon;

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
                'role' => 'hr_manager',
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
                'role' => 'area_manager',
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
| Trabajadores adicionales
|--------------------------------------------------------------------------
*/

$faker = Faker::create('es_MX');

$workers = collect([$worker]);

for ($i = 6; $i <= 55; $i++) {

    $workers->push(
        User::updateOrCreate(
            ['employee_number' => str_pad($i, 4, '0', STR_PAD_LEFT)],
            [
                'name' => $faker->firstName(),
                'last_name' => $faker->lastName() . ' ' . $faker->lastName(),
                'password' => Hash::make('password'),
                'must_change_password' => false,
                'role' => 'worker',
                'area_id' => $production->id,
                'group' => $faker->randomElement(['A', 'B', 'C', 'D']),
                'supervisor_id' => $supervisor->id,
            ]
        )
    );
}


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
| Solicitudes de prueba
|--------------------------------------------------------------------------
*/

$statuses = [
    'pending_area_manager',
    'pending_hr_manager',
    'pending_plant_manager',
    'approved',
    'rejected',
];

$reasons = [
    'Vacation',
    'Sick leave',
    'Employee to cover',
    'Vacancy',
    'Training',
    'Production support',
    'Other',
];

$createdRequests = [];

foreach (range(1, 100) as $index) {

    $employee = $workers->random();

    $status = $faker->randomElement($statuses);

    $baseDate = Carbon::instance(
        $faker->dateTimeBetween('-60 days', '+30 days')
    );

    $request = Request::create([
        'employee_id' => $employee->id,
        'area_id' => $employee->area_id,
        'group' => $employee->group,
        'reason' => $faker->randomElement($reasons),
        'status' => $status,
        'week' => $baseDate->weekOfYear,
        'created_at' => $faker->dateTimeBetween('-90 days', 'now'),
        'updated_at' => now(),
    ]);

    $createdRequests[] = $request;

    $daysCount = rand(1, 5);

    for ($day = 0; $day < $daysCount; $day++) {

        $date = $baseDate->copy()->addDays($day);

        RequestDay::create([
            'request_id' => $request->id,
            'day_name' => ucfirst($date->locale('es')->dayName),
            'day_date' => $date->toDateString(),
            'hours' => $faker->randomFloat(2, 1, 12),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Flujo de autorizaciones
    |--------------------------------------------------------------------------
    */

    if (
        in_array($status, [
            'pending_hr',
            'pending_plant_manager',
            'approved'
        ])
    ) {

        Authorization::create([
            'request_id' => $request->id,
            'user_id' => $areaManager->id,
            'authorization_role' => 'area_manager',
            'action' => 'approved',
        ]);
    }

    if (
        in_array($status, [
            'pending_plant_manager',
            'approved'
        ])
    ) {

        Authorization::create([
            'request_id' => $request->id,
            'user_id' => $hr->id,
            'authorization_role' => 'hr_manager',
            'action' => 'approved',
        ]);
    }

    if ($status === 'approved') {

        Authorization::create([
            'request_id' => $request->id,
            'user_id' => $plantManager->id,
            'authorization_role' => 'plant_manager',
            'action' => 'approved',
        ]);
    }

    if ($status === 'rejected') {

        Authorization::create([
            'request_id' => $request->id,
            'user_id' => $faker->randomElement([
                $areaManager->id,
                $hr->id,
                $plantManager->id,
            ]),
            'authorization_role' => 'area_manager',
            'action' => 'rejected',
            'reason' => 'Solicitud rechazada para pruebas',
        ]);
    }
}

        /*
        |--------------------------------------------------------------------------
        | Información en consola
        |--------------------------------------------------------------------------
        */

        $this->command->newLine();

        $this->command->info('Datos de prueba generados correctamente');

        $this->command->table(
            ['Concepto', 'Cantidad'],
            [
                ['Trabajadores', User::where('role', 'worker')->count()],
                ['Solicitudes', Request::count()],
                ['Autorizaciones', Authorization::count()],
            ]
        );
    }
}
