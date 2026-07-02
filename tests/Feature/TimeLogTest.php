<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\Project;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a verified, authenticated user and act as them.
     */
    private function actingAsUser(): User
    {
        $user = User::factory()->create(); // UserFactory sets email_verified_at

        $this->actingAs($user);

        return $user;
    }

    /**
     * Create an active project the store request will accept.
     */
    private function activeProject(): Project
    {
        return Project::factory()->create(['is_active' => true]);
    }

    // =========================================================================
    // Happy Path Scenarios
    // =========================================================================

    /**
     * The /time-logs index route is accessible to a verified user and returns 200 OK.
     */
    public function test_time_logs_index_route_returns_ok_for_authenticated_user(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('time-logs.index'));

        $response->assertStatus(200);
    }

    /**
     * A time log can be created with all valid data.
     */
    public function test_time_log_can_be_created_with_valid_data(): void
    {
        $user    = $this->actingAsUser();
        $project = $this->activeProject();
        $date    = today()->toDateString();

        $data = [
            'work_date'   => $date,
            'project_id'  => $project->id,
            'description' => 'Implemented the reporting module',
            'time'        => '2:30',
        ];

        $response = $this->post(route('time-logs.store'), $data);

        $response->assertStatus(302);
        $response->assertRedirect(route('time-logs.index', ['work_date' => $date]));
        $response->assertSessionHas('success', 'Time log added successfully.');

        // 2:30 => 150 minutes
        $this->assertDatabaseHas('time_logs', [
            'user_id'     => $user->id,
            'project_id'  => $project->id,
            'description' => 'Implemented the reporting module',
            'minutes'     => 150,
        ]);
    }

    /**
     * A bare whole number in the time field is stored as hours (2 => 120 minutes).
     */
    public function test_time_log_bare_number_is_stored_as_hours(): void
    {
        $this->actingAsUser();
        $project = $this->activeProject();

        $this->post(route('time-logs.store'), [
            'work_date'   => today()->toDateString(),
            'project_id'  => $project->id,
            'description' => 'Bare hours entry',
            'time'        => '2',
        ]);

        $this->assertDatabaseHas('time_logs', [
            'description' => 'Bare hours entry',
            'minutes'     => 120,
        ]);
    }

    // =========================================================================
    // Validation & Error Scenarios
    // =========================================================================

    /**
     * Guests are redirected to login and cannot reach the listing.
     */
    public function test_guest_cannot_access_time_logs_index(): void
    {
        $response = $this->get(route('time-logs.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Guests cannot create a time log.
     */
    public function test_guest_cannot_create_time_log(): void
    {
        $response = $this->post(route('time-logs.store'), [
            'work_date'   => today()->toDateString(),
            'project_id'  => 1,
            'description' => 'Should not be stored',
            'time'        => '1:00',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('time_logs', ['description' => 'Should not be stored']);
    }

    /**
     * Creation fails when required fields are missing.
     */
    public function test_time_log_creation_fails_with_missing_required_fields(): void
    {
        $this->actingAsUser();

        $response = $this->post(route('time-logs.store'), []);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['work_date', 'project_id', 'description', 'time']);
        $this->assertDatabaseCount('time_logs', 0);
    }

    /**
     * Creation fails when the time format is invalid.
     */
    public function test_time_log_creation_fails_with_invalid_time_format(): void
    {
        $this->actingAsUser();
        $project = $this->activeProject();

        $response = $this->post(route('time-logs.store'), [
            'work_date'   => today()->toDateString(),
            'project_id'  => $project->id,
            'description' => 'Bad time',
            'time'        => 'abc',
        ]);

        $response->assertSessionHasErrors(['time']);
        $this->assertDatabaseMissing('time_logs', ['description' => 'Bad time']);
    }

    /**
     * Creation fails for a future work date (before_or_equal:today).
     */
    public function test_time_log_creation_fails_for_future_work_date(): void
    {
        $this->actingAsUser();
        $project = $this->activeProject();

        $response = $this->post(route('time-logs.store'), [
            'work_date'   => today()->addDay()->toDateString(),
            'project_id'  => $project->id,
            'description' => 'Future entry',
            'time'        => '1:00',
        ]);

        $response->assertSessionHasErrors(['work_date']);
        $this->assertDatabaseMissing('time_logs', ['description' => 'Future entry']);
    }

    /**
     * Creation fails when the project is inactive.
     */
    public function test_time_log_creation_fails_for_inactive_project(): void
    {
        $this->actingAsUser();
        $inactive = Project::factory()->create(['is_active' => false]);

        $response = $this->post(route('time-logs.store'), [
            'work_date'   => today()->toDateString(),
            'project_id'  => $inactive->id,
            'description' => 'Inactive project entry',
            'time'        => '1:00',
        ]);

        $response->assertSessionHasErrors(['project_id']);
        $this->assertDatabaseMissing('time_logs', ['description' => 'Inactive project entry']);
    }

    /**
     * Creation fails for a non-existent project.
     */
    public function test_time_log_creation_fails_for_non_existent_project(): void
    {
        $this->actingAsUser();

        $response = $this->post(route('time-logs.store'), [
            'work_date'   => today()->toDateString(),
            'project_id'  => 999999,
            'description' => 'Ghost project entry',
            'time'        => '1:00',
        ]);

        $response->assertSessionHasErrors(['project_id']);
        $this->assertDatabaseMissing('time_logs', ['description' => 'Ghost project entry']);
    }

    /**
     * Creation fails when the entry would exceed the 10-hour daily cap.
     */
    public function test_time_log_creation_fails_when_daily_cap_exceeded(): void
    {
        $user    = $this->actingAsUser();
        $project = $this->activeProject();
        $date    = today()->toDateString();

        // Pre-fill the day to the 600-minute (10h) cap.
        TimeLog::create([
            'user_id'     => $user->id,
            'project_id'  => $project->id,
            'work_date'   => $date,
            'description' => 'Existing full day',
            'minutes'     => 600,
        ]);

        $response = $this->post(route('time-logs.store'), [
            'work_date'   => $date,
            'project_id'  => $project->id,
            'description' => 'Over the cap',
            'time'        => '0:30',
        ]);

        $response->assertSessionHasErrors(['time']);
        $this->assertDatabaseMissing('time_logs', ['description' => 'Over the cap']);
        $this->assertDatabaseCount('time_logs', 1); // only the pre-existing entry
    }

    /**
     * Creation is blocked on a date the user has leave for.
     */
    public function test_time_log_creation_fails_on_a_leave_date(): void
    {
        $user    = $this->actingAsUser();
        $project = $this->activeProject();
        $date    = today()->toDateString();

        Leave::create([
            'user_id'    => $user->id,
            'start_date' => $date,
            'end_date'   => $date,
        ]);

        $response = $this->post(route('time-logs.store'), [
            'work_date'   => $date,
            'project_id'  => $project->id,
            'description' => 'Working on leave day',
            'time'        => '1:00',
        ]);

        $response->assertSessionHasErrors(['work_date']);
        $this->assertDatabaseMissing('time_logs', ['description' => 'Working on leave day']);
    }
}
