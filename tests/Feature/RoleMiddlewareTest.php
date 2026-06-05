<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.sub-contractors.index'));

        $response->assertOk();
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $engineer = User::factory()->engineer()->create();

        $response = $this->actingAs($engineer)
            ->get(route('admin.sub-contractors.index'));

        $response->assertForbidden();
    }

    public function test_middleware_allows_correct_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get('/dashboard');

        $response->assertOk();
    }

    public function test_middleware_blocks_unauthorized_role(): void
    {
        $engineer = User::factory()->engineer()->create();

        $response = $this->actingAs($engineer)
            ->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
