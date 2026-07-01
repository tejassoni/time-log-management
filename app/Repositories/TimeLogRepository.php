<?php

namespace App\Repositories;

use App\Models\TimeLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TimeLogRepository
{
  // Sortable columns for the paginated listing
  private const SORTABLE = [
    'project'     => 'projects.name',
    'description' => 'time_logs.description',
    'minutes'     => 'time_logs.minutes',
    'work_date'   => 'time_logs.work_date',
  ];

  /**
   * Get all time logs for a user on a specific date.
   * @param int $userId
   * @param Carbon $date
   * @return Collection
   */
  public function forDate(int $userId, Carbon $date): Collection
  {
    return TimeLog::query()
      ->where('user_id', $userId)
      ->whereDate('work_date', $date)
      ->with('project')
      ->latest('id')
      ->get();
  }

  /**
   * Paginated, searchable, sortable listing for one day.
   *
   * @param  array{search?:?string, sort?:?string, direction?:?string, per_page?:?int}  $filters
   */
  public function paginatedForDate(int $userId, Carbon $date, array $filters = []): LengthAwarePaginator
  {
    $search    = trim((string) ($filters['search'] ?? ''));
    $sort      = array_key_exists($filters['sort'] ?? null, self::SORTABLE) ? $filters['sort'] : 'work_date';
    $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
    $perPage   = (int) ($filters['per_page'] ?? 10);
    $perPage   = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

    return TimeLog::query()
      ->select('time_logs.*')
      ->where('time_logs.user_id', $userId)
      ->whereDate('time_logs.work_date', $date)
      ->with('project')
      ->leftJoin('projects', 'projects.id', '=', 'time_logs.project_id')
      ->when($search !== '', fn($q) => $q->where(fn($q) => $q
        ->where('time_logs.description', 'like', "%{$search}%")
        ->orWhere('projects.name', 'like', "%{$search}%")))
      ->orderBy(self::SORTABLE[$sort], $direction)
      ->orderBy('time_logs.id', 'desc')
      ->paginate($perPage)
      ->withQueryString()
      ->fragment('logs');
  }

  /*
    * Get the total minutes logged by a user on a specific date.
    * @param int $userId
    * @param Carbon $date
    * @param bool $lock Whether to lock the rows for update (for concurrency control)
    * @return int
    */
  public function minutesLoggedOn(int $userId, Carbon $date, bool $lock = false): int
  {
    return TimeLog::query()
      ->where('user_id', $userId)
      ->whereDate('work_date', $date)
      ->when($lock, fn($q) => $q->lockForUpdate())
      ->sum('minutes');
  }

  /**
   * Check if a user has any time logs in a given date range.
   *
   * @param int $userId
   * @param Carbon $start
   * @param Carbon $end
   * @return bool
   */
  public function existsInRange(int $userId, Carbon $start, Carbon $end): bool
  {
    return TimeLog::query()
      ->where('user_id', $userId)
      ->whereBetween('work_date', [$start, $end])
      ->exists();
  }

  /**
   * Create a new time log entry.
   *
   * @param array $data
   * @return TimeLog
   */
  public function create(array $data): TimeLog
  {
    return TimeLog::create($data);
  }
}
