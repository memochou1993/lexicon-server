<?php

namespace Tests\Feature\Api;

use App\Enums\ErrorType;
use App\Enums\PermissionType;
use App\Models\Language;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testIndex()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::TEAM_VIEW,
            PermissionType::PROJECT_VIEW_ANY,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();
        $team->projects()->save(Project::factory()->make());

        $this->json('GET', 'api/teams/'.$team->id.'/projects', [
            'relations' => 'users,languages',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'owner',
                        'users',
                        'languages',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function testStore()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::TEAM_VIEW,
            PermissionType::PROJECT_CREATE,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();

        $data = Project::factory()->make()->toArray();

        $this->json('POST', 'api/teams/'.$team->id.'/projects', $data)
            ->assertCreated();

        $this->assertCount(1, $team->refresh()->projects);
    }

    /**
     * @return void
     */
    public function testStoreDuplicate()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::TEAM_VIEW,
            PermissionType::PROJECT_CREATE,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();
        $team->projects()->save(Project::factory()->make([
            'name' => 'Unique Project',
        ]));

        $data = Project::factory()->make([
            'name' => 'Unique Project',
        ])->toArray();

        $this->json('POST', 'api/teams/'.$team->id.'/projects', $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'name',
            ]);

        $this->assertCount(1, $team->refresh()->projects);
    }

    /**
     * @return void
     */
    public function testShow()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::PROJECT_VIEW,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Project $project */
        $project = $team->projects()->save(Project::factory()->make());

        $this->json('GET', 'api/projects/'.$project->id, [
            'relations' => 'team,users,languages,languages.forms,hooks,setting',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'owner',
                    'team',
                    'users',
                    'languages',
                    'hooks',
                    'setting',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => $project->name,
                ],
            ]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::PROJECT_UPDATE,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Project $project */
        $project = $team->projects()->save(Project::factory()->make());

        $data = Project::factory()->make([
            'name' => 'New Project',
        ])->toArray();

        $this->json('PATCH', 'api/projects/'.$project->id, $data)
            ->assertOk();
    }

    /**
     * @return void
     */
    public function testUpdateDuplicate()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::PROJECT_UPDATE,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Collection $projects */
        $projects = $team->projects()->saveMany(Project::factory()->count(2)->make());

        $data = Project::factory()->make([
            'name' => $projects->last()->name,
        ])->toArray();

        $this->json('PATCH', 'api/projects/'.$projects->first()->id, $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'name',
            ]);
    }

    /**
     * @return void
     */
    public function testDestroy()
    {
        /** @var User $user */
        $user = Sanctum::actingAs($this->user, [
            PermissionType::PROJECT_DELETE,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Project $project */
        $project = $team->projects()->save(Project::factory()->make());

        /** @var Language $language */
        $language = $project->languages()->save(Language::factory()->make());

        $this->assertCount(1, $project->users);
        $this->assertCount(1, $project->languages);

        $this->json('DELETE', 'api/projects/'.$project->id)
            ->assertNoContent();

        $this->assertDeleted($project);

        $this->assertDatabaseMissing('model_has_users', [
            'user_id' => $user->id,
            'model_type' => 'project',
            'model_id' => $project->id,
        ]);

        $this->assertDatabaseMissing('model_has_languages', [
            'language_id' => $language->id,
            'model_type' => 'project',
            'model_id' => $project->id,
        ]);
    }

    /**
     * @return void
     */
    public function testViewAllByGuest()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::TEAM_VIEW,
            PermissionType::PROJECT_VIEW_ANY,
        ]);

        $this->flushEventListeners(Team::class);

        /** @var Team $team */
        $team = Team::factory()->create();

        $response = $this->json('GET', 'api/teams/'.$team->id.'/projects')
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::USER_NOT_IN_TEAM,
            $response->exception->getCode()
        );
    }

    /**
     * @return void
     */
    public function testCreateByGuest()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::TEAM_VIEW,
            PermissionType::PROJECT_CREATE,
        ]);

        $this->flushEventListeners(Team::class);

        /** @var Team $team */
        $team = Team::factory()->create();

        $data = Project::factory()->make()->toArray();

        $response = $this->json('POST', 'api/teams/'.$team->id.'/projects', $data)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::USER_NOT_IN_TEAM,
            $response->exception->getCode()
        );
    }

    /**
     * @return void
     */
    public function testViewByGuest()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::PROJECT_VIEW,
        ]);

        $this->flushEventListeners(Team::class, Project::class);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Project $project */
        $project = $team->projects()->save(Project::factory()->make());

        $response = $this->json('GET', 'api/projects/'.$project->id)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::USER_NOT_IN_PROJECT,
            $response->exception->getCode()
        );
    }

    /**
     * @return void
     */
    public function testUpdateByGuest()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::PROJECT_UPDATE,
        ]);

        $this->flushEventListeners(Team::class, Project::class);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Project $project */
        $project = $team->projects()->save(Project::factory()->make());

        $response = $this->json('PATCH', 'api/projects/'.$project->id)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::USER_NOT_IN_PROJECT,
            $response->exception->getCode()
        );
    }

    /**
     * @return void
     */
    public function testDeleteByGuest()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::PROJECT_DELETE,
        ]);

        $this->flushEventListeners(Team::class, Project::class);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Project $project */
        $project = $team->projects()->save(Project::factory()->make());

        $response = $this->json('DELETE', 'api/projects/'.$project->id)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::USER_NOT_IN_PROJECT,
            $response->exception->getCode()
        );
    }

    /**
     * @return void
     */
    public function testViewAllWithoutPermission()
    {
        Sanctum::actingAs($this->user);

        /** @var Team $team */
        $team = Team::factory()->create();

        $response = $this->json('GET', 'api/teams/'.$team->id.'/projects')
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::PERMISSION_DENIED,
            $response->exception->getCode()
        );
    }

    /**
     * @return void
     */
    public function testCreateWithoutPermission()
    {
        Sanctum::actingAs($this->user);

        /** @var Team $team */
        $team = Team::factory()->create();

        $data = Project::factory()->make()->toArray();

        $response = $this->json('POST', 'api/teams/'.$team->id.'/projects', $data)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::PERMISSION_DENIED,
            $response->exception->getCode()
        );
    }

    /**
     * @return void
     */
    public function testViewWithoutPermission()
    {
        Sanctum::actingAs($this->user);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Project $project */
        $project = $team->projects()->save(Project::factory()->make());

        $response = $this->json('GET', 'api/projects/'.$project->id)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::PERMISSION_DENIED,
            $response->exception->getCode()
        );
    }

    /**
     * @return void
     */
    public function testUpdateWithoutPermission()
    {
        Sanctum::actingAs($this->user);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Project $project */
        $project = $team->projects()->save(Project::factory()->make());

        $response = $this->json('PATCH', 'api/projects/'.$project->id)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::PERMISSION_DENIED,
            $response->exception->getCode()
        );
    }

    /**
     * @return void
     */
    public function testDeleteWithoutPermission()
    {
        Sanctum::actingAs($this->user);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Project $project */
        $project = $team->projects()->save(Project::factory()->make());

        $response = $this->json('DELETE', 'api/projects/'.$project->id)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::PERMISSION_DENIED,
            $response->exception->getCode()
        );
    }
}
