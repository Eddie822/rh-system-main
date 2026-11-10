<?php

namespace App\Livewire\Requests;

use App\Models\Request;
use App\Models\RequestDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class EditRequest extends Component
{
    public Request $request;

    public $group;

    public $reason;

    public $rows = [];

    public function mount(Request $request)
    {
        Gate::authorize('update', $request);
        abort_if($request->is_group, 403);
        $this->request = $request;

        $this->group = $request->group;
        $this->reason = $request->reason;

        $this->rows = $request->days
            ->map(fn ($day) => [
                'id' => $day->id,
                'day_date' => $day->day_date->format('Y-m-d'),
                'hours' => $day->hours,
            ])
            ->toArray();
    }

    protected function rules()
    {
        return [
            'group' => 'required|string|max:255',
            'reason' => 'required|string|max:255',

            'rows' => 'required|array|min:1',
            'rows.*.day_date' => 'required|date',
            'rows.*.hours' => 'required|numeric|min:0|max:12',
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
            unset($this->rows[$index]);
            $this->rows = array_values($this->rows);
        }
    }

    public function save()
    {
        $validated = $this->validate();

        // Fechas duplicadas
        $dates = collect($validated['rows'])
            ->pluck('day_date');

        if ($dates->duplicates()->isNotEmpty()) {

            foreach ($validated['rows'] as $index => $row) {

                if (
                    $dates->duplicates()
                        ->contains($row['day_date'])
                ) {
                    $this->addError(
                        "rows.$index.day_date",
                        'Esta fecha ya fue seleccionada.'
                    );
                }
            }

            return;
        }

        // Misma semana
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

        DB::transaction(function () use ($validated, $week) {

            $this->request = Request::lockForUpdate()->findOrFail($this->request->id);
            Gate::authorize('update', $this->request);
            abort_if($this->request->is_group, 403);

            $this->request->update([
                'group' => $validated['group'],
                'reason' => $validated['reason'],
                'week' => $week,
            ]);

            $this->request->days()->delete();

            $now = now();
            $dayRows = array_map(function (array $row) use ($now) {
                $date = Carbon::parse($row['day_date']);

                return [
                    'request_id' => $this->request->id,
                    'day_name' => ucfirst($date->locale('es')->dayName),
                    'day_date' => $row['day_date'],
                    'hours' => $row['hours'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $validated['rows']);

            RequestDay::insert($dayRows);
            $this->request->syncExpiration();
        });

        session()->flash(
            'success',
            'Solicitud actualizada correctamente.'
        );

        return redirect()->route('requests.index');
    }

    public function render()
    {
        return view(
            'livewire.requests.edit-request'
        );
    }
}
