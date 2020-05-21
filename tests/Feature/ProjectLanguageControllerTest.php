<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectLanguageControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testAttach()
    {
        $user = Sanctum::actingAs($this->user, ['update-project']);

        $team = $user->teams()->save(factory(Team::class)->make());
        $project = $team->projects()->save(factory(Project::class)->make());
        $language = $team->languages()->save(factory(Language::class)->make());

        $this->assertCount(0, $project->languages);

        $this->json('POST', 'api/projects/'.$project->id.'/languages', [
            'language_ids' => $language->id,
        ])
            ->assertNoContent();

        $this->assertCount(1, $project->refresh()->languages);
    }

    /**
     * @return void
     */
    public function testSync()
    {
        $user = Sanctum::actingAs($this->user, ['update-project']);

        $team = $user->teams()->save(factory(Team::class)->make());
        $project = $team->projects()->save(factory(Project::class)->make());
        $languages = $team->languages()->saveMany(factory(Language::class, 2)->make());
        $project->languages()->attach($languages);

        $this->assertCount(2, $project->languages);

        $this->json('POST', 'api/projects/'.$project->id.'/languages', [
            'language_ids' => $languages->pluck('id')->first(),
            'sync' => true,
        ])
            ->assertNoContent();

        $this->assertCount(1, $project->refresh()->languages);
    }

    /**
     * @return void
     */
    public function testDetach()
    {
        $user = Sanctum::actingAs($this->user, ['update-project']);

        $team = $user->teams()->save(factory(Team::class)->make());
        $project = $team->projects()->save(factory(Project::class)->make());
        $language = $team->languages()->save(factory(Language::class)->make());
        $project->languages()->attach($language);

        $this->assertCount(1, $project->languages);

        $this->json('DELETE', 'api/projects/'.$project->id.'/languages/'.$language->id)
            ->assertNoContent();

        $this->assertCount(0, $project->refresh()->languages);
    }

    /**
     * @return void
     */
    public function testGuestAttach()
    {
        $user = Sanctum::actingAs($this->user, ['update-project']);

        $team = $user->teams()->save(factory(Team::class)->make());
        $project = $team->projects()->save(factory(Project::class)->withoutEvents()->make());

        $this->json('POST', 'api/projects/'.$project->id.'/languages')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function testGuestDetach()
    {
        $user = Sanctum::actingAs($this->user, ['update-project']);

        $team = $user->teams()->save(factory(Team::class)->make());
        $project = $team->projects()->save(factory(Project::class)->withoutEvents()->make());
        $language = $team->languages()->save(factory(Language::class)->make());
        $project->languages()->attach($language);

        $this->json('DELETE', 'api/projects/'.$project->id.'/languages/'.$language->id)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function testAttachWithoutPermission()
    {
        $user = Sanctum::actingAs($this->user);

        $team = $user->teams()->save(factory(Team::class)->make());
        $project = $team->projects()->save(factory(Project::class)->make());

        $this->json('POST', 'api/projects/'.$project->id.'/languages')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function testDetachWithoutPermission()
    {
        $user = Sanctum::actingAs($this->user);

        $team = $user->teams()->save(factory(Team::class)->make());
        $project = $team->projects()->save(factory(Project::class)->make());
        $language = $team->languages()->save(factory(Language::class)->make());
        $project->languages()->attach($language);

        $this->json('DELETE', 'api/projects/'.$project->id.'/languages/'.$language->id)
            ->assertForbidden();
    }
}
