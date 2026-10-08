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
        return User::with(['area', 'directManager'])
            ->orderBy('employee_number')
            ->get()
            ->map(fn (User $user) => [
                $user->employee_number,
                $user->name,
                $user->last_name,
                UserRosterImporter::ROLE_LABELS[$user->role] ?? $user->role,
                $user->area?->name ?? '',
                $user->group ?? '',
                $user->directManager?->employee_number ?? '',
            ])->all();
    }

    public function columnFormats(): array
    {
        return ['A' => '@', 'G' => '@'];
    }
}
