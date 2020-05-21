<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class UserRoleControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testAttach()
    {
        $user = Sanctum::actingAs($this->user, ['update-user']);

        $role = factory(Role::class)->create();

        $this->assertCount(0, $user->roles);

        $this->json('POST', 'api/users/'.$user->id.'/roles', [
            'role_ids' => $role->id,
        ])
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertCount(1, $user->refresh()->roles);
    }

    /**
     * @return void
     */
    public function testSync()
    {
        $user = Sanctum::actingAs($this->user, ['update-user']);

        $roles = $user->roles()->saveMany(factory(Role::class, 2)->make());

        $this->assertCount(2, $user->roles);

        $this->json('POST', 'api/users/'.$user->id.'/roles', [
            'role_ids' => $roles->first()->id,
            'sync' => true,
        ])
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertCount(1, $user->refresh()->roles);
    }

    /**
     * @return void
     */
    public function testDetach()
    {
        $user = Sanctum::actingAs($this->user, ['update-user']);

        $role = $user->roles()->save(factory(Role::class)->make());

        $this->assertCount(1, $user->roles);

        $this->json('DELETE', 'api/users/'.$user->id.'/roles/'.$role->id)
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertCount(0, $user->refresh()->roles);
    }

    /**
     * @return void
     */
    public function testAttachWithoutPermission()
    {
        $user = Sanctum::actingAs($this->user);

        $role = factory(Role::class)->create();

        $this->json('POST', 'api/users/'.$user->id.'/roles', [
            'role_ids' => $role->id,
        ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function testDetachWithoutPermission()
    {
        $user = Sanctum::actingAs($this->user);

        $role = $user->roles()->save(factory(Role::class)->make());

        $this->json('DELETE', 'api/users/'.$user->id.'/roles/'.$role->id)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}