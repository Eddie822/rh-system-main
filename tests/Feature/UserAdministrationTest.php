<?php

namespace Tests\Feature;

use App\Imports\UserRosterImporter;
use App\Livewire\Admin\User\UserCreate;
use App\Livewire\Admin\User\UserEdit;
use App\Models\Area;
use App\Models\Request;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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

        return User::create([
            'employee_number' => (string) ++$number,
            'name' => 'Tester', 'last_name' => 'Admin',
            'password' => 'password123', 'role' => $role,
            'must_change_password' => false,
            'area_id' => func_num_args() === 0 ? Area::firstOrCreate(['name' => 'Sistemas'])->id : null,
        ]);
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
            'employee_number' => '0098', 'name' => 'Gerente', 'last_name' => 'Área',
            'role' => 'area_manager', 'area_id' => $area->id, 'direct_manager_id' => $plant->id,
            'password' => 'password123',
        ]);
        $supervisor = User::create([
            'employee_number' => '0099', 'name' => 'Supervisora', 'last_name' => 'Prueba',
            'role' => 'supervisor', 'area_id' => $area->id, 'area_manager_id' => $manager->id,
            'direct_manager_id' => $manager->id, 'password' => 'password123',
        ]);
        Livewire::test(UserCreate::class)
            ->set('employee_number', '0007')->set('name', 'Ana')->set('last_name', 'Ruiz')
            ->set('role', 'worker')->set('area_id', (string) $area->id)
            ->set('direct_manager_number', $supervisor->employee_number)
            ->set('password', 'temporary123')->set('password_confirmation', 'temporary123')
            ->call('save')->assertHasNoErrors();
        $user = User::where('employee_number', '0007')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertSame($supervisor->id, $user->direct_manager_id);
        $this->assertTrue(Hash::check('temporary123', $user->password));
        $this->delete(route('admin.areas.destroy', $area))->assertSessionHas('error');
        $this->assertDatabaseHas('areas', ['id' => $area->id]);

        $spare = Area::create(['name' => 'Almacén']);
        $this->delete(route('admin.areas.destroy', $spare))->assertRedirect(route('admin.areas.index'));
        $this->assertDatabaseMissing('areas', ['id' => $spare->id]);
    }

    public function test_import_links_employees_and_downloads_distinct_credentials(): void
    {
        $this->actingAs($this->user());
        Area::create(['name' => 'Producción']);
        $template = $this->get(route('admin.users.import.template'));
        $template->assertOk()->assertDownload('plantilla-usuarios.xlsx');
        $templateBook = IOFactory::load($template->baseResponse->getFile()->getPathname());
        $this->assertSame('Número de nómina', $templateBook->getSheet(0)->getCell('A1')->getValue());
        $this->assertSame('Nómina del jefe directo', $templateBook->getSheet(0)->getCell('G1')->getValue());
        $this->assertSame('G', $templateBook->getSheet(0)->getHighestDataColumn());
        $this->assertGreaterThan(10, $templateBook->getSheet(0)->getColumnDimension('A')->getWidth());
        $this->assertStringContainsString('Producción', json_encode($templateBook->getSheet(1)->toArray(), JSON_UNESCAPED_UNICODE));
        $templateBook->disconnectWorksheets();

        $file = $this->roster([
            ['0001', 'Luz', 'Pérez', 'worker', 'Producción', 'A', '0002'],
            ['0002', 'José', 'López', 'supervisor', 'Producción', 'A', '0003'],
            ['0003', 'María', 'García', 'area_manager', 'Producción', '', '0004'],
            ['0004', 'Pedro', 'Torres', 'plant_manager', '', '', ''],
        ]);
        try {
            $response = $this->post(route('admin.users.import.store'), ['file' => $file]);
            $response->assertOk()->assertDownload();
            $credentials = IOFactory::load($response->baseResponse->getFile()->getPathname());
            $first = $credentials->getActiveSheet()->getCell('D2')->getValue();
            $second = $credentials->getActiveSheet()->getCell('D3')->getValue();
            $this->assertSame(16, strlen($first));
            $this->assertNotSame($first, $second);
            $this->assertSame('0001', (string) $credentials->getActiveSheet()->getCell('A2')->getValue());
            $credentials->disconnectWorksheets();
            $worker = User::where('employee_number', '0001')->firstOrFail();
            $this->assertSame('0002', $worker->supervisor->employee_number);
            $this->assertSame('0003', $worker->areaManager->employee_number);
            $this->assertSame('0002', $worker->directManager->employee_number);
            $this->assertSame('0003', User::where('employee_number', '0002')->firstOrFail()->directManager->employee_number);
            $this->assertSame('0004', User::where('employee_number', '0003')->firstOrFail()->directManager->employee_number);
            $this->assertTrue($worker->must_change_password);
            $this->assertTrue(Hash::check($first, $worker->password));
            $roster = $this->get(route('admin.users.import.roster'));
            $roster->assertOk()->assertDownload('lista-completa-usuarios.xlsx');
            $rosterBook = IOFactory::load($roster->baseResponse->getFile()->getPathname());
            $this->assertSame('Producción', $rosterBook->getActiveSheet()->getCell('E2')->getValue());
            $this->assertSame('Empleado', $rosterBook->getActiveSheet()->getCell('D2')->getValue());
            $this->assertSame('0002', (string) $rosterBook->getActiveSheet()->getCell('G2')->getValue());
            $this->assertSame('G', $rosterBook->getActiveSheet()->getHighestDataColumn());
            $rosterBook->disconnectWorksheets();
        } finally {
            @unlink($file->getRealPath());
        }
    }

    public function test_spanish_excel_roles_are_saved_as_internal_codes(): void
    {
        $this->actingAs($this->user());
        $file = $this->roster([
            ['0091', 'Ana', 'Ruiz', 'Empleado', 'Empaque', '', '0092'],
            ['0092', 'Sara', 'Vega', 'SUPERVISOR', 'Empaque', '', '0093'],
            ['0093', 'Eva', 'Luna', 'gerente de area', 'Empaque', '', ''],
            ['0095', 'Rosa', 'Sol', 'Gerente de RH', 'RH', '', ''],
            ['0096', 'Pablo', 'Sol', 'Gerente de planta', '', '', ''],
            ['0097', 'Luis', 'Sol', 'Trabajador', 'Empaque', '', '0093'],
        ]);
        try {
            $this->post(route('admin.users.import.store'), ['file' => $file])->assertOk()->assertDownload();
            foreach (['0091' => 'worker', '0092' => 'supervisor', '0093' => 'area_manager', '0095' => 'hr_manager', '0096' => 'plant_manager', '0097' => 'worker'] as $number => $role) {
                $this->assertDatabaseHas('users', ['employee_number' => $number, 'role' => $role]);
            }
            $response = $this->get(route('admin.users.import.roster'));
            $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
            $roles = array_column($book->getActiveSheet()->toArray(), 3);
            foreach (UserRosterImporter::ROLE_LABELS as $label) {
                $this->assertContains($label, $roles);
            }
            $book->disconnectWorksheets();
        } finally {
            @unlink($file->getRealPath());
        }
    }

    public function test_duplicates_are_skipped_and_new_areas_are_created(): void
    {
        $this->actingAs($this->user());
        User::create([
            'employee_number' => '0010', 'name' => 'Existing', 'last_name' => 'User',
            'role' => 'worker', 'password' => 'password123', 'must_change_password' => false,
        ]);
        $file = $this->roster([
            ['0010', 'Different', 'Name', 'worker', 'Ignored Area', '', ''],
            ['0011', 'Ana', 'Ruiz', 'worker', 'New Area', '', '0013'],
            ['0011', 'Luis', 'Díaz', 'worker', 'Ignored Area', '', ''],
            ['0012', 'Luz', 'Pérez', 'worker', 'new area', '', '0013'],
            ['0013', 'Sara', 'Vega', 'supervisor', 'New Area', '', '0014'],
            ['0014', 'Eva', 'Luna', 'area_manager', 'New Area', '', '0015'],
            ['0015', 'Pablo', 'Sol', 'plant_manager', '', '', ''],
        ]);
        try {
            $this->post(route('admin.users.import.store'), ['file' => $file])->assertOk()->assertDownload();
            $this->assertSame(2, Area::count());
            $this->assertSame(7, User::count());
            $this->assertSame('Existing', User::where('employee_number', '0010')->firstOrFail()->name);
            $this->assertSame('Ana', User::where('employee_number', '0011')->firstOrFail()->name);
            $this->assertSame('New Area', User::where('employee_number', '0012')->firstOrFail()->area->name);
        } finally {
            @unlink($file->getRealPath());
        }
    }

    public function test_invalid_new_row_stops_users_and_areas(): void
    {
        $this->actingAs($this->user());
        $file = $this->roster([
            ['0021', 'Ana', 'Ruiz', 'worker', 'New Area', '', '0023'],
            ['0022', 'Luis', 'Díaz', 'invalid', 'New Area', '', ''],
            ['0023', 'Sara', 'Vega', 'supervisor', 'New Area', '', '0024'],
            ['0024', 'Eva', 'Luna', 'area_manager', 'New Area', '', '0025'],
            ['0025', 'Pablo', 'Sol', 'plant_manager', '', '', ''],
        ]);
        try {
            $this->post(route('admin.users.import.store'), ['file' => $file])->assertSessionHasErrors('file');
            $this->assertDatabaseMissing('users', ['employee_number' => '0021']);
            $this->assertDatabaseMissing('areas', ['name' => 'New Area']);
        } finally {
            @unlink($file->getRealPath());
        }
    }

    public function test_file_with_only_existing_payroll_numbers_reports_skips(): void
    {
        $admin = $this->user();
        $this->actingAs($admin);
        $file = $this->roster([[
            $admin->employee_number, 'Different', 'Name', 'worker', 'Ignored Area', '', '',
        ]]);
        try {
            $this->post(route('admin.users.import.store'), ['file' => $file])
                ->assertRedirect(route('admin.users.import.index'))
                ->assertSessionHas('success', '0 usuarios importados; 1 nóminas duplicadas omitidas.');
            $this->assertSame(1, User::count());
            $this->assertSame(1, Area::count());
        } finally {
            @unlink($file->getRealPath());
        }
    }

    public function test_import_requires_the_confirmed_direct_manager_hierarchy(): void
    {
        $this->actingAs($this->user());
        $file = $this->roster([
            ['0031', 'Ana', 'Ruiz', 'worker', 'Empaque', '', '0032'],
            ['0032', 'Eva', 'Luna', 'area_manager', 'Otra área', '', '0033'],
            ['0033', 'Pablo', 'Sol', 'plant_manager', '', '', ''],
        ]);
        try {
            $this->post(route('admin.users.import.store'), ['file' => $file])->assertSessionHasErrors('file');
            $this->assertDatabaseMissing('users', ['employee_number' => '0031']);
            $this->assertDatabaseMissing('areas', ['name' => 'Empaque']);
        } finally {
            @unlink($file->getRealPath());
        }
    }

    public function test_workers_can_report_directly_to_their_area_manager_without_a_supervisor(): void
    {
        $this->actingAs($this->user());
        $file = $this->roster([
            ['0071', 'Ana', 'Ruiz', 'worker', 'Sistemas', '', '0074'],
            ['0072', 'Luis', 'Sol', 'worker', 'Sistemas', '', '0074'],
            ['0073', 'Rosa', 'Vega', 'worker', 'Sistemas', '', '0074'],
            ['0074', 'Eva', 'Luna', 'area_manager', 'Sistemas', '', ''],
        ]);
        try {
            $this->post(route('admin.users.import.store'), ['file' => $file])->assertOk()->assertDownload();
            $manager = User::where('employee_number', '0074')->firstOrFail();
            foreach (['0071', '0072', '0073'] as $number) {
                $worker = User::where('employee_number', $number)->firstOrFail();
                $this->assertSame($manager->id, $worker->direct_manager_id);
                $this->assertSame($manager->id, $worker->area_manager_id);
                $this->assertNull($worker->supervisor_id);
            }
        } finally {
            @unlink($file->getRealPath());
        }
    }

    public function test_import_allows_managers_without_bosses_and_links_workers_and_supervisors(): void
    {
        $this->actingAs($this->user());
        $file = $this->roster([
            ['0041', 'Ana', 'Ruiz', 'worker', 'Empaque', '', '0042'],
            ['0042', 'Sara', 'Vega', 'supervisor', 'Empaque', '', '0043'],
            ['0043', 'Eva', 'Luna', 'area_manager', 'Empaque', '', ''],
            ['0044', 'Rosa', 'Sol', 'hr_manager', '', '', ''],
            ['0045', 'Pablo', 'Sol', 'plant_manager', '', '', ''],
        ]);
        try {
            $this->post(route('admin.users.import.store'), ['file' => $file])->assertOk()->assertDownload();
            foreach (['0043', '0044', '0045'] as $number) {
                $manager = User::where('employee_number', $number)->firstOrFail();
                $this->assertNull($manager->direct_manager_id);
                $this->assertNull($manager->area_manager_id);
            }
            $worker = User::where('employee_number', '0041')->firstOrFail();
            $supervisor = User::where('employee_number', '0042')->firstOrFail();
            $this->assertSame($supervisor->id, $worker->direct_manager_id);
            $this->assertSame($worker->area_manager_id, $supervisor->direct_manager_id);
        } finally {
            @unlink($file->getRealPath());
        }
    }

    public function test_import_requires_bosses_for_non_manager_roles(): void
    {
        $this->actingAs($this->user());
        foreach (['worker', 'supervisor'] as $role) {
            $file = $this->roster([
                ['0061', 'Ana', 'Ruiz', $role, 'Empaque', '', ''],
                ['0062', 'Eva', 'Luna', 'area_manager', 'Empaque', '', ''],
            ]);
            try {
                $this->post(route('admin.users.import.store'), ['file' => $file])->assertSessionHasErrors('file');
                $this->assertDatabaseMissing('users', ['employee_number' => '0061']);
                $this->assertDatabaseMissing('areas', ['name' => 'Empaque']);
            } finally {
                @unlink($file->getRealPath());
            }
        }
    }

    public function test_numeric_payroll_cells_can_start_with_any_digit(): void
    {
        $this->actingAs($this->user());
        $file = $this->roster([
            [8123, 'Ana', 'Ruiz', 'worker', 'Empaque', '', 3456],
            [3456, 'Sara', 'Vega', 'supervisor', 'Empaque', '', 5678],
            [5678, 'Eva', 'Luna', 'area_manager', 'Empaque', '', 7890],
            [7890, 'Pablo', 'Sol', 'plant_manager', '', '', ''],
        ]);
        try {
            $this->post(route('admin.users.import.store'), ['file' => $file])->assertOk()->assertDownload();
            $worker = User::where('employee_number', '8123')->firstOrFail();
            $this->assertSame('3456', $worker->directManager->employee_number);
            $this->assertSame('5678', $worker->areaManager->employee_number);
        } finally {
            @unlink($file->getRealPath());
        }
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
                $this->get(route('request'))->assertSee('Panel de administrador');
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

    public function test_systems_area_manager_can_create_an_employee_reporting_to_them(): void
    {
        $manager = $this->user('area_manager');
        $area = Area::firstOrCreate(['name' => 'Sistemas']);
        $manager->update(['area_id' => $area->id]);
        Livewire::actingAs($manager)->test(UserCreate::class)
            ->set('employee_number', '0081')->set('name', 'Ana')->set('last_name', 'Ruiz')
            ->set('area_id', (string) $area->id)->set('direct_manager_number', $manager->employee_number)
            ->set('password', 'password123')->set('password_confirmation', 'password123')
            ->call('save')->assertHasNoErrors();
        $worker = User::where('employee_number', '0081')->firstOrFail();
        $this->assertSame($manager->id, $worker->direct_manager_id);
        $this->assertSame($manager->id, $worker->area_manager_id);
        $this->assertNull($worker->supervisor_id);
        $this->assertTrue($worker->canAccessAdminPanel());
    }

    public function test_confirmed_legacy_accounts_are_converted_without_losing_requests(): void
    {
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
        $employee = $this->user('worker');
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
        $this->assertNull($employee->fresh()->supervisor_id);
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

    public function test_user_deletion_preserves_requests_reports_and_own_account(): void
    {
        $admin = $this->user();
        $employee = $this->user('worker');
        $manager = $this->user('supervisor');
        $this->actingAs($admin);
        $employee->update(['supervisor_id' => $manager->id]);
        $this->delete(route('admin.users.destroy', $manager))->assertSessionHas('error');
        $this->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');

        $area = Area::create(['name' => 'Producción']);
        Request::create([
            'employee_id' => $employee->id, 'area_id' => $area->id,
            'group' => 'A', 'reason' => 'Horas extra', 'week' => '1',
        ]);
        $this->delete(route('admin.users.destroy', $employee))->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
        $this->assertDatabaseCount('requests', 1);
    }

    public function test_only_admin_can_delete_users(): void
    {
        $employee = $this->user('worker');
        $this->actingAs($this->user('hr_manager'))
            ->delete(route('admin.users.destroy', $employee))
            ->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
    }
}
