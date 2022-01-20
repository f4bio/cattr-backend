<?php
namespace Tests\Feature\Invitations;

use App\Models\User;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'invitations/show';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;
    private Model $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->admin = User::factory()->asAdmin()->create();
        $this->auditor = User::factory()->asAuditor()->create();

        $this->invitation = Invitation::factory()->create();
    }

    public function test_show_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->invitation->only('id'));

        $response->assertOk();
        $response->assertJson($this->invitation->toArray());
    }

    public function test_show_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->invitation->only('id'));

        $response->assertForbidden();
    }

    public function test_show_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->invitation->only('id'));

        $response->assertForbidden();
    }

    public function test_show_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->invitation->only('id'));

        $response->assertForbidden();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }
}
