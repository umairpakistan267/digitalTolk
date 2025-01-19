<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use DTApi\Models\User;
use DTApi\Models\UserMeta;
use DTApi\Repository\UserRepository;
use Illuminate\Http\Request;

class UserRepositoryTest extends TestCase
{
     public function test_create_user_successfully()
    {
        $request = [
            'role' => 'admin',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'consumer_type' => 'paid',
            'company_id' => '',
            'department_id' => '',
        ];

        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('save')->once()->andReturnTrue();

        $repository = new UserRepository($mockUser);
        $result = $repository->createOrUpdate(null, $request);

        $this->assertNotNull($result);
        $this->assertEquals('Test User', $result->name);
    }

    public function test_update_user_successfully()
    {
        $request = [
            'role' => 'translator',
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'password' => 'newpassword',
        ];

        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('save')->once()->andReturnTrue();

        $repository = new UserRepository($mockUser);
        $result = $repository->createOrUpdate(1, $request);

        $this->assertNotNull($result);
        $this->assertEquals('Updated User', $result->name);
    }
}
