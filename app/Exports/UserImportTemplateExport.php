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
        return ['A' => '@', 'G' => '@'];
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
            ['Número de nómina', 'Obligatorio. Puede iniciar con cualquier dígito. Usa texto para ceros iniciales o más de 15 dígitos. Nóminas duplicadas se omiten.'],
            ['Nombre / Apellidos', 'Obligatorios, máximo 255 caracteres cada uno.'],
            ['Rol', 'Empleado, Supervisor, Gerente de área, Gerente de RH, Gerente de planta.'],
            ['Área', 'Nombre del área. Si no existe, se crea en la tabla de áreas. Obligatoria para Empleado, Supervisor y Gerente de área.'],
            ['Grupo', 'Opcional. Grupo o línea, máximo 255 caracteres.'],
            ['Nómina del jefe directo', 'Obligatoria para Empleado y Supervisor; opcional para Gerente de área y Gerente de RH; vacía para Gerente de planta. Para Empleado, el jefe puede ser Supervisor o gerente de su misma área. El jefe puede aparecer después en el archivo.'],
            ['', 'Áreas disponibles al descargar la plantilla:'],
        ];
        foreach (Area::orderBy('name')->pluck('name') as $name) {
            $rows[] = [$name, 'Disponible en la base de datos'];
        }

        return $rows;
    }
}
