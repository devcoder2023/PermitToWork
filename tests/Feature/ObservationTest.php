<?php

namespace Tests\Feature;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalStage;
use App\Enums\DurationType;
use App\Enums\ObservationStatus;
use App\Enums\PermitStatus;
use App\Enums\Role;
use App\Models\ApprovalRecord;
use App\Models\Observation;
use App\Models\PermitType;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkPermit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservationTest extends TestCase
{
    use RefreshDatabase;

    private User $engineer;

    private User $supervisor;

    private User $hseOfficer;

    private User $consultant;

    private Project $project;

    private Site $site;

    private WorkPermit $activePermit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->site = Site::factory()->create(['project_id' => $this->project->id]);

        $permitType = PermitType::factory()->create(['duration_type' => DurationType::Weekly, 'is_active' => true]);

        $this->engineer = User::factory()->create(['role' => Role::ExecutionEngineer, 'project_id' => $this->project->id]);
        $this->supervisor = User::factory()->create(['role' => Role::WorkSupervisor, 'project_id' => $this->project->id]);
        $this->hseOfficer = User::factory()->create(['role' => Role::HseOfficer, 'project_id' => $this->project->id]);
        $this->site->users()->attach($this->hseOfficer->id, ['role' => 'hse_officer']);
        $this->consultant = User::factory()->create(['role' => Role::Consultant, 'project_id' => $this->project->id]);

        $this->activePermit = WorkPermit::factory()->create([
            'permit_type_id' => $permitType->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'status' => PermitStatus::Active,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        ApprovalRecord::create(['work_permit_id' => $this->activePermit->id, 'user_id' => $this->engineer->id, 'stage' => ApprovalStage::SiteManager, 'decision' => ApprovalDecision::Approved]);
        ApprovalRecord::create(['work_permit_id' => $this->activePermit->id, 'user_id' => $this->engineer->id, 'stage' => ApprovalStage::PermitOfficer, 'decision' => ApprovalDecision::Approved]);
        ApprovalRecord::create(['work_permit_id' => $this->activePermit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);
    }

    public function test_hse_officer_can_create_observation(): void
    {
        $response = $this->actingAs($this->hseOfficer)->post("/permits/{$this->activePermit->id}/observations", [
            'description' => 'Safety hazard identified: tools left unattended',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('observations', [
            'work_permit_id' => $this->activePermit->id,
            'created_by' => $this->hseOfficer->id,
            'description' => 'Safety hazard identified: tools left unattended',
            'status' => ObservationStatus::Open->value,
        ]);
    }

    public function test_consultant_can_create_observation(): void
    {
        $response = $this->actingAs($this->consultant)->post("/permits/{$this->activePermit->id}/observations", [
            'description' => 'Observation from consultant',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('observations', [
            'work_permit_id' => $this->activePermit->id,
            'created_by' => $this->consultant->id,
            'description' => 'Observation from consultant',
        ]);
    }

    public function test_engineer_cannot_create_observation(): void
    {
        $response = $this->actingAs($this->engineer)->post("/permits/{$this->activePermit->id}/observations", [
            'description' => 'This should fail',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_create_observation_on_inactive_permit(): void
    {
        $approvedPermit = WorkPermit::factory()->create([
            'permit_type_id' => $this->activePermit->permit_type_id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'status' => PermitStatus::Approved,
        ]);

        $response = $this->actingAs($this->hseOfficer)->post("/permits/{$approvedPermit->id}/observations", [
            'description' => 'This should fail',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_engineer_can_resolve_observation(): void
    {
        $observation = Observation::factory()->create([
            'work_permit_id' => $this->activePermit->id,
            'created_by' => $this->hseOfficer->id,
            'status' => ObservationStatus::Open,
        ]);

        $response = $this->actingAs($this->engineer)->post("/observations/{$observation->id}/resolve", [
            'resolution_note' => 'Issue has been addressed',
        ]);

        $response->assertRedirect();
        $observation->refresh();
        $this->assertEquals(ObservationStatus::Resolved, $observation->status);
        $this->assertEquals('Issue has been addressed', $observation->resolution_note);
    }

    public function test_supervisor_can_resolve_observation(): void
    {
        $observation = Observation::factory()->create([
            'work_permit_id' => $this->activePermit->id,
            'created_by' => $this->hseOfficer->id,
            'status' => ObservationStatus::Open,
        ]);

        $response = $this->actingAs($this->supervisor)->post("/observations/{$observation->id}/resolve", [
            'resolution_note' => 'Fixed by supervisor',
        ]);

        $response->assertRedirect();
        $observation->refresh();
        $this->assertEquals(ObservationStatus::Resolved, $observation->status);
    }

    public function test_hse_officer_can_accept_resolution(): void
    {
        $observation = Observation::factory()->create([
            'work_permit_id' => $this->activePermit->id,
            'created_by' => $this->hseOfficer->id,
            'status' => ObservationStatus::Resolved,
            'resolution_note' => 'Fixed',
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($this->hseOfficer)->post("/observations/{$observation->id}/accept-resolution");

        $response->assertRedirect();
        $observation->refresh();
        $this->assertEquals(ObservationStatus::Closed, $observation->status);
        $this->assertNotNull($observation->closed_at);
    }

    public function test_hse_officer_can_reject_resolution(): void
    {
        $observation = Observation::factory()->create([
            'work_permit_id' => $this->activePermit->id,
            'created_by' => $this->hseOfficer->id,
            'status' => ObservationStatus::Resolved,
            'resolution_note' => 'Incomplete fix',
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($this->hseOfficer)->post("/observations/{$observation->id}/reject-resolution", [
            'rejection_reason' => 'Not properly fixed',
        ]);

        $response->assertRedirect();
        $observation->refresh();
        $this->assertEquals(ObservationStatus::Open, $observation->status);
        $this->assertEquals('Not properly fixed', $observation->rejection_reason);
    }

    public function test_hse_officer_can_suspend_permit(): void
    {
        $response = $this->actingAs($this->hseOfficer)->post("/permits/{$this->activePermit->id}/suspend", [
            'suspension_reason' => 'Safety violation detected',
        ]);

        $response->assertRedirect();
        $this->activePermit->refresh();
        $this->assertEquals(PermitStatus::Suspended, $this->activePermit->status);
        $this->assertEquals('Safety violation detected', $this->activePermit->suspension_reason);
    }

    public function test_consultant_can_suspend_permit(): void
    {
        $response = $this->actingAs($this->consultant)->post("/permits/{$this->activePermit->id}/suspend", [
            'suspension_reason' => 'Safety concern',
        ]);

        $response->assertRedirect();
        $this->activePermit->refresh();
        $this->assertEquals(PermitStatus::Suspended, $this->activePermit->status);
    }

    public function test_engineer_can_request_resumption(): void
    {
        $this->activePermit->update(['status' => PermitStatus::Suspended, 'suspension_reason' => 'Test']);

        $response = $this->actingAs($this->engineer)->post("/permits/{$this->activePermit->id}/request-resume", [
            'resumption_note' => 'Issue has been resolved',
        ]);

        $response->assertRedirect();
        $this->activePermit->refresh();
        $this->assertTrue($this->activePermit->resumption_requested);
        $this->assertEquals('Issue has been resolved', $this->activePermit->resumption_note);
    }

    public function test_supervisor_can_request_resumption(): void
    {
        $this->activePermit->update(['status' => PermitStatus::Suspended, 'suspension_reason' => 'Test']);

        $response = $this->actingAs($this->supervisor)->post("/permits/{$this->activePermit->id}/request-resume", [
            'resumption_note' => 'Fixed the issue',
        ]);

        $response->assertRedirect();
        $this->activePermit->refresh();
        $this->assertTrue($this->activePermit->resumption_requested);
    }

    public function test_hse_officer_can_approve_resumption(): void
    {
        $this->activePermit->update(['status' => PermitStatus::Suspended, 'suspension_reason' => 'Test', 'resumption_requested' => true, 'resumption_approval_note' => 'Fixed']);

        $response = $this->actingAs($this->hseOfficer)->post("/permits/{$this->activePermit->id}/approve-resume");

        $response->assertRedirect();
        $this->activePermit->refresh();
        $this->assertEquals(PermitStatus::Active, $this->activePermit->status);
        $this->assertFalse($this->activePermit->resumption_requested);
    }

    public function test_hse_officer_can_reject_resumption(): void
    {
        $this->activePermit->update(['status' => PermitStatus::Suspended, 'suspension_reason' => 'Test', 'resumption_requested' => true, 'resumption_rejection_reason' => 'Fixed']);

        $response = $this->actingAs($this->hseOfficer)->post("/permits/{$this->activePermit->id}/reject-resume", [
            'resumption_rejection_reason' => 'Not adequately addressed',
        ]);

        $response->assertRedirect();
        $this->activePermit->refresh();
        $this->assertFalse($this->activePermit->resumption_requested);
        $this->assertEquals('Not adequately addressed', $this->activePermit->resumption_rejection_reason);
    }

    public function test_hse_officer_can_terminate_permit(): void
    {
        $response = $this->actingAs($this->hseOfficer)->post("/permits/{$this->activePermit->id}/terminate", [
            'termination_reason' => 'Serious safety violation',
        ]);

        $response->assertRedirect();
        $this->activePermit->refresh();
        $this->assertEquals(PermitStatus::Terminated, $this->activePermit->status);
        $this->assertEquals('Serious safety violation', $this->activePermit->termination_reason);
    }

    public function test_consultant_can_terminate_permit(): void
    {
        $response = $this->actingAs($this->consultant)->post("/permits/{$this->activePermit->id}/terminate", [
            'termination_reason' => 'Critical safety issue',
        ]);

        $response->assertRedirect();
        $this->activePermit->refresh();
        $this->assertEquals(PermitStatus::Terminated, $this->activePermit->status);
    }

    public function test_terminated_permit_cannot_be_resumed(): void
    {
        $this->activePermit->update(['status' => PermitStatus::Terminated, 'termination_reason' => 'Test']);

        $response = $this->actingAs($this->engineer)->post("/permits/{$this->activePermit->id}/request-resume", [
            'resumption_note' => 'Request after termination',
        ]);

        $response->assertSessionHas('error');
    }
}
