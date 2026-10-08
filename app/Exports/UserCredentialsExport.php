<?php

namespace App\Exports;

use App\Exports\Concerns\FormatsUserExcelSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;

class UserCredentialsExport implements Export, FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    use FormatsUserExcelSheet;

    public function __construct(private array $credentials) {}

    public function headings(): array
    {
        return ['Número de nómina', 'Nombre', 'Apellidos', 'Contraseña temporal'];
    }

    public function array(): array
    {
        return $this->credentials;
    }

    public function columnFormats(): array
    {
        return ['A' => '@', 'D' => '@'];
    }
}
