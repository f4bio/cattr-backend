<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $fullName = $this->faker->name();
        return [
            'full_name' => $fullName,
            'email' => $this->faker->unique()->safeEmail(),
            'url' => '',
            'company_id' => 1,
            'avatar' => '',
            'screenshots_active' => 1,
            'manual_time' => 0,
            'computer_time_popup' => 300,
            'blur_screenshots' => 0,
            'web_and_app_monitoring' => 1,
            'screenshots_interval' => 5,
            'active' => 1,
            'password' => $fullName,
            'user_language' => 'en',
            'role_id' => 2,
            'type' => 'employee',
            'nonce' => 0,
            'last_activity' => Carbon::now()->subMinutes(rand(1, 55)),
        ];
    }

    public function configure(): UserFactory
    {
        return $this->afterCreating(function (User $user) {
            $tokens = array_map(fn() => [
            'token' => JWTAuth::fromUser($user),
            'expires_at' => now()->addDay()
        ], range(0, 1));
            cache(["testing:{$user->id}:tokens" => $tokens]);
        });
    }

    /***
     * For registrations
     ***/

    public function createRandomData(): array
    {
        return [
            'full_name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'active' => 1,
            'password' => $this->faker->password,
            'screenshots_interval' => 5,
            'user_language' => 'en',
            'screenshots_active' => true,
            'computer_time_popup' => 10,
            'timezone' => 'UTC',
            'role_id' => 2,
            'type' => 'employee'
        ];
    }

    public function asAdmin(): UserFactory
    {
        return $this->state(function () {
            return [
                'email' => $this->faker->unique()->safeEmail,
                'is_admin' => true,
            ];
        });
    }

    public function asManager(): UserFactory
    {
        return $this->state(function () {
            return [
                'email' => $this->faker->unique()->safeEmail,
                'role_id' => 1,
            ];
        });
    }

    public function asAuditor(): UserFactory
    {
        return $this->state(function () {
            return [
                'email' => $this->faker->unique()->safeEmail,
                'role_id' => 3,
                ];
        });
    }

    public function asUser(): UserFactory
    {
        return $this->state(function () {
            return [
                'email' => $this->faker->unique()->safeEmail,
                'role_id' => 2,
            ];
        });
    }
}
