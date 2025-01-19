<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use DTApi\Helpers\TeHelper;

class TeHelperTest extends TestCase
{
    public function test_will_expire_at_within_90_minutes()
    {
        $due_time = Carbon::now()->addMinutes(60)->toDateTimeString();
        $created_at = Carbon::now()->toDateTimeString();

        $result = TeHelper::willExpireAt($due_time, $created_at);
        $this->assertEquals($due_time, $result);
    }

    public function test_will_expire_at_within_24_hours()
    {
        $due_time = Carbon::now()->addHours(20)->toDateTimeString();
        $created_at = Carbon::now()->toDateTimeString();

        $result = TeHelper::willExpireAt($due_time, $created_at);
        $expected_time = Carbon::now()->addMinutes(90)->format('Y-m-d H:i:s');
        $this->assertEquals($expected_time, $result);
    }

    public function test_will_expire_at_between_24_and_72_hours()
    {
        $due_time = Carbon::now()->addHours(48)->toDateTimeString();
        $created_at = Carbon::now()->toDateTimeString();

        $result = TeHelper::willExpireAt($due_time, $created_at);
        $expected_time = Carbon::now()->addHours(16)->format('Y-m-d H:i:s');
        $this->assertEquals($expected_time, $result);
    }

    public function test_will_expire_at_more_than_72_hours()
    {
        $due_time = Carbon::now()->addHours(100)->toDateTimeString();
        $created_at = Carbon::now()->toDateTimeString();

        $result = TeHelper::willExpireAt($due_time, $created_at);
        $expected_time = Carbon::now()->addHours(52)->format('Y-m-d H:i:s'); // 100 - 48
        $this->assertEquals($expected_time, $result);
    }
}
