<?php

namespace Tests\Feature;

use App\Imports\UserRosterImporter;
use App\Livewire\Admin\User\UserCreate;
use App\Livewire\Admin\User\UserEdit;
use App\Livewire\Admin\User\UserList;
use App\Models\Area;
use App\Models\Request;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private ?string $excelTemporaryPath = null;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.url', null);
        $this->excelTemporaryPath = sys_get_temp_dir().'/rh-user-tests-'.bin2hex(random_bytes(8));
        $app['config']->set('excel.temporary_files.local_path', $this->excelTemporaryPath);

        return $app;
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            if ($this->excelTemporaryPath) {
                (new Filesystem)->deleteDirectory($this->excelTemporaryPath);
            }
        }
    }

    private function user(string $role = 'supervisor'): User
    {
        static $number = 5000;

        $user = new User;
        $user->forceFill([
            'employee_number' => (string) ++$number,
            'name' => 'Tester', (Schema::hasColumn('users', 'apellidos_pat') ? 'apellidos_pat' : 'last_name') => 'Admin',
            'password' => 'password123', 'role' => $role,
            'must_change_password' => false,
            'area_id' => func_num_args() === 0 ? Area::firstOrCreate(['name' => 'Sistemas'])->id : null,
        ])->save();

        return $user;
    }

    private function roster(array $rows): UploadedFile
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->fromArray([UserRosterImporter::HEADERS]);
        foreach ($rows as $offset => $row) {
            foreach ($row as $column => $value) {
                $sheet->setCellValueExplicit([$column + 1, $offset + 2], $value,
                    is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'roster-');
        IOFactory::createWriter($book, 'Xlsx')->save($path);
        $book->disconnectWorksheets();

        return new UploadedFile($path, 'roster.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_admin_can_create_user_and_area_crud_respects_assignments(): void
    {
        $this->actingAs($this->user());
        $this->get(route('admin.areas.index'))->assertOk();
        $this->post(route('admin.areas.store'), ['name' => '  Producción  '])->assertRedirect(route('admin.areas.index'));
        $area = Area::where('name', 'Producción')->firstOrFail();
        $this->post(route('admin.areas.store'), ['name' => 'Producción'])->assertSessionHasErrors('name');
        $this->get(route('admin.areas.edit', $area))->assertOk()->assertSee('confirmAreaEdit');
        $this->put(route('admin.areas.update', $area), ['name' => 'Empaque'])->assertRedirect(route('admin.areas.index'));
        $this->assertDatabaseHas('areas', ['id' => $area->id, 'name' => 'Empaque']);

        $this->get(route('admin.users.create'))->assertOk()->assertSee('Empaque');
        $plant = $this->user('plant_manager');
        $manager = User::create([
            'employee_number' => '0098', 'name' => 'Gerente', 'apellidos_pat' => 'Área',
            'role' => 'area_manager', 'area_id' => $area->id, 'direct_manager_id' => $plant->id,
            'password' => 'password123',
        ]);
        $supervisor = User::create([
            'employee_number' => '0099', 'name' => 'Supervisora', 'apellidos_pat' => 'Prueba',
            'role' => 'supervisor', 'area_id' => $area->id, 'area_manager_id' => $manager->id,
            'direct_manager_id' => $manager->id, 'password' => 'password123',
        ]);
        Livewire::test(UserCreate::class)
            ->set('employee_number', '0007')->set('name', 'Ana')->set('apellidos_pat', 'Ruiz')
            ->set('role', 'worker')->set('area_id', (string) $area->id)
            ->set('direct_manager_number', $supervisor->employee_number)
            ->set('password', 'temporary123')->set('password_confirmation', 'temporary123')
            ->call('save')->assertHasNoErrors();
        $user = User::where('employee_number', '0007')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertNull($user->direct_manager_id);
        $this->assertTrue(Hash::check('temporary123', $user->password));
        $this->delete(route('admin.areas.destroy', $area))->assertSessionHas('error');
        $this->assertDatabaseHas('areas', ['id' => $area->id]);

        $spare = Area::create(['name' => 'Almacén']);
        $this->delete(route('admin.areas.destroy', $spare))->assertRedirect(route('admin.areas.index'));
        $this->assertDatabaseMissing('areas', ['id' => $spare->id]);
    }

    public function test_import_and_area_management_are_restricted(): void
    {
        $this->actingAs($this->user('worker'));
        $this->get(route('admin.users.import.index'))->assertForbidden();
        $this->get(route('admin.areas.index'))->assertForbidden();
    }

    public function test_panel_permissions_depend_on_area_and_not_job_role(): void
    {
        foreach (['Sistemas', 'RH', 'Recursos Humanos'] as $name) {
            $area = Area::firstOrCreate(['name' => $name]);
            foreach (['worker', 'supervisor', 'area_manager', 'hr_manager', 'plant_manager'] as $role) {
                $user = $this->user($role);
                $user->update(['area_id' => $area->id]);
                app('session')->flush();
                app('auth')->forgetGuards();
                $this->actingAs($user, 'web')->get(route('admin.dashboard'))->assertOk();
                foreach (['accessAdminPanel', 'viewReports', 'manageAreas', 'importUsers', 'deleteUsers'] as $ability) {
                    $this->assertTrue($user->can($ability));
                }
                $this->get(route('requests.index'))->assertSee('Panel de administrador');
            }
        }
        $otherArea = Area::firstOrCreate(['name' => 'Producción']);
        foreach (['worker', 'supervisor', 'area_manager', 'hr_manager', 'plant_manager', 'admin'] as $role) {
            $user = $this->user($role);
            $user->update(['area_id' => $otherArea->id]);
            app('session')->flush();
            app('auth')->forgetGuards();
            $this->actingAs($user, 'web')->get(route('admin.dashboard'))->assertForbidden();
            $this->assertFalse($user->can('deleteUsers'));
        }
    }

    public function test_systems_area_manager_can_create_an_unassigned_employee(): void
    {
        $manager = $this->user('area_manager');
        $area = Area::firstOrCreate(['name' => 'Sistemas']);
        $manager->update(['area_id' => $area->id]);
        Livewire::actingAs($manager)->test(UserCreate::class)
            ->set('employee_number', '0081')->set('name', 'Ana')->set('apellidos_pat', 'Ruiz')
            ->set('area_id', (string) $area->id)->set('direct_manager_number', $manager->employee_number)
            ->set('password', 'password123')->set('password_confirmation', 'password123')
            ->call('save')->assertHasNoErrors();
        $worker = User::where('employee_number', '0081')->firstOrFail();
        $this->assertNull($worker->direct_manager_id);
        $this->assertNull($worker->area_manager_id);
        $this->assertTrue($worker->canAccessAdminPanel());
    }

    public function test_confirmed_legacy_accounts_are_converted_without_losing_requests(): void
    {
        $restructure = require database_path('migrations/2026_10_22_000001_restructure_employee_details.php');
        $restructure->down();
        $area = Area::create(['name' => 'Sistemas']);
        $manager = $this->user('admin');
        $manager->update(['employee_number' => '0356', 'area_id' => $area->id]);
        $employee = $this->user('admin');
        $employee->update(['employee_number' => '0094', 'area_id' => $area->id]);
        $oldManager = $this->user('area_manager');
        $oldManager->update(['employee_number' => '0103', 'area_id' => $area->id]);
        $supervisor = $this->user('supervisor');
        $supervisor->update(['area_id' => $area->id, 'area_manager_id' => $oldManager->id, 'direct_manager_id' => $oldManager->id]);
        $request = Request::create([
            'employee_id' => $employee->id, 'area_id' => $area->id,
            'group' => 'A', 'reason' => 'Horas extra', 'week' => '1',
        ]);
        $migration = require database_path('migrations/2026_10_07_000001_replace_systems_admin_roles.php');
        $migration->up();
        $this->assertSame('area_manager', $manager->fresh()->role);
        foreach ([$employee, $oldManager] as $worker) {
            $worker->refresh();
            $this->assertSame('worker', $worker->role);
            $this->assertSame($manager->id, $worker->direct_manager_id);
            $this->assertSame($manager->id, $worker->area_manager_id);
        }
        $this->assertSame($manager->id, $supervisor->fresh()->direct_manager_id);
        $this->assertSame($manager->id, $supervisor->fresh()->area_manager_id);
        $this->assertSame($employee->id, $request->fresh()->employee_id);
        $migration->up();
        $this->assertSame(1, User::where('area_id', $area->id)->where('role', 'area_manager')->count());
    }

    public function test_direct_manager_is_entered_by_payroll_number_when_editing(): void
    {
        $this->actingAs($this->user());
        $area = Area::firstOrCreate(['name' => 'Sistemas']);
        $manager = $this->user('area_manager');
        $manager->update(['employee_number' => '0356', 'area_id' => $area->id]);
        $employee = $this->user('supervisor');
        $employee->update(['area_id' => $area->id, 'direct_manager_id' => $manager->id]);
        $component = Livewire::test(UserEdit::class, ['user' => $employee])
            ->assertSet('direct_manager_number', '0356')
            ->set('direct_manager_number', '999999')->call('save')
            ->assertHasErrors(['direct_manager_number']);
        $this->assertSame($manager->id, $employee->fresh()->direct_manager_id);
        $component->set('direct_manager_number', $employee->employee_number)->call('save')
            ->assertHasErrors(['direct_manager_number']);
        $component->set('direct_manager_number', ' 0356 ')->call('save')->assertHasNoErrors();
        $this->assertSame($manager->id, $employee->fresh()->direct_manager_id);
        $this->assertSame($manager->id, $employee->fresh()->area_manager_id);
    }

    public function test_admin_can_delete_an_unreferenced_user_and_sees_sweetalert_confirmation(): void
    {
        $admin = $this->user();
        $unused = $this->user('worker');
        $this->actingAs($admin);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(route('admin.users.destroy', $unused))
            ->assertSee('confirmDeleteForm(event, this)')
            ->assertSee('Swal.fire');

        $this->delete(route('admin.users.destroy', $unused))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('deleted');
        $this->assertDatabaseMissing('users', ['id' => $unused->id]);
    }

    public function test_user_deletion_deletes_users_with_requests_and_protects_own_account(): void
    {
        $admin = $this->user();
        $employee = $this->user('worker');
        $manager = $this->user('supervisor');
        $this->actingAs($admin);
        $employee->update(['role' => 'supervisor', 'direct_manager_id' => $manager->id]);
        $this->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');

        $area = Area::create(['name' => 'Producción']);
        Request::create([
            'employee_id' => $employee->id, 'area_id' => $area->id,
            'group' => 'A', 'reason' => 'Horas extra', 'week' => '1',
        ]);
        $this->delete(route('admin.users.destroy', $employee))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('deleted');
        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_only_admin_can_delete_users(): void
    {
        $employee = $this->user('worker');
        $this->actingAs($this->user('hr_manager'))
            ->delete(route('admin.users.destroy', $employee))
            ->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
    }

    public function test_six_column_import_creates_unassigned_employees_and_downloads_credentials(): void
    {
        $this->actingAs($this->user());
        $file = $this->roster([
            ['Producción', '0001', 'De la Cruz', 'López', 'Ana María', 'Operador'],
            ['Almacén', '0002', 'Pérez', '', 'José', 'Supervisor'],
        ]);
        try {
            $response = $this->post(route('admin.users.import.store'), ['file' => $file]);
            $response->assertOk()->assertDownload();
            $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
            $sheet = $book->getActiveSheet();
            $firstPassword = $sheet->getCell('E2')->getValue();
            $this->assertSame('0001', $sheet->getCell('A2')->getValue());
            $this->assertSame('Apellido materno', $sheet->getCell('D1')->getValue());
            $this->assertSame('López', $sheet->getCell('D2')->getValue());
            $this->assertNotSame($firstPassword, $sheet->getCell('E3')->getValue());
            $book->disconnectWorksheets();
            $first = User::where('employee_number', '0001')->firstOrFail();
            $second = User::where('employee_number', '0002')->firstOrFail();
            $this->assertSame('Ana María De la Cruz López', $first->full_name);
            $this->assertSame('Operador', $first->puesto);
            $this->assertSame('Producción', $first->area->name);
            $this->assertNull($first->direct_manager_id);
            $this->assertNull($second->apellido_mat);
            $this->assertSame('worker', $second->role); // Puesto must not grant authorization privileges.
            $this->assertTrue($first->must_change_password);
            $this->assertTrue(Hash::check($firstPassword, $first->password));
            $this->assertSame(16, strlen($firstPassword));
        } finally {
            @unlink($file->getRealPath());
        }
    }

    public function test_template_and_roster_have_the_same_six_columns_and_preserve_payroll(): void
    {
        $admin = $this->user();
        $admin->update(['employee_number' => '0001', 'puesto' => 'Analista', 'apellido_mat' => 'López']);
        $this->actingAs($admin);
        foreach (['admin.users.import.template', 'admin.users.import.roster'] as $route) {
            $response = $this->get(route($route))->assertOk()->assertDownload();
            $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
            $sheet = $book->getSheet(0);
            $this->assertSame(UserRosterImporter::HEADERS, $sheet->rangeToArray('A1:F1')[0]);
            $this->assertSame('F', $sheet->getHighestDataColumn());
            if ($route === 'admin.users.import.roster') {
                $this->assertSame('Sistemas', $sheet->getCell('A2')->getValue());
                $this->assertSame('0001', $sheet->getCell('B2')->getValue());
                $this->assertSame('López', $sheet->getCell('D2')->getValue());
                $this->assertSame('Analista', $sheet->getCell('F2')->getValue());
            } else {
                $this->assertStringContainsString('Sistemas', json_encode($book->getSheet(1)->toArray()));
            }
            $book->disconnectWorksheets();
        }
    }

    public function test_import_updates_existing_area_and_position_and_skips_repeated_rows(): void
    {
        $existing = $this->user();
        $file = $this->roster([
            ['Ignored area', $existing->employee_number, 'Different', '', 'Different', 'Different'],
            ['Nueva área', '0011', 'Ruiz', '', 'Ana', 'Operador'],
            ['Nueva área', '0011', 'Other', '', 'Other', 'Other'],
            ['nueva área', 8123, 'Pérez', 'Torres', 'José', 'Técnico'],
        ]);
        try {
            $result = app(UserRosterImporter::class)->import($file);
            $this->assertCount(2, $result['credentials']);
            $this->assertCount(1, $result['skipped']);
            $this->assertSame(1, $result['updated']);
            $this->assertSame('Tester', $existing->fresh()->name);
            $this->assertSame('Ana', User::where('employee_number', '0011')->firstOrFail()->name);
            $this->assertDatabaseHas('users', ['employee_number' => '8123']);
            $this->assertSame(1, Area::whereRaw('lower(name) = ?', ['nueva área'])->count());
            $this->assertSame('Ignored area', $existing->fresh()->area->name);
            $this->assertSame('Different', $existing->fresh()->puesto);
        } finally {
            @unlink($file->getRealPath());
        }
    }

    public function test_invalid_import_is_atomic_and_reports_row_numbers(): void
    {
        $this->actingAs($this->user());
        foreach ([
            ['Nueva', '0022', '', '', 'Ana', 'Operador'],
            ['Nueva', '0022', 'Ruiz', '', 'Ana', ''],
            ['Nueva', '0022', 'Ruiz', '=1+1', 'Ana', 'Operador'],
            ['Nueva', 12.5, 'Ruiz', '', 'Ana', 'Operador'],
            ['Nueva', -1, 'Ruiz', '', 'Ana', 'Operador'],
            ['Nueva', 1000000000000000, 'Ruiz', '', 'Ana', 'Operador'],
        ] as $invalid) {
            $file = $this->roster([['Nueva', '0021', 'Pérez', '', 'José', 'Técnico'], $invalid]);
            try {
                $this->post(route('admin.users.import.store'), ['file' => $file])
                    ->assertSessionHasErrors('file');
                $this->assertDatabaseMissing('users', ['employee_number' => '0021']);
                $this->assertDatabaseMissing('areas', ['name' => 'Nueva']);
                $this->assertStringContainsString('Fila 3:', implode(' ', session('errors')->get('file')));
            } finally {
                @unlink($file->getRealPath());
            }
        }
    }

    public function test_import_rejects_old_columns_and_extra_columns(): void
    {
        $this->actingAs($this->user());
        $file = $this->roster([['Nueva', '0021', 'Pérez', '', 'José', 'Técnico', 'extra']]);
        try {
            $this->post(route('admin.users.import.store'), ['file' => $file])->assertSessionHasErrors('file');
            $this->assertDatabaseMissing('users', ['employee_number' => '0021']);
        } finally {
            @unlink($file->getRealPath());
        }
    }

    public function test_employee_edit_saves_both_surnames_and_position_without_a_supervisor(): void
    {
        $admin = $this->user();
        $worker = $this->user('worker');
        Livewire::actingAs($admin)->test(UserEdit::class, ['user' => $worker])
            ->set('apellidos_pat', 'De la Cruz')->set('apellido_mat', 'López')->set('puesto', 'Técnico')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Tester De la Cruz López', $worker->fresh()->full_name);
        $this->assertSame('Técnico', $worker->fresh()->puesto);
        $this->assertNull($worker->fresh()->direct_manager_id);
        Livewire::actingAs($admin)->test(UserEdit::class, ['user' => $worker])
            ->set('apellido_mat', '')->call('save')->assertHasNoErrors();
        $this->assertNull($worker->fresh()->apellido_mat);
    }

    public function test_import_handles_production_cost_password_hashing_with_a_short_request_limit(): void
    {
        $this->actingAs($this->user());
        $file = $this->roster(array_map(fn ($number) => [
            'Producción', (string) $number, 'Pérez', '', 'Usuario '.$number, 'Operador',
        ], range(90001, 90008)));
        $originalLimit = (int) ini_get('max_execution_time');
        $originalRounds = config('hashing.bcrypt.rounds');
        config(['hashing.driver' => 'bcrypt', 'hashing.bcrypt.rounds' => 12]);
        Hash::forgetDrivers();

        try {
            // Actual bcrypt work exceeds this limit on typical servers; it must not
            // inherit the short limit used by ordinary requests.
            set_time_limit(1);
            $response = $this->post(route('admin.users.import.store'), ['file' => $file]);
            $restoredLimit = (int) ini_get('max_execution_time');
            set_time_limit(0);
            $response->assertOk()->assertDownload();
            $this->assertSame(1, $restoredLimit);
            $users = User::whereBetween('employee_number', ['90001', '90008'])->get();
            $this->assertCount(8, $users);
            foreach ($users as $user) {
                $this->assertSame(12, password_get_info($user->password)['options']['cost']);
            }
            $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
            $this->assertTrue(Hash::check($book->getSheet(0)->getCell('E2')->getValue(), $users->first()->password));
            $book->disconnectWorksheets();
        } finally {
            set_time_limit($originalLimit);
            config(['hashing.bcrypt.rounds' => $originalRounds]);
            Hash::forgetDrivers();
            @unlink($file->getRealPath());
        }
    }

    public function test_failed_import_restores_request_limit_and_keeps_database_unchanged(): void
    {
        $this->actingAs($this->user());
        $file = $this->roster([
            ['Nueva', '90001', 'Pérez', '', 'José', 'Operador'],
            ['Nueva', '90002', '', '', 'Ana', 'Operador'],
        ]);
        $originalLimit = (int) ini_get('max_execution_time');
        try {
            set_time_limit(30);
            $this->post(route('admin.users.import.store'), ['file' => $file])->assertSessionHasErrors('file');
            $this->assertSame(30, (int) ini_get('max_execution_time'));
            $this->assertDatabaseMissing('users', ['employee_number' => '90001']);
            $this->assertDatabaseMissing('areas', ['name' => 'Nueva']);
        } finally {
            set_time_limit($originalLimit);
            @unlink($file->getRealPath());
        }
    }

    public function test_supervisor_manager_is_assigned_by_payroll_across_areas_with_a_name_preview(): void
    {
        $admin = $this->user();
        $production = Area::create(['name' => 'Producción']);
        $other = Area::create(['name' => 'Almacén']);
        $manager = $this->user('area_manager');
        $manager->update(['name' => 'Gerente externo', 'area_id' => $other->id]);
        Livewire::actingAs($admin)->test(UserCreate::class)
            ->set('role', 'supervisor')->set('area_id', (string) $production->id)
            ->set('direct_manager_number', $manager->employee_number)
            ->assertViewHas('assignedManager', fn ($assigned) => $assigned->is($manager))
            ->assertSee('Gerente externo')->assertSee('Almacén')
            ->set('employee_number', '9998')->set('name', 'Supervisor')->set('apellidos_pat', 'Nuevo')
            ->set('password', 'Temporal123!')->set('password_confirmation', 'Temporal123!')
            ->call('save')->assertHasNoErrors();
        $supervisor = User::where('employee_number', '9998')->firstOrFail();
        $this->assertSame($manager->id, $supervisor->area_manager_id);
        $this->assertSame($manager->id, $supervisor->direct_manager_id);
        $this->assertSame($production->id, $supervisor->area_id);
        $plant = $this->user('plant_manager');
        Livewire::actingAs($admin)->test(UserEdit::class, ['user' => $supervisor])
            ->set('direct_manager_number', $plant->employee_number)
            ->assertViewHas('assignedManager', fn ($assigned) => $assigned->is($plant))
            ->call('save')->assertHasNoErrors();
        $this->assertSame($plant->id, $supervisor->fresh()->area_manager_id);
        $worker = $this->user('worker');
        Livewire::actingAs($admin)->test(UserEdit::class, ['user' => $supervisor])
            ->set('direct_manager_number', $worker->employee_number)->call('save')->assertHasErrors('direct_manager_number');
    }

    public function test_bulk_deletion_removes_users_including_with_requests_and_protects_own_account(): void
    {
        $admin = $this->user();
        $free = $this->user('worker');
        $second = $this->user('worker');
        $historic = $this->user('worker');
        $area = Area::create(['name' => 'Producción']);
        Request::create(['employee_id' => $historic->id, 'area_id' => $area->id,
            'group' => 'A', 'reason' => 'Historial', 'week' => '1', 'status' => 'pending_area_manager']);
        Livewire::actingAs($admin)->test(UserList::class)
            ->set('selected', array_map('strval', [$free->id, $second->id, $historic->id, $admin->id]))
            ->call('deleteSelected')->assertHasNoErrors()->assertSet('selected', [])
            ->assertSee('3 usuario(s) eliminado(s); 1 conservado(s).')->assertSee($admin->employee_number);
        $this->assertDatabaseMissing('users', ['id' => $free->id]);
        $this->assertDatabaseMissing('users', ['id' => $second->id]);
        $this->assertDatabaseMissing('users', ['id' => $historic->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_bulk_selection_respects_filters_and_requires_admin_access(): void
    {
        $admin = $this->user();
        $workers = collect(range(1, 20))->map(fn () => $this->user('worker'));
        $component = Livewire::actingAs($admin)->test(UserList::class)
            ->set('roleFilter', 'worker')->call('selectPage');
        $this->assertCount(15, $component->get('selected'));
        $this->assertNotContains((string) $admin->id, $component->get('selected'));
        $component->call('clearSelection')->assertSet('selected', [])
            ->call('deleteSelected')->assertHasErrors('selected');
        Livewire::actingAs($workers->first())->test(UserList::class)->assertForbidden();
        $this->assertDatabaseCount('users', 21);
    }

    public function test_reimport_changes_only_area_and_position_and_does_not_reset_credentials(): void
    {
        $admin = $this->user();
        $employee = $this->user('supervisor');
        $employee->update(['area_id' => $admin->area_id, 'puesto' => 'Antes', 'direct_manager_id' => $admin->id]);
        $before = $employee->fresh()->getAttributes();
        $file = $this->roster([['Nueva área', $employee->employee_number, 'Ignorado', 'Ignorado', 'Ignorado', 'Nuevo puesto']]);
        try {
            $this->actingAs($admin)->post(route('admin.users.import.store'), ['file' => $file])
                ->assertRedirect(route('admin.users.import.index'))->assertSessionHas('success', fn ($message) => str_contains($message, '1 actualizados'));
            $employee->refresh();
            $this->assertSame('Nueva área', $employee->area->name);
            $this->assertSame('Nuevo puesto', $employee->puesto);
            foreach (['name', 'apellidos_pat', 'apellido_mat', 'role', 'password', 'must_change_password', 'direct_manager_id'] as $field) {
                $this->assertSame($before[$field], $employee->getAttributes()[$field]);
            }
            $result = app(UserRosterImporter::class)->import($file);
            $this->assertSame(0, $result['updated']);
            $this->assertSame(1, $result['unchanged']);
            $this->assertSame([], $result['credentials']);
        } finally {
            @unlink($file->getRealPath());
        }

        // Also verify numeric cell without leading zero updates existing 4-digit employee_number ('0007' matched by 7)
        $paddedUser = $this->user('worker');
        $paddedUser->update(['employee_number' => '0007', 'puesto' => 'Operador A']);
        $numericFile = $this->roster([['Mantenimiento', 7, 'Pérez', '', 'Juan', 'Técnico B']]);
        try {
            $result = app(UserRosterImporter::class)->import($numericFile);
            $this->assertSame(1, $result['updated']);
            $this->assertSame([], $result['credentials']);
            $paddedUser->refresh();
            $this->assertSame('0007', $paddedUser->employee_number);
            $this->assertSame('Mantenimiento', $paddedUser->area->name);
            $this->assertSame('Técnico B', $paddedUser->puesto);
        } finally {
            @unlink($numericFile->getRealPath());
        }
    }

    public function test_invalid_import_rolls_back_updates_as_well_as_new_users(): void
    {
        $employee = $this->user('worker');
        $employee->update(['puesto' => 'Antes']);
        $file = $this->roster([
            ['Nueva área', $employee->employee_number, 'Pérez', '', 'José', 'Después'],
            ['Nueva área', '7778', '', '', 'Ana', 'Operador'],
        ]);
        try {
            app(UserRosterImporter::class)->import($file);
            $this->fail('The invalid file must fail before any updates.');
        } catch (ValidationException) {
            $this->assertSame('Antes', $employee->fresh()->puesto);
            $this->assertDatabaseMissing('areas', ['name' => 'Nueva área']);
            $this->assertDatabaseMissing('users', ['employee_number' => '7778']);
        } finally {
            @unlink($file->getRealPath());
        }
    }
}
