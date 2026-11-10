<?php

namespace Tests\Feature;

use App\Actions\DecideRequest;
use App\Exports\ApprovedOvertimeExport;
use App\Livewire\Approvals\ApprovalList;
use App\Livewire\Approvals\ApprovalShow;
use App\Livewire\Requests\GroupRequestForm;
use App\Livewire\Requests\RequestList;
use App\Livewire\Requests\RequestLookup;
use App\Models\Area;
use App\Models\Request;
use App\Models\User;
use App\Reports\ApprovedOvertimeReport;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Tests\TestCase;

class FlexibleApprovalsTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        $production = Area::firstOrCreate(['name' => 'Producción']);
        $other = Area::firstOrCreate(['name' => 'Almacén']);
        $area = User::factory()->create(['role' => 'area_manager', 'area_id' => $other->id, 'must_change_password' => false]);
        $hr = User::factory()->create(['role' => 'hr_manager', 'must_change_password' => false]);
        $plant = User::factory()->create(['role' => 'plant_manager', 'must_change_password' => false]);
        $supervisor = User::factory()->create(['role' => 'supervisor', 'area_id' => $production->id,
            'area_manager_id' => $area->id, 'direct_manager_id' => $area->id, 'must_change_password' => false]);

        return [$supervisor, $area, $hr, $plant];
    }

    private function request(User $supervisor): Request
    {
        $request = Request::create(['employee_id' => $supervisor->id, 'area_id' => $supervisor->area_id,
            'is_group' => true, 'group' => 'Línea A', 'reason' => 'Cobertura', 'week' => '2', 'status' => 'pending_area_manager']);
        $request->days()->create(['day_date' => now()->addWeeks(2)->startOfWeek()->toDateString(), 'hours' => 2]);

        return $request;
    }

    public function test_all_six_approval_orders_finish_only_after_three_authorizations(): void
    {
        [$supervisor, $area, $hr, $plant] = $this->scenario();
        foreach ([[$area, $hr, $plant], [$area, $plant, $hr], [$hr, $area, $plant],
            [$hr, $plant, $area], [$plant, $area, $hr], [$plant, $hr, $area]] as $order) {
            $request = $this->request($supervisor);
            foreach ($order as $index => $manager) {
                Livewire::actingAs($manager)->test(ApprovalList::class)
                    ->assertViewHas('requests', fn ($rows) => $rows->contains('id', $request->id));
                app(DecideRequest::class)->handle($request, $manager, 'approved');
                $this->assertSame($index === 2, $request->fresh()->status === 'approved');
                $this->assertSame($index + 1, $request->authorizations()->count());
                $this->assertFalse($manager->can('approve', $request->fresh()));
                Livewire::actingAs($manager)->test(ApprovalList::class)
                    ->assertViewHas('requests', fn ($rows) => ! $rows->contains('id', $request->id));
            }
        }
    }

    public function test_plant_manager_assigned_to_supervisor_signs_both_responsibilities(): void
    {
        [$supervisor, $area, $hr, $plant] = $this->scenario();
        $supervisor->update(['area_manager_id' => $plant->id, 'direct_manager_id' => $plant->id]);
        $request = $this->request($supervisor);
        $this->assertSame($plant->id, $request->assigned_area_manager_id);
        $this->assertFalse($area->can('viewApproval', $request));
        Livewire::actingAs($plant)->test(ApprovalShow::class, ['request' => $request])
            ->assertSee($plant->full_name)->assertViewHas('myApprovalRoles', ['area_manager', 'plant_manager'])
            ->call('approve')->assertRedirect(route('approvals.index'));
        $this->assertSame('pending_hr_manager', $request->fresh()->status);
        $this->assertSame(['area_manager', 'plant_manager'], $request->authorizations()->pluck('authorization_role')->all());
        $this->assertSame([$plant->id], $request->authorizations()->pluck('user_id')->unique()->values()->all());
        app(DecideRequest::class)->handle($request, $hr, 'approved');
        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_dual_authorization_also_works_when_hr_signs_first(): void
    {
        [$supervisor, $area, $hr, $plant] = $this->scenario();
        $supervisor->update(['area_manager_id' => $plant->id, 'direct_manager_id' => $plant->id]);
        $request = $this->request($supervisor);
        app(DecideRequest::class)->handle($request, $hr, 'approved');
        app(DecideRequest::class)->handle($request, $plant, 'approved');
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame(3, $request->authorizations()->count());
        $this->expectException(AuthorizationException::class);
        app(DecideRequest::class)->handle($request, $plant, 'approved');
    }

    public function test_hr_manager_can_also_hold_the_area_responsibility(): void
    {
        [$supervisor, $area, $hr, $plant] = $this->scenario();
        $supervisor->update(['area_manager_id' => $hr->id, 'direct_manager_id' => $hr->id]);
        $request = $this->request($supervisor);
        app(DecideRequest::class)->handle($request, $plant, 'approved');
        app(DecideRequest::class)->handle($request, $hr, 'approved');
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame(2, $request->authorizations()->where('user_id', $hr->id)->count());
    }

    public function test_explicit_cross_area_assignment_overrides_area_and_is_preserved_on_old_requests(): void
    {
        [$supervisor, $area, $hr, $plant] = $this->scenario();
        $ownAreaManager = User::factory()->create(['role' => 'area_manager', 'area_id' => $supervisor->area_id, 'must_change_password' => false]);
        $request = $this->request($supervisor);
        $this->assertTrue($area->can('approve', $request));
        $this->assertFalse($ownAreaManager->can('viewApproval', $request));
        Livewire::actingAs($ownAreaManager)->test(ApprovalList::class)->assertViewHas('requests', fn ($rows) => $rows->isEmpty());
        $supervisor->update(['area_manager_id' => $plant->id, 'direct_manager_id' => $plant->id]);
        $this->assertSame($area->id, $request->fresh()->areaApproverId());
        $this->assertSame($plant->id, $this->request($supervisor)->areaApproverId());
    }

    public function test_any_responsible_manager_can_reject_before_others_sign(): void
    {
        [$supervisor, $area, $hr, $plant] = $this->scenario();
        $request = $this->request($supervisor);
        app(DecideRequest::class)->handle($request, $plant, 'rejected', 'No se requiere cobertura');
        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertFalse($area->can('approve', $request->fresh()));
        $this->assertFalse($hr->can('approve', $request->fresh()));
        $this->assertSame('No se requiere cobertura', $request->authorizations()->sole()->reason);
    }

    public function test_request_cannot_be_edited_after_any_signature_even_if_area_is_pending(): void
    {
        [$supervisor, $area, $hr, $plant] = $this->scenario();
        $request = $this->request($supervisor);
        app(DecideRequest::class)->handle($request, $plant, 'approved');
        $this->assertSame('pending_area_manager', $request->fresh()->status);
        $this->assertFalse($supervisor->can('update', $request->fresh()));
        Livewire::actingAs($supervisor)->test(GroupRequestForm::class, ['request' => $request])->assertForbidden();
    }

    public function test_report_final_approval_date_is_last_signature_and_stays_empty_while_pending(): void
    {
        [$supervisor, $area, $hr, $plant] = $this->scenario();
        $this->travelTo(now()->startOfHour());
        $request = $this->request($supervisor);
        $date = $request->created_at->toDateString();
        $report = new ApprovedOvertimeReport(['period' => 'day', 'date' => $date]);
        $export = new ApprovedOvertimeExport($report);
        app(DecideRequest::class)->handle($request, $plant, 'approved');
        $this->assertNull($export->map($report->query()->first())[12]);
        $this->travel(1)->hours();
        app(DecideRequest::class)->handle($request, $hr, 'approved');
        $this->travel(1)->hours();
        app(DecideRequest::class)->handle($request, $area, 'approved');
        $this->assertSame(Date::dateTimeToExcel(now()), $export->map($report->query()->first())[12]);
    }

    public function test_status_labels_include_manager_names_and_any_user_can_lookup_requests_by_id(): void
    {
        [$supervisor, $area, $hr, $plant] = $this->scenario();
        $request = $this->request($supervisor);
        $outsider = User::factory()->create(['role' => 'worker', 'must_change_password' => false]);

        $this->assertStringContainsString("({$area->full_name})", $request->status_label);

        Livewire::actingAs($supervisor)->test(RequestList::class)
            ->assertSee("Pendiente Gerente Área ({$area->full_name})");

        Livewire::actingAs($outsider)->test(RequestLookup::class)
            ->set('search', '#'.$request->id)
            ->assertSee('Solicitud #'.$request->id)
            ->assertSee("Pendiente Gerente de Área ({$area->full_name})")
            ->assertSee("Gerente de Área ({$area->full_name})")
            ->assertSee("Gerente de RH ({$hr->full_name})")
            ->assertSee("Gerente de Planta ({$plant->full_name})");

        $this->actingAs($outsider)
            ->get(route('requests.search.show', $request))
            ->assertOk()
            ->assertSee('Solicitud #'.$request->id)
            ->assertSee("Pendiente Gerente de Área ({$area->full_name})");
    }
}
