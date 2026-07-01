<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeLogRequest;
use App\Models\Project;
use App\Repositories\TimeLogRepository;
use App\Services\TimeLogService;
use App\ValueObjects\Duration;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class TimeLogController extends Controller
{

    /**
     * Create a new controller instance.
     */
    public function __construct(private TimeLogRepository $timeLogRepository) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $date   = Carbon::parse($request->query('work_date', today()))->startOfDay();
        $userId = $request->user()->id;

        // Full-day total drives the 10h cap — independent of search/pagination.
        $totalMinutes = $this->timeLogRepository->minutesLoggedOn($userId, $date);

        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

        // No minutes logged => no rows for this date; skip the listing query, use an empty paginator.
        if ($totalMinutes > 0) {
            $userTimeLogsResult = $this->timeLogRepository->paginatedForDate($userId, $date, [
                'search'    => $request->query('search'),
                'sort'      => $request->query('sort'),
                'direction' => $request->query('direction'),
                'per_page'  => $perPage,
            ]);
        } else {
            $userTimeLogsResult = new LengthAwarePaginator([], 0, $perPage, 1, [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]);
        }
        
        return view('time-logs.index', [
            'projects'      => Project::where('is_active', true)->orderBy('name')->get(),
            'userTimeLogs'       => $userTimeLogsResult,
            'workDate'      => $date->toDateString(),
            'totalMinutes'  => $totalMinutes,
            'remaining'     => max(0, Duration::MAX_MINUTES - $totalMinutes),
            'search'        => (string) $request->query('search', ''),
            'sort'          => (string) $request->query('sort', 'work_date'),
            'direction'     => strtolower($request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            'perPage'       => $perPage,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTimeLogRequest $request, TimeLogService $service)
    {
        // The validated data is passed to the service for processing
        $service->addTask($request->user()->id, $request->validated());

        return redirect()
            ->route('time-logs.index', ['work_date' => $request->validated('work_date')])
            ->with('success', 'Time log added successfully.');
    }
}
