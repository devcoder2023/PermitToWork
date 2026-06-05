<?php

namespace Tests\Feature;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalStage;
use App\Enums\DurationType;
use App\Enums\PermitStatus;
use App\Enums\Role;
use App\Models\ApprovalRecord;
use App\Models\DailyClosure;
use App\Models\DailyFollowUp;
use App\Models\PermitType;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkPermit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $engineer;

    private User $siteManager;

    private User $permitOfficer;

    private User $supervisor;

    private User $hseOfficer;

    private Project $project;

    private Site $site;

    private PermitType $weeklyPermitType;

    private PermitType $dailyPermitType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->site = Site::factory()->create(['project_id' => $this->project->id]);

        $this->weeklyPermitType = PermitType::factory()->create(['duration_type' => DurationType::Weekly, 'is_active' => true]);
        $this->dailyPermitType = PermitType::factory()->create(['duration_type' => DurationType::Daily, 'is_active' => true]);

        $this->engineer = User::factory()->create(['role' => Role::ExecutionEngineer, 'project_id' => $this->project->id]);
        $this->siteManager = User::factory()->create(['role' => Role::SiteManager, 'project_id' => $this->project->id]);
        $this->site->users()->attach($this->siteManager->id, ['role' => 'site_manager']);
        $this->permitOfficer = User::factory()->create(['role' => Role::PermitOfficer, 'project_id' => $this->project->id]);
        $this->supervisor = User::factory()->create(['role' => Role::WorkSupervisor, 'project_id' => $this->project->id]);
        $this->hseOfficer = User::factory()->create(['role' => Role::HseOfficer, 'project_id' => $this->project->id]);
        $this->site->users()->attach($this->hseOfficer->id, ['role' => 'hse_officer']);
    }

    private function createApprovedPermit(): WorkPermit
    {
        $permit = WorkPermit::factory()->create([
            'permit_type_id' => $this->weeklyPermitType->id,
            'project_id' => $this->project->id,
            'site_id' => $this->site->id,
            'engineer_id' => $this->engineer->id,
            'supervisor_id' => $this->supervisor->id,
            'status' => PermitStatus::Approved,
            'start_date' => today(),
            'end_date' => today()->addDays(3),
        ]);

        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->siteManager->id, 'stage' => ApprovalStage::SiteManager, 'decision' => ApprovalDecision::Approved]);
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->permitOfficer->id, 'stage' => ApprovalStage::PermitOfficer, 'decision' => ApprovalDecision::Approved]);

        return $permit;
    }

    public function test_hse_officer_can_perform_first_follow_up(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);

        $response = $this->actingAs($this->hseOfficer)->post("/permits/{$permit->id}/first-follow-up", ['notes' => 'Site conditions are safe.']);

        $response->assertRedirect();
        $permit->refresh();
        $this->assertEquals(PermitStatus::Active, $permit->status);
        $this->assertTrue($permit->hasFirstFollowUp());
    }

    public function test_work_supervisor_cannot_perform_first_follow_up(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);

        $response = $this->actingAs($this->supervisor)->post("/permits/{$permit->id}/first-follow-up", ['notes' => 'Site conditions are safe.']);

        $response->assertStatus(403);
        $permit->refresh();
        $this->assertEquals(PermitStatus::Approved, $permit->status);
    }

    public function test_hse_officer_cannot_follow_up_before_work_supervisor(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today()->subDay(), 'notes' => 'First follow-up (yesterday)', 'is_first_follow_up' => true]);
        $permit->update(['status' => PermitStatus::Active]);

        $response = $this->actingAs($this->hseOfficer)->post("/permits/{$permit->id}/follow-up", ['notes' => 'HSE follow-up']);

        $response->assertSessionHas('error');
        $this->assertFalse($permit->hasHseFollowUpToday());
    }

    public function test_hse_officer_can_follow_up_after_work_supervisor(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'First follow-up', 'is_first_follow_up' => true]);
        $permit->update(['status' => PermitStatus::Active]);

        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'WS daily follow-up', 'is_first_follow_up' => false]);

        $response = $this->actingAs($this->hseOfficer)->post("/permits/{$permit->id}/follow-up", ['notes' => 'HSE follow-up']);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $permit->refresh();
        $this->assertTrue($permit->hasHseFollowUpToday());
    }

    public function test_work_supervisor_cannot_close_before_both_follow_ups(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'First follow-up', 'is_first_follow_up' => true]);
        $permit->update(['status' => PermitStatus::Active]);

        $response = $this->actingAs($this->supervisor)->post("/permits/{$permit->id}/close-day");

        $response->assertSessionHas('error');
        $this->assertFalse($permit->hasSupervisorClosureToday());
    }

    public function test_work_supervisor_can_close_after_both_follow_ups(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'First follow-up', 'is_first_follow_up' => true]);
        $permit->update(['status' => PermitStatus::Active]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'WS follow-up', 'is_first_follow_up' => false]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->hseOfficer->id, 'follow_up_date' => today(), 'notes' => 'HSE follow-up', 'is_first_follow_up' => false]);

        $response = $this->actingAs($this->supervisor)->post("/permits/{$permit->id}/close-day");

        $response->assertRedirect();
        $this->assertTrue($permit->hasSupervisorClosureToday());
    }

    public function test_hse_officer_cannot_close_before_work_supervisor(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'First follow-up', 'is_first_follow_up' => true]);
        $permit->update(['status' => PermitStatus::Active]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'WS follow-up', 'is_first_follow_up' => false]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->hseOfficer->id, 'follow_up_date' => today(), 'notes' => 'HSE follow-up', 'is_first_follow_up' => false]);

        $response = $this->actingAs($this->hseOfficer)->post("/permits/{$permit->id}/close-day");

        $response->assertSessionHas('error');
        $this->assertFalse($permit->hasHseClosureToday());
    }

    public function test_hse_officer_can_close_after_work_supervisor(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'First follow-up', 'is_first_follow_up' => true]);
        $permit->update(['status' => PermitStatus::Active]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'WS follow-up', 'is_first_follow_up' => false]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->hseOfficer->id, 'follow_up_date' => today(), 'notes' => 'HSE follow-up', 'is_first_follow_up' => false]);
        DailyClosure::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'closure_date' => today(), 'closed_at' => now()]);

        $response = $this->actingAs($this->hseOfficer)->post("/permits/{$permit->id}/close-day");

        $response->assertRedirect();
        $this->assertTrue($permit->hasHseClosureToday());
    }

    public function test_permit_becomes_daily_closed_when_both_close(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'First follow-up', 'is_first_follow_up' => true]);
        $permit->update(['status' => PermitStatus::Active, 'end_date' => today()->addDays(2)]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'WS follow-up', 'is_first_follow_up' => false]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->hseOfficer->id, 'follow_up_date' => today(), 'notes' => 'HSE follow-up', 'is_first_follow_up' => false]);

        $this->actingAs($this->supervisor)->post("/permits/{$permit->id}/close-day");
        $this->actingAs($this->hseOfficer)->post("/permits/{$permit->id}/close-day");

        $permit->refresh();
        $this->assertEquals(PermitStatus::DailyClosed, $permit->status);
    }

    public function test_permit_becomes_expired_on_last_day(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'First follow-up', 'is_first_follow_up' => true]);
        $permit->update(['status' => PermitStatus::Active, 'start_date' => today(), 'end_date' => today()]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'follow_up_date' => today(), 'notes' => 'WS follow-up', 'is_first_follow_up' => false]);
        DailyFollowUp::create(['work_permit_id' => $permit->id, 'user_id' => $this->hseOfficer->id, 'follow_up_date' => today(), 'notes' => 'HSE follow-up', 'is_first_follow_up' => false]);

        $this->actingAs($this->supervisor)->post("/permits/{$permit->id}/close-day");
        $this->actingAs($this->hseOfficer)->post("/permits/{$permit->id}/close-day");

        $permit->refresh();
        $this->assertEquals(PermitStatus::Expired, $permit->status);
    }

    public function test_auto_reopen_command_reopens_daily_closed_weekly_permits(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);
        $permit->update(['status' => PermitStatus::DailyClosed, 'start_date' => today()->subDay(), 'end_date' => today()->addDays(2)]);

        $this->artisan('permits:reopen-daily-closed')->assertExitCode(0);

        $permit->refresh();
        $this->assertEquals(PermitStatus::Active, $permit->status);
    }

    public function test_auto_archive_command_archives_expired_permits(): void
    {
        $permit = $this->createApprovedPermit();
        $permit->update(['status' => PermitStatus::Expired]);

        $this->artisan('permits:archive-expired')->assertExitCode(0);

        $permit->refresh();
        $this->assertEquals(PermitStatus::Archived, $permit->status);
    }

    public function test_full_daily_workflow_order(): void
    {
        $permit = $this->createApprovedPermit();
        ApprovalRecord::create(['work_permit_id' => $permit->id, 'user_id' => $this->supervisor->id, 'stage' => ApprovalStage::WorkSupervisor, 'decision' => ApprovalDecision::Approved]);

        // First follow-up must be done by HSE Officer
        $this->actingAs($this->hseOfficer)->post("/permits/{$permit->id}/first-follow-up", ['notes' => 'First follow-up']);
        $permit->refresh();
        $this->assertEquals(PermitStatus::Active, $permit->status);

        // Work Supervisor records daily follow-up
        $this->actingAs($this->supervisor)->post("/permits/{$permit->id}/follow-up", ['notes' => 'WS daily follow-up']);
        $permit->refresh();
        $this->assertTrue($permit->hasSupervisorFollowUpToday());

        // HSE Officer records daily follow-up
        $this->actingAs($this->hseOfficer)->post("/permits/{$permit->id}/follow-up", ['notes' => 'HSE daily follow-up']);
        $permit->refresh();
        $this->assertTrue($permit->hasHseFollowUpToday());

        // Work Supervisor closes the day
        $this->actingAs($this->supervisor)->post("/permits/{$permit->id}/close-day");
        $permit->refresh();
        $this->assertTrue($permit->hasSupervisorClosureToday());

        // HSE Officer closes the day
        $this->actingAs($this->hseOfficer)->post("/permits/{$permit->id}/close-day");
        $permit->refresh();
        $this->assertTrue($permit->hasHseClosureToday());
    }
}
