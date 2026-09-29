<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Area;
use App\Reports\ApprovedOvertimeReport;
use App\Reports\OvertimeReportFilters;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovedOvertime extends Component
{
    use WithPagination;

    #[Url]
    public string $period = 'week';

    #[Url]
    public string $year = '';

    #[Url]
    public string $week = '';

    #[Url]
    public string $date = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $area_id = '';

    #[Url]
    public string $employee_number = '';

    public function mount(): void
    {
        Gate::authorize('viewReports');
        foreach (OvertimeReportFilters::defaults() as $key => $value) {
            if ($this->$key === '') {
                $this->$key = (string) $value;
            }
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['period', 'year', 'week', 'date', 'from', 'to', 'area_id', 'employee_number'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        foreach (OvertimeReportFilters::defaults() as $key => $value) {
            $this->$key = (string) $value;
        }
        $this->area_id = '';
        $this->employee_number = '';
        $this->resetPage();
    }

    private function filters(): array
    {
        return [
            'period' => $this->period,
            'year' => $this->year,
            'week' => $this->week,
            'date' => $this->date,
            'from' => $this->from,
            'to' => $this->to,
            'area_id' => $this->area_id,
            'employee_number' => trim($this->employee_number),
        ];
    }

    public function render()
    {
        Gate::authorize('viewReports');
        $validation = OvertimeReportFilters::validator($this->filters());
        $valid = ! $validation->fails();
        $filters = $valid ? $validation->validated() : null;
        $report = $valid ? new ApprovedOvertimeReport($filters) : null;
        $query = $report?->query();

        return view('livewire.admin.reports.approved-overtime', [
            'areas' => Area::orderBy('name')->get(),
            'filterErrors' => $valid ? [] : $validation->errors()->all(),
            'dates' => $report?->dates(),
            'rows' => $query
                ? (clone $query)->paginate(25)
                : new LengthAwarePaginator([], 0, 25, 1),
            'hours' => $query ? (clone $query)->sum('hours') : 0,
            'requestCount' => $query ? (clone $query)->distinct()->count('request_id') : 0,
            'exportUrl' => $valid ? route('admin.reports.export', $filters) : null,
        ]);
    }
}
