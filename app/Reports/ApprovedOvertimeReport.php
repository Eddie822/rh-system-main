<?php

namespace App\Reports;

use App\Models\RequestDay;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ApprovedOvertimeReport
{
    public function __construct(public readonly array $filters) {}

    public function dates(): array
    {
        return match ($this->filters['period']) {
            'day' => [$this->filters['date'], $this->filters['date']],
            'range' => [$this->filters['from'], $this->filters['to']],
            'week' => $this->weekDates(),
        };
    }

    private function weekDates(): array
    {
        $monday = CarbonImmutable::now()->setISODate((int) $this->filters['year'], (int) $this->filters['week'], 1)->startOfDay();

        return [$monday->toDateString(), $monday->addDays(6)->toDateString()];
    }

    public function query(): Builder
    {
        [$from, $to] = $this->dates();

        return RequestDay::query()
            ->with(['employee', 'request.employee', 'request.area', 'request.authorizations' => fn ($query) => $query
                ->where('authorization_role', 'plant_manager')->where('action', 'approved')])
            ->whereHas('request', fn ($query) => $query->where('status', 'approved')
                ->when($this->filters['area_id'] ?? null, fn ($query, $area) => $query->where('area_id', $area)))
            ->where('day_date', '>=', $from)
            ->where('day_date', '<', CarbonImmutable::parse($to)->addDay()->toDateString())
            ->when($this->filters['employee_number'] ?? null, fn ($query, $number) => $query
                ->where(function ($query) use ($number) {
                    $query->whereHas('employee', fn ($query) => $query->where('employee_number', $number))
                        ->orWhere(fn ($query) => $query->whereNull('employee_id')
                            ->whereHas('request', fn ($query) => $query->where('is_group', false)
                                ->whereHas('employee', fn ($query) => $query->where('employee_number', $number))));
                }))
            ->orderBy('day_date')->orderBy('id');
    }
}
