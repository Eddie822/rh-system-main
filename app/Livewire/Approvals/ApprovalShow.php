<?php

namespace App\Livewire\Approvals;

use App\Models\Authorization;
use App\Models\Request as RequestModel;
use Livewire\Component;

class ApprovalShow extends Component
{
    public RequestModel $request;

    public bool $showRejectModal = false;

    public string $rejectReason = '';

    public function mount(RequestModel $request)
    {
        $this->request = $request->load([
            'employee',
            'area',
            'days',
            'authorizations.user',
        ]);

        abort_unless(
            auth()->user()->canApprove(),
            403
        );
    }

    public function approve()
    {
        if (! $this->canApproveRequest()) {
            abort(403);
        }

        Authorization::create([
            'request_id'         => $this->request->id,
            'user_id'            => auth()->id(),
            'authorization_role' => auth()->user()->role,
            'action'             => 'approved',
            'reason'             => null,
            'timestamp'          => now(),
        ]);

        match (auth()->user()->role) {

            'area_manager' => $this->request->update([
                'status' => 'pending_hr_manager',
            ]),

            'hr_manager' => $this->request->update([
                'status' => 'pending_plant_manager',
            ]),

            'plant_manager' => $this->request->update([
                'status' => 'approved',
            ]),
        };

        session()->flash(
            'success',
            'Solicitud aprobada correctamente.'
        );

        return redirect()->route('approvals.index');
    }

    public function openRejectModal()
    {
        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->reset('rejectReason');
    }
    public function reject()
    {
        $this->validate([
            'rejectReason' => 'required|string|min:5|max:500',
        ]);

        if (! $this->canApproveRequest()) {
            abort(403);
        }

        Authorization::create([
            'request_id'         => $this->request->id,
            'user_id'            => auth()->id(),
            'authorization_role' => auth()->user()->role,
            'action'             => 'rejected',
            'reason'             => $this->rejectReason,
            'timestamp'          => now(),
        ]);

        $this->request->update([
            'status' => 'rejected',
        ]);

        $this->showRejectModal = false;

        $this->reset('rejectReason');

        session()->flash(
            'success',
            'Solicitud rechazada correctamente.'
        );

        return redirect()->route('approvals.index');
    }

    protected function canApproveRequest(): bool
    {
        return match (auth()->user()->role) {

            'area_manager'
                => $this->request->status === 'pending_area_manager',

            'hr_manager'
                => $this->request->status === 'pending_hr_manager',

            'plant_manager'
                => $this->request->status === 'pending_plant_manager',

            default => false,
        };
    }

    public function render()
    {
        return view(
            'livewire.approvals.approval-show'
        );
    }
}