<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UserCredentialsExport;
use App\Exports\UserImportTemplateExport;
use App\Exports\UserRosterExport;
use App\Http\Controllers\Controller;
use App\Imports\UserRosterImporter;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class UserImportController extends Controller
{
    public function index()
    {
        Gate::authorize('importUsers');

        return view('admin.users.import', ['areas' => Area::orderBy('name')->get()]);
    }

    public function template()
    {
        Gate::authorize('importUsers');

        return Excel::download(new UserImportTemplateExport, 'plantilla-usuarios.xlsx');
    }

    public function roster()
    {
        Gate::authorize('importUsers');

        return Excel::download(new UserRosterExport, 'lista-completa-usuarios.xlsx');
    }

    public function store(Request $request, UserRosterImporter $importer)
    {
        Gate::authorize('importUsers');
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:5120']]);
        $result = $importer->import($request->file('file'));
        $imported = count($result['credentials']);
        $skipped = count($result['skipped']);
        $summary = "{$imported} usuarios importados; {$skipped} nóminas duplicadas omitidas.";
        if ($imported === 0) {
            return redirect()->route('admin.users.import.index')->with('success', $summary);
        }
        session()->flash('success', $summary.' El archivo de contraseñas se descarga una sola vez.');

        return Excel::download(new UserCredentialsExport($result['credentials']), 'credenciales-temporales-'.now()->format('Ymd-His').'.xlsx');
    }
}
