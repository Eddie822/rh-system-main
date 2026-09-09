<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Request as RequestModel;
use App\Models\RequestDay;
use Carbon\Carbon;

class RequestForm extends Component
{
    public $group;
    public $reason;
    public $employee_number;

    public $rows = [];

    protected function rules()
    {
        return [
            'group' => 'required|string|max:255',
            'reason' => 'required|string|max:255',
            'reason_other' => 'nullable|string|max:255',
            'employee_number' => $this->reason === 'No. nom a cubrir'
                ? 'required|int|max:4'
                : 'nullable|int|max:4',
            'rows' => 'required|array|min:1',
            'rows.*.day_name' => 'required|string|max:50',
            'rows.*.hours' => ['required', 'regex:/^([01]\d|2[0-3]):([0-5]\d)$/'],
        ];
    }

    protected $messages = [
        'rows.required' => 'Agrega al menos un día.',
        'rows.*.hours.regex' => 'El formato de horas debe ser HH:mm.',
        'rows.*.day_name.required' => 'Selecciona un día.',
    ];

    public function mount()
    {
        $this->rows = [
            ['day_name' => '', 'hours' => '', 'day_date' => null]
        ];
    }

    public function addRow()
    {
        $this->rows[] = ['day_name' => '', 'hours' => ''];
    }

    public function removeRow($index)
    {
        if (count($this->rows) > 1) {
            array_splice($this->rows, $index, 1);
        }
    }

    public $reason_other; // agrega esta propiedad

    public function submit()
    {
        $validated = $this->validate();

        // Si la razón es "Otros", usar el campo adicional
        $finalReason = $validated['reason'];

        if ($finalReason === 'Otros') {
            $finalReason = $this->reason_other;
        }

        if ($finalReason === 'No. nom a cubrir' && !empty($this->employee_number)) {
            $finalReason .= ' - Nómina: ' . $this->employee_number;
        }

        DB::transaction(function () use ($validated, $finalReason) {
            $requestModel = RequestModel::create([
                'employee_id' => auth()->id(),
                'area_id' => auth()->user()->area_id,
                'group' => $validated['group'],
                'reason' => $finalReason, // ✅ ahora sí guarda el texto personalizado
                'status' => 'pending_supervisor',
                'week' => now()->weekOfYear,
                'employee_signature' => null,
            ]);

            foreach ($validated['rows'] as $row) {
                $dayDate = $this->calculateDateForDay($row['day_name']);

                RequestDay::create([
                    'request_id' => $requestModel->id,
                    'day_name'   => $row['day_name'],
                    'day_date'   => $dayDate,
                    'hours'      => $this->timeToDecimal($row['hours']),
                ]);
            }
        });

        session()->flash('success', 'Solicitud enviada correctamente.');
        $this->resetForm();
    }


    private function timeToDecimal(string $hhmm): float
    {
        [$h, $m] = explode(':', $hhmm);
        return (float) $h + ((float) $m / 60);
    }

    private function calculateDateForDay(string $dayName): ?string
    {
        $weekStart = Carbon::now()->startOfWeek(); // lunes actual
        $daysMap = [
            'Lunes' => 0,
            'Martes' => 1,
            'Miércoles' => 2,
            'Jueves' => 3,
            'Viernes' => 4,
            'Sábado' => 5,
            'Domingo' => 6,
        ];

        return isset($daysMap[$dayName])
            ? $weekStart->copy()->addDays($daysMap[$dayName])->format('Y-m-d') // se guarda en DB
            : null;
    }

    public function getDateForDay($dayName): ?string
    {
        $weekStart = \Carbon\Carbon::now()->startOfWeek(); // lunes actual
        $daysMap = [
            'Lunes' => 0,
            'Martes' => 1,
            'Miércoles' => 2,
            'Jueves' => 3,
            'Viernes' => 4,
            'Sábado' => 5,
            'Domingo' => 6,
        ];

        return isset($daysMap[$dayName])
            ? $weekStart->copy()->addDays($daysMap[$dayName])->format('d-m-Y')
            : null;
    }

    public function updateDayDate($index)
    {
        if (!empty($this->rows[$index]['day_name'])) {
            $this->rows[$index]['day_date'] = $this->calculateDateForDay($this->rows[$index]['day_name']);
        }
    }

    public function updateReason()
    {
        // limpiar campos cuando cambias de opción
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
        $this->rows = [['day_name' => '', 'hours' => '']];
    }


    public function render()
    {
        return view('livewire.request-form');
    }
}
