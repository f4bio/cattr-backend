<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'time-intervals/create';

    private User $admin;

    private array $intervalData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();

        $this->intervalData = TimeInterval::factory()
            ->for($this->admin)
            ->make()
            ->makeHidden('id', 'updated_at', 'created_at', 'deleted_at', 'can')
            ->toArray();
    }

    public function test_create(): void
    {
        $this->assertDatabaseMissing('time_intervals', $this->intervalData);
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->intervalData);

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', $this->intervalData);
    }

    public function test_already_exists_left(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 00:55:00';
        $newInterval['end_at'] = '1900-01-01 01:05:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_right(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 01:00:00';
        $newInterval['end_at'] = '1900-01-01 01:10:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_left_inner(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 00:55:00';
        $newInterval['end_at'] = '1900-01-01 01:03:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_right_inner(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 01:03:00';
        $newInterval['end_at'] = '1900-01-01 01:10:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_inner(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 01:01:00';
        $newInterval['end_at'] = '1900-01-01 01:03:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists_outer(): void
    {
        $this->intervalData['start_at'] = '1900-01-01 01:00:00';
        $this->intervalData['end_at'] = '1900-01-01 01:05:00';

        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $newInterval = $this->intervalData;
        $newInterval['start_at'] = '1900-01-01 00:55:00';
        $newInterval['end_at'] = '1900-01-01 01:10:00';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $newInterval);

        $response->assertValidationError();
    }

    public function test_already_exists(): void
    {
        TimeInterval::create($this->intervalData);
        $this->assertDatabaseHas('time_intervals', $this->intervalData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->intervalData);

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
