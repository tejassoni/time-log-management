<?php

namespace App\Repositories;

use App\Models\Leave;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeaveRepository
{
    /**
     * Get all leave records for a specific user, ordered by start date descending.
     *
     * @param int $userId
     * @return Collection
     */
    public function all(int $userId): Collection
    {
        return Leave::query()
            ->where('user_id', $userId)
            ->orderByDesc('start_date')
            ->get();
    }

    /**
     * Check if a user has any leave that covers a specific date.
     *
     * @param int $userId
     * @param Carbon $date
     * @param bool $lock Whether to lock the rows for update (for concurrency control)
     * @return bool
     */
    public function coversDate(int $userId, Carbon $date, bool $lock = false): bool
    {
        return Leave::query()
            ->where('user_id', $userId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->exists();
    }

    /**
     * Check if a user has any leave that overlaps with a specific date range.
     *
     * @param int $userId
     * @param Carbon $start
     * @param Carbon $end
     * @return bool
     */
    public function overlaps(int $userId, Carbon $start, Carbon $end): bool
    {
        return Leave::query()
            ->where('user_id', $userId)
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();
    }

    /**
     * Create a new leave record.
     *
     * @param array $data
     * @return Leave
     */
    public function create(array $data): Leave
    {
        return Leave::create($data);
    }
}
