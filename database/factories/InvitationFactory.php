<?php

namespace Database\Factories;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvitationFactory extends Factory
{

    protected $model = Invitation::class;

    public function definition()
    {
        return [
            'email' => $this->faker->unique()->email,
            'key' => $this->faker->uuid,
            'expires_at' => now()->addDays(1),
        ];
    }

    public function requestData(): array
    {
        return [
            'users' => [
                    [
                    'email' => $this->faker->unique()->email,
                    'role_id' => 1
                    ]
                ],
            ];
    }
}
