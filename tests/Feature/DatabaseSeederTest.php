<?php

namespace Tests\Feature;

use App\Models\Request;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.url', null);

        return $app;
    }

    public function test_seeded_hierarchy_and_request_histories_are_consistent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(3, User::where('role', 'area_manager')->count());
        $this->assertSame(9, User::where('role', 'supervisor')->count());
        $this->assertSame(90, User::where('role', 'worker')->count());
        $this->assertDatabaseCount('users', 104);
        $this->assertSame(45, Request::where('is_group', true)->count());
        $this->assertSame(90, Request::where('is_group', false)->count());

        foreach (User::where('role', 'supervisor')->with('subordinates', 'areaManager')->get() as $supervisor) {
            $this->assertCount(10, $supervisor->subordinates);
            $this->assertSame('area_manager', $supervisor->areaManager->role);
            $this->assertSame($supervisor->area_id, $supervisor->areaManager->area_id);
            foreach ($supervisor->subordinates as $worker) {
                $this->assertSame('worker', $worker->role);
                $this->assertSame($supervisor->area_id, $worker->area_id);
                $this->assertSame($supervisor->area_manager_id, $worker->area_manager_id);
            }
        }

        foreach (Request::with('employee', 'participants', 'days', 'authorizations.user')->get() as $request) {
            $this->assertCount(1, $request->days->map(fn ($day) => $day->day_date->format('o-W'))->unique());
            if ($request->is_group) {
                $this->assertSame('supervisor', $request->employee->role);
                $this->assertCount(2, $request->participants);
                $this->assertCount(4, $request->days);
                foreach ($request->participants as $worker) {
                    $this->assertSame($request->employee_id, $worker->supervisor_id);
                    $this->assertCount(2, $request->days->where('employee_id', $worker->id));
                }
            }
            $expected = match ($request->status) {
                'pending_area_manager' => [],
                'pending_hr_manager' => ['area_manager'],
                'pending_plant_manager' => ['area_manager', 'hr_manager'],
                'approved' => ['area_manager', 'hr_manager', 'plant_manager'],
                'rejected' => ['area_manager'],
            };
            $this->assertSame($expected, $request->authorizations->pluck('authorization_role')->all());
            foreach ($request->authorizations as $authorization) {
                $this->assertSame($authorization->user->role, $authorization->authorization_role);
                $this->assertSame($request->status === 'rejected' ? 'rejected' : 'approved', $authorization->action);
                if ($authorization->authorization_role === 'area_manager') {
                    $this->assertSame($request->area_id, $authorization->user->area_id);
                }
            }
        }
    }

    public function test_rerunning_seeder_preserves_requests_decisions_and_changed_passwords(): void
    {
        $this->seed(DatabaseSeeder::class);
        $supervisor = User::where('employee_number', '0002')->firstOrFail();
        $supervisor->update(['password' => 'changed-password', 'must_change_password' => false]);
        $request = Request::where('is_group', true)->where('status', 'pending_area_manager')->firstOrFail();
        $request->update(['status' => 'pending_hr_manager']);
        $request->authorizations()->create([
            'user_id' => $request->employee->area_manager_id,
            'authorization_role' => 'area_manager', 'action' => 'approved',
        ]);
        $originalDays = $request->days()->pluck('id')->all();
        $custom = Request::create([
            'employee_id' => $supervisor->id, 'area_id' => $supervisor->area_id,
            'group' => 'A', 'reason' => 'Existing manually created request',
            'status' => 'pending_area_manager', 'week' => '1',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 104);
        $this->assertDatabaseCount('requests', 136);
        $this->assertNotNull($custom->fresh());
        $this->assertSame('pending_hr_manager', $request->fresh()->status);
        $this->assertCount(1, $request->fresh()->authorizations);
        $this->assertSame($originalDays, $request->days()->pluck('id')->all());
        $this->assertTrue(Hash::check('changed-password', $supervisor->fresh()->password));
        $this->assertFalse($supervisor->fresh()->must_change_password);
    }
}
