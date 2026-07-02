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
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

        return view('leaves.index', [
            'leaves'    => $this->leaves->paginatedForUser($request->user()->id, [
                'from'      => $request->query('from'),
                'to'        => $request->query('to'),
                'sort'      => $request->query('sort'),
                'direction' => $request->query('direction'),
                'per_page'  => $perPage,
            ]),
            'from'      => (string) $request->query('from', ''),
            'to'        => (string) $request->query('to', ''),
            'sort'      => (string) $request->query('sort', 'start_date'),
            'direction' => strtolower($request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            'perPage'   => $perPage,
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
