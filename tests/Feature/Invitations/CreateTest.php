<?php

namespace Tests\Feature\Invitations;

use App\Models\User;
use App\Models\invitation;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'invitations/create';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;

    private $invitationRequestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->admin = User::factory()->asAdmin()->create();
        $this->auditor = User::factory()->asAuditor()->create();

        $this->invitationRandomData = Invitation::factory()->create()->toArray();
        $this->invitationRequestData = Invitation::factory()->requestData();
    }

    public function test_create_as_admin(): void
    {

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->invitationRequestData);
        $response->assertOk();

        $this->assertDatabaseHas((new Invitation)->getTable(), $this->invitationRequestData['users'][0]);

        foreach ($response->json('res') as $invitation) {
            $this->assertDatabaseHas((new Invitation)->getTable(), $invitation);
        }
    }

    public function test_create_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->invitationRandomData);

        $response->assertForbidden();
    }

    public function test_create_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->invitationRandomData);

        $response->assertForbidden();
    }

    public function test_create_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->invitationRandomData);

        $response->assertForbidden();
    }

    public function test_create_already_exists(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->invitationRequestData);

        $this->assertDatabaseHas((new Invitation)->getTable(), $response->decodeResponseJson()['res'][0]);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->invitationRequestData);

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
