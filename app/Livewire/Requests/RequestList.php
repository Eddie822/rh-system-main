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

    public $deadline = '';

    public $from_date = '';

    public $to_date = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset([
            'status',
            'week',
            'deadline',
            'from_date',
            'to_date',
        ]);
        $this->resetPage();
    }

    public function render()
    {
        $requests = Request::with(['days' => fn ($query) => $query->where(fn ($query) => $query
            ->whereNull('employee_id')->orWhere('employee_id', auth()->id())
            ->orWhereHas('request', fn ($query) => $query->where('employee_id', auth()->id())))])
            ->forEmployee(auth()->user())
            ->when($this->deadline === 'soon', fn ($query) => $query->expiringSoon())
            ->when($this->deadline === 'overdue', fn ($query) => $query->overdue())

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
