<?php

namespace App\Livewire;

use App\Models\Request as RequestModel;
use App\Models\RequestDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
            'reason_other' => 'nullable|string|max:255',

            'employee_number' => $this->reason === 'No. nom a cubrir'
                ? 'required|digits:4'
                : 'nullable|digits:4',

            'rows' => 'required|array|min:1',
            'rows.*.day_date' => 'required|date|after:today',
            'rows.*.hours' => 'required|numeric|min:0|max:12',
        ];
    }

    protected $messages = [
        'rows.required' => 'Agrega al menos un día.',

        'rows.*.day_date.required' => 'Selecciona una fecha.',
        'rows.*.day_date.date' => 'La fecha seleccionada no es válida.',
        'rows.*.day_date.after' => 'La fecha debe ser posterior a hoy.',

        'rows.*.hours.required' => 'Captura las horas.',
        'rows.*.hours.numeric' => 'Las horas deben ser numéricas.',
        'rows.*.hours.min' => 'Las horas deben ser mayores o iguales a 0.',
        'rows.*.hours.max' => 'No puedes capturar más de 12 horas.',

        'employee_number.digits' => 'La nómina debe tener exactamente 4 dígitos.',
    ];

    public function mount()
    {
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

        // Validar misma semana
        $weeks = collect($validated['rows'])
            ->pluck('day_date')
            ->map(fn($date) => Carbon::parse($date)->weekOfYear)
            ->unique();

        if ($weeks->count() > 1) {
            $this->addError(
                'rows',
                'Todas las fechas deben pertenecer a la misma semana.'
            );

            return;
        }

        $week = $weeks->first();

        $finalReason = $validated['reason'];

        if ($finalReason === 'Otros') {
            $finalReason = $this->reason_other;
        }

        if (
            $validated['reason'] === 'No. nom a cubrir'
            && !empty($this->employee_number)
        ) {
            $finalReason .= ' - Nómina: ' . $this->employee_number;
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
                'status' => 'pending_supervisor',
                'week' => $week,
                'employee_signature' => null,
            ]);

            foreach ($validated['rows'] as $row) {

                $date = Carbon::parse($row['day_date']);

                RequestDay::create([
                    'request_id' => $requestModel->id,
                    'day_name' => ucfirst($date->locale('es')->dayName),
                    'day_date' => $row['day_date'],
                    'hours' => $row['hours'],
                ]);
            }
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
        return view('livewire.request-form');
    }
}