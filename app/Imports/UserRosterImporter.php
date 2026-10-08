<?php

namespace App\Imports;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserRosterImporter
{
    public const HEADERS = [
        'Número de nómina', 'Nombre', 'Apellidos', 'Rol', 'Área', 'Grupo',
        'Nómina del jefe directo',
    ];

    public const ROLE_LABELS = [
        'worker' => 'Empleado',
        'supervisor' => 'Supervisor',
        'area_manager' => 'Gerente de área',
        'hr_manager' => 'Gerente de RH',
        'plant_manager' => 'Gerente de planta',
    ];

    private function normalizeRole(string $value): string
    {
        $normalized = Str::lower(Str::ascii($value));
        foreach (self::ROLE_LABELS as $role => $label) {
            if ($normalized === $role || $normalized === Str::lower(Str::ascii($label))) {
                return $role;
            }
        }

        return $normalized === 'trabajador' ? 'worker' : $value;
    }

    public function import(UploadedFile $file): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $book = $reader->load($file->getRealPath());
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception|Exception $exception) {
            throw ValidationException::withMessages(['file' => 'No se pudo leer el Excel. Descarga la plantilla y vuelve a guardar el archivo como .xlsx.']);
        }
        try {
            $sheet = $book->getSheet(0);
            $headers = array_map(fn ($value) => trim((string) $value), $sheet->rangeToArray('A1:G1', null, false, false)[0]);
            if ($headers !== self::HEADERS) {
                throw ValidationException::withMessages(['file' => 'Los encabezados deben coincidir exactamente con la plantilla. Usa la primera hoja y no cambies el orden de las columnas.']);
            }
            $lastRow = $sheet->getHighestDataRow();
            if ($lastRow < 2 || $lastRow > 5001) {
                throw ValidationException::withMessages(['file' => 'El archivo debe contener entre 1 y 5000 usuarios.']);
            }
            $rows = $sheet->rangeToArray("A2:G{$lastRow}", null, false, false);
        } finally {
            $book->disconnectWorksheets();
        }

        $areas = Area::all()->keyBy(fn (Area $area) => mb_strtolower(trim($area->name)));
        $existing = User::all(['id', 'employee_number', 'role', 'area_id'])->keyBy('employee_number');
        $seen = [];
        $skipped = [];
        $prepared = [];
        $errors = [];
        foreach ($rows as $offset => $cells) {
            $number = $offset + 2;
            if (collect($cells)->every(fn ($cell) => $cell === null || trim((string) $cell) === '')) {
                continue;
            }
            if (collect($cells)->contains(fn ($cell) => is_string($cell) && str_starts_with(trim($cell), '='))) {
                $errors[] = "Fila {$number}: no se admiten fórmulas; pega valores como texto.";

                continue;
            }
            [$employeeNumber, $name, $lastName, $role, $areaName, $group, $directManagerNumber] = array_map(
                fn ($value) => trim((string) ($value ?? '')), $cells
            );
            $role = $this->normalizeRole($role);
            foreach ([0, 6] as $payrollColumn) {
                $value = $cells[$payrollColumn];
                if ((is_int($value) || is_float($value))
                    && (! is_finite((float) $value) || $value < 0 || floor((float) $value) !== (float) $value || $value > 999999999999999)) {
                    $errors[] = "Fila {$number}: las nóminas numéricas deben ser enteros de hasta 15 dígitos. Para conservar ceros iniciales o números más largos, usa celdas de texto.";
                    break;
                }
            }
            if ($employeeNumber === '' || mb_strlen($employeeNumber) > 50) {
                $errors[] = "Fila {$number}: la nómina es obligatoria y admite máximo 50 caracteres.";
            } elseif (isset($seen[$employeeNumber]) || $existing->has($employeeNumber)) {
                $skipped[] = ['row' => $number, 'employee_number' => $employeeNumber];

                continue;
            }
            $seen[$employeeNumber] = $number;
            if ($name === '' || mb_strlen($name) > 255 || $lastName === '' || mb_strlen($lastName) > 255) {
                $errors[] = "Fila {$number}: nombre y apellidos son obligatorios (máximo 255 caracteres cada uno).";
            }
            if (! array_key_exists($role, self::ROLE_LABELS)) {
                $errors[] = "Fila {$number}: rol no válido. Usa Empleado, Supervisor, Gerente de área, Gerente de RH o Gerente de planta.";
            }
            if (mb_strlen($areaName) > 255) {
                $errors[] = "Fila {$number}: el nombre del área admite máximo 255 caracteres.";
            }
            if (in_array($role, ['worker', 'supervisor', 'area_manager'], true) && $areaName === '') {
                $errors[] = "Fila {$number}: el área es obligatoria para trabajadores, supervisores y gerentes de área.";
            }
            if (mb_strlen($group) > 255) {
                $errors[] = "Fila {$number}: el grupo admite máximo 255 caracteres.";
            }
            if (mb_strlen($directManagerNumber) > 50) {
                $errors[] = "Fila {$number}: las nóminas de jefes admiten máximo 50 caracteres.";
            }
            if ($role === 'plant_manager' && $directManagerNumber !== '') {
                $errors[] = "Fila {$number}: el gerente de planta no debe tener jefe directo.";
            }
            if (! in_array($role, ['area_manager', 'hr_manager', 'plant_manager'], true) && $directManagerNumber === '') {
                $errors[] = "Fila {$number}: la nómina del jefe directo es obligatoria.";
            }
            if ($employeeNumber === $directManagerNumber) {
                $errors[] = "Fila {$number}: el usuario no puede ser su propio jefe.";
            }
            $supervisorNumber = '';
            $managerNumber = '';
            $prepared[$employeeNumber] = compact('number', 'employeeNumber', 'name', 'lastName', 'role', 'areaName', 'group', 'supervisorNumber', 'managerNumber', 'directManagerNumber');
        }
        if (! $prepared && ! $skipped) {
            $errors[] = 'El archivo no contiene usuarios.';
        }

        $managersByArea = [];
        foreach ($existing as $user) {
            if ($user->role === 'area_manager' && $user->area_id) {
                $key = mb_strtolower(trim($areas->firstWhere('id', $user->area_id)?->name ?? ''));
                $managersByArea[$key][] = $user->employee_number;
            }
        }
        foreach ($prepared as $row) {
            if ($row['role'] === 'area_manager') {
                $managersByArea[mb_strtolower($row['areaName'])][] = $row['employeeNumber'];
            }
        }
        foreach ($managersByArea as $areaKey => $numbers) {
            if (count(array_unique($numbers)) > 1) {
                $errors[] = "El área {$areaKey} tiene más de un gerente de área.";
            }
        }
        foreach (['plant_manager' => 'gerente de planta', 'hr_manager' => 'gerente de RH'] as $role => $label) {
            $count = $existing->where('role', $role)->count() + collect($prepared)->where('role', $role)->count();
            if ($count > 1) {
                $errors[] = "Debe haber solo un {$label}; se encontraron {$count}.";
            }
        }
        foreach ($prepared as $key => $row) {
            $role = $row['role'];
            $boss = $row['directManagerNumber'];
            $expected = match ($role) {
                'worker' => ['supervisor', 'area_manager'],
                'supervisor' => ['area_manager'],
                'plant_manager' => null,
                default => ['plant_manager'],
            };
            $bossRole = $prepared[$boss]['role'] ?? $existing->get($boss)?->role;
            if ($boss !== '' && $expected !== null && ! in_array($bossRole, $expected, true)) {
                $expectedRoles = implode(' o ', array_map(fn ($role) => self::ROLE_LABELS[$role], $expected));
                $errors[] = "Fila {$row['number']}: el jefe directo {$boss} debe tener rol {$expectedRoles} y estar en la base de datos o en este archivo.";
            }
            if ($role === 'plant_manager') {
                continue;
            }
            if (in_array($role, ['worker', 'supervisor'], true)) {
                $areaKey = mb_strtolower($row['areaName']);
                $candidates = array_unique($managersByArea[$areaKey] ?? []);
                if (count($candidates) !== 1) {
                    $errors[] = "Fila {$row['number']}: el área {$row['areaName']} debe tener exactamente un gerente de área, existente o incluido en el archivo.";
                } else {
                    $row['managerNumber'] = reset($candidates);
                }
                $bossAreaId = $existing->get($boss)?->area_id;
                $bossAreaName = $bossAreaId ? $areas->firstWhere('id', $bossAreaId)?->name : ($prepared[$boss]['areaName'] ?? null);
                if ($bossAreaName !== null && mb_strtolower($bossAreaName) !== $areaKey) {
                    $errors[] = "Fila {$row['number']}: el jefe directo debe pertenecer a la misma área.";
                }
            }
            if ($role === 'worker' && $bossRole === 'supervisor') {
                $row['supervisorNumber'] = $boss;
            }
            if ($role === 'supervisor' && $row['managerNumber'] !== '' && $row['managerNumber'] !== $boss) {
                $errors[] = "Fila {$row['number']}: el gerente de área debe coincidir con el jefe directo.";
            }
            $prepared[$key] = $row;
        }
        if ($errors) {
            $count = count($errors);
            throw ValidationException::withMessages(['file' => array_merge(["La importación se canceló: {$count} problema(s). No se creó ningún usuario."], array_slice($errors, 0, 50), $count > 50 ? ['Corrige los primeros problemas y vuelve a subir el archivo para revisar el resto.'] : [])]);
        }

        try {
            return DB::transaction(function () use ($prepared, $areas, $skipped) {
                $created = [];
                $credentials = [];
                foreach ($prepared as $row) {
                    $areaName = $row['areaName'];
                    $areaKey = mb_strtolower($areaName);
                    if ($areaName !== '' && ! $areas->has($areaKey)) {
                        $areas->put($areaKey, Area::create(['name' => $areaName]));
                    }
                    $password = Str::random(16);
                    $created[$row['employeeNumber']] = User::create([
                        'employee_number' => $row['employeeNumber'],
                        'name' => $row['name'],
                        'last_name' => $row['lastName'],
                        'role' => $row['role'],
                        'area_id' => $areaName === '' ? null : $areas->get($areaKey)->id,
                        'group' => $row['group'] ?: null,
                        'password' => $password,
                        'must_change_password' => true,
                    ]);
                    $credentials[] = [$row['employeeNumber'], $row['name'], $row['lastName'], $password];
                }
                foreach ($prepared as $row) {
                    $referenceId = fn (string $number) => $number === '' ? null
                        : ($created[$number]->id ?? User::where('employee_number', $number)->value('id'));
                    $created[$row['employeeNumber']]->update([
                        'supervisor_id' => $referenceId($row['supervisorNumber']),
                        'area_manager_id' => $referenceId($row['managerNumber']),
                        'direct_manager_id' => $referenceId($row['directManagerNumber']),
                    ]);
                }

                return ['credentials' => $credentials, 'skipped' => $skipped];
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505', '19'], true)) {
                throw ValidationException::withMessages(['file' => 'La importación se canceló porque una nómina ya existe. Descarga una lista actualizada y vuelve a intentarlo.']);
            }
            throw $exception;
        }
    }
}
