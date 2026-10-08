<?php

namespace App\Livewire\Admin\Area;

use App\Models\Area;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AreaList extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('manageAreas');

        return view('livewire.admin.area.area-list', [
            'areas' => Area::withCount(['users', 'requests'])
                ->when($this->search, fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
                ->orderBy('name')->paginate(15),
        ]);
    }
}
