<?php

namespace App\Services;

use App\Repositories\LeaveRepository;
use App\Repositories\TimeLogRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function __construct(
        private readonly LeaveRepository $leaves,
        private readonly TimeLogRepository $timeLogs,
    ) {
    }

    public function apply(int $userId, array $data): void
    {
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end   = Carbon::parse($data['end_date'])->startOfDay();

        DB::transaction(function () use ($userId, $start, $end) {
            if ($this->timeLogs->existsInRange($userId, $start, $end)) {
                throw ValidationException::withMessages([
                    'start_date' => 'You have already logged work within this date range and cannot apply leave.',
                ]);
            }

            if ($this->leaves->overlaps($userId, $start, $end)) {
                throw ValidationException::withMessages([
                    'start_date' => 'This leave overlaps an existing leave request.',
                ]);
            }

            $this->leaves->create([
                'user_id'    => $userId,
                'start_date' => $start,
                'end_date'   => $end,
            ]);
        });
    }
}
