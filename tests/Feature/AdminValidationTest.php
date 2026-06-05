<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Project;
use App\Models\Site;
use App\Models\SubContractor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_sub_contractor_name_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.sub-contractors.store'), [
                'name' => '',
                'contact_email' => 'test@example.com',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_sub_contractor_email_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.sub-contractors.store'), [
                'name' => 'Test Company',
                'contact_email' => '',
            ]);

        $response->assertSessionHasErrors('contact_email');
    }

    public function test_sub_contractor_email_must_be_valid(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.sub-contractors.store'), [
                'name' => 'Test Company',
                'contact_email' => 'invalid-email',
            ]);

        $response->assertSessionHasErrors('contact_email');
    }

    public function test_sub_contractor_email_must_be_unique(): void
    {
        SubContractor::factory()->create(['contact_email' => 'existing@example.com']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.sub-contractors.store'), [
                'name' => 'Test Company',
                'contact_email' => 'existing@example.com',
            ]);

        $response->assertSessionHasErrors('contact_email');
    }

    public function test_sub_contractor_email_unique_on_update_ignores_self(): void
    {
        $subContractor = SubContractor::factory()->create(['contact_email' => 'test@example.com']);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.sub-contractors.update', $subContractor), [
                'name' => 'Updated Name',
                'contact_email' => 'test@example.com',
            ]);

        $response->assertSessionDoesntHaveErrors('contact_email');
    }

    public function test_project_name_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), [
                'name' => '',
                'status' => 'active',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_project_status_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), [
                'name' => 'Test Project',
                'status' => '',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_project_status_must_be_valid(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), [
                'name' => 'Test Project',
                'status' => 'invalid_status',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_project_start_date_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), [
                'name' => 'Test Project',
                'status' => 'active',
                'start_date' => '',
                'end_date' => '2026-12-31',
            ]);

        $response->assertSessionHasErrors('start_date');
    }

    public function test_project_end_date_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), [
                'name' => 'Test Project',
                'status' => 'active',
                'start_date' => '2026-01-01',
                'end_date' => '',
            ]);

        $response->assertSessionHasErrors('end_date');
    }

    public function test_project_end_date_must_be_after_or_equal_start_date(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), [
                'name' => 'Test Project',
                'status' => 'active',
                'start_date' => '2026-12-31',
                'end_date' => '2026-01-01',
            ]);

        $response->assertSessionHasErrors('end_date');
    }

    public function test_site_name_is_required(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.sites.store', $project), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_site_managers_must_exist(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.sites.store', $project), [
                'name' => 'Test Site',
                'site_managers' => [999],
            ]);

        $response->assertSessionHasErrors('site_managers.0');
    }

    public function test_hse_officers_must_exist(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.sites.store', $project), [
                'name' => 'Test Site',
                'hse_officers' => [999],
            ]);

        $response->assertSessionHasErrors('hse_officers.0');
    }

    public function test_user_name_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => '',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => Role::ExecutionEngineer->value,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_email_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => '',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => Role::ExecutionEngineer->value,
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_email_must_be_valid(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'invalid-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => Role::ExecutionEngineer->value,
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => Role::ExecutionEngineer->value,
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_email_unique_on_update_ignores_self(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Updated Name',
                'email' => 'test@example.com',
                'password' => '',
                'password_confirmation' => '',
                'role' => $user->role->value,
            ]);

        $response->assertSessionDoesntHaveErrors('email');
    }

    public function test_user_password_is_required_on_create(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => '',
                'password_confirmation' => '',
                'role' => Role::ExecutionEngineer->value,
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_user_password_must_be_confirmed(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different',
                'role' => Role::ExecutionEngineer->value,
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_user_role_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => '',
            ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_user_role_must_be_valid(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'invalid_role',
            ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_user_project_id_must_exist(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => Role::ExecutionEngineer->value,
                'project_id' => 999,
            ]);

        $response->assertSessionHasErrors('project_id');
    }

    public function test_user_sub_contractor_id_must_exist(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => Role::ExecutionEngineer->value,
                'sub_contractor_id' => 999,
            ]);

        $response->assertSessionHasErrors('sub_contractor_id');
    }

    public function test_deleting_project_cascades_to_sites(): void
    {
        $project = Project::factory()->create();
        $site = Site::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.projects.destroy', $project));

        $response->assertRedirect(route('admin.projects.index'));

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);

        $this->assertDatabaseMissing('sites', [
            'id' => $site->id,
        ]);
    }

    public function test_deleting_sub_contractor_nullifies_user_fk(): void
    {
        $subContractor = SubContractor::factory()->create();
        $user = User::factory()->create(['sub_contractor_id' => $subContractor->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.sub-contractors.destroy', $subContractor));

        $response->assertRedirect(route('admin.sub-contractors.index'));

        $this->assertDatabaseMissing('sub_contractors', [
            'id' => $subContractor->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'sub_contractor_id' => null,
        ]);
    }

    public function test_deleting_user_removes_site_assignments(): void
    {
        $user = User::factory()->siteManager()->create();
        $site = Site::factory()->create();
        $site->users()->attach($user->id, ['role' => 'site_manager']);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);

        $this->assertDatabaseMissing('site_user', [
            'user_id' => $user->id,
        ]);
    }
}
