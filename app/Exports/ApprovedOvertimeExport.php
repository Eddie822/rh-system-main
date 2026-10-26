<?php

namespace App\Exports;

use App\Exports\Concerns\FormatsUserExcelSheet;
use App\Reports\ApprovedOvertimeReport;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ApprovedOvertimeExport extends DefaultValueBinder implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    use FormatsUserExcelSheet;

    public function __construct(private ApprovedOvertimeReport $report) {}

    public function query(): Builder
    {
        return $this->report->query();
    }

    public function headings(): array
    {
        return ['Solicitud', 'Tipo', 'Nómina', 'Empleado', 'Área de la solicitud', 'Grupo / Línea',
            'Fecha de horas extra', 'Año ISO', 'Semana ISO', 'Horas', 'Justificación', 'Solicitante', 'Aprobación final',
            'Estado', 'Fecha de registro', 'Última actualización', 'Vencimiento', 'Situación del plazo'];
    }

    public function map(mixed $row): array
    {
        $request = $row->request;
        $employee = $request->is_group ? $row->employee : $request->employee;
        $approvedAt = $request->authorizations->where('authorization_role', 'plant_manager')->where('action', 'approved')->max('created_at');
        $date = fn ($value) => $value ? Date::dateTimeToExcel($value) : null;

        return [$request->id, $request->is_group ? 'Grupal' : 'Individual', $employee?->employee_number,
            $employee?->full_name, $request->area?->name, $request->group,
            $date($row->day_date), $row->day_date->isoWeekYear(), $row->day_date->isoWeek(),
            (float) $row->hours, $request->reason, $request->employee?->full_name, $date($approvedAt),
            $request->status_label,
            $date($request->created_at), $date($request->updated_at), $date($request->expires_at),
            match ($request->expirationAlert()) {
                'overdue' => 'Vencida', 'soon' => 'Por vencer',
                default => in_array($request->status, ['approved', 'rejected'], true) ? 'Finalizada' : 'En plazo',
            }];
    }

    public function columnFormats(): array
    {
        return ['C' => '@', 'G' => 'dd/mm/yyyy', 'J' => '0.00', 'M' => 'dd/mm/yyyy hh:mm',
            'O' => 'dd/mm/yyyy hh:mm', 'P' => 'dd/mm/yyyy hh:mm', 'Q' => 'dd/mm/yyyy hh:mm'];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        // Preserve leading zeros and never interpret user-entered text as a formula.
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
