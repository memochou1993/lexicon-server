<?php

namespace Tests\Feature\Api;

use App\Enums\ErrorType;
use App\Enums\PermissionType;
use App\Models\Form;
use App\Models\Key;
use App\Models\Language;
use App\Models\Project;
use App\Models\Team;
use App\Models\Value;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LanguageControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testStore()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::TEAM_VIEW,
            PermissionType::LANGUAGE_CREATE,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Form $form */
        $form = $team->forms()->save(Form::factory()->make());

        $data = Language::factory()->make([
            'form_ids' => $form->id,
        ])->toArray();

        $response = $this->json('POST', 'api/teams/'.$team->id.'/languages', $data)
            ->assertCreated();

        $this->assertCount(1, $team->refresh()->languages);

        /** @var Language $language */
        $language = Language::query()->find(json_decode($response->getContent())->data->id);

        $this->assertCount(1, $language->refresh()->forms);
    }

    /**
     * @return void
     */
    public function testStoreDuplicate()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::TEAM_VIEW,
            PermissionType::LANGUAGE_CREATE,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();
        $team->languages()->save(Language::factory()->make([
            'name' => 'Unique Language',
        ]));

        $data = Language::factory()->make([
            'name' => 'Unique Language',
        ])->toArray();

        $this->json('POST', 'api/teams/'.$team->id.'/languages', $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'name',
            ]);

        $this->assertCount(1, $team->refresh()->languages);
    }

    /**
     * @return void
     */
    public function testShow()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::LANGUAGE_VIEW,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Language $language */
        $language = $team->languages()->save(Language::factory()->make());

        $this->json('GET', 'api/languages/'.$language->id, [
            'relations' => 'forms',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'forms',
                ],
            ])
            ->assertJson([
                'data' => $language->toArray(),
            ]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::LANGUAGE_UPDATE,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Language $language */
        $language = $team->languages()->save(Language::factory()->make());

        /** @var Form $form */
        $form = $team->forms()->save(Form::factory()->make());

        $data = Language::factory()->make([
            'name' => 'New Language',
            'form_ids' => $form->id,
        ])->toArray();

        $this->json('PATCH', 'api/languages/'.$language->id, $data)
            ->assertOk();

        $this->assertCount(1, $language->refresh()->forms);
    }

    /**
     * @return void
     */
    public function testUpdateDuplicate()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::LANGUAGE_UPDATE,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Collection $languages */
        $languages = $team->languages()->saveMany(Language::factory()->count(2)->make());

        $data = Language::factory()->make([
            'name' => $languages->last()->name,
        ])->toArray();

        $this->json('PATCH', 'api/languages/'.$languages->first()->id, $data)
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
        Sanctum::actingAs($this->user, [
            PermissionType::LANGUAGE_DELETE,
        ]);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Project $project */
        $project = $team->projects()->save(Project::factory()->make());

        /** @var Language $language */
        $language = $team->languages()->save(Language::factory()->make());
        $project->languages()->attach($language);

        /** @var Form $form */
        $form = $team->forms()->save(Form::factory()->make());
        $language->forms()->attach($form);

        /** @var Key $key */
        $key = $project->keys()->save(Key::factory()->make());

        /** @var Value $value */
        $value = $key->values()->save(Value::factory()->make());
        $value->languages()->attach($language);
        $value->forms()->attach($form);

        $this->json('DELETE', 'api/languages/'.$language->id)
            ->assertNoContent();

        $this->assertDeleted($language);

        $this->assertDeleted($value);

        $this->assertDatabaseMissing('model_has_forms', [
            'form_id' => $form->id,
            'model_type' => 'language',
            'model_id' => $language->id,
        ]);
    }

    /**
     * @return void
     */
    public function testCreateByGuest()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::TEAM_VIEW,
            PermissionType::LANGUAGE_CREATE,
        ]);

        $this->flushEventListeners(Team::class);

        /** @var Team $team */
        $team = Team::factory()->create();

        $data = Language::factory()->make()->toArray();

        $response = $this->json('POST', 'api/teams/'.$team->id.'/languages', $data)
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
            PermissionType::LANGUAGE_VIEW,
        ]);

        $this->flushEventListeners(Team::class);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Language $language */
        $language = $team->languages()->save(Language::factory()->make());

        $response = $this->json('GET', 'api/languages/'.$language->id)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::USER_NOT_IN_TEAM,
            $response->exception->getCode()
        );
    }

    /**
     * @return void
     */
    public function testUpdateByGuest()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::LANGUAGE_UPDATE,
        ]);

        $this->flushEventListeners(Team::class);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Language $language */
        $language = $team->languages()->save(Language::factory()->make());

        $response = $this->json('PATCH', 'api/languages/'.$language->id)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::USER_NOT_IN_TEAM,
            $response->exception->getCode()
        );
    }

    /**
     * @return void
     */
    public function testDeleteByGuest()
    {
        Sanctum::actingAs($this->user, [
            PermissionType::LANGUAGE_DELETE,
        ]);

        $this->flushEventListeners(Team::class);

        /** @var Team $team */
        $team = Team::factory()->create();

        /** @var Language $language */
        $language = $team->languages()->save(Language::factory()->make());

        $response = $this->json('DELETE', 'api/languages/'.$language->id)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::USER_NOT_IN_TEAM,
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

        $data = Language::factory()->make()->toArray();

        $response = $this->json('POST', 'api/teams/'.$team->id.'/languages', $data)
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

        /** @var Language $language */
        $language = $team->languages()->save(Language::factory()->make());

        $response = $this->json('GET', 'api/languages/'.$language->id)
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

        /** @var Language $language */
        $language = $team->languages()->save(Language::factory()->make());

        $response = $this->json('PATCH', 'api/languages/'.$language->id)
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

        /** @var Language $language */
        $language = $team->languages()->save(Language::factory()->make());

        $response = $this->json('DELETE', 'api/languages/'.$language->id)
            ->assertForbidden();

        $this->assertEquals(
            ErrorType::PERMISSION_DENIED,
            $response->exception->getCode()
        );
    }
}
