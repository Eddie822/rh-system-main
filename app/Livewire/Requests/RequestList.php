<?php

namespace App\Livewire\Requests;

use App\Models\Request;
use Livewire\Component;
use Livewire\WithPagination;

class RequestList extends Component
{
    use WithPagination;

    public $status = '';
    public $week = '';
    public $from_date = '';
    public $to_date = '';


    public function clearFilters()
    {
            $this->reset([
            'status',
            'week',
            'from_date',
            'to_date',
        ]);
    }
    public function render()
    {
        $requests = Request::with('days')
            ->where('employee_id', auth()->id())

            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })

            ->when($this->week, function ($query) {
                $query->where('week', $this->week);
            })

            ->when($this->from_date, function ($query) {
                $query->whereDate(
                    'created_at',
                    '>=',
                    $this->from_date
                );
            })

            ->when($this->to_date, function ($query) {
                $query->whereDate(
                    'created_at',
                    '<=',
                    $this->to_date
                );
            })

            ->latest()
            ->paginate(10);

        return view(
            'livewire.requests.request-list',
            compact('requests')
        );
    }
}