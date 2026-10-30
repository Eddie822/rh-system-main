<?php

namespace App\Exports;

use App\Exports\Concerns\FormatsUserExcelSheet;
use App\Imports\UserRosterImporter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class UserRosterExport implements Export, FromQuery, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles
{
    use FormatsUserExcelSheet;

    public function headings(): array
    {
        return UserRosterImporter::HEADERS;
    }

    public function query(): Builder
    {
        return User::query()->with('area')->orderBy('employee_number');
    }

    public function map(mixed $user): array
    {
        return [
            $user->area?->name ?? '',
            $user->employee_number,
            $user->apellidos_pat,
            $user->apellido_mat ?? '',
            $user->name,
            $user->puesto ?? '',
        ];
    }

    public function columnFormats(): array
    {
        return ['B' => '@'];
    }
}
