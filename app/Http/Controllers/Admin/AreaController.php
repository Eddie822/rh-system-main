<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AreaController extends Controller
{
    public function index()
    {
        Gate::authorize('manageAreas');

        return view('admin.areas.index');
    }

    public function create()
    {
        Gate::authorize('manageAreas');

        return view('admin.areas.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('manageAreas');
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $validated = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('areas', 'name')]]);
        Area::create(['name' => trim($validated['name'])]);

        return redirect()->route('admin.areas.index')->with('success', 'Área creada correctamente.');
    }

    public function edit(Area $area)
    {
        Gate::authorize('manageAreas');

        return view('admin.areas.edit', compact('area'));
    }

    public function update(Request $request, Area $area)
    {
        Gate::authorize('manageAreas');
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $validated = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('areas', 'name')->ignore($area->id)]]);
        $area->update(['name' => trim($validated['name'])]);

        return redirect()->route('admin.areas.index')->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(Area $area)
    {
        Gate::authorize('manageAreas');
        if ($area->users()->exists() || $area->requests()->exists()) {
            return back()->with('error', 'No se puede eliminar un área con usuarios o solicitudes asignadas.');
        }
        $area->delete();

        return redirect()->route('admin.areas.index')->with('deleted', 'Área eliminada correctamente.');
    }
}
