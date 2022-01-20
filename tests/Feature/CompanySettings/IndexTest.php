<?php

namespace Tests\Feature\CompanySettings;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class IndexTest extends TestCase
{
    private const URI = 'company-settings';

    private $admin;
    private Model $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->admin = User::factory()->asAdmin()->create();
    }

    public function test_index_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
    }

    public function test_index_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertOk();
    }
}
