<?php

namespace Tests\Feature;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalStage;
use App\Enums\DurationType;
use App\Enums\PermitStatus;
use App\Enums\Role;
use App\Models\ApprovalRecord;
use App\Models\PermitType;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkPermit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermitWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $engineer;

    private User $siteManager;

    private User $permitOfficer;

    private User $supervisor;

    private Project $project;

    private Site $site;

    private PermitType $permitType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->site = Site::factory()->create(['project_id' => $this->project->id]);
        $this->permitType = PermitType::factory()->create([
            'duration_type' => DurationType::Weekly,
            'is_active' => true,
        ]);

        $this->engineer = User::factory()->create([
            'role' => Role::ExecutionEngineer,
            'project_id' => $this->project->id,
        ]);

        $this->siteManager = User::factory()->create([
            'role' => Role::SiteManager,
            'project_id' => $this->project->id,
        ]);
        $this->site->users()->attach($this->siteManager->id, ['role' => 'site_manager']);

        $this->permitOfficer = User::factory()->create([
            'role' => Role::PermitOfficer,
            'project_id' => $this->project->id,
        ]);

        $this->supervisor = User::factory()->create([
            'role' => Role::WorkSupervisor,
            'project_id' => $this->project->id,
        ]);
    }

    public function test_engineer_can_create_permit(): void
    {
        $response = $this->actingAs($this->engineer)
            ->post('/permits', [
                'permit_type_id' => $this->permitType->id,
                'site_id' => $this->site->id,
                'supervisor_id' => $this->supervisor->id,
                'location_area' => 'Area 1',
                'location_floor' => 'Ground',
                'work_description' => 'Test work description',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'shift' => 'Morning',
            ]);

        $response->assertRedirect();

        $permit = WorkPermit::where('engineer_id', $this->engineer->id)->first();
        $this->assertNotNull($permit);
        $this->assertEquals(PermitStatus::New, $permit->status);
        $this->assertStringStartsWith('PTW-'.now()->year.'-', $permit->permit_number);
    }

    public function test_permit_number_is_unique(): void
    {
        $permit1 = WorkPermit::factory()->create([
            'permit_number' => 'PTW-2026-00001',
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $permit2 = WorkPermit::factory()->create([
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $this->assertNotEquals($permit1->permit_number, $permit2->permit_number);
    }

    public function test_daily_permit_cannot_exceed_one_day(): void
    {
        $dailyType = PermitType::factory()->create([
            'duration_type' => DurationType::Daily,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->engineer)
            ->post('/permits', [
                'permit_type_id' => $dailyType->id,
                'site_id' => $this->site->id,
                'supervisor_id' => $this->supervisor->id,
                'location_area' => 'Area 1',
                'location_floor' => 'Ground',
                'work_description' => 'Test work',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'shift' => 'Morning',
            ]);

        $response->assertSessionHasErrors('end_date');
    }

    public function test_weekly_permit_cannot_exceed_seven_days(): void
    {
        $response = $this->actingAs($this->engineer)
            ->post('/permits', [
                'permit_type_id' => $this->permitType->id,
                'site_id' => $this->site->id,
                'supervisor_id' => $this->supervisor->id,
                'location_area' => 'Area 1',
                'location_floor' => 'Ground',
                'work_description' => 'Test work',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(8)->toDateString(),
                'shift' => 'Morning',
            ]);

        $response->assertSessionHasErrors('end_date');
    }

    public function test_supervisor_cannot_have_multiple_active_permits(): void
    {
        WorkPermit::factory()->create([
            'supervisor_id' => $this->supervisor->id,
            'status' => PermitStatus::Active,
            'engineer_id' => $this->engineer->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $response = $this->actingAs($this->engineer)
            ->post('/permits', [
                'permit_type_id' => $this->permitType->id,
                'site_id' => $this->site->id,
                'supervisor_id' => $this->supervisor->id,
                'location_area' => 'Area 1',
                'location_floor' => 'Ground',
                'work_description' => 'Test work',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'shift' => 'Morning',
            ]);

        $response->assertSessionHasErrors('supervisor_id');
    }

    public function test_site_manager_can_approve_permit(): void
    {
        $permit = WorkPermit::factory()->create([
            'status' => PermitStatus::New,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $response = $this->actingAs($this->siteManager)
            ->post("/permits/{$permit->id}/approve/site-manager");

        $response->assertRedirect();
        $permit->refresh();
        $this->assertEquals(PermitStatus::UnderReview, $permit->status);

        $this->assertDatabaseHas('approval_records', [
            'work_permit_id' => $permit->id,
            'user_id' => $this->siteManager->id,
            'stage' => ApprovalStage::SiteManager->value,
            'decision' => ApprovalDecision::Approved->value,
        ]);
    }

    public function test_site_manager_can_reject_permit(): void
    {
        $permit = WorkPermit::factory()->create([
            'status' => PermitStatus::New,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $response = $this->actingAs($this->siteManager)
            ->post("/permits/{$permit->id}/reject/site-manager", [
                'reason' => 'Insufficient safety measures',
            ]);

        $response->assertRedirect();
        $permit->refresh();
        $this->assertEquals(PermitStatus::Rejected, $permit->status);
        $this->assertEquals('Insufficient safety measures', $permit->rejection_reason);
    }

    public function test_permit_officer_can_approve_permit(): void
    {
        $permit = WorkPermit::factory()->create([
            'status' => PermitStatus::UnderReview,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        ApprovalRecord::create([
            'work_permit_id' => $permit->id,
            'user_id' => $this->siteManager->id,
            'stage' => ApprovalStage::SiteManager,
            'decision' => ApprovalDecision::Approved,
        ]);

        $response = $this->actingAs($this->permitOfficer)
            ->post("/permits/{$permit->id}/approve/permit-officer");

        $response->assertRedirect();
        $permit->refresh();
        $this->assertEquals(PermitStatus::Approved, $permit->status);
    }

    public function test_work_supervisor_can_accept_assignment(): void
    {
        $permit = WorkPermit::factory()->create([
            'status' => PermitStatus::Approved,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        ApprovalRecord::create([
            'work_permit_id' => $permit->id,
            'user_id' => $this->siteManager->id,
            'stage' => ApprovalStage::SiteManager,
            'decision' => ApprovalDecision::Approved,
        ]);

        ApprovalRecord::create([
            'work_permit_id' => $permit->id,
            'user_id' => $this->permitOfficer->id,
            'stage' => ApprovalStage::PermitOfficer,
            'decision' => ApprovalDecision::Approved,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->post("/permits/{$permit->id}/accept/work-supervisor");

        $response->assertRedirect();
        $this->assertDatabaseHas('approval_records', [
            'work_permit_id' => $permit->id,
            'user_id' => $this->supervisor->id,
            'stage' => ApprovalStage::WorkSupervisor->value,
            'decision' => ApprovalDecision::Approved->value,
        ]);
    }

    public function test_work_supervisor_can_decline_assignment(): void
    {
        $permit = WorkPermit::factory()->create([
            'status' => PermitStatus::Approved,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->post("/permits/{$permit->id}/decline/work-supervisor", [
                'reason' => 'Not available on these dates',
            ]);

        $response->assertRedirect();
        $permit->refresh();
        $this->assertEquals(PermitStatus::Rejected, $permit->status);
    }

    public function test_engineer_can_edit_rejected_permit(): void
    {
        $permit = WorkPermit::factory()->create([
            'status' => PermitStatus::Rejected,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
            'rejection_reason' => 'Test rejection',
        ]);

        $newSupervisor = User::factory()->create([
            'role' => Role::WorkSupervisor,
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->engineer)
            ->put("/permits/{$permit->id}", [
                'permit_type_id' => $this->permitType->id,
                'site_id' => $this->site->id,
                'supervisor_id' => $newSupervisor->id,
                'location_area' => 'Area 2',
                'location_floor' => '1st',
                'work_description' => 'Updated work description',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'shift' => 'Evening',
            ]);

        $response->assertRedirect();
        $permit->refresh();
        $this->assertEquals(PermitStatus::New, $permit->status);
        $this->assertEquals('Area 2', $permit->location_area);
        $this->assertNull($permit->rejection_reason);
    }

    public function test_engineer_can_cancel_new_permit(): void
    {
        $permit = WorkPermit::factory()->create([
            'status' => PermitStatus::New,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $response = $this->actingAs($this->engineer)
            ->delete("/permits/{$permit->id}");

        $response->assertRedirect();
        $permit->refresh();
        $this->assertEquals(PermitStatus::Archived, $permit->status);
    }

    public function test_engineer_cannot_edit_approved_permit(): void
    {
        $permit = WorkPermit::factory()->create([
            'status' => PermitStatus::Approved,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
            'location_area' => 'Original Area',
        ]);

        $response = $this->actingAs($this->engineer)
            ->put("/permits/{$permit->id}", [
                'permit_type_id' => $this->permitType->id,
                'site_id' => $this->site->id,
                'supervisor_id' => $this->supervisor->id,
                'location_area' => 'Area 2',
                'location_floor' => '1st',
                'work_description' => 'Updated work',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'shift' => 'Morning',
            ]);

        $response->assertRedirect();
        $permit->refresh();
        $this->assertEquals('Original Area', $permit->location_area);
    }

    public function test_non_engineer_cannot_create_permit(): void
    {
        $response = $this->actingAs($this->siteManager)
            ->post('/permits', [
                'permit_type_id' => $this->permitType->id,
                'site_id' => $this->site->id,
                'supervisor_id' => $this->supervisor->id,
                'location_area' => 'Area 1',
                'location_floor' => 'Ground',
                'work_description' => 'Test work',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'shift' => 'Morning',
            ]);

        $response->assertForbidden();
    }

    public function test_approval_record_cannot_be_updated(): void
    {
        $permit = WorkPermit::factory()->create([
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $record = ApprovalRecord::create([
            'work_permit_id' => $permit->id,
            'user_id' => $this->siteManager->id,
            'stage' => ApprovalStage::SiteManager,
            'decision' => ApprovalDecision::Approved,
        ]);

        $result = $record->update(['decision' => ApprovalDecision::Rejected->value]);

        $this->assertFalse($result);
        $record->refresh();
        $this->assertEquals(ApprovalDecision::Approved, $record->decision);
    }

    public function test_approval_record_cannot_be_deleted(): void
    {
        $permit = WorkPermit::factory()->create([
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $record = ApprovalRecord::create([
            'work_permit_id' => $permit->id,
            'user_id' => $this->siteManager->id,
            'stage' => ApprovalStage::SiteManager,
            'decision' => ApprovalDecision::Approved,
        ]);

        $result = $record->delete();

        $this->assertFalse($result);
        $this->assertDatabaseHas('approval_records', [
            'id' => $record->id,
        ]);
    }

    public function test_permit_list_filters_by_role(): void
    {
        $otherEngineer = User::factory()->create([
            'role' => Role::ExecutionEngineer,
            'project_id' => $this->project->id,
        ]);

        $permit1 = WorkPermit::factory()->create([
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $permit2 = WorkPermit::factory()->create([
            'engineer_id' => $otherEngineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $response = $this->actingAs($this->engineer)
            ->get('/permits');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('permits/index')
            ->has('permits.data', 1)
            ->where('permits.data.0.id', $permit1->id)
        );
    }

    public function test_full_approval_workflow(): void
    {
        $permit = WorkPermit::factory()->create([
            'status' => PermitStatus::New,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'permit_type_id' => $this->permitType->id,
        ]);

        $this->actingAs($this->siteManager)
            ->post("/permits/{$permit->id}/approve/site-manager");
        $permit->refresh();
        $this->assertEquals(PermitStatus::UnderReview, $permit->status);

        $this->actingAs($this->permitOfficer)
            ->post("/permits/{$permit->id}/approve/permit-officer");
        $permit->refresh();
        $this->assertEquals(PermitStatus::Approved, $permit->status);

        $this->actingAs($this->supervisor)
            ->post("/permits/{$permit->id}/accept/work-supervisor");
        $permit->refresh();

        $this->assertEquals(3, $permit->approvalRecords()->count());
        $this->assertTrue($permit->approvalRecords()->where('stage', ApprovalStage::SiteManager)->exists());
        $this->assertTrue($permit->approvalRecords()->where('stage', ApprovalStage::PermitOfficer)->exists());
        $this->assertTrue($permit->approvalRecords()->where('stage', ApprovalStage::WorkSupervisor)->exists());
    }
}
