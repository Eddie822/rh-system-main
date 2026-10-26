<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Request;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
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
                // Demo data must never reset existing user details or credentials.
                if ($user->exists) {
                    if ($user->role !== $attributes['role'] || $user->area_id !== $attributes['area_id']) {
                        throw new \RuntimeException("La nómina {$number} ya existe con otro rol o área. Ejecuta los datos de ejemplo en una base nueva.");
                    }

                    return $user;
                }
                $user->password = $password;
                $user->must_change_password = true;
                $user->fill(array_merge([
                    'puesto' => null,
                    'direct_manager_id' => null,
                    'area_manager_id' => null,
                ], $attributes))->save();

                return $user;
            };

            $hrManager = $saveUser('0004', [
                'name' => 'Uriel', 'apellidos_pat' => 'Mendez', 'apellido_mat' => 'Gonzalez',
                'puesto' => 'Gerente de Recursos Humanos',
                'role' => 'hr_manager', 'area_id' => $areas['Recursos Humanos']->id,
            ]);
            $plantManager = $saveUser('0005', [
                'name' => 'Ricardo Cristopher', 'apellidos_pat' => 'Rincon', 'apellido_mat' => 'Gonzalez',
                'puesto' => 'Gerente de planta',
                'role' => 'plant_manager', 'area_id' => $areas['Gerencia de planta']->id,
            ]);

            $managers = collect();
            $supervisors = collect();
            foreach (['Producción' => '0101', 'Finanzas' => '0003', 'Sistemas' => 'DEMO-SIS-001'] as $areaName => $number) {
                $area = $areas[$areaName];
                $manager = $saveUser($number, [
                    'name' => $faker->firstName(), 'apellidos_pat' => $faker->lastName(), 'apellido_mat' => $faker->lastName(),
                    'puesto' => 'Gerente de área',
                    'role' => 'area_manager', 'area_id' => $area->id,
                ]);
                $managers->put($area->id, $manager);

                for ($shift = 0; $shift < 3; $shift++) {
                    $index = $supervisors->count();
                    $supervisors->push($saveUser($index === 0 ? '0002' : sprintf('%04d', 201 + $index), [
                        'name' => $faker->firstName(), 'apellidos_pat' => $faker->lastName(), 'apellido_mat' => $faker->lastName(),
                        'role' => 'supervisor', 'area_id' => $area->id,
                        'puesto' => 'Supervisor', 'area_manager_id' => $manager->id,
                        'direct_manager_id' => $manager->id,
                    ]));
                }
            }

            $workers = collect();
            for ($index = 0; $index < 90; $index++) {
                $supervisor = $supervisors[$index % $supervisors->count()];
                $workers->push($saveUser($index === 0 ? '0001' : sprintf('%04d', $index + 5), [
                    'name' => $faker->firstName(), 'apellidos_pat' => $faker->lastName(),
                    'apellido_mat' => $index % 3 === 0 ? null : $faker->lastName(),
                    'role' => 'worker', 'area_id' => $supervisor->area_id,
                    'puesto' => 'Operador',
                ]));
            }

            $statuses = ['pending_area_manager', 'pending_hr_manager', 'pending_plant_manager', 'approved', 'rejected'];
            foreach ($supervisors as $supervisor) {
                // Mix areas deliberately: workers can support any supervisor.
                $local = $workers->where('area_id', $supervisor->area_id)->values();
                $borrowed = $workers->where('area_id', '!=', $supervisor->area_id)->values();
                $offset = $supervisors->search($supervisor) * 5;
                $team = collect(range(0, 4))->flatMap(fn ($index) => [
                    $local[($offset + $index) % $local->count()],
                    $borrowed[($offset + $index) % $borrowed->count()],
                ]);
                foreach ($statuses as $index => $status) {
                    $this->seedRequest($supervisor, $team->slice($index * 2, 2), $status, true,
                        $managers[$supervisor->area_id], $hrManager, $plantManager, $index);
                }
            }

            // Exercise deadline alerts at every approval stage without changing older examples.
            $supervisor = $supervisors->first();
            foreach (['pending_area_manager', 'pending_hr_manager', 'pending_plant_manager'] as $index => $status) {
                foreach (['overdue' => now()->startOfWeek()->subWeeks(2), 'soon' => now()->next(Carbon::TUESDAY)->subWeek()] as $scenario => $date) {
                    $this->seedRequest($supervisor, $workers->whereIn('id', [
                        $workers->firstWhere('area_id', $supervisor->area_id)->id,
                        $workers->first(fn (User $worker) => $worker->area_id !== $supervisor->area_id)->id,
                    ]), $status, true,
                        $managers[$supervisor->area_id], $hrManager, $plantManager,
                        5 + $index * 2 + ($scenario === 'soon' ? 1 : 0), $date);
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
        $this->command?->info('Nóminas de referencia (las cuentas existentes conservan su contraseña):');
        $this->command?->table(['Perfil', 'Nómina'], [
            ['Empleado', '0001'], ['Supervisor', '0002'],
            ['Gerente de área · Finanzas', '0003'], ['Gerente de RH', '0004'],
            ['Gerente de planta', '0005'], ['Gerente de área · Producción', '0101'],
            ['Sistemas · Panel de administrador', 'DEMO-SIS-001'],
        ]);
    }

    private function seedRequest(User $creator, Collection $employees, string $status, bool $grouped,
        User $areaManager, User $hrManager, User $plantManager, int $index, ?Carbon $startDate = null): void
    {
        // The seed reference is stable even if a manager later changes the status.
        $reference = 'DEMO-'.($grouped ? 'GRUPAL' : 'INDIVIDUAL').'-'.$creator->employee_number.'-'.$index;
        $firstDay = $startDate?->copy()->startOfDay() ?? now()->addWeeks(2 + $index)->startOfWeek();
        $request = Request::firstOrCreate([
            'employee_id' => $creator->id,
            'is_group' => $grouped,
            'reason' => $reference.' · Apoyo por carga de trabajo',
        ], [
            'area_id' => $creator->area_id,
            'group' => 'Línea de producción',
            'status' => $status,
            'week' => $firstDay->isoWeek(),
        ]);

        // Do not delete or overwrite existing requests, their days, or decisions.
        if (! $request->wasRecentlyCreated) {
            return;
        }
        if ($grouped) {
            $request->participants()->attach($employees->pluck('id'));
        }
        foreach ($employees as $employee) {
            foreach (($firstDay->isSunday() ? [2] : [2, 3.5]) as $offset => $hours) {
                // Keep both days in the same ISO week, including Sunday scenarios.
                $date = $firstDay->copy()->addDays($offset);
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
