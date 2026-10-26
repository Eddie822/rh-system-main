<?php

namespace Tests\Feature;

use App\Models\Request;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
        $app['config']->set('rh.initial_access.employee_number', '0356');
        $app['config']->set('rh.initial_access.password', null);

        return $app;
    }

    public function test_seeded_hierarchy_and_request_histories_are_consistent(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->assertSame(3, User::where('role', 'area_manager')->count());
        $this->assertSame(9, User::where('role', 'supervisor')->count());
        $this->assertSame(90, User::where('role', 'worker')->count());
        $this->assertDatabaseCount('users', 104);
        $this->assertSame(51, Request::where('is_group', true)->count());
        $this->assertSame(90, Request::where('is_group', false)->count());
        $this->assertSame(3, Request::overdue()->count());
        $this->assertSame(3, Request::expiringSoon()->count());

        foreach (User::where('role', 'supervisor')->with('areaManager')->get() as $supervisor) {
            $this->assertSame('area_manager', $supervisor->areaManager->role);
            $this->assertSame($supervisor->area_id, $supervisor->areaManager->area_id);
        }
        $this->assertSame(0, User::where('role', 'worker')->whereNotNull('direct_manager_id')->count());

        foreach (Request::with('employee', 'participants', 'days', 'authorizations.user')->get() as $request) {
            $this->assertCount(1, $request->days->map(fn ($day) => $day->day_date->format('o-W'))->unique());
            if ($request->is_group) {
                $this->assertSame('supervisor', $request->employee->role);
                $this->assertCount(2, $request->participants);
                $this->assertCount($request->participants->count() * ($request->days->first()->day_date->isSunday() ? 1 : 2), $request->days);
                foreach ($request->participants as $worker) {
                    $this->assertSame('worker', $worker->role);
                    $this->assertCount($request->days->first()->day_date->isSunday() ? 1 : 2, $request->days->where('employee_id', $worker->id));
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
        $this->seed(DemoDataSeeder::class);
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

        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('users', 104);
        $this->assertDatabaseCount('requests', 142);
        $this->assertNotNull($custom->fresh());
        $this->assertSame('pending_hr_manager', $request->fresh()->status);
        $this->assertCount(1, $request->fresh()->authorizations);
        $this->assertSame($originalDays, $request->days()->pluck('id')->all());
        $this->assertTrue(Hash::check('changed-password', $supervisor->fresh()->password));
        $this->assertFalse($supervisor->fresh()->must_change_password);
    }

    public function test_default_seeder_creates_only_the_initial_access_account(): void
    {
        config(['rh.initial_access.password' => 'Temporal-RH-2026!']);
        $this->seed(DatabaseSeeder::class);
        $user = User::sole();
        $this->assertSame('0356', $user->employee_number);
        $this->assertSame('Sistemas', $user->area->name);
        $this->assertSame('area_manager', $user->role);
        $this->assertSame('Sistemas', $user->apellidos_pat);
        $this->assertNull($user->apellido_mat);
        $this->assertNotEmpty($user->puesto);
        $this->assertTrue($user->canAccessAdminPanel());
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('Temporal-RH-2026!', $user->password));
        $this->assertDatabaseCount('areas', 1);
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_initial_account_can_log_in_change_password_and_import_users(): void
    {
        config(['rh.initial_access.password' => 'Temporal-RH-2026!']);
        $this->seed(DatabaseSeeder::class);
        $this->post('/login', ['employee_number' => '0356', 'password' => 'Temporal-RH-2026!'])
            ->assertRedirect('/request');
        $this->assertAuthenticated();
        $this->get(route('request'))->assertRedirect(route('password.change'));
        $this->post(route('password.update'), [
            'password' => 'NuevaClave-RH-2026!', 'password_confirmation' => 'NuevaClave-RH-2026!',
        ])->assertRedirect(route('requests.index'));
        $this->assertFalse(User::sole()->must_change_password);
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.users.import.index'))->assertOk();
    }

    public function test_initial_seeder_prints_a_generated_password_once_and_preserves_existing_accounts(): void
    {
        config(['rh.initial_access.password' => null]);
        $this->assertSame(0, Artisan::call('db:seed'));
        $output = Artisan::output();
        $this->assertSame(1, preg_match('/\|\s*0356\s*\|\s*(\S+)\s*\|/', $output, $matches));
        $this->assertTrue(Hash::check($matches[1], User::sole()->password));
        $this->assertSame(20, strlen($matches[1]));
        User::sole()->update(['name' => 'Nombre real', 'password' => 'ClavePersonal123!', 'must_change_password' => false]);
        $attributes = User::sole()->getAttributes();
        $this->assertSame(0, Artisan::call('db:seed'));
        $this->assertSame($attributes, User::sole()->getAttributes());
        $this->assertStringNotContainsString($matches[1], Artisan::output());
    }

    public function test_initial_payroll_can_be_configured_without_overwriting_an_existing_employee(): void
    {
        $employee = User::factory()->create(['employee_number' => '0356']);
        $attributes = $employee->fresh()->getAttributes();
        $this->seed(DatabaseSeeder::class);
        $this->assertSame($attributes, $employee->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 1);
        config(['rh.initial_access.employee_number' => 'RH-INICIAL', 'rh.initial_access.password' => 'Temporal-RH-2026!']);
        $this->seed(DatabaseSeeder::class);
        $this->assertTrue(User::where('employee_number', 'RH-INICIAL')->firstOrFail()->canAccessAdminPanel());
        $this->assertDatabaseCount('users', 2);
    }

    public function test_demo_can_be_added_after_initial_setup_without_resetting_initial_credentials(): void
    {
        config(['rh.initial_access.password' => 'Temporal-RH-2026!']);
        $this->seed(DatabaseSeeder::class);
        $attributes = User::sole()->getAttributes();
        $this->seed(DemoDataSeeder::class);
        $this->assertSame($attributes, User::where('employee_number', '0356')->firstOrFail()->getAttributes());
        $this->assertDatabaseCount('users', 105);
        $this->assertDatabaseCount('requests', 141);
        $this->assertTrue(User::where('role', 'worker')->whereNull('apellido_mat')->exists());
        foreach (Request::where('is_group', true)->with('participants')->get() as $request) {
            $this->assertTrue($request->participants->contains(fn ($worker) => $worker->area_id !== $request->area_id));
        }
    }

    public function test_demo_leaves_payroll_0356_untouched_even_with_a_different_role(): void
    {
        $employee = User::factory()->create(['employee_number' => '0356', 'role' => 'supervisor']);
        $attributes = $employee->fresh()->getAttributes();

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($attributes, $employee->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 105);
        $this->assertSame('area_manager', User::where('employee_number', 'DEMO-SIS-001')->firstOrFail()->role);
    }

    public function test_demo_payroll_collision_rolls_back_without_touching_real_accounts(): void
    {
        $employee = User::factory()->create(['employee_number' => '0004']);
        $attributes = $employee->fresh()->getAttributes();
        try {
            $this->seed(DemoDataSeeder::class);
            $this->fail('A conflicting real account must stop demo seeding.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('0004', $exception->getMessage());
        }
        $this->assertSame($attributes, $employee->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('areas', 0);
        $this->assertDatabaseCount('requests', 0);
    }
}
