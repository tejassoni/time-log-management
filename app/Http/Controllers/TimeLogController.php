<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Repositories\TimeLogRepository;
use App\ValueObjects\Duration;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TimeLogController extends Controller
{
    private $timeLogRepository;
    public function __construct(TimeLogRepository $timeLogRepository)
    {
        $this->timeLogRepository = $timeLogRepository;
    }
    
    public function index(Request $request)
    {
        $date   = Carbon::parse($request->query('work_date', today()))->startOfDay();
        $userId = $request->user()->id;

        $entries      = $this->timeLogRepository->forDate($userId, $date);
        $totalMinutes = (int) $entries->sum('minutes');

        return view('time-logs.index', [
            'projects'      => Project::where('is_active', true)->orderBy('name')->get(),
            'entries'       => $entries,
            'workDate'      => $date->toDateString(),
            'totalMinutes'  => $totalMinutes,
            'remaining'     => max(0, Duration::MAX_MINUTES - $totalMinutes),
        ]);
    }

    public function store(Request $request)
    {
        // Handle the request to create a new time log
        // You can add your logic here to validate and save the time log
        return response()->json(['message' => 'Time log created']);
    }

}
