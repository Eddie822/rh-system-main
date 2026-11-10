<?php

namespace App\Livewire\Requests;

use App\Models\Request as RequestModel;
use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;

class RequestLookup extends Component
{
    #[Url]
    public string $search = '';

    public function searchRequest(): void
    {
        $this->search = trim($this->search);
    }

    public function clearSearch(): void
    {
        $this->reset('search');
    }

    public function render()
    {
        $raw = trim($this->search);
        $cleanId = ltrim($raw, "# \t\n\r\0\x0B");
        $hasSearched = $raw !== '';
        $invalidFormat = $hasSearched && (! ctype_digit($cleanId) || (int) $cleanId <= 0);

        $foundRequest = null;
        $areaApprover = null;
        $missingApprovals = [];
        $stageDetails = [];

        if ($hasSearched && ! $invalidFormat) {
            $foundRequest = RequestModel::with([
                'employee',
                'area',
                'assignedAreaManager',
                'authorizations.user',
                'days.employee',
                'participants',
            ])->find((int) $cleanId);

            if ($foundRequest) {
                $approverId = $foundRequest->areaApproverId();
                $areaApprover = ($approverId && $foundRequest->assigned_area_manager_id === $approverId)
                    ? $foundRequest->assignedAreaManager
                    : ($approverId ? User::find($approverId) : null);

                $missingApprovals = $foundRequest->missingApprovalRoles();

                foreach ([
                    'area_manager' => 'Gerente de Área',
                    'hr_manager' => 'Gerente de RH',
                    'plant_manager' => 'Gerente de Planta',
                ] as $role => $label) {
                    $authRecord = $foundRequest->authorizations->firstWhere('authorization_role', $role);
                    $stageDetails[$role] = [
                        'label' => $foundRequest->roleLabelWithManager($role, $label),
                        'manager_name' => $foundRequest->managerNameForRole($role) ?? 'Sin asignar',
                        'authorization' => $authRecord,
                        'status' => $authRecord
                            ? $authRecord->action
                            : ($foundRequest->status === 'rejected' ? 'cancelled' : 'pending'),
                    ];
                }
            }
        }

        return view('livewire.requests.request-lookup', [
            'hasSearched' => $hasSearched,
            'invalidFormat' => $invalidFormat,
            'foundRequest' => $foundRequest,
            'areaApprover' => $areaApprover,
            'missingApprovals' => $missingApprovals,
            'stageDetails' => $stageDetails,
        ]);
    }
}
