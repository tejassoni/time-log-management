<?php

namespace App\Services;

use App\Repositories\LeaveRepository;
use App\Repositories\TimeLogRepository;
use App\ValueObjects\Duration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeLogService
{

  /**
   * TimeLogService constructor.
   *
   * @param TimeLogRepository $timeLogs
   * @param LeaveRepository $leaves
   */
  public function __construct(
    private TimeLogRepository $timeLogs,
    private LeaveRepository $leaves,
  ) {}

  public function addTask(int $userId, array $data): void
  {
    $date     = Carbon::parse($data['work_date'])->startOfDay();
    $duration = Duration::parse($data['time']);

    DB::transaction(function () use ($userId, $date, $duration, $data) {
      if ($this->leaves->coversDate($userId, $date, lock: true)) {
        throw ValidationException::withMessages([
          'work_date' => 'You have applied leave for this date and cannot log work.',
        ]);
      }

      $alreadyLogged = $this->timeLogs->minutesLoggedOn($userId, $date, lock: true);

      if ($alreadyLogged + $duration->minutes > Duration::MAX_MINUTES) {
        $remaining = max(0, Duration::MAX_MINUTES - $alreadyLogged);
        throw ValidationException::withMessages([
          'time' => sprintf(
            'Daily limit is 10h. Only %02d:%02d left for this date.',
            intdiv($remaining, 60),
            $remaining % 60,
          ),
        ]);
      }

      $this->timeLogs->create([
        'user_id'     => $userId,
        'project_id'  => $data['project_id'],
        'work_date'   => $date,
        'description' => $data['description'],
        'minutes'     => $duration->minutes,
      ]);
    });
  }
}
