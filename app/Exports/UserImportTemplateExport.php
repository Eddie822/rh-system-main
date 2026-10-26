<?php

namespace App\Exports;

use App\Exports\Concerns\FormatsUserExcelSheet;
use App\Imports\UserRosterImporter;
use App\Models\Area;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

class UserImportTemplateExport implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [new UserRosterSheet, new UserImportGuideSheet];
    }
}

class UserRosterSheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles, WithTitle
{
    use FormatsUserExcelSheet;

    public function headings(): array
    {
        return UserRosterImporter::HEADERS;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Usuarios';
    }

    public function columnFormats(): array
    {
        return ['B' => '@'];
    }
}

class UserImportGuideSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use FormatsUserExcelSheet;

    public function headings(): array
    {
        return ['Campo o área disponible', 'Instrucción'];
    }

    public function title(): string
    {
        return 'Guía y áreas';
    }

    public function array(): array
    {
        $rows = [
            ['Area', 'Obligatoria. Se conserva la relación con la tabla de áreas; si el nombre no existe se crea.'],
            ['num_emp', 'Número de nómina obligatorio, máximo 50 caracteres. Usa texto para conservar ceros iniciales o más de 15 dígitos. Las nóminas duplicadas se omiten.'],
            ['apell_pat', 'Apellido paterno obligatorio, máximo 255 caracteres. Se guarda en apellidos_pat.'],
            ['apell_mat', 'Apellido materno opcional, máximo 255 caracteres. Se guarda en apellido_mat; vacío se guarda como null.'],
            ['nombre', 'Nombre o nombres obligatorios, máximo 255 caracteres.'],
            ['Puesto', 'Puesto laboral obligatorio, máximo 255 caracteres. No asigna permisos ni rol.'],
            ['Acceso', 'Todos se crean como empleados, sin supervisor ni jefe directo. Los roles de autorización se configuran desde administración.'],
            ['Límites', 'Hasta 5000 usuarios y 5 MB. Sin fórmulas. Una fila nueva inválida cancela toda la importación.'],
            ['', 'Áreas disponibles al descargar la plantilla:'],
        ];
        foreach (Area::orderBy('name')->pluck('name') as $name) {
            $rows[] = [$name, 'Disponible en la base de datos'];
        }

        return $rows;
    }
}
