<?php

namespace App\Exports;

use App\Reports\ApprovedOvertimeReport;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ApprovedOvertimeExport extends DefaultValueBinder implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping
{
    public function __construct(private ApprovedOvertimeReport $report) {}

    public function query(): Builder
    {
        return $this->report->query();
    }

    public function headings(): array
    {
        return ['Solicitud', 'Tipo', 'Nómina', 'Empleado', 'Área', 'Grupo / Línea', 'Fecha de horas extra',
            'Año ISO', 'Semana ISO', 'Horas', 'Justificación', 'Solicitante', 'Aprobación final'];
    }

    public function map(mixed $row): array
    {
        $request = $row->request;
        $employee = $request->is_group ? $row->employee : $request->employee;
        $approvedAt = $request->authorizations->max('created_at');

        return [$request->id, $request->is_group ? 'Grupal' : 'Individual', $employee?->employee_number,
            trim(($employee?->name ?? '').' '.($employee?->last_name ?? '')), $request->area?->name, $request->group,
            Date::dateTimeToExcel($row->day_date), $row->day_date->isoWeekYear(), $row->day_date->isoWeek(),
            $row->hours, $request->reason, trim($request->employee->name.' '.$request->employee->last_name),
            $approvedAt ? Date::dateTimeToExcel($approvedAt) : null];
    }

    public function columnFormats(): array
    {
        return ['C' => '@', 'G' => 'dd/mm/yyyy', 'J' => '0.00', 'M' => 'dd/mm/yyyy hh:mm'];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        // Preserve payroll leading zeros and treat user-entered text as text, never formulas.
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
