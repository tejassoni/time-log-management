<?php

namespace Tests\Unit;

use App\Repositories\LeaveRepository;
use App\Repositories\TimeLogRepository;
use App\Services\LeaveService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for the LeaveService::apply() business logic.
 *
 * Repositories are mocked so no database is touched — only the branching
 * (time-log conflict, overlap, success) is exercised. DB::transaction is
 * stubbed to run its closure inline.
 */
class LeaveApplyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run the transaction closure immediately, no real DB connection.
        DB::shouldReceive('transaction')->andReturnUsing(fn ($callback) => $callback());
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * apply() persists the leave when there is no conflicting work or overlap.
     */
    public function test_apply_creates_leave_when_no_conflicts(): void
    {
        $userId = 1;
        $data   = ['start_date' => '2026-06-01', 'end_date' => '2026-06-05'];

        $timeLogs = Mockery::mock(TimeLogRepository::class);
        $timeLogs->shouldReceive('existsInRange')->once()->andReturnFalse();

        $leaves = Mockery::mock(LeaveRepository::class);
        $leaves->shouldReceive('overlaps')->once()->andReturnFalse();
        $leaves->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $payload) use ($userId) {
                return $payload['user_id'] === $userId
                    && $payload['start_date'] instanceof Carbon
                    && $payload['start_date']->toDateString() === '2026-06-01'
                    && $payload['end_date']->toDateString() === '2026-06-05';
            }));

        $service = new LeaveService($leaves, $timeLogs);
        $service->apply($userId, $data);

        // Reaching here without exception, with create() satisfied, is the assertion.
        $this->assertTrue(true);
    }

    /**
     * apply() throws and never creates when work is already logged in the range.
     */
    public function test_apply_throws_when_work_logged_in_range(): void
    {
        $timeLogs = Mockery::mock(TimeLogRepository::class);
        $timeLogs->shouldReceive('existsInRange')->once()->andReturnTrue();

        $leaves = Mockery::mock(LeaveRepository::class);
        $leaves->shouldNotReceive('create');

        $service = new LeaveService($leaves, $timeLogs);

        try {
            $service->apply(1, ['start_date' => '2026-06-01', 'end_date' => '2026-06-05']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('start_date', $e->errors());
        }
    }

    /**
     * apply() throws and never creates when the range overlaps an existing leave.
     */
    public function test_apply_throws_when_overlapping_existing_leave(): void
    {
        $timeLogs = Mockery::mock(TimeLogRepository::class);
        $timeLogs->shouldReceive('existsInRange')->once()->andReturnFalse();

        $leaves = Mockery::mock(LeaveRepository::class);
        $leaves->shouldReceive('overlaps')->once()->andReturnTrue();
        $leaves->shouldNotReceive('create');

        $service = new LeaveService($leaves, $timeLogs);

        try {
            $service->apply(1, ['start_date' => '2026-06-01', 'end_date' => '2026-06-05']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('start_date', $e->errors());
        }
    }
}
