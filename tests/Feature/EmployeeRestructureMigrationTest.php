<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeRestructureMigrationTest extends TestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.url', null);

        return $app;
    }

    public function test_migration_preserves_employees_compound_surnames_and_request_history(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $migration = require database_path('migrations/2026_10_22_000001_restructure_employee_details.php');
        $migration->down();
        $area = DB::table('areas')->insertGetId(['name' => 'Producción']);
        $supervisor = DB::table('users')->insertGetId([
            'employee_number' => '0001', 'name' => 'José', 'last_name' => 'De la Cruz',
            'password' => 'hash', 'role' => 'supervisor', 'area_id' => $area,
        ]);
        $employee = DB::table('users')->insertGetId([
            'employee_number' => '0002', 'name' => 'Ana', 'last_name' => 'Del Río Pérez',
            'password' => 'hash', 'role' => 'worker', 'area_id' => $area,
            'supervisor_id' => $supervisor, 'direct_manager_id' => $supervisor, 'group' => 'Turno A',
        ]);
        $request = DB::table('requests')->insertGetId([
            'employee_id' => $supervisor, 'area_id' => $area, 'group' => 'Línea 1',
            'is_group' => true, 'reason' => 'Cobertura', 'week' => '2', 'status' => 'approved',
        ]);
        DB::table('request_employees')->insert(['request_id' => $request, 'employee_id' => $employee]);
        DB::table('request_days')->insert(['request_id' => $request, 'employee_id' => $employee,
            'day_date' => '2030-01-07', 'hours' => 3]);
        DB::table('authorizations')->insert(['request_id' => $request, 'user_id' => $supervisor,
            'authorization_role' => 'area_manager', 'action' => 'approved']);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('users', 'last_name'));
        $this->assertFalse(Schema::hasColumn('users', 'group'));
        $this->assertFalse(Schema::hasColumn('users', 'supervisor_id'));
        $this->assertDatabaseHas('users', ['id' => $employee, 'apellidos_pat' => 'Del Río Pérez',
            'apellido_mat' => null, 'puesto' => 'Turno A', 'direct_manager_id' => null, 'area_id' => $area]);
        $this->assertDatabaseHas('requests', ['id' => $request, 'status' => 'approved', 'group' => 'Línea 1']);
        $this->assertDatabaseCount('request_days', 1);
        $this->assertDatabaseCount('request_employees', 1);
        $this->assertDatabaseCount('authorizations', 1);
        DB::table('users')->where('id', $employee)->update(['apellido_mat' => 'López']);
        $migration->down();
        $this->assertDatabaseHas('users', ['id' => $employee, 'last_name' => 'Del Río Pérez López']);
        $this->assertDatabaseCount('requests', 1);
        $migration->up();
        $this->assertDatabaseCount('requests', 1);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }
}
