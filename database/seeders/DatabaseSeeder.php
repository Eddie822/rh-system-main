<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Request;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create('es_MX');
            $faker->seed(20260928);
            $password = Hash::make('password');
            $areas = collect(['Producción', 'Finanzas', 'Sistemas', 'Recursos Humanos', 'Gerencia de planta'])
                ->mapWithKeys(fn ($name) => [$name => Area::firstOrCreate(['name' => $name])]);

            // Stable payroll numbers keep reruns from duplicating the demo users.
            // Only new accounts receive the demo password; existing passwords are preserved.
            $saveUser = function (string $number, array $attributes) use ($password): User {
                $user = User::firstOrNew(['employee_number' => $number]);
                // Preserve the confirmed job assignments when reseeding existing accounts.
                if ($user->exists && in_array($number, ['0356', '0094', '0103'], true)) {
                    return $user;
                }
                if (! $user->exists) {
                    $user->password = $password;
                    $user->must_change_password = true;
                }
                $user->fill(array_merge([
                    'group' => null,
                    'area_manager_id' => null,
                    'supervisor_id' => null,
                ], $attributes))->save();

                return $user;
            };

            $hrManager = $saveUser('0004', [
                'name' => 'Uriel', 'last_name' => 'Mendez Gonzalez',
                'role' => 'hr_manager', 'area_id' => $areas['Recursos Humanos']->id,
            ]);
            $plantManager = $saveUser('0005', [
                'name' => 'Ricardo Cristopher', 'last_name' => 'Rincon Gonzalez',
                'role' => 'plant_manager', 'area_id' => $areas['Gerencia de planta']->id,
            ]);

            $managers = collect();
            $supervisors = collect();
            foreach (['Producción' => '0101', 'Finanzas' => '0003', 'Sistemas' => '0356'] as $areaName => $number) {
                $area = $areas[$areaName];
                $manager = $saveUser($number, [
                    'name' => $faker->firstName(), 'last_name' => $faker->lastName().' '.$faker->lastName(),
                    'role' => 'area_manager', 'area_id' => $area->id,
                ]);
                $managers->put($area->id, $manager);

                foreach (['A', 'B', 'C'] as $group) {
                    $index = $supervisors->count();
                    $supervisors->push($saveUser($index === 0 ? '0002' : sprintf('%04d', 201 + $index), [
                        'name' => $faker->firstName(), 'last_name' => $faker->lastName().' '.$faker->lastName(),
                        'role' => 'supervisor', 'area_id' => $area->id,
                        'group' => $group, 'area_manager_id' => $manager->id,
                    ]));
                }
            }

            $workers = collect();
            for ($index = 0; $index < 90; $index++) {
                $supervisor = $supervisors[$index % $supervisors->count()];
                $workers->push($saveUser($index === 0 ? '0001' : sprintf('%04d', $index + 5), [
                    'name' => $faker->firstName(), 'last_name' => $faker->lastName().' '.$faker->lastName(),
                    'role' => 'worker', 'area_id' => $supervisor->area_id,
                    'group' => $supervisor->group, 'area_manager_id' => $supervisor->area_manager_id,
                    'supervisor_id' => $supervisor->id,
                ]));
            }

            $statuses = ['pending_area_manager', 'pending_hr_manager', 'pending_plant_manager', 'approved', 'rejected'];
            foreach ($supervisors as $supervisor) {
                $team = $workers->where('supervisor_id', $supervisor->id)->values();
                foreach ($statuses as $index => $status) {
                    $this->seedRequest($supervisor, $team->slice($index * 2, 2), $status, true,
                        $managers[$supervisor->area_id], $hrManager, $plantManager, $index);
                }
            }

            // Retain individual examples alongside the new grouped requests.
            foreach ($workers as $index => $worker) {
                $this->seedRequest($worker, collect([$worker]), $statuses[$index % count($statuses)], false,
                    $managers[$worker->area_id], $hrManager, $plantManager, $index % count($statuses));
            }
        });

        $this->command?->info('Datos de prueba generados. Nuevas cuentas: contraseña password; cambio obligatorio al ingresar.');
        $this->command?->table(['Concepto', 'Cantidad total'], [
            ['Gerentes de área', User::where('role', 'area_manager')->count()],
            ['Supervisores', User::where('role', 'supervisor')->count()],
            ['Trabajadores', User::where('role', 'worker')->count()],
            ['Solicitudes grupales', Request::where('is_group', true)->count()],
            ['Solicitudes individuales', Request::where('is_group', false)->count()],
        ]);
    }

    private function seedRequest(User $creator, $employees, string $status, bool $grouped,
        User $areaManager, User $hrManager, User $plantManager, int $index): void
    {
        // The seed reference is stable even if a manager later changes the status.
        $reference = 'DEMO-'.($grouped ? 'GRUPAL' : 'INDIVIDUAL').'-'.$creator->employee_number.'-'.$index;
        $monday = now()->addWeeks(2 + $index)->startOfWeek();
        $request = Request::firstOrCreate([
            'employee_id' => $creator->id,
            'is_group' => $grouped,
            'reason' => $reference.' · Apoyo por carga de trabajo',
        ], [
            'area_id' => $creator->area_id,
            'group' => $creator->group,
            'status' => $status,
            'week' => $monday->isoWeek(),
        ]);

        // Do not delete or overwrite existing requests, their days, or decisions.
        if (! $request->wasRecentlyCreated) {
            return;
        }
        if ($grouped) {
            $request->participants()->attach($employees->pluck('id'));
        }
        foreach ($employees as $employee) {
            foreach ([2, 3.5] as $offset => $hours) {
                $date = $monday->copy()->addDays($offset);
                $request->days()->create([
                    'employee_id' => $grouped ? $employee->id : null,
                    'day_name' => ucfirst($date->locale('es')->dayName),
                    'day_date' => $date->toDateString(),
                    'hours' => $hours,
                ]);
            }
        }

        $approvedStages = match ($status) {
            'pending_hr_manager' => [$areaManager],
            'pending_plant_manager' => [$areaManager, $hrManager],
            'approved' => [$areaManager, $hrManager, $plantManager],
            default => [],
        };
        foreach ($approvedStages as $manager) {
            $request->authorizations()->create([
                'user_id' => $manager->id, 'authorization_role' => $manager->role, 'action' => 'approved',
            ]);
        }
        if ($status === 'rejected') {
            $request->authorizations()->create([
                'user_id' => $areaManager->id, 'authorization_role' => 'area_manager',
                'action' => 'rejected', 'reason' => 'No se requiere cobertura adicional para el grupo solicitado.',
            ]);
        }
    }
}
