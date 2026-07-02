<?php

namespace Tests\Unit;

use App\Models\TimeLog;
use PHPUnit\Framework\TestCase;

class TimeLogTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Test that the duration accessor on the TimeLog model formats
     * minutes as a zero-padded HH:MM string.
     *
     * @return void
     */
    public function test_duration_accessor_returns_correct_result(): void
    {
        // Create a new TimeLog instance with a minutes value
        $timeLog = new TimeLog([
            'minutes' => 150,
        ]);

        // 150 minutes => 2 hours 30 minutes => "02:30"
        $this->assertEquals('02:30', $timeLog->duration);
    }

    /**
     * The duration accessor zero-pads hours and minutes below 10.
     *
     * @return void
     */
    public function test_duration_accessor_zero_pads_small_values(): void
    {
        $timeLog = new TimeLog(['minutes' => 5]);

        // 5 minutes => "00:05"
        $this->assertEquals('00:05', $timeLog->duration);
    }
}
