<?php

namespace App\Exports;

use App\Exports\Concerns\FormatsUserExcelSheet;
use App\Imports\UserRosterImporter;
use App\Models\User;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;

class UserRosterExport implements Export, FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    use FormatsUserExcelSheet;

    public function headings(): array
    {
        return UserRosterImporter::HEADERS;
    }

    public function array(): array
    {
        return User::with('area')->orderBy('employee_number')->get()
            ->map(fn (User $user) => [
                $user->area?->name ?? '', $user->employee_number, $user->apellidos_pat,
                $user->apellido_mat ?? '', $user->name, $user->puesto ?? '',
            ])->all();
    }

    public function columnFormats(): array
    {
        return ['B' => '@'];
    }
}
