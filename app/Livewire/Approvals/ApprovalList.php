<?php

namespace App\Livewire\Approvals;

use App\Models\Area;
use App\Models\Request as RequestModel;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovalList extends Component
{
    use WithPagination;

    public $search = '';

    public $area = '';

    public $week = '';

    public $deadline = '';

    public $from_date = '';

    public $to_date = '';

    protected $queryString = [
        'week',
        'from_date',
        'to_date',
    ];

    public function updating()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset([
            'week',
            'deadline',
            'from_date',
            'to_date',
        ]);

        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        abort_unless($user->canApprove(), 403);

        $query = RequestModel::with([
            'employee',
            'area',
            'days',
            'participants',
        ]);

        if ($user->role === 'area_manager') {

            $query->where('status', 'pending_area_manager')
                ->where('area_id', $user->area_id);

        } elseif ($user->role === 'hr_manager') {

            $query->where('status', 'pending_hr_manager');

        } elseif ($user->role === 'plant_manager') {

            $query->where('status', 'pending_plant_manager');
        }

        if ($this->search) {

            $query->where(function ($query) {
                $query->whereHas('employee', function ($q) {

                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('employee_number', 'like', "%{$this->search}%");
                })->orWhereHas('participants', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('employee_number', 'like', "%{$this->search}%");
                });
            });
        }

        if ($this->area) {

            $query->where('area_id', $this->area);
        }

        if ($this->week) {
            $query->where('week', $this->week);
        }

        if ($this->from_date) {
            $query->whereDate(
                'created_at',
                '>=',
                $this->from_date
            );
        }

        if ($this->to_date) {
            $query->whereDate(
                'created_at',
                '<=',
                $this->to_date
            );
        }

        $query->when($this->deadline === 'soon', fn ($query) => $query->expiringSoon())
            ->when($this->deadline === 'overdue', fn ($query) => $query->overdue());

        $areas = Area::orderBy('name')->get();

        $requests = $query
            ->latest()
            ->paginate(10);

        return view(
            'livewire.approvals.approval-list',
            compact(
                'requests',
                'areas'
            )
        );
    }
}
