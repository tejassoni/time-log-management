<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Repositories\LeaveRepository;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    private LeaveRepository $leaves;
    public function __construct(LeaveRepository $leaves)
    {
        $this->leaves = $leaves;
    }

    public function index(Request $request)
    {
        return view('leaves.index', [
            'leaves' => $this->leaves->all($request->user()->id),
        ]);
    }

    public function store(StoreLeaveRequest $request, LeaveService $service)
    {
        $service->apply($request->user()->id, $request->validated());

        return redirect()
            ->route('leaves.index')
            ->with('status', 'Leave applied.');
    }
}
