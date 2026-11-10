<?php

namespace App\Livewire\Requests;

use App\Models\Request;
use App\Models\RequestDay;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GroupRequestForm extends Component
{
    #[Locked]
    public ?Request $request = null;

    public string $group = '';

    public string $reason = '';

    public array $employees = [];

    public array $employeeSearch = [];

    public function mount(?Request $request = null): void
    {
        Gate::authorize('createGroup', Request::class);
        if ($request?->exists) {
            Gate::authorize('update', $request);
            abort_unless($request->is_group, 404);
            $this->request = $request;
            $this->group = $request->group;
            $this->reason = $request->reason;
            $this->employees = $request->participants->map(fn (User $employee) => [
                'employee_number' => $employee->employee_number,
                'days' => $request->days->where('employee_id', $employee->id)->map(fn ($day) => [
                    'day_date' => $day->day_date->format('Y-m-d'),
                    'hours' => $day->hours,
                ])->values()->all(),
            ])->all();
        } else {
            $this->addEmployee();
        }
    }

    public function addEmployee(): void
    {
        $this->employees[] = ['employee_number' => '', 'days' => [['day_date' => '', 'hours' => '']]];
    }

    public function removeEmployee(int $index): void
    {
        if (count($this->employees) > 1) {
            unset($this->employees[$index]);
            $this->employees = array_values($this->employees);
            $this->employeeSearch = []; // Clear searches when row indices change.
            $this->resetValidation();
        }
    }

    public function updatedEmployeeSearch($value, $index): void
    {
        if (isset($this->employees[$index])) {
            $this->employees[$index]['employee_number'] = '';
        }
    }

    public function selectEmployee(int $index, string $number): void
    {
        Gate::authorize('createGroup', Request::class);
        if (! isset($this->employees[$index])) {
            return;
        }
        $employee = User::where('role', 'worker')->where('employee_number', $number)->firstOrFail();
        $this->employees[$index]['employee_number'] = $employee->employee_number;
        $this->employeeSearch[$index] = '';
        $this->resetValidation("employees.$index.employee_number");
    }

    public function addDay(int $index): void
    {
        if (isset($this->employees[$index])) {
            $this->employees[$index]['days'][] = ['day_date' => '', 'hours' => ''];
        }
    }

    public function removeDay(int $employee, int $day): void
    {
        if (isset($this->employees[$employee]) && count($this->employees[$employee]['days']) > 1) {
            unset($this->employees[$employee]['days'][$day]);
            $this->employees[$employee]['days'] = array_values($this->employees[$employee]['days']);
            $this->resetValidation();
        }
    }

    public function save()
    {
        Gate::authorize('createGroup', Request::class);
        $user = auth()->user();
        $validated = $this->validate([
            'group' => 'required|string|max:255',
            'reason' => 'required|string|max:255',
            'employees' => 'required|array|min:1|max:100',
            'employees.*.employee_number' => [
                'required', 'string', 'distinct',
                Rule::exists('users', 'employee_number')->where('role', 'worker'),
            ],
            'employees.*.days' => 'required|array|min:1|max:7',
            'employees.*.days.*.day_date' => 'required|date_format:Y-m-d',
            'employees.*.days.*.hours' => 'required|numeric|gt:0|max:12|decimal:0,2',
        ], [
            'employees.*.employee_number.exists' => 'Selecciona un empleado registrado con rol de trabajador.',
            'employees.*.employee_number.distinct' => 'El empleado ya está incluido en esta solicitud.',
        ]);

        $weeks = collect();
        foreach ($validated['employees'] as $index => $employee) {
            $dates = collect($employee['days'])->pluck('day_date');
            if ($dates->duplicates()->isNotEmpty()) {
                $this->addError("employees.$index.days", 'No repitas fechas para el mismo empleado.');
            }
            foreach ($dates as $date) {
                $weeks->push(Carbon::parse($date)->format('o-W'));
            }
        }
        if ($weeks->unique()->count() !== 1) {
            $this->addError('employees', 'Todas las fechas deben pertenecer a la misma semana y año.');
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () use ($validated, $user) {
            $request = $this->request
                ? Request::lockForUpdate()->findOrFail($this->request->id)
                : new Request;
            if ($request->exists) {
                Gate::authorize('update', $request);
                abort_unless($request->is_group, 403);
            }

            $employees = User::where('role', 'worker')
                ->whereIn('employee_number', array_column($validated['employees'], 'employee_number'))
                ->lockForUpdate()->get()->keyBy('employee_number');
            abort_unless($employees->count() === count($validated['employees']), 403);

            $request->fill([
                'employee_id' => $user->id,
                'is_group' => true,
                'area_id' => $user->area_id,
                'group' => $validated['group'],
                'reason' => $validated['reason'],
                'week' => Carbon::parse($validated['employees'][0]['days'][0]['day_date'])->isoWeek(),
                'status' => 'pending_area_manager',
            ])->save();
            $request->participants()->sync($employees->pluck('id')->all());
            $request->days()->delete();
            $now = now();
            $dayRows = [];
            foreach ($validated['employees'] as $entry) {
                $employeeId = $employees[$entry['employee_number']]->id;
                foreach ($entry['days'] as $day) {
                    $dayRows[] = [
                        'request_id' => $request->id,
                        'employee_id' => $employeeId,
                        'day_date' => $day['day_date'],
                        'day_name' => ucfirst(Carbon::parse($day['day_date'])->locale('es')->dayName),
                        'hours' => $day['hours'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            RequestDay::insert($dayRows);
            $request->syncExpiration();
        });

        session()->flash('success', 'Solicitud grupal guardada correctamente.');

        return redirect()->route('requests.index');
    }

    public function render()
    {
        Gate::authorize('createGroup', Request::class);

        $results = [];
        foreach ($this->employees as $index => $entry) {
            $term = trim(mb_substr((string) ($this->employeeSearch[$index] ?? ''), 0, 100));
            $numericTerm = ctype_digit($term) ? (int) (ltrim($term, '0') ?: '0') : -1;
            $results[$index] = $term === '' ? collect() : User::where('role', 'worker')
                ->where(function ($query) use ($term) {
                    foreach (preg_split('/\s+/u', $term) as $word) {
                        $query->where(fn ($part) => $part->where('employee_number', 'like', "%{$word}%")
                            ->orWhere('name', 'like', "%{$word}%")
                            ->orWhere('apellidos_pat', 'like', "%{$word}%")
                            ->orWhere('apellido_mat', 'like', "%{$word}%"));
                    }
                })
                ->orderByRaw(
                    'CASE WHEN employee_number = ? THEN 0 WHEN CAST(employee_number AS UNSIGNED) = ? THEN 1 WHEN employee_number LIKE ? THEN 2 WHEN employee_number LIKE ? THEN 3 ELSE 4 END ASC, CAST(employee_number AS UNSIGNED) ASC, LENGTH(employee_number) ASC, employee_number ASC',
                    [$term, $numericTerm, $term.'%', '%'.$term.'%']
                )
                ->limit(10)
                ->get();
        }

        $selectedNumbers = array_values(array_filter(array_column($this->employees, 'employee_number')));
        $selectedEmployees = $selectedNumbers === []
            ? collect()
            : User::where('role', 'worker')
                ->whereIn('employee_number', $selectedNumbers)
                ->get()
                ->keyBy('employee_number');

        return view('livewire.requests.group-request-form', [
            'searchResults' => $results,
            'selectedEmployees' => $selectedEmployees,
            'hasEmployees' => $selectedEmployees->isNotEmpty() || User::where('role', 'worker')->exists(),
        ]);
    }
}
