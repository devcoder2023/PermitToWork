<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Project;
use App\Models\Site;
use App\Models\SubContractor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $engineer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->engineer = User::factory()->engineer()->create();
    }

    public function test_admin_can_access_sub_contractors_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.sub-contractors.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/sub-contractors/index')
            ->has('subContractors')
        );
    }

    public function test_non_admin_cannot_access_sub_contractors_index(): void
    {
        $response = $this->actingAs($this->engineer)
            ->get(route('admin.sub-contractors.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_sub_contractor(): void
    {
        $data = [
            'name' => 'Test Sub-Contractor',
            'contact_email' => 'test@subcontractor.com',
            'contact_phone' => '+1234567890',
            'address' => '123 Test Street',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.sub-contractors.store'), $data);

        $response->assertRedirect(route('admin.sub-contractors.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sub_contractors', [
            'name' => 'Test Sub-Contractor',
            'contact_email' => 'test@subcontractor.com',
        ]);
    }

    public function test_admin_can_update_sub_contractor(): void
    {
        $subContractor = SubContractor::factory()->create();

        $data = [
            'name' => 'Updated Name',
            'contact_email' => 'updated@subcontractor.com',
            'contact_phone' => '+0987654321',
            'address' => '456 Updated Street',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.sub-contractors.update', $subContractor), $data);

        $response->assertRedirect(route('admin.sub-contractors.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sub_contractors', [
            'id' => $subContractor->id,
            'name' => 'Updated Name',
            'contact_email' => 'updated@subcontractor.com',
        ]);
    }

    public function test_admin_can_delete_sub_contractor(): void
    {
        $subContractor = SubContractor::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.sub-contractors.destroy', $subContractor));

        $response->assertRedirect(route('admin.sub-contractors.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('sub_contractors', [
            'id' => $subContractor->id,
        ]);
    }

    public function test_admin_can_access_projects_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.projects.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/projects/index')
            ->has('projects')
        );
    }

    public function test_non_admin_cannot_access_projects_index(): void
    {
        $response = $this->actingAs($this->engineer)
            ->get(route('admin.projects.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_project(): void
    {
        $data = [
            'name' => 'Test Project',
            'description' => 'Test project description',
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), $data);

        $response->assertRedirect(route('admin.projects.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_project(): void
    {
        $project = Project::factory()->create();

        $data = [
            'name' => 'Updated Project',
            'description' => 'Updated description',
            'status' => 'completed',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.projects.update', $project), $data);

        $response->assertRedirect(route('admin.projects.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project',
            'status' => 'completed',
        ]);
    }

    public function test_admin_can_delete_project_without_sites(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.projects.destroy', $project));

        $response->assertRedirect(route('admin.projects.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_admin_can_access_sites_index(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.projects.sites.index', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/projects/sites/index')
            ->has('sites')
            ->has('project')
        );
    }

    public function test_non_admin_cannot_access_sites_index(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->engineer)
            ->get(route('admin.projects.sites.index', $project));

        $response->assertForbidden();
    }

    public function test_admin_can_create_site(): void
    {
        $project = Project::factory()->create();
        $manager = User::factory()->siteManager()->create(['project_id' => $project->id]);
        $hseOfficer = User::factory()->hseOfficer()->create(['project_id' => $project->id]);

        $data = [
            'name' => 'Test Site',
            'description' => 'Test site description',
            'site_managers' => [$manager->id],
            'hse_officers' => [$hseOfficer->id],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.sites.store', $project), $data);

        $response->assertRedirect(route('admin.projects.sites.index', $project));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sites', [
            'name' => 'Test Site',
            'project_id' => $project->id,
        ]);

        $site = Site::where('name', 'Test Site')->first();
        $this->assertTrue($site->users->contains($manager));
        $this->assertTrue($site->users->contains($hseOfficer));
    }

    public function test_admin_can_update_site(): void
    {
        $project = Project::factory()->create();
        $site = Site::factory()->create(['project_id' => $project->id]);

        $data = [
            'name' => 'Updated Site',
            'description' => 'Updated description',
            'site_managers' => [],
            'hse_officers' => [],
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.sites.update', $site), $data);

        $response->assertRedirect(route('admin.projects.sites.index', $project));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'name' => 'Updated Site',
        ]);
    }

    public function test_admin_can_delete_site(): void
    {
        $project = Project::factory()->create();
        $site = Site::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.sites.destroy', $site));

        $response->assertRedirect(route('admin.projects.sites.index', $project));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('sites', [
            'id' => $site->id,
        ]);
    }

    public function test_admin_can_access_users_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->has('users')
        );
    }

    public function test_non_admin_cannot_access_users_index(): void
    {
        $response = $this->actingAs($this->engineer)
            ->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_user(): void
    {
        $project = Project::factory()->create();

        $data = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => Role::ExecutionEngineer->value,
            'project_id' => $project->id,
            'sub_contractor_id' => null,
            'phone' => '+1234567890',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), $data);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'role' => Role::ExecutionEngineer->value,
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->engineer()->create();

        $data = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role' => Role::SiteManager->value,
            'project_id' => null,
            'sub_contractor_id' => null,
            'phone' => '+0987654321',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), $data);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => Role::SiteManager->value,
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->engineer()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_can_access_permit_types_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.permit-types.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/permit-types/index')
            ->has('permitTypes')
        );
    }

    public function test_non_admin_cannot_access_permit_types_index(): void
    {
        $response = $this->actingAs($this->engineer)
            ->get(route('admin.permit-types.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_admin_routes(): void
    {
        $response = $this->get(route('admin.sub-contractors.index'));

        $response->assertRedirect(route('login'));
    }
}
