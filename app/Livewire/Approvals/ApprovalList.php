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
            'from_date',
            'to_date',
        ]);

        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = RequestModel::with([
            'employee',
            'area',
            'days',
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

            $query->whereHas('employee', function ($q) {

                $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('employee_number', 'like', "%{$this->search}%");
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

        $areas = Area::all();

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