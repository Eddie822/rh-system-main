<?php

namespace App\Imports;

use App\Models\Area;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserRosterImporter
{
    public const HEADERS = ['Area', 'num_emp', 'apell_pat', 'apell_mat', 'nombre', 'Puesto'];

    public const ROLE_LABELS = [
        'worker' => 'Empleado',
        'supervisor' => 'Supervisor',
        'area_manager' => 'Gerente de área',
        'hr_manager' => 'Gerente de RH',
        'plant_manager' => 'Gerente de planta',
    ];

    public function import(UploadedFile $file): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            if (method_exists($reader, 'setReadEmptyCells')) {
                $reader->setReadEmptyCells(false);
            }
            $book = $reader->load($file->getRealPath());
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception|Exception $exception) {
            throw ValidationException::withMessages(['file' => 'No se pudo leer el Excel. Descarga la plantilla y vuelve a guardar el archivo como .xlsx.']);
        }
        try {
            $sheet = $book->getSheet(0);
            $headers = array_map(fn ($value) => trim((string) $value), $sheet->rangeToArray('A1:F1', null, false, false)[0]);
            if ($headers !== self::HEADERS || $sheet->getHighestDataColumn() !== 'F') {
                throw ValidationException::withMessages(['file' => 'Usa exactamente estas seis columnas, en este orden: Area, num_emp, apell_pat, apell_mat, nombre, Puesto.']);
            }
            $lastRow = $sheet->getHighestDataRow();
            if ($lastRow < 2 || $lastRow > 5001) {
                throw ValidationException::withMessages(['file' => 'El archivo debe contener entre 1 y 5000 usuarios.']);
            }
            $rows = $sheet->rangeToArray("A2:F{$lastRow}", null, false, false);
        } finally {
            $book->disconnectWorksheets();
        }

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
            [$area, $employeeNumber, $paternal, $maternal, $name, $position] = array_map(
                fn ($value) => trim((string) ($value ?? '')), $cells
            );
            $payroll = $cells[1];
            if ((is_int($payroll) || is_float($payroll))
                && (! is_finite((float) $payroll) || $payroll < 0 || floor((float) $payroll) !== (float) $payroll || $payroll > 999999999999999)) {
                $errors[] = "Fila {$number}: la nómina numérica debe ser un entero de hasta 15 dígitos. Usa texto para ceros iniciales o números más largos.";

                continue;
            }
            if ($employeeNumber === '' || mb_strlen($employeeNumber) > 50) {
                $errors[] = "Fila {$number}: num_emp es obligatorio y admite máximo 50 caracteres.";
            } elseif (isset($seen[mb_strtolower($employeeNumber)])) {
                $skipped[] = ['row' => $number, 'employee_number' => $employeeNumber];

                continue;
            }
            $seen[mb_strtolower($employeeNumber)] = true;
            foreach (['Area' => $area, 'apell_pat' => $paternal, 'nombre' => $name, 'Puesto' => $position] as $field => $value) {
                if ($value === '' || mb_strlen($value) > 255) {
                    $errors[] = "Fila {$number}: {$field} es obligatorio y admite máximo 255 caracteres.";
                }
            }
            if (mb_strlen($maternal) > 255) {
                $errors[] = "Fila {$number}: apell_mat admite máximo 255 caracteres y puede quedar vacío.";
            }
            $prepared[] = compact('area', 'employeeNumber', 'paternal', 'maternal', 'name', 'position');
        }
        if (! $prepared && ! $skipped) {
            $errors[] = 'El archivo no contiene usuarios.';
        }
        if ($errors) {
            throw ValidationException::withMessages(['file' => array_merge(
                ['La importación se canceló. No se creó ni actualizó ningún usuario.'], array_slice($errors, 0, 50)
            )]);
        }

        try {
            return DB::transaction(function () use ($prepared, $skipped) {
                $areas = Area::all()->keyBy(fn (Area $area) => mb_strtolower(trim($area->name)));
                $existingUsers = [];
                $existingByTrimmedZero = [];
                $existingRows = DB::table('users')
                    ->select(['id', 'employee_number', 'area_id', 'puesto'])
                    ->lockForUpdate()
                    ->get();
                foreach ($existingRows as $existingUser) {
                    $key = mb_strtolower(trim((string) $existingUser->employee_number));
                    $existingUsers[$key] = $existingUser;
                    if (ctype_digit($key)) {
                        $normalizedDigits = ltrim($key, '0') ?: '0';
                        $existingByTrimmedZero[$normalizedDigits][] = $existingUser;
                    }
                }

                $now = now();
                $credentials = [];
                $inserts = [];
                $updates = [];
                $updated = 0;
                $unchanged = 0;
                foreach ($prepared as $row) {
                    $areaKey = mb_strtolower($row['area']);
                    if (! $areas->has($areaKey)) {
                        $areas->put($areaKey, Area::create(['name' => $row['area']]));
                    }
                    $targetArea = $areas->get($areaKey);
                    $lookupKey = mb_strtolower($row['employeeNumber']);
                    $user = $existingUsers[$lookupKey] ?? null;
                    if (! $user && ctype_digit($lookupKey)) {
                        $normalizedDigits = ltrim($lookupKey, '0') ?: '0';
                        if (isset($existingByTrimmedZero[$normalizedDigits]) && count($existingByTrimmedZero[$normalizedDigits]) === 1) {
                            $user = $existingByTrimmedZero[$normalizedDigits][0];
                        }
                    }

                    if ($user) {
                        $areaChanged = (int) $user->area_id !== (int) $targetArea->id;
                        $positionChanged = trim((string) ($user->puesto ?? '')) !== $row['position'];
                        if ($areaChanged || $positionChanged) {
                            $user->area_id = $targetArea->id;
                            $user->puesto = $row['position'];
                            $updates[$user->id] = [
                                'id' => (int) $user->id,
                                'area_id' => (int) $targetArea->id,
                                'puesto' => $row['position'],
                            ];
                            $updated++;
                        } else {
                            $unchanged++;
                        }

                        continue;
                    }
                    $password = Str::random(16);
                    $inserts[] = [
                        'employee_number' => $row['employeeNumber'],
                        'name' => $row['name'],
                        'apellidos_pat' => $row['paternal'],
                        'apellido_mat' => $row['maternal'] ?: null,
                        'puesto' => $row['position'],
                        'role' => 'worker',
                        'area_id' => $targetArea->id,
                        'password' => Hash::make($password),
                        'must_change_password' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $placeholder = (object) [
                        'id' => null,
                        'employee_number' => $row['employeeNumber'],
                        'area_id' => $targetArea->id,
                        'puesto' => $row['position'],
                    ];
                    $existingUsers[$lookupKey] = $placeholder;
                    if (ctype_digit($lookupKey)) {
                        $normalizedDigits = ltrim($lookupKey, '0') ?: '0';
                        $existingByTrimmedZero[$normalizedDigits][] = $placeholder;
                    }
                    $credentials[] = [$row['employeeNumber'], $row['name'], $row['paternal'], $row['maternal'], $password];
                }

                foreach (array_chunk(array_values($updates), 250) as $chunk) {
                    $ids = [];
                    $areaCases = [];
                    $puestoCases = [];
                    $bindings = [];

                    foreach ($chunk as $item) {
                        $areaCases[] = 'WHEN ? THEN ?';
                        $bindings[] = $item['id'];
                        $bindings[] = $item['area_id'];
                    }
                    foreach ($chunk as $item) {
                        $puestoCases[] = 'WHEN ? THEN ?';
                        $bindings[] = $item['id'];
                        $bindings[] = $item['puesto'];
                    }
                    $bindings[] = $now;
                    foreach ($chunk as $item) {
                        $ids[] = '?';
                        $bindings[] = $item['id'];
                    }

                    $areaSql = implode(' ', $areaCases);
                    $puestoSql = implode(' ', $puestoCases);
                    $idSql = implode(', ', $ids);
                    DB::update(
                        "UPDATE users SET area_id = CASE id {$areaSql} END, puesto = CASE id {$puestoSql} END, updated_at = ? WHERE id IN ({$idSql})",
                        $bindings
                    );
                }

                foreach (array_chunk($inserts, 250) as $chunk) {
                    DB::table('users')->insert($chunk);
                }

                return compact('credentials', 'skipped', 'updated', 'unchanged');
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505', '19'], true)) {
                throw ValidationException::withMessages(['file' => 'La importación se canceló porque una nómina ya existe. Descarga una lista actualizada y vuelve a intentarlo.']);
            }
            throw $exception;
        }
    }
}
