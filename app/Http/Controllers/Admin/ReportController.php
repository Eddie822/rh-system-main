<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ApprovedOvertimeExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\OvertimeReportRequest;
use App\Reports\ApprovedOvertimeReport;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        Gate::authorize('viewReports');

        return view('admin.reports.index');
    }

    public function export(OvertimeReportRequest $request)
    {
        $report = new ApprovedOvertimeReport($request->validated());
        [$from, $to] = $report->dates();

        return Excel::download(new ApprovedOvertimeExport($report), "horas-aprobadas-{$from}-{$to}.xlsx");
    }
}
