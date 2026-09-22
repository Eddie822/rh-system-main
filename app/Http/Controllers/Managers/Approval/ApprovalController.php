<?php

namespace App\Http\Controllers\Managers\Approval;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('approval.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort(404);
    }

    /**
     * Display the specified resource.
     */
    public function show(RequestModel $approval)
    {
        $approval->load([
            'employee',
            'area',
            'days',
            'authorizations.user',
        ]);

        return view(
            'approval.show',
            [
                'request' => $approval,
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RequestModel $approval)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RequestModel $approval)
    {
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RequestModel $approval)
    {
        abort(404);
    }
}