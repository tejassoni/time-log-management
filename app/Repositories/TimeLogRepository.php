<?php

namespace App\Repositories;

use App\Models\TimeLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TimeLogRepository
{
    public function forDate(int $userId, Carbon $date): Collection
    {
        return TimeLog::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', $date)
            ->with('project')
            ->latest('id')
            ->get();
    }

    public function minutesLoggedOn(int $userId, Carbon $date, bool $lock = false): int
    {
        return TimeLog::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', $date)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->sum('minutes');
    }

    public function existsInRange(int $userId, Carbon $start, Carbon $end): bool
    {
        return TimeLog::query()
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start, $end])
            ->exists();
    }

    public function create(array $data): TimeLog
    {
        return TimeLog::create($data);
    }
}
