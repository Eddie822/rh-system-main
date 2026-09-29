<?php

namespace App\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorInstance;

class OvertimeReportFilters
{
    public static function defaults(): array
    {
        return [
            'period' => 'week',
            'year' => now()->isoWeekYear(),
            'week' => now()->isoWeek(),
            'date' => now()->toDateString(),
            'from' => now()->startOfWeek()->toDateString(),
            'to' => now()->endOfWeek()->toDateString(),
        ];
    }

    public static function rules(): array
    {
        return [
            'period' => ['required', Rule::in(['day', 'week', 'range'])],
            'year' => ['exclude_unless:period,week', 'required', 'integer', 'between:2000,2100'],
            'week' => ['exclude_unless:period,week', 'required', 'integer', 'between:1,53'],
            'date' => ['exclude_unless:period,day', 'required', 'date_format:Y-m-d'],
            'from' => ['exclude_unless:period,range', 'required', 'date_format:Y-m-d'],
            'to' => ['exclude_unless:period,range', 'required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'employee_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public static function validator(array $filters): ValidatorInstance
    {
        return Validator::make($filters, self::rules())->after(function (ValidatorInstance $validator) use ($filters) {
            if ($validator->errors()->isNotEmpty() || ($filters['period'] ?? null) !== 'week') {
                return;
            }
            $year = (int) $filters['year'];
            $week = (int) $filters['week'];
            $date = CarbonImmutable::now()->setISODate($year, $week, 1);
            if ($date->isoWeekYear() !== $year || $date->isoWeek() !== $week) {
                $validator->errors()->add('week', 'La semana seleccionada no existe en ese año ISO.');
            }
        });
    }
}
