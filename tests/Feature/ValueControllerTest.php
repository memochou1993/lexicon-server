<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Key;
use App\Models\Language;
use App\Models\Project;
use App\Models\Team;
use App\Models\Value;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ValueControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testShow()
    {
        $user = Sanctum::actingAs($this->user, ['view-value']);

        $team = $user->teams()->save(factory(Team::class)->create());
        $project = $team->projects()->save(factory(Project::class)->make());
        $key = $project->keys()->save(factory(Key::class)->make());
        $value = $key->values()->save(factory(Value::class)->make());

        $this->json('GET', 'api/values/'.$value->id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'language',
                    'form',
                ],
            ])
            ->assertJson([
                'data' => $value->toArray(),
            ]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $user = Sanctum::actingAs($this->user, ['update-value']);

        $team = $user->teams()->save(factory(Team::class)->create());
        $project = $team->projects()->save(factory(Project::class)->make());
        $key = $project->keys()->save(factory(Key::class)->make());
        $value = $key->values()->save(factory(Value::class)->make());

        $data = factory(Value::class)->make([
            'text' => 'New Value',
        ])->toArray();

        $this->json('PATCH', 'api/values/'.$value->id, $data)
            ->assertOk()
            ->assertJson([
                'data' => $data,
            ]);

        $this->assertDatabaseHas('values', $data);

        $this->assertCount(1, $key->values);
    }

    /**
     * @return void
     */
    public function testDestroy()
    {
        $user = Sanctum::actingAs($this->user, ['delete-value']);

        $team = $user->teams()->save(factory(Team::class)->create());
        $language = $team->languages()->save(factory(Language::class)->make());
        $form = $team->forms()->save(factory(Form::class)->make());
        $language->forms()->attach($form);
        $project = $team->projects()->save(factory(Project::class)->make());
        $project->languages()->attach($language);
        $key = $project->keys()->save(factory(Key::class)->make());
        $value = $key->values()->save(factory(Value::class)->make());
        $value->languages()->attach($language);
        $value->forms()->attach($form);

        $this->assertCount(1, $value->languages);
        $this->assertCount(1, $value->forms);

        $this->json('DELETE', 'api/values/'.$value->id)
            ->assertNoContent();

        $this->assertDeleted($value);

        $this->assertDatabaseMissing('model_has_languages', [
            'language_id' => $language->id,
            'model_type' => 'value',
            'model_id' => $value->id,
        ]);

        $this->assertDatabaseMissing('model_has_forms', [
            'form_id' => $form->id,
            'model_type' => 'value',
            'model_id' => $value->id,
        ]);
    }

    /**
     * @return void
     */
    public function testGuestViewForbidden()
    {
        $user = Sanctum::actingAs($this->user, ['view-value']);

        $team = $user->teams()->save(factory(Team::class)->make());
        $project = $team->projects()->save(factory(Project::class)->withoutEvents()->make());
        $key = $project->keys()->save(factory(Key::class)->make());
        $value = $key->values()->save(factory(Value::class)->make());

        $this->json('GET', 'api/values/'.$value->id)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function testGuestUpdateForbidden()
    {
        $user = Sanctum::actingAs($this->user, ['update-value']);

        $team = $user->teams()->save(factory(Team::class)->make());
        $project = $team->projects()->save(factory(Project::class)->withoutEvents()->make());
        $key = $project->keys()->save(factory(Key::class)->make());
        $value = $key->values()->save(factory(Value::class)->make());

        $this->json('PATCH', 'api/values/'.$value->id)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function testGuestDeleteForbidden()
    {
        $user = Sanctum::actingAs($this->user, ['delete-value']);

        $team = $user->teams()->save(factory(Team::class)->make());
        $project = $team->projects()->save(factory(Project::class)->withoutEvents()->make());
        $key = $project->keys()->save(factory(Key::class)->make());
        $value = $key->values()->save(factory(Value::class)->make());

        $this->json('DELETE', 'api/values/'.$value->id)
            ->assertForbidden();
    }

    // TODO: make testViewWithoutPermission()
    // TODO: make testUpdateWithoutPermission()
    // TODO: make testDeleteWithoutPermission()
}
