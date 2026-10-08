<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Models\Area;
use App\Models\Authorization;
use App\Models\Request;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
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

    private function user(string $role, string $number): User
    {
        return User::create([
            'employee_number' => $number,
            'name' => 'Usuario',
            'last_name' => $number,
            'role' => $role,
            'area_id' => $role === 'supervisor' ? Area::firstOrCreate(['name' => 'Sistemas'])->id : null,
            'password' => 'password123',
            'must_change_password' => false,
        ]);
    }

    private function request(User $employee, Area $area, string $status, string $date): Request
    {
        $request = Request::create([
            'employee_id' => $employee->id,
            'area_id' => $area->id,
            'group' => 'A',
            'reason' => 'Horas extra',
            'status' => $status,
            'week' => '1',
        ]);
        $request->timestamps = false;
        $request->created_at = $date.' 12:00:00';
        $request->save();

        return $request;
    }

    public function test_dashboard_filters_indicators_and_charts_by_area_and_request_date(): void
    {
        $admin = $this->user('supervisor', '100');
        $employee = $this->user('worker', '101');
        $manager = $this->user('area_manager', '102');
        $production = Area::create(['name' => 'Producción']);
        $packing = Area::create(['name' => 'Empaque']);
        $approved = $this->request($employee, $production, 'approved', '2026-10-01');
        $rejected = $this->request($employee, $packing, 'rejected', '2026-09-01');
        Authorization::create(['request_id' => $approved->id, 'user_id' => $manager->id, 'authorization_role' => 'area_manager', 'action' => 'approved']);
        Authorization::create(['request_id' => $rejected->id, 'user_id' => $manager->id, 'authorization_role' => 'area_manager', 'action' => 'rejected']);

        $dashboard = Livewire::actingAs($admin, 'web')->test(Dashboard::class)
            ->assertViewHas('dashboardData', fn ($data) => $data['indicators']['approved'] === 1
                && $data['indicators']['rejected'] === 1
                && $data['decisions']['area_manager']['approved'] === 1
                && $data['decisions']['area_manager']['rejected'] === 1);

        $dashboard->set('areaId', (string) $production->id)
            ->set('dateFrom', '2026-10-01')
            ->set('dateTo', '2026-10-31')
            ->assertDispatched('dashboard-data-updated')
            ->assertViewHas('dashboardData', fn ($data) => $data['indicators']['approved'] === 1
                && $data['indicators']['rejected'] === 0
                && $data['areas']['labels'] === ['Producción']
                && $data['areas']['values'] === [1]
                && $data['decisions']['area_manager']['approved'] === 1
                && $data['decisions']['area_manager']['rejected'] === 0);

        $dashboard->call('resetFilters')
            ->assertViewHas('dashboardData', fn ($data) => $data['indicators']['approved'] === 1
                && $data['indicators']['rejected'] === 1);
    }

    public function test_dashboard_renders_filters_and_theme_aware_chart_options(): void
    {
        $this->actingAs($this->user('supervisor', '200'))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('dashboard-area')
            ->assertSee('dashboard-from')
            ->assertSee('dashboard-to')
            ->assertSee('dashboard-requests-by-area')
            ->assertSee("dark ? 'dark' : 'light'", false);
    }
}
