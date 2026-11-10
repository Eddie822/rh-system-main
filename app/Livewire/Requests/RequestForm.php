<?php

namespace App\Livewire\Requests;

use App\Models\Request as RequestModel;
use App\Models\RequestDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class RequestForm extends Component
{
    public $group;
    public $reason;
    public $reason_other;
    public $employee_number;

    public $rows = [];

    protected function rules()
    {
        return [
            'group' => 'required|string|max:255',
            'reason' => 'required|string|max:255',
            'reason_other' => $this->reason === 'Otros'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',

            'employee_number' => $this->reason === 'No. nom a cubrir'
                ? 'required|string|max:50'
                : 'nullable|string|max:50',

            'rows' => 'required|array|min:1',
            'rows.*.day_date' => 'required|date',
            'rows.*.hours' => 'required|numeric|gt:0|max:12|decimal:0,2',
        ];
    }

    protected $messages = [
        'rows.required' => 'Agrega al menos un día.',

        'rows.*.day_date.required' => 'Selecciona una fecha.',
        'rows.*.day_date.date' => 'La fecha seleccionada no es válida.',
        'rows.*.day_date.after' => 'La fecha debe ser posterior a hoy.',

        'rows.*.hours.required' => 'Captura las horas.',
        'rows.*.hours.numeric' => 'Las horas deben ser numéricas.',
        'rows.*.hours.gt' => 'Las horas deben ser mayores a 0.',
        'rows.*.hours.max' => 'No puedes capturar más de 12 horas.',

        'reason_other.required' => 'Especifica el motivo.',
        'employee_number.required' => 'Captura el número de nómina a cubrir.',
        'employee_number.max' => 'La nómina admite máximo 50 caracteres.',
    ];

    public function mount()
    {
        Gate::authorize('create', RequestModel::class);

        $this->rows = [
            [
                'day_date' => null,
                'hours' => '',
            ]
        ];
    }

    public function addRow()
    {
        $this->rows[] = [
            'day_date' => null,
            'hours' => '',
        ];
    }

    public function removeRow($index)
    {
        if (count($this->rows) > 1) {
            array_splice($this->rows, $index, 1);
        }
    }

    public function submit()
    {
        Gate::authorize('create', RequestModel::class);

        $validated = $this->validate();

        // Validar fechas repetidas
        $dates = collect($validated['rows'])->pluck('day_date');

        if ($dates->duplicates()->isNotEmpty()) {

            foreach ($validated['rows'] as $index => $row) {

                if ($dates->duplicates()->contains($row['day_date'])) {

                    $this->addError(
                        "rows.$index.day_date",
                        'Esta fecha ya fue seleccionada.'
                    );
                }
            }

            return;
        }

        // Validar misma semana y año ISO
        $weeks = collect($validated['rows'])
            ->pluck('day_date')
            ->map(fn ($date) => Carbon::parse($date)->format('o-W'))
            ->unique();

        if ($weeks->count() > 1) {
            $this->addError(
                'rows',
                'Todas las fechas deben pertenecer a la misma semana.'
            );

            return;
        }

        $week = Carbon::parse($validated['rows'][0]['day_date'])->isoWeek();

        $finalReason = $validated['reason'];

        if ($finalReason === 'Otros') {
            $finalReason = trim((string) $this->reason_other);
        }

        if (
            $validated['reason'] === 'No. nom a cubrir'
            && !empty($this->employee_number)
        ) {
            $finalReason .= ' - Nómina: ' . trim((string) $this->employee_number);
        }

        DB::transaction(function () use (
            $validated,
            $finalReason,
            $week
        ) {

            $requestModel = RequestModel::create([
                'employee_id' => auth()->id(),
                'area_id' => auth()->user()->area_id,
                'group' => $validated['group'],
                'reason' => $finalReason,
                'status' => 'pending_area_manager',
                'week' => $week,
            ]);

            $now = now();
            $dayRows = array_map(function (array $row) use ($requestModel, $now) {
                $date = Carbon::parse($row['day_date']);

                return [
                    'request_id' => $requestModel->id,
                    'day_name' => ucfirst($date->locale('es')->dayName),
                    'day_date' => $row['day_date'],
                    'hours' => $row['hours'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $validated['rows']);

            RequestDay::insert($dayRows);
            $requestModel->syncExpiration();
        });

        session()->flash(
            'success',
            'Solicitud enviada correctamente.'
        );

        $this->resetForm();
    }

    public function updateReason()
    {
        if ($this->reason !== 'Otros') {
            $this->reason_other = '';
        }

        if ($this->reason !== 'No. nom a cubrir') {
            $this->employee_number = '';
        }
    }

    private function resetForm()
    {
        $this->group = '';
        $this->reason = '';
        $this->reason_other = '';
        $this->employee_number = '';

        $this->rows = [
            [
                'day_date' => null,
                'hours' => '',
            ]
        ];

        $this->resetErrorBag();
    }

    public function render()
    {
        Gate::authorize('create', RequestModel::class);

        return view('livewire/requests.request-form');
    }
}