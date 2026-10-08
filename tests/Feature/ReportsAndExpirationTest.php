<?php

namespace Tests\Feature;

use App\Actions\DecideRequest;
use App\Exports\ApprovedOvertimeExport;
use App\Livewire\Admin\Reports\ApprovedOvertime;
use App\Livewire\Admin\User\UserEdit;
use App\Livewire\Admin\User\UserList;
use App\Livewire\Approvals\ApprovalList;
use App\Livewire\Approvals\ApprovalShow;
use App\Livewire\Requests\RequestList;
use App\Models\Area;
use App\Models\Request;
use App\Models\User;
use App\Reports\ApprovedOvertimeReport;
use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportsAndExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.url', null);
        $this->excelTemporaryPath = sys_get_temp_dir().'/rh-excel-tests-'.bin2hex(random_bytes(8));
        $app['config']->set('excel.temporary_files.local_path', $this->excelTemporaryPath);

        return $app;
    }

    private ?string $excelTemporaryPath = null;

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            if ($this->excelTemporaryPath !== null) {
                (new Filesystem)->deleteDirectory($this->excelTemporaryPath);
            }
        }
    }

    private function user(string $role = 'worker', array $attributes = []): User
    {
        static $number = 1000;

        return User::create(array_merge([
            'employee_number' => (string) ++$number, 'name' => 'Employee '.$number,
            'last_name' => 'Test', 'password' => 'password123', 'role' => $role,
            'must_change_password' => false,
        ], $attributes));
    }

    private function request(string $status = 'approved', string $date = '2030-01-07'): Request
    {
        $area = Area::firstOrCreate(['name' => 'Production']);
        $employee = $this->user(attributes: ['area_id' => $area->id]);
        $request = Request::create([
            'employee_id' => $employee->id, 'area_id' => $area->id, 'group' => 'A',
            'reason' => 'Overtime', 'status' => $status, 'week' => '2',
        ]);
        $request->days()->create(['day_date' => $date, 'day_name' => 'Lunes', 'hours' => 2.5]);

        return $request->fresh();
    }

    private function httpUser(User $user): static
    {
        $redirector = new Redirector(app('url'));
        $redirector->setSession(app('session.store'));
        app()->instance('redirect', $redirector);
        app('session')->flush();
        app('auth')->forgetGuards();

        return $this->actingAs($user, 'web');
    }

    public function test_report_module_and_export_are_restricted_by_area(): void
    {
        foreach (['worker', 'supervisor', 'area_manager', 'plant_manager', 'it'] as $role) {
            $this->httpUser($this->user($role))->get(route('admin.reports.index'))->assertForbidden();
            $this->get(route('admin.reports.export'))->assertForbidden();
        }
        foreach (['worker', 'supervisor', 'area_manager', 'hr_manager', 'plant_manager'] as $role) {
            $this->httpUser($this->user($role, ['area_id' => Area::firstOrCreate(['name' => 'RH'])->id]))->get(route('admin.reports.index'))->assertOk()->assertSee('Reportes');
            $response = $this->get(route('admin.reports.export'));
            $response->assertOk()->assertDownload();
            @unlink($response->baseResponse->getFile()->getPathname());
        }
    }

    public function test_report_filters_overtime_dates_and_includes_only_final_approvals(): void
    {
        $approved = $this->request();
        $approved->days()->create(['day_date' => '2030-01-08', 'hours' => 3]);
        foreach (['pending_area_manager', 'pending_hr_manager', 'pending_plant_manager', 'rejected'] as $status) {
            $this->request($status);
        }
        $this->request(date: '2030-01-14');
        $daily = new ApprovedOvertimeReport(['period' => 'day', 'date' => '2030-01-07']);
        $this->assertSame([$approved->id], $daily->query()->pluck('request_id')->all());
        $weekly = new ApprovedOvertimeReport(['period' => 'week', 'year' => 2030, 'week' => 2]);
        $this->assertCount(2, $weekly->query()->get());
        $range = new ApprovedOvertimeReport(['period' => 'range', 'from' => '2030-01-08', 'to' => '2030-01-14']);
        $this->assertCount(2, $range->query()->get());
        Livewire::actingAs($this->user('hr_manager', ['area_id' => Area::firstOrCreate(['name' => 'Recursos Humanos'])->id]), 'web')->test(ApprovedOvertime::class)
            ->set('year', '2030')->set('week', '2')
            ->assertViewHas('hours', 5.5)->assertViewHas('requestCount', 1);
    }

    public function test_group_report_uses_participant_payroll_numbers_without_double_counting(): void
    {
        $request = $this->request();
        $request->update(['is_group' => true]);
        $request->employee->update(['role' => 'supervisor']);
        $first = $this->user(attributes: ['employee_number' => '0007']);
        $second = $this->user(attributes: ['employee_number' => '0008']);
        $request->participants()->attach([$first->id, $second->id]);
        $request->days()->first()->update(['employee_id' => $first->id]);
        $request->days()->create(['employee_id' => $second->id, 'day_date' => '2030-01-07', 'hours' => 4]);
        $report = new ApprovedOvertimeReport(['period' => 'day', 'date' => '2030-01-07', 'employee_number' => '0008', 'area_id' => $request->area_id]);
        $this->assertCount(1, $report->query()->get());
        $this->assertEquals(4, $report->query()->sum('hours'));
        $export = new ApprovedOvertimeExport($report);
        $this->assertSame('0008', $export->map($report->query()->first())[2]);
        $report = new ApprovedOvertimeReport(['period' => 'day', 'date' => '2030-01-07', 'employee_number' => $request->employee->employee_number]);
        $this->assertCount(0, $report->query()->get());
        $otherArea = Area::create(['name' => 'Other']);
        $report = new ApprovedOvertimeReport(['period' => 'day', 'date' => '2030-01-07', 'area_id' => $otherArea->id]);
        $this->assertCount(0, $report->query()->get());
    }

    public function test_area_options_come_from_database_across_report_approvals_and_user_admin(): void
    {
        $reportUser = $this->user('hr_manager', ['area_id' => Area::firstOrCreate(['name' => 'Recursos Humanos'])->id]);
        $preview = Livewire::actingAs($reportUser, 'web')->test(ApprovedOvertime::class);
        $newArea = Area::create(['name' => 'Ensamble']);
        $preview->call('clearFilters')->assertSee('Ensamble');
        Livewire::actingAs($reportUser, 'web')->test(UserList::class)->assertSee('Ensamble');
        Livewire::actingAs($reportUser, 'web')->test(UserEdit::class, ['user' => $reportUser])->assertSee('Ensamble');
        $manager = $this->user('area_manager', ['area_id' => $newArea->id]);
        Livewire::actingAs($manager, 'web')->test(ApprovalList::class)->assertSee('Ensamble');

        $matching = $this->request(date: '2030-01-07');
        $matching->update(['area_id' => $newArea->id]);
        $this->request(date: '2030-01-07');
        Livewire::actingAs($reportUser, 'web')->test(ApprovedOvertime::class)
            ->set('period', 'day')->set('date', '2030-01-07')->set('area_id', (string) $newArea->id)
            ->assertViewHas('rows', fn ($rows) => $rows->total() === 1 && $rows->first()->request_id === $matching->id)
            ->assertViewHas('exportUrl', fn ($url) => str_contains($url, 'area_id='.$newArea->id));
    }

    public function test_live_filters_update_preview_totals_and_reset_pagination(): void
    {
        $first = $this->request(date: '2030-01-07');
        $this->request(date: '2030-01-08');
        $component = Livewire::actingAs($this->user('hr_manager', ['area_id' => Area::firstOrCreate(['name' => 'Recursos Humanos'])->id]), 'web')->test(ApprovedOvertime::class)
            ->set('period', 'day')->set('date', '2030-01-07')
            ->assertViewHas('hours', 2.5)->assertViewHas('requestCount', 1)
            ->assertViewHas('rows', fn ($rows) => $rows->first()->request_id === $first->id);
        $component->set('date', '2030-01-08')->assertViewHas('hours', 2.5)
            ->assertViewHas('rows', fn ($rows) => $rows->first()->request_id !== $first->id)
            ->call('clearFilters')->assertSet('period', 'week')->assertSet('area_id', '');
    }

    public function test_live_filters_show_errors_and_excel_endpoint_validates_them(): void
    {
        $hr = $this->user('hr_manager', ['area_id' => Area::firstOrCreate(['name' => 'Recursos Humanos'])->id]);
        Livewire::actingAs($hr, 'web')->test(ApprovedOvertime::class)
            ->set('year', '2030')->set('week', '53')->assertViewHas('exportUrl', null)
            ->assertSee('La semana seleccionada no existe');
        Livewire::actingAs($hr, 'web')->test(ApprovedOvertime::class)
            ->set('period', 'range')->set('from', '2030-01-08')->set('to', '2030-01-07')
            ->assertViewHas('exportUrl', null);
        $this->httpUser($hr);
        $this->getJson(route('admin.reports.export', ['period' => 'range', 'from' => '2030-01-08', 'to' => '2030-01-07']))->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->getJson(route('admin.reports.export', ['period' => 'week', 'year' => 2030, 'week' => 53]))->assertUnprocessable()->assertJsonValidationErrors('week');
        $this->getJson(route('admin.reports.export', ['period' => 'day', 'date' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors('date');
        $request = $this->request(date: '2029-12-31');
        $report = new ApprovedOvertimeReport(['period' => 'week', 'year' => 2030, 'week' => 1]);
        $this->assertSame([$request->id], $report->query()->pluck('request_id')->all());
    }

    public function test_excel_contains_numeric_hours_and_literal_payroll_and_text(): void
    {
        $request = $this->request();
        $request->employee->update(['employee_number' => '0001', 'name' => '=HYPERLINK("bad")']);
        $request->update(['reason' => '=1+1']);
        $manager = $this->user('plant_manager');
        $request->authorizations()->create(['user_id' => $manager->id, 'authorization_role' => 'plant_manager', 'action' => 'approved']);
        $report = new ApprovedOvertimeReport(['period' => 'day', 'date' => '2030-01-07']);
        $file = tempnam(sys_get_temp_dir(), 'report-test-');
        try {
            file_put_contents($file, Excel::raw(new ApprovedOvertimeExport($report), \Maatwebsite\Excel\Excel::XLSX));
            $book = IOFactory::load($file);
            $sheet = $book->getActiveSheet();
            $this->assertSame('Nómina', $sheet->getCell('C1')->getValue());
            $this->assertSame('0001', $sheet->getCell('C2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('D2')->getDataType());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('K2')->getDataType());
            $this->assertSame('=1+1', $sheet->getCell('K2')->getValue());
            $this->assertEquals(2.5, $sheet->getCell('J2')->getValue());
            $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('J2')->getDataType());
            $this->assertNotNull($sheet->getCell('M2')->getValue());
            $book->disconnectWorksheets();
        } finally {
            unlink($file);
        }
    }

    public function test_excel_exports_all_rows_not_just_the_preview_page(): void
    {
        $request = $this->request();
        for ($i = 1; $i <= 30; $i++) {
            $request->days()->create(['day_date' => '2030-01-07', 'hours' => 1]);
        }
        $filters = ['period' => 'day', 'date' => '2030-01-07'];
        Livewire::actingAs($this->user('hr_manager', ['area_id' => Area::firstOrCreate(['name' => 'Recursos Humanos'])->id]), 'web')->test(ApprovedOvertime::class)
            ->set('period', 'day')->set('date', '2030-01-07')
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 25 && $rows->total() === 31);
        $file = tempnam(sys_get_temp_dir(), 'report-all-');
        try {
            file_put_contents($file, Excel::raw(new ApprovedOvertimeExport(new ApprovedOvertimeReport($filters)), \Maatwebsite\Excel\Excel::XLSX));
            $book = IOFactory::load($file);
            $this->assertSame(32, $book->getActiveSheet()->getHighestDataRow());
            $book->disconnectWorksheets();
        } finally {
            unlink($file);
        }
    }

    public function test_deadline_tracks_earliest_overtime_day_and_updates_when_days_change(): void
    {
        $request = $this->request('pending_area_manager', '2030-01-10');
        $this->assertSame('2030-01-10 00:00:00', $request->expires_at->format('Y-m-d H:i:s'));
        $earlier = $request->days()->create(['day_date' => '2030-01-08', 'hours' => 2]);
        $this->assertSame('2030-01-08', $request->fresh()->expires_at->toDateString());
        $earlier->update(['day_date' => '2030-01-09']);
        $this->assertSame('2030-01-09', $request->fresh()->expires_at->toDateString());
        $earlier->delete();
        $this->assertSame('2030-01-10', $request->fresh()->expires_at->toDateString());
    }

    public function test_alert_boundaries_and_query_scopes_agree_without_changing_status(): void
    {
        $request = $this->request('pending_area_manager', '2030-01-10');
        $this->travelTo(Carbon::parse('2030-01-04 23:59:59'));
        $this->assertNull($request->expirationAlert());
        $this->travelTo(Carbon::parse('2030-01-05 00:00:00'));
        $this->assertSame('soon', $request->expirationAlert());
        $this->assertSame(1, Request::expiringSoon()->count());
        $this->assertSame(0, Request::overdue()->count());
        $this->travelTo(Carbon::parse('2030-01-10 00:00:00'));
        $this->assertSame('overdue', $request->expirationAlert());
        $this->assertSame(0, Request::expiringSoon()->count());
        $this->assertSame(1, Request::overdue()->count());
        $this->assertSame('pending_area_manager', $request->fresh()->status);
        $request->update(['status' => 'approved']);
        $this->assertNull($request->expirationAlert());
        $this->assertSame(0, Request::overdue()->count());
        $request->update(['status' => 'rejected']);
        $this->assertNull($request->expirationAlert());
        $this->travelBack();
    }

    public function test_overdue_alerts_are_visible_and_do_not_block_any_approval_stage(): void
    {
        $request = $this->request('pending_area_manager', '2030-01-10');
        $this->travelTo(Carbon::parse('2030-01-11 12:00:00'));
        Livewire::actingAs($request->employee, 'web')->test(RequestList::class)->set('deadline', 'overdue')->assertSee('Vencida');
        Livewire::actingAs($request->employee, 'web')->test(ApprovalShow::class, ['request' => $request])->assertSee('Vencida');
        foreach (['area_manager', 'hr_manager', 'plant_manager'] as $role) {
            $manager = $this->user($role, ['area_id' => $request->area_id]);
            Livewire::actingAs($manager, 'web')->test(ApprovalList::class)->set('deadline', 'overdue')->assertSee('Vencida');
            app(DecideRequest::class)->handle($request, $manager, 'approved');
        }
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertDatabaseCount('authorizations', 3);
        $this->travelBack();
    }

    public function test_migration_backfills_existing_dates_without_changing_approval_status(): void
    {
        $request = $this->request('pending_hr_manager', '2030-01-10');
        $request->days()->create(['day_date' => '2030-01-08', 'hours' => 2]);
        $migration = require database_path('migrations/2026_09_29_120000_add_request_expiration.php');
        $migration->down();
        $migration->up();
        $alignment = require database_path('migrations/2026_09_29_130000_align_request_deadlines_with_overtime.php');
        $alignment->up();
        $this->assertSame('2030-01-08 00:00:00', $request->fresh()->expires_at->format('Y-m-d H:i:s'));
        $this->assertSame('pending_hr_manager', $request->fresh()->status);
    }
}
