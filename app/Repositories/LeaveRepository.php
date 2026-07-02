<?php

namespace App\Repositories;

use App\Models\Leave;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeaveRepository
{

    // Sortable columns for the paginated listings
    private const SORTABLE = [
        'start_date' => 'start_date',
        'end_date'   => 'end_date',
    ];

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
     * Paginated, date-range filterable, sortable listing for a user.
     *
     * @param array $filters
     */
    public function paginatedForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $from      = $this->parseDate($filters['from'] ?? null);
        $to        = $this->parseDate($filters['to'] ?? null);
        $sort      = array_key_exists($filters['sort'] ?? null, self::SORTABLE) ? $filters['sort'] : 'start_date';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage   = (int) ($filters['per_page'] ?? 10);
        $perPage   = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

        return Leave::query()
            ->where('user_id', $userId)
            // Overlap: any leave touching the [from, to] window.
            ->when($from, fn($q) => $q->where('end_date', '>=', $from))
            ->when($to, fn($q) => $q->where('start_date', '<=', $to))
            ->orderBy(self::SORTABLE[$sort], $direction)
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString()
            ->fragment('leaves');
    }

    /**
     * Parse a date string into a Y-m-d format or return null if invalid.
     *
     * @param string|null $value
     * @return string|null
     */
    private function parseDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception) {
            return null;
        }
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
            ->when($lock, fn($q) => $q->lockForUpdate())
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
