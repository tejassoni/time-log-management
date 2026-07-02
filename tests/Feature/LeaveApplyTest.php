<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\Project;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveApplyTest extends TestCase
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
     * A date string within the current year at the given month/day.
     */
    private function thisYear(string $monthDay): string
    {
        return now()->year . '-' . $monthDay;
    }

    // =========================================================================
    // Happy Path Scenarios
    // =========================================================================

    /**
     * The /leaves index route is accessible to a verified user and returns 200 OK.
     */
    public function test_leaves_index_route_returns_ok_for_authenticated_user(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('leaves.index'));

        $response->assertStatus(200);
    }

    /**
     * A leave can be applied with valid dates in the current year.
     */
    public function test_leave_can_be_applied_with_valid_data(): void
    {
        $user  = $this->actingAsUser();
        $start = $this->thisYear('06-01');
        $end   = $this->thisYear('06-05');

        $response = $this->post(route('leaves.store'), [
            'start_date' => $start,
            'end_date'   => $end,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('leaves.index'));
        $response->assertSessionHas('status', 'Leave applied.');

        $this->assertDatabaseHas('leaves', [
            'user_id'    => $user->id,
            'start_date' => $start . ' 00:00:00',
            'end_date'   => $end . ' 00:00:00',
        ]);
    }

    /**
     * A single-day leave (start equals end) is allowed.
     */
    public function test_single_day_leave_can_be_applied(): void
    {
        $user = $this->actingAsUser();
        $day  = $this->thisYear('06-10');

        $this->post(route('leaves.store'), [
            'start_date' => $day,
            'end_date'   => $day,
        ]);

        $this->assertDatabaseHas('leaves', [
            'user_id'    => $user->id,
            'start_date' => $day . ' 00:00:00',
        ]);
    }

    // =========================================================================
    // Validation & Error Scenarios
    // =========================================================================

    /**
     * Guests are redirected to login and cannot reach the listing.
     */
    public function test_guest_cannot_access_leaves_index(): void
    {
        $response = $this->get(route('leaves.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Guests cannot apply for leave.
     */
    public function test_guest_cannot_apply_leave(): void
    {
        $response = $this->post(route('leaves.store'), [
            'start_date' => $this->thisYear('06-01'),
            'end_date'   => $this->thisYear('06-05'),
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('leaves', 0);
    }

    /**
     * Application fails when required fields are missing.
     */
    public function test_leave_application_fails_with_missing_required_fields(): void
    {
        $this->actingAsUser();

        $response = $this->post(route('leaves.store'), []);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['start_date', 'end_date']);
        $this->assertDatabaseCount('leaves', 0);
    }

    /**
     * Application fails when the end date is before the start date.
     */
    public function test_leave_application_fails_when_end_before_start(): void
    {
        $this->actingAsUser();

        $response = $this->post(route('leaves.store'), [
            'start_date' => $this->thisYear('06-10'),
            'end_date'   => $this->thisYear('06-05'),
        ]);

        $response->assertSessionHasErrors(['end_date']);
        $this->assertDatabaseCount('leaves', 0);
    }

    /**
     * Application fails when the start date is in a previous year.
     */
    public function test_leave_application_fails_for_start_date_before_current_year(): void
    {
        $this->actingAsUser();

        $response = $this->post(route('leaves.store'), [
            'start_date' => (now()->year - 1) . '-12-31',
            'end_date'   => $this->thisYear('01-05'),
        ]);

        $response->assertSessionHasErrors(['start_date']);
        $this->assertDatabaseCount('leaves', 0);
    }

    /**
     * Application fails when the end date runs into the next year.
     */
    public function test_leave_application_fails_for_end_date_after_current_year(): void
    {
        $this->actingAsUser();

        $response = $this->post(route('leaves.store'), [
            'start_date' => $this->thisYear('12-30'),
            'end_date'   => (now()->year + 1) . '-01-02',
        ]);

        $response->assertSessionHasErrors(['end_date']);
        $this->assertDatabaseCount('leaves', 0);
    }

    /**
     * Application fails when the range overlaps an existing leave.
     */
    public function test_leave_application_fails_when_overlapping_existing_leave(): void
    {
        $user = $this->actingAsUser();

        Leave::create([
            'user_id'    => $user->id,
            'start_date' => $this->thisYear('06-01'),
            'end_date'   => $this->thisYear('06-10'),
        ]);

        // New request overlaps the 06-01..06-10 window.
        $response = $this->post(route('leaves.store'), [
            'start_date' => $this->thisYear('06-05'),
            'end_date'   => $this->thisYear('06-15'),
        ]);

        $response->assertSessionHasErrors(['start_date']);
        $this->assertDatabaseCount('leaves', 1); // only the pre-existing leave
    }

    /**
     * Application fails when work is already logged within the requested range.
     */
    public function test_leave_application_fails_when_work_logged_in_range(): void
    {
        $user    = $this->actingAsUser();
        $project = Project::factory()->create(['is_active' => true]);
        $workDay = $this->thisYear('06-07');

        TimeLog::create([
            'user_id'     => $user->id,
            'project_id'  => $project->id,
            'work_date'   => $workDay,
            'description' => 'Logged work',
            'minutes'     => 120,
        ]);

        $response = $this->post(route('leaves.store'), [
            'start_date' => $this->thisYear('06-05'),
            'end_date'   => $this->thisYear('06-10'),
        ]);

        $response->assertSessionHasErrors(['start_date']);
        $this->assertDatabaseCount('leaves', 0);
    }
}
