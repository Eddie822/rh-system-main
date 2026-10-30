<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RequestApproverMigrationTest extends TestCase
{
    public function test_migration_preserves_history_and_backfills_explicit_and_legacy_managers(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $migration = require database_path('migrations/2026_10_28_000001_assign_request_area_approvers.php');
        $migration->down();
        $production = Area::create(['name' => 'Producción']);
        $other = Area::create(['name' => 'Almacén']);
        $local = User::factory()->create(['role' => 'area_manager', 'area_id' => $production->id]);
        $assigned = User::factory()->create(['role' => 'plant_manager', 'area_id' => $other->id]);
        $supervisor = User::factory()->create(['role' => 'supervisor', 'area_id' => $production->id,
            'area_manager_id' => $assigned->id, 'direct_manager_id' => $assigned->id]);
        $worker = User::factory()->create(['area_id' => $production->id]);
        $create = fn ($employee) => DB::table('requests')->insertGetId(['employee_id' => $employee->id,
            'area_id' => $production->id, 'group' => 'A', 'reason' => 'Historial', 'week' => '1', 'status' => 'pending_area_manager']);
        $explicit = $create($supervisor);
        $legacy = $create($worker);
        $historical = $create($supervisor);
        DB::table('authorizations')->insert(['request_id' => $historical, 'user_id' => $local->id,
            'authorization_role' => 'area_manager', 'action' => 'approved']);
        DB::table('request_days')->insert(['request_id' => $explicit, 'employee_id' => $worker->id, 'day_date' => '2030-01-07', 'hours' => 3]);
        DB::table('request_employees')->insert(['request_id' => $explicit, 'employee_id' => $worker->id]);
        $migration->up();
        $this->assertDatabaseHas('requests', ['id' => $explicit, 'assigned_area_manager_id' => $assigned->id]);
        $this->assertDatabaseHas('requests', ['id' => $legacy, 'assigned_area_manager_id' => $local->id]);
        $this->assertDatabaseHas('requests', ['id' => $historical, 'assigned_area_manager_id' => $local->id]);
        $this->assertDatabaseCount('request_days', 1);
        $this->assertDatabaseCount('request_employees', 1);
        $this->assertDatabaseCount('authorizations', 1);
        $migration->down();
        $this->assertDatabaseCount('requests', 3);
        $this->assertDatabaseCount('request_days', 1);
        $migration->up();
        $this->assertDatabaseCount('request_employees', 1);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }
}
