<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeLogRequest;
use App\Models\Project;
use App\Repositories\TimeLogRepository;
use App\Services\TimeLogService;
use App\ValueObjects\Duration;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TimeLogController extends Controller
{
    private TimeLogRepository $timeLogRepository;
    public function __construct(TimeLogRepository $timeLogRepository)
    {
        $this->timeLogRepository = $timeLogRepository;
    }
    
    public function index(Request $request)
    {
        $date   = Carbon::parse($request->query('work_date', today()))->startOfDay();
        $userId = $request->user()->id;

        $userTimeLogsResult = $this->timeLogRepository->paginatedForDate($userId, $date, [
            'search'    => $request->query('search'),
            'sort'      => $request->query('sort'),
            'direction' => $request->query('direction'),
            'per_page'  => $request->query('per_page'),
        ]);

        // Full-day total drives the 10h cap — independent of search/pagination.
        $totalMinutes = $this->timeLogRepository->minutesLoggedOn($userId, $date);

        return view('time-logs.index', [
            'projects'      => Project::where('is_active', true)->orderBy('name')->get(),
            'userTimeLogs'       => $userTimeLogsResult,
            'workDate'      => $date->toDateString(),
            'totalMinutes'  => $totalMinutes,
            'remaining'     => max(0, Duration::MAX_MINUTES - $totalMinutes),
            'search'        => (string) $request->query('search', ''),
            'sort'          => (string) $request->query('sort', 'work_date'),
            'direction'     => strtolower($request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            'perPage'       => (int) $request->query('per_page', 10),
        ]);
    }

    public function store(StoreTimeLogRequest $request, TimeLogService $service)
    {
        $service->addTask($request->user()->id, $request->validated());

        return redirect()
        ->route('time-logs.index', ['work_date' => $request->validated('work_date')])
        ->with('success', 'Time log added successfully.');
    }
}
