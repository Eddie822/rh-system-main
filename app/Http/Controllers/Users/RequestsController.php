<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use Illuminate\Http\Request;

class RequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $requests = RequestModel::with('days')
            ->where('employee_id', auth()->id());

        if (request('status')) {
            $requests->where('status', request('status'));
        }

        if (request('week')) {
            $requests->where('week', request('week'));
        }

        if (request('from_date')) {
            $requests->whereDate(
                'created_at',
                '>=',
                request('from_date')
            );
        }

        if (request('to_date')) {
            $requests->whereDate(
                'created_at',
                '<=',
                request('to_date')
            );
        }

        $requests = $requests
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view(
            'users.requests.index',
            compact('requests')
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RequestModel $request)
    {
        abort_if(
                $request->employee_id !== auth()->id(),
                            403
                );
        abort_if(
    $request->status !== 'pending_area_manager',
                            403,
                        'La solicitud ya no puede modificarse.'
            );
        return view('users.requests.edit', compact('request'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RequestModel $requestModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        //
    }
}
