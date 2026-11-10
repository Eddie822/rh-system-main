<?php

namespace Tests\Feature;

use App\Actions\DecideRequest;
use App\Livewire\Admin\User\UserEdit;
use App\Livewire\Approvals\ApprovalList;
use App\Livewire\Approvals\ApprovalShow;
use App\Livewire\Requests\EditRequest;
use App\Livewire\Requests\GroupRequestForm;
use App\Livewire\Requests\RequestForm;
use App\Livewire\Requests\RequestList;
use App\Models\Area;
use App\Models\Request;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class GroupRequestTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        // Never use the application's database for these migration/Livewire tests.
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.url', null);

        return $app;
    }

    public function test_supervisor_can_search_by_name_or_payroll_and_select_an_employee(): void
    {
        $supervisor = $this->user('supervisor', ['area_id' => Area::create(['name' => 'Búsqueda'])->id]);
        $worker = $this->user('worker', ['name' => 'Lucia', 'apellidos_pat' => 'Perez', 'employee_number' => 'BUSCA-001']);
        $form = Livewire::actingAs($supervisor)->test(GroupRequestForm::class)
            ->set('employeeSearch.0', 'Lucia Perez')
            ->assertSee('BUSCA-001')
            ->call('selectEmployee', 0, 'BUSCA-001')
            ->assertSet('employees.0.employee_number', 'BUSCA-001')
            ->set('employeeSearch.0', 'BUSCA-001')
            ->assertSet('employees.0.employee_number', '')
            ->assertSee('Lucia');
        $form->set('employeeSearch.0', 'Sin coincidencias')->assertSee('No se encontraron empleados.');
    }

    private function user(string $role = 'worker', array $attributes = []): User
    {
        static $number = 1000;

        return User::create(array_merge([
            'employee_number' => (string) ++$number,
            'name' => 'Employee '.$number,
            'apellidos_pat' => 'Test',
            'password' => 'password123',
            'role' => $role,
            'must_change_password' => false,
        ], $attributes));
    }

    private function httpUser(User $user): static
    {
        // Aborted Livewire test requests can leave their redirector bound in the
        // shared test container. Start HTTP requests with normal request state.
        $redirector = new Redirector(app('url'));
        $redirector->setSession(app('session.store'));
        app()->instance('redirect', $redirector);
        app('session')->flush();
        app('auth')->forgetGuards();

        return $this->actingAs($user, 'web');
    }

    private function team(): array
    {
        $area = Area::create(['name' => 'Production']);
        $supervisor = $this->user('supervisor', ['area_id' => $area->id]);
        $first = $this->user(attributes: ['area_id' => $area->id]);
        $second = $this->user(attributes: ['area_id' => $area->id]);

        return [$supervisor, $first, $second];
    }

    private function entries(User $first, User $second): array
    {
        $monday = now()->addWeeks(2)->startOfWeek();

        return [
            ['employee_number' => $first->employee_number, 'days' => [
                ['day_date' => $monday->toDateString(), 'hours' => 2],
                ['day_date' => $monday->copy()->addDay()->toDateString(), 'hours' => 3.5],
            ]],
            ['employee_number' => $second->employee_number, 'days' => [
                ['day_date' => $monday->toDateString(), 'hours' => 4],
            ]],
        ];
    }

    private function submit(User $supervisor, array $entries)
    {
        return Livewire::actingAs($supervisor, 'web')->test(GroupRequestForm::class)
            ->set('group', 'Line A')->set('reason', 'Overtime coverage')
            ->set('employees', $entries)->call('save');
    }

    private function groupRequest(): array
    {
        [$supervisor, $first, $second] = $this->team();
        $this->submit($supervisor, $this->entries($first, $second))->assertHasNoErrors()->assertRedirect(route('requests.index'));

        return [Request::firstOrFail(), $supervisor, $first, $second];
    }

    public function test_supervisor_creates_one_request_linked_to_each_employee_and_day(): void
    {
        [$request, $supervisor, $first, $second] = $this->groupRequest();
        $this->assertTrue($request->is_group);
        $this->assertSame($supervisor->id, $request->employee_id);
        $this->assertSame('pending_area_manager', $request->status);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $request->participants->modelKeys());
        $this->assertCount(3, $request->days);
        $this->assertEquals(5.5, $request->days->where('employee_id', $first->id)->sum('hours'));
        $this->assertSame($request->id, $first->groupRequests()->first()->id);
    }

    public function test_only_supervisors_and_managers_can_create_group_requests(): void
    {
        foreach (['worker', 'area_manager', 'supervisor'] as $role) {
            $user = $this->user($role);
            Livewire::actingAs($user, 'web')->test(GroupRequestForm::class)->assertForbidden();
            $this->httpUser($user)->get(route('requests.group.create'))->assertForbidden();
        }

        $area = Area::create(['name' => 'Operaciones']);
        foreach (['supervisor', 'area_manager', 'hr_manager', 'plant_manager'] as $role) {
            $user = $this->user($role, ['area_id' => $area->id]);
            Livewire::actingAs($user, 'web')->test(GroupRequestForm::class)->assertOk();
            $this->httpUser($user)->get(route('request'))->assertOk()->assertSeeLivewire(GroupRequestForm::class);
        }
    }

    public function test_supervisors_can_request_unassigned_workers_from_any_area(): void
    {
        [$supervisor, $first, $second] = $this->team();
        $otherArea = Area::create(['name' => 'Almacén']);
        $second->update(['area_id' => $otherArea->id]);
        $this->submit($supervisor, $this->entries($first, $second))->assertHasNoErrors();
        $request = Request::firstOrFail();
        $this->assertSame($supervisor->area_id, $request->area_id);
        $this->assertTrue($request->participants->contains($second));
        $this->assertNull($second->fresh()->direct_manager_id);
        $otherSupervisor = $this->user('supervisor', ['area_id' => $otherArea->id]);
        $this->submit($otherSupervisor, $this->entries($first, $second))->assertHasNoErrors();
        $this->assertSame(2, $second->groupRequests()->count());
        $otherManager = $this->user('area_manager', ['area_id' => $otherArea->id]);
        $this->assertFalse($otherManager->can('approve', $request));
    }

    public function test_validation_rejects_duplicate_employees_dates_and_invalid_hours(): void
    {
        [$supervisor, $first, $second] = $this->team();
        $entries = $this->entries($first, $first);
        $this->submit($supervisor, $entries)->assertHasErrors(['employees.0.employee_number']);
        $entries = $this->entries($first, $second);
        $entries[0]['days'][1]['day_date'] = $entries[0]['days'][0]['day_date'];
        $this->submit($supervisor, $entries)->assertHasErrors(['employees.0.days']);
        foreach ([0, -1, 13, 1.234] as $hours) {
            $entries = $this->entries($first, $second);
            $entries[0]['days'][0]['hours'] = $hours;
            $this->submit($supervisor, $entries)->assertHasErrors(['employees.0.days.0.hours']);
        }
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_validation_accepts_past_dates_and_rejects_different_weeks_or_years(): void
    {
        [$supervisor, $first, $second] = $this->team();
        $entries = $this->entries($first, $second);
        $entries[1]['days'][0]['day_date'] = now()->addWeeks(4)->toDateString();
        $this->submit($supervisor, $entries)->assertHasErrors(['employees']);
        $entries[0]['days'] = [['day_date' => '2030-01-07', 'hours' => 2]];
        $entries[1]['days'] = [['day_date' => '2031-01-06', 'hours' => 2]];
        $this->submit($supervisor, $entries)->assertHasErrors(['employees']);
        $pastMonday = now()->subWeek()->startOfWeek();
        $entries[0]['days'] = [['day_date' => $pastMonday->toDateString(), 'hours' => 2]];
        $entries[1]['days'] = [['day_date' => $pastMonday->copy()->addDay()->toDateString(), 'hours' => 3]];
        $this->submit($supervisor, $entries)->assertHasNoErrors();
    }

    public function test_employees_track_only_their_own_days_and_cannot_edit_or_approve(): void
    {
        [$request, $supervisor, $first, $second] = $this->groupRequest();
        Livewire::actingAs($first, 'web')->test(RequestList::class)
            ->assertViewHas('requests', fn ($requests) => $requests->count() === 1 && $requests->first()->days->count() === 2);
        Livewire::actingAs($first, 'web')->test(ApprovalShow::class, ['request' => $request])
            ->assertViewHas('days', fn ($days) => $days->count() === 2 && $days->every(fn ($day) => $day->employee_id === $first->id))
            ->assertDontSee($second->name)->call('approve')->assertForbidden();
        $this->httpUser($first)->get(route('requests.show', $request))->assertOk();
        $this->httpUser($first)->get(route('requests.edit', $request))->assertForbidden();
        Livewire::actingAs($first, 'web')->test(ApprovalList::class)->assertForbidden();
        $outsider = $this->user();
        Livewire::actingAs($outsider, 'web')->test(RequestList::class)->assertViewHas('requests', fn ($requests) => $requests->isEmpty());
        $this->httpUser($outsider)->get(route('requests.show', $request))->assertForbidden();
        Livewire::actingAs($supervisor, 'web')->test(ApprovalShow::class, ['request' => $request])
            ->assertViewHas('days', fn ($days) => $days->count() === 3);
    }

    public function test_approval_moves_entire_group_through_all_three_stages(): void
    {
        [$request, $supervisor, $first] = $this->groupRequest();
        foreach (['area_manager' => 'pending_hr_manager', 'hr_manager' => 'pending_plant_manager', 'plant_manager' => 'approved'] as $role => $status) {
            $manager = $this->user($role, ['area_id' => $supervisor->area_id]);
            Livewire::actingAs($manager, 'web')->test(ApprovalList::class)->set('search', $first->employee_number)
                ->assertViewHas('requests', fn ($requests) => $requests->count() === 1);
            Livewire::actingAs($manager, 'web')->test(ApprovalShow::class, ['request' => $request])
                ->call('approve')->assertRedirect(route('approvals.index'));
            $this->assertSame($status, $request->fresh()->status);
        }
        $this->assertSame('approved', $first->groupRequests()->first()->status);
        $this->assertDatabaseCount('authorizations', 3);
    }

    public function test_wrong_area_cannot_approve_but_hr_can_approve_first(): void
    {
        [$request] = $this->groupRequest();
        $otherArea = Area::create(['name' => 'Other']);
        $manager = $this->user('area_manager', ['area_id' => $otherArea->id]);
        $this->httpUser($manager)->get(route('approvals.show', $request))->assertForbidden();
        $hr = $this->user('hr_manager');
        Livewire::actingAs($hr, 'web')->test(ApprovalShow::class, ['request' => $request])->call('approve')->assertRedirect(route('approvals.index'));
        $this->assertDatabaseCount('authorizations', 1);
        $this->assertSame('pending_area_manager', $request->fresh()->status);
    }

    public function test_area_manager_rejects_the_whole_group_with_a_reason(): void
    {
        [$request, $supervisor, $first, $second] = $this->groupRequest();
        $manager = $this->user('area_manager', ['area_id' => $supervisor->area_id]);
        Livewire::actingAs($manager, 'web')->test(ApprovalShow::class, ['request' => $request])
            ->call('reject')->assertHasErrors(['rejectReason'])
            ->set('rejectReason', 'No coverage required')->call('reject')->assertRedirect(route('approvals.index'));
        foreach ([$first, $second] as $employee) {
            $this->assertSame('rejected', $employee->groupRequests()->first()->status);
        }
        $this->assertDatabaseHas('authorizations', ['request_id' => $request->id, 'action' => 'rejected', 'reason' => 'No coverage required']);
    }

    public function test_repeated_decision_does_not_add_another_authorization(): void
    {
        [$request, $supervisor] = $this->groupRequest();
        $manager = $this->user('area_manager', ['area_id' => $supervisor->area_id]);
        app(DecideRequest::class)->handle($request, $manager, 'approved');
        try {
            app(DecideRequest::class)->handle($request, $manager, 'rejected', 'Stale decision');
            $this->fail('A stale decision must be rejected.');
        } catch (AuthorizationException) {
            $this->assertSame('pending_hr_manager', $request->fresh()->status);
            $this->assertDatabaseCount('authorizations', 1);
        }
    }

    public function test_supervisor_can_edit_pending_group_but_not_after_approval(): void
    {
        [$request, $supervisor, $first, $second] = $this->groupRequest();
        $form = Livewire::actingAs($supervisor, 'web')->test(GroupRequestForm::class, ['request' => $request]);
        $form->set('employees.0.days.0.hours', 6)->call('save')->assertHasNoErrors();
        $this->assertEquals(6, $request->days()->where('employee_id', $first->id)->first()->hours);
        $manager = $this->user('area_manager', ['area_id' => $supervisor->area_id]);
        app(DecideRequest::class)->handle($request, $manager, 'approved');
        $form->set('reason', 'Changed after approval')->call('save')->assertForbidden();
        $this->assertSame('Overtime coverage', $request->fresh()->reason);
    }

    public function test_role_revocation_is_checked_for_employees_and_supervisors_at_save(): void
    {
        [$supervisor, $first, $second] = $this->team();
        $form = Livewire::actingAs($supervisor, 'web')->test(GroupRequestForm::class)
            ->set('group', 'A')->set('reason', 'Coverage')->set('employees', $this->entries($first, $second));
        $second->update(['role' => 'supervisor']);
        $form->call('save')->assertHasErrors(['employees.1.employee_number']);
        $supervisor->update(['role' => 'worker']);
        $form->call('save')->assertForbidden();
        $this->assertDatabaseCount('requests', 0);
    }

    public function test_removing_an_employee_updates_tracking_without_leaving_days(): void
    {
        [$request, $supervisor, $first, $second] = $this->groupRequest();
        $this->httpUser($supervisor)->get(route('requests.edit', $request))->assertOk();
        Livewire::actingAs($supervisor, 'web')->test(GroupRequestForm::class, ['request' => $request])
            ->call('removeEmployee', 1)->call('save')->assertHasNoErrors();
        $this->assertSame([$first->id], $request->participants()->pluck('users.id')->all());
        $this->assertFalse($request->days()->where('employee_id', $second->id)->exists());
        $this->httpUser($second)->get(route('requests.show', $request))->assertForbidden();
        Livewire::actingAs($supervisor, 'web')->test(EditRequest::class, ['request' => $request])->assertForbidden();
    }

    public function test_approval_failure_rolls_back_the_audit_record(): void
    {
        [$request, $supervisor] = $this->groupRequest();
        $manager = $this->user('area_manager', ['area_id' => $supervisor->area_id]);
        DB::unprepared("CREATE TRIGGER fail_status_update BEFORE UPDATE OF status ON requests BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
        try {
            app(DecideRequest::class)->handle($request, $manager, 'approved');
            $this->fail('The simulated database failure must occur.');
        } catch (QueryException) {
            $this->assertDatabaseCount('authorizations', 0);
            $this->assertSame('pending_area_manager', $request->fresh()->status);
        }
    }

    public function test_administration_can_assign_supervisor_role_without_linking_employees(): void
    {
        [$supervisor, $first] = $this->team();
        $admin = $this->user('worker', ['area_id' => Area::firstOrCreate(['name' => 'Sistemas'])->id]);
        $newSupervisor = $this->user(attributes: ['area_id' => $supervisor->area_id]);
        Livewire::actingAs($admin, 'web')->test(UserEdit::class, ['user' => $newSupervisor])
            ->set('role', 'supervisor')->call('save')->assertHasNoErrors();
        $this->assertSame('supervisor', $newSupervisor->fresh()->role);
        $this->assertNull($first->fresh()->direct_manager_id);
        $this->assertFalse(Schema::hasColumn('users', 'supervisor_id'));
    }

    public function test_individual_requests_keep_their_tracking_and_reject_stale_edits(): void
    {
        $area = Area::create(['name' => 'Existing']);
        $requester = $this->user('hr_manager', ['area_id' => $area->id]);
        $request = Request::create(['employee_id' => $requester->id, 'area_id' => $area->id,
            'group' => 'A', 'reason' => 'Individual request', 'week' => '1', 'status' => 'pending_area_manager']);
        $request->days()->create(['day_name' => 'Lunes', 'day_date' => now()->addWeek()->toDateString(), 'hours' => 2]);
        Livewire::actingAs($requester, 'web')->test(RequestList::class)->assertViewHas('requests', fn ($requests) => $requests->first()->days->count() === 1);
        $form = Livewire::actingAs($requester, 'web')->test(EditRequest::class, ['request' => $request]);
        $manager = $this->user('area_manager', ['area_id' => $area->id]);
        app(DecideRequest::class)->handle($request, $manager, 'approved');
        $form->set('reason', 'Changed')->call('save')->assertForbidden();
        $this->assertSame('Individual request', $request->fresh()->reason);
    }

    public function test_workers_cannot_create_requests_and_are_redirected_to_their_request_list(): void
    {
        $area = Area::create(['name' => 'Production']);
        $worker = $this->user('worker', ['area_id' => $area->id]);

        $this->httpUser($worker)->get(route('request'))->assertRedirect(route('requests.index'));
        $this->httpUser($worker)->get(route('requests.index'))->assertOk()->assertDontSee(route('request'));
        Livewire::actingAs($worker, 'web')->test(RequestForm::class)->assertForbidden();
    }
}
