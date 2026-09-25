<?php

namespace App\Livewire\Admin;

use App\Models\Area;
use App\Models\Authorization;
use App\Models\Request as RequestModel;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            'indicators' => $this->getIndicators(),
            'requestsByAreaChart' => $this->requestsByAreaChart(),
            'areaManagerChart' => $this->authorizationChart('area_manager', 'Gerentes de Área'),
            'hrManagerChart' => $this->authorizationChart('hr_manager', 'Gerentes de RH'),
            'plantManagerChart' => $this->authorizationChart('plant_manager', 'Gerentes de Planta'),
        ]);
    }

    protected function getIndicators(): array
    {
        return [
            'approved' => RequestModel::where('status', 'approved')->count(),
            'rejected' => RequestModel::where('status', 'rejected')->count(),
            'pending_area_manager' => RequestModel::where('status', 'pending_area_manager')->count(),
            'pending_hr_manager' => RequestModel::where('status', 'pending_hr_manager')->count(),
            'pending_plant_manager' => RequestModel::where('status', 'pending_plant_manager')->count(),
        ];
    }

    protected function requestsByAreaChart(): LarapexChart
    {
        $data = Area::withCount('requests')
            ->orderByDesc('requests_count')
            ->get();

        return (new LarapexChart)
            ->barChart()
            ->setHeight(320)
            ->setColors(['#3b82f6'])
            ->addData($data->pluck('requests_count')->toArray(), 'Solicitudes')
            ->setXAxis($data->pluck('name')->toArray());
    }

    protected function authorizationChart(string $role, string $label): LarapexChart
    {
        $approved = Authorization::where('authorization_role', $role)
            ->where('action', 'approved')
            ->count();

        $rejected = Authorization::where('authorization_role', $role)
            ->where('action', 'rejected')
            ->count();

        return (new LarapexChart)
            ->donutChart()
            ->setHeight(280)
            ->setTitle($label)
            ->setColors(['#22c55e', '#ef4444'])
            ->addData([$approved, $rejected])
            ->setLabels(['Aceptadas', 'Rechazadas']);
    }
}