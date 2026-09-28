<?php

namespace App\Livewire\Approvals;

use App\Actions\DecideRequest;
use App\Models\Request as RequestModel;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ApprovalShow extends Component
{
    public RequestModel $request;

    public bool $showRejectModal = false;

    public string $rejectReason = '';

    public function mount(RequestModel $request): void
    {
        Gate::authorize('view', $request);
        $this->request = $request;
    }

    public function approve()
    {
        app(DecideRequest::class)->handle($this->request, auth()->user(), 'approved');
        session()->flash('success', 'Solicitud aprobada correctamente.');

        return redirect()->route('approvals.index');
    }

    public function openRejectModal(): void
    {
        Gate::authorize('approve', $this->request->fresh());
        $this->showRejectModal = true;
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->reset('rejectReason');
    }

    public function reject()
    {
        $this->validate(['rejectReason' => 'required|string|min:5|max:500']);
        app(DecideRequest::class)->handle($this->request, auth()->user(), 'rejected', $this->rejectReason);
        session()->flash('success', 'Solicitud rechazada correctamente.');

        return redirect()->route('approvals.index');
    }

    public function render()
    {
        $this->request->refresh();
        Gate::authorize('view', $this->request);
        $this->request->load(['employee', 'area', 'authorizations.user']);
        $canSeeGroup = $this->request->employee_id === auth()->id()
            || Gate::allows('viewApproval', $this->request);
        $days = $this->request->days()->with('employee')
            ->when($this->request->is_group && ! $canSeeGroup, fn ($query) => $query->where('employee_id', auth()->id()))
            ->orderBy('employee_id')->orderBy('day_date')->get();

        return view('livewire.approvals.approval-show', compact('days'));
    }
}
