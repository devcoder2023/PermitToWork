<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStage;
use App\Enums\PermitStatus;
use App\Enums\Role;
use App\Models\PermitType;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkPermit;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WorkPermitController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = WorkPermit::query()
            ->with(['permitType', 'project', 'site', 'engineer', 'supervisor', 'subContractor']);

        $status = $request->input('status', '');
        $permitType = $request->input('permit_type', '');
        $site = $request->input('site', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo = $request->input('date_to', '');
        $engineer = $request->input('engineer', '');
        $company = $request->input('company', '');

        $query->when($status, fn ($q) => $q->where('status', $status));
        $query->when($permitType, fn ($q) => $q->where('permit_type_id', $permitType));
        $query->when($site, fn ($q) => $q->where('site_id', $site));
        $query->when($dateFrom, fn ($q) => $q->where('start_date', '>=', $dateFrom));
        $query->when($dateTo, fn ($q) => $q->where('end_date', '<=', $dateTo));
        $query->when($engineer, fn ($q) => $q->where('engineer_id', $engineer));
        $query->when($company, fn ($q) => $q->where('sub_contractor_id', $company === 'main' ? null : $company));

        match ($user->role) {
            Role::SystemAdmin, Role::QaInspector => null,
            Role::ExecutionEngineer => $query->where('engineer_id', $user->id),
            Role::SiteManager => $query->whereHas('site', fn ($q) => $q->whereHas('users', fn ($q) => $q->where('users.id', $user->id))),
            Role::PermitOfficer => $query->where('project_id', $user->project_id),
            Role::HseOfficer => $query->whereHas('site', fn ($q) => $q->whereHas('users', fn ($q) => $q->where('users.id', $user->id))),
            Role::Consultant => $query->where('project_id', $user->project_id),
            Role::WorkSupervisor => $query->where('supervisor_id', $user->id),
        };

        $permits = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('permits/index', [
            'permits' => $permits,
            'filters' => [
                'status' => $status,
                'permit_type' => $permitType,
                'site' => $site,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'engineer' => $engineer,
                'company' => $company,
            ],
            'permitTypes' => PermitType::where('is_active', true)->get(),
            'sites' => Site::with('project')->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $projectId = $user->project_id;

        if (! $projectId) {
            abort(403, 'You must be assigned to a project to create permits.');
        }

        $supervisors = User::query()
            ->where('role', Role::WorkSupervisor)
            ->where('project_id', $projectId)
            ->whereDoesntHave('supervisedPermits', function ($query) {
                $query->whereIn('status', [PermitStatus::New, PermitStatus::UnderReview, PermitStatus::Approved, PermitStatus::Active]);
            })
            ->get(['id', 'name']);

        return Inertia::render('permits/create', [
            'permitTypes' => PermitType::where('is_active', true)->get(),
            'sites' => Site::where('project_id', $projectId)->get(['id', 'name']),
            'supervisors' => $supervisors,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'permit_type_id' => ['required', 'exists:permit_types,id'],
            'site_id' => ['required', 'exists:sites,id'],
            'supervisor_id' => ['required', 'exists:users,id'],
            'location_area' => ['required', 'string', 'max:255'],
            'location_floor' => ['required', 'string', 'max:255'],
            'location_description' => ['nullable', 'string', 'max:1000'],
            'work_description' => ['required', 'string', 'max:5000'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'shift' => ['required', 'string', 'max:100'],
        ]);

        $permitType = PermitType::findOrFail($validated['permit_type_id']);
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $daysDiff = $startDate->diffInDays($endDate) + 1;

        if ($permitType->duration_type->value === 'daily' && $daysDiff > 1) {
            return back()->withErrors(['end_date' => 'Daily permits can only be for one day.'])->withInput();
        }

        if ($permitType->duration_type->value === 'weekly' && $daysDiff > 7) {
            return back()->withErrors(['end_date' => 'Weekly permits cannot exceed 7 days.'])->withInput();
        }

        $supervisor = User::findOrFail($validated['supervisor_id']);
        if ($supervisor->role !== Role::WorkSupervisor) {
            return back()->withErrors(['supervisor_id' => 'Selected user is not a work supervisor.'])->withInput();
        }

        $hasActivePermit = WorkPermit::query()
            ->where('supervisor_id', $validated['supervisor_id'])
            ->whereIn('status', [PermitStatus::New, PermitStatus::UnderReview, PermitStatus::Approved, PermitStatus::Active])
            ->exists();

        if ($hasActivePermit) {
            return back()->withErrors(['supervisor_id' => 'This supervisor already has an active permit.'])->withInput();
        }

        $permitNumber = $this->generatePermitNumber();

        $permit = WorkPermit::create([
            'permit_number' => $permitNumber,
            'permit_type_id' => $validated['permit_type_id'],
            'project_id' => $user->project_id,
            'sub_contractor_id' => $user->sub_contractor_id,
            'site_id' => $validated['site_id'],
            'engineer_id' => $user->id,
            'supervisor_id' => $validated['supervisor_id'],
            'status' => PermitStatus::New,
            'location_area' => $validated['location_area'],
            'location_floor' => $validated['location_floor'],
            'location_description' => $validated['location_description'] ?? null,
            'work_description' => $validated['work_description'],
            'request_date' => now()->toDateString(),
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'shift' => $validated['shift'],
        ]);

        return redirect()->route('permits.show', $permit)
            ->with('success', 'Permit created successfully.');
    }

    public function show(Request $request, WorkPermit $permit): Response
    {
        $user = $request->user();

        $permit->load([
            'permitType',
            'project',
            'site',
            'engineer',
            'supervisor',
            'subContractor',
            'approvalRecords.user',
            'dailyClosures.user',
            'dailyFollowUps.user',
            'observations.creator',
            'observations.attachments',
        ]);

        $approvalTimeline = $this->buildApprovalTimeline($permit);
        $dailyOperations = $this->buildDailyOperationsData($user, $permit);
        $observationData = $this->buildObservationData($user, $permit);

        return Inertia::render('permits/show', [
            'permit' => $permit,
            'approvalTimeline' => $approvalTimeline,
            'dailyOperations' => $dailyOperations,
            'observations' => $observationData,
        ]);
    }

    public function edit(WorkPermit $permit): Response|RedirectResponse
    {
        if (! $permit->isEditable()) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'This permit cannot be edited.');
        }

        $user = request()->user();
        $projectId = $user->project_id;

        $supervisors = User::query()
            ->where('role', Role::WorkSupervisor)
            ->where('project_id', $projectId)
            ->where(function ($query) use ($permit) {
                $query->whereDoesntHave('supervisedPermits', function ($query) {
                    $query->whereIn('status', [PermitStatus::New, PermitStatus::UnderReview, PermitStatus::Approved, PermitStatus::Active]);
                })->orWhere('id', $permit->supervisor_id);
            })
            ->get(['id', 'name']);

        return Inertia::render('permits/edit', [
            'permit' => $permit,
            'permitTypes' => PermitType::where('is_active', true)->get(),
            'sites' => Site::where('project_id', $projectId)->get(['id', 'name']),
            'supervisors' => $supervisors,
        ]);
    }

    public function update(Request $request, WorkPermit $permit): RedirectResponse
    {
        if (! $permit->isEditable()) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'This permit cannot be edited.');
        }

        $validated = $request->validate([
            'permit_type_id' => ['required', 'exists:permit_types,id'],
            'site_id' => ['required', 'exists:sites,id'],
            'supervisor_id' => ['required', 'exists:users,id'],
            'location_area' => ['required', 'string', 'max:255'],
            'location_floor' => ['required', 'string', 'max:255'],
            'location_description' => ['nullable', 'string', 'max:1000'],
            'work_description' => ['required', 'string', 'max:5000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'shift' => ['required', 'string', 'max:100'],
        ]);

        $permitType = PermitType::findOrFail($validated['permit_type_id']);
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $daysDiff = $startDate->diffInDays($endDate) + 1;

        if ($permitType->duration_type->value === 'daily' && $daysDiff > 1) {
            return back()->withErrors(['end_date' => 'Daily permits can only be for one day.'])->withInput();
        }

        if ($permitType->duration_type->value === 'weekly' && $daysDiff > 7) {
            return back()->withErrors(['end_date' => 'Weekly permits cannot exceed 7 days.'])->withInput();
        }

        if ((int) $validated['supervisor_id'] !== $permit->supervisor_id) {
            $hasActivePermit = WorkPermit::query()
                ->where('supervisor_id', $validated['supervisor_id'])
                ->whereIn('status', [PermitStatus::New, PermitStatus::UnderReview, PermitStatus::Approved, PermitStatus::Active])
                ->exists();

            if ($hasActivePermit) {
                return back()->withErrors(['supervisor_id' => 'This supervisor already has an active permit.'])->withInput();
            }
        }

        $permit->update([
            'permit_type_id' => $validated['permit_type_id'],
            'site_id' => $validated['site_id'],
            'supervisor_id' => $validated['supervisor_id'],
            'location_area' => $validated['location_area'],
            'location_floor' => $validated['location_floor'],
            'location_description' => $validated['location_description'] ?? null,
            'work_description' => $validated['work_description'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'shift' => $validated['shift'],
            'status' => PermitStatus::New,
            'rejection_reason' => null,
        ]);

        return redirect()->route('permits.show', $permit)
            ->with('success', 'Permit updated successfully.');
    }

    public function destroy(WorkPermit $permit): RedirectResponse
    {
        if (! $permit->isCancellable()) {
            return redirect()->route('permits.show', $permit)
                ->with('error', 'This permit cannot be cancelled.');
        }

        $permit->update(['status' => PermitStatus::Archived]);

        return redirect()->route('permits.index')
            ->with('success', 'Permit cancelled successfully.');
    }

    private function generatePermitNumber(): string
    {
        $year = now()->year;
        $prefix = "PTW-{$year}-";

        $lastPermit = DB::table('work_permits')
            ->where('permit_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->max('permit_number');

        if ($lastPermit) {
            $lastNumber = (int) substr($lastPermit, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    private function buildApprovalTimeline(WorkPermit $permit): array
    {
        $stages = [
            ApprovalStage::SiteManager->value => [
                'stage' => 'site_manager',
                'label' => 'Site Manager',
                'user' => null,
                'decision' => null,
                'reason' => null,
                'timestamp' => null,
            ],
            ApprovalStage::PermitOfficer->value => [
                'stage' => 'permit_officer',
                'label' => 'Permit Officer',
                'user' => null,
                'decision' => null,
                'reason' => null,
                'timestamp' => null,
            ],
            ApprovalStage::WorkSupervisor->value => [
                'stage' => 'work_supervisor',
                'label' => 'Work Supervisor',
                'user' => null,
                'decision' => null,
                'reason' => null,
                'timestamp' => null,
            ],
        ];

        foreach ($permit->approvalRecords as $record) {
            $stageKey = $record->stage->value;
            if (isset($stages[$stageKey])) {
                $stages[$stageKey]['user'] = $record->user;
                $stages[$stageKey]['decision'] = $record->decision->value;
                $stages[$stageKey]['reason'] = $record->reason;
                $stages[$stageKey]['timestamp'] = $record->created_at->toIso8601String();
            }
        }

        return array_values($stages);
    }

    private function buildDailyOperationsData($user, WorkPermit $permit): array
    {
        $today = today()->toDateString();

        $supervisorFollowUpToday = $permit->hasSupervisorFollowUpToday();
        $hseFollowUpToday = $permit->hasHseFollowUpToday();
        $supervisorClosedToday = $permit->hasSupervisorClosureToday();
        $hseClosedToday = $permit->hasHseClosureToday();

        $userClosedToday = $permit->dailyClosures()
            ->where('user_id', $user->id)
            ->where('closure_date', $today)
            ->exists();

        $userFollowUpToday = $permit->dailyFollowUps()
            ->where('user_id', $user->id)
            ->where('follow_up_date', $today)
            ->exists();

        $isHseAssignedToSite = $permit->site->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'hse_officer')
            ->exists();

        $isSupervisorAssigned = $permit->supervisor_id === $user->id;

        $canFirstFollowUp = $user->role === Role::HseOfficer
            && $isHseAssignedToSite
            && $permit->status === PermitStatus::Approved
            && $permit->hasWorkSupervisorAccepted()
            && $supervisorFollowUpToday
            && ! $permit->hasFirstFollowUp();

        $canSupervisorRecordFollowUp = $user->role === Role::WorkSupervisor
            && in_array($permit->status, [PermitStatus::Approved, PermitStatus::DailyClosed])
            && $permit->hasWorkSupervisorAccepted()
            && $isSupervisorAssigned
            && ! $userFollowUpToday;

        $canHseRecordFollowUp = $user->role === Role::HseOfficer
            && in_array($permit->status, [PermitStatus::Active, PermitStatus::DailyClosed])
            && $isHseAssignedToSite
            && $supervisorFollowUpToday
            && ! $userFollowUpToday;

        $canRecordFollowUp = $canSupervisorRecordFollowUp || $canHseRecordFollowUp;

        $canSupervisorCloseDay = $user->role === Role::WorkSupervisor
            && in_array($permit->status, [PermitStatus::Active, PermitStatus::DailyClosed])
            && $permit->isWithinPermitPeriod()
            && $isSupervisorAssigned
            && $supervisorFollowUpToday
            && $hseFollowUpToday
            && ! $userClosedToday;

        $canHseCloseDay = $user->role === Role::HseOfficer
            && in_array($permit->status, [PermitStatus::Active, PermitStatus::DailyClosed])
            && $permit->isWithinPermitPeriod()
            && $isHseAssignedToSite
            && $supervisorClosedToday
            && ! $userClosedToday;

        $canCloseDay = $canSupervisorCloseDay || $canHseCloseDay;

        return [
            'today' => $today,
            'is_daily_closed' => $permit->isDailyClosed(),
            'is_last_day' => $permit->isLastDay(),
            'is_within_period' => $permit->isWithinPermitPeriod(),
            'supervisor_follow_up_today' => $supervisorFollowUpToday,
            'hse_follow_up_today' => $hseFollowUpToday,
            'supervisor_closed_today' => $supervisorClosedToday,
            'hse_closed_today' => $hseClosedToday,
            'user_closed_today' => $userClosedToday,
            'user_follow_up_today' => $userFollowUpToday,
            'can_first_follow_up' => $canFirstFollowUp,
            'can_record_follow_up' => $canRecordFollowUp,
            'can_supervisor_record_follow_up' => $canSupervisorRecordFollowUp,
            'can_hse_record_follow_up' => $canHseRecordFollowUp,
            'can_close_day' => $canCloseDay,
            'can_supervisor_close_day' => $canSupervisorCloseDay,
            'can_hse_close_day' => $canHseCloseDay,
            'work_supervisor_accepted' => $permit->hasWorkSupervisorAccepted(),
            'first_follow_up_done' => $permit->hasFirstFollowUp(),
            'is_hse_assigned' => $isHseAssignedToSite,
            'is_supervisor_assigned' => $isSupervisorAssigned,
            'follow_ups' => $permit->dailyFollowUps()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn ($followUp) => [
                    'id' => $followUp->id,
                    'user' => $followUp->user,
                    'date' => $followUp->follow_up_date->toDateString(),
                    'notes' => $followUp->notes,
                    'is_first' => $followUp->is_first_follow_up,
                    'created_at' => $followUp->created_at->toIso8601String(),
                ]),
            'closures' => $permit->dailyClosures()
                ->with('user')
                ->orderBy('closure_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(fn ($closure) => [
                    'id' => $closure->id,
                    'user' => $closure->user,
                    'date' => $closure->closure_date->toDateString(),
                    'closed_at' => $closure->closed_at->toIso8601String(),
                ]),
        ];
    }

    private function buildObservationData($user, WorkPermit $permit): array
    {
        $isHseAssigned = $permit->site->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'hse_officer')
            ->exists();

        $isEngineerOrSupervisor = $user->id === $permit->engineer_id ||
            $user->id === $permit->supervisor_id;

        $canCreateObservation = in_array($user->role, [Role::HseOfficer, Role::Consultant]) &&
            ($user->role !== Role::HseOfficer || $isHseAssigned) &&
            $permit->status === PermitStatus::Active;

        $canResolveObservation = function ($observation) use ($user) {
            return in_array($observation->status->value, ['open', 'in_progress']) &&
                ($user->id === $observation->workPermit->engineer_id ||
                    $user->id === $observation->workPermit->supervisor_id);
        };

        $canReviewObservation = function ($observation) use ($user, $isHseAssigned) {
            return $user->role === Role::HseOfficer &&
                $isHseAssigned &&
                $observation->status->value === 'resolved';
        };

        $canSuspend = $permit->canBeSuspendedBy($user);
        $canTerminate = $permit->canBeTerminatedBy($user);
        $canRequestResume = $permit->canRequestResume($user);
        $canApproveResume = $permit->canApproveResume($user);

        return [
            'can_create' => $canCreateObservation,
            'can_resolve' => $isEngineerOrSupervisor,
            'can_review' => $isHseAssigned,
            'can_suspend' => $canSuspend,
            'can_terminate' => $canTerminate,
            'can_request_resume' => $canRequestResume,
            'can_approve_resume' => $canApproveResume,
            'is_suspended' => $permit->status === PermitStatus::Suspended,
            'is_terminated' => $permit->status === PermitStatus::Terminated,
            'resumption_requested' => $permit->resumption_requested ?? false,
            'suspension_reason' => $permit->suspension_reason,
            'termination_reason' => $permit->termination_reason,
            'list' => $permit->observations()
                ->with(['creator', 'attachments'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($obs) => [
                    'id' => $obs->id,
                    'description' => $obs->description,
                    'status' => $obs->status,
                    'resolution_note' => $obs->resolution_note,
                    'rejection_reason' => $obs->rejection_reason,
                    'created_by' => $obs->creator,
                    'created_at' => $obs->created_at->toIso8601String(),
                    'resolved_at' => $obs->resolved_at?->toIso8601String(),
                    'closed_at' => $obs->closed_at?->toIso8601String(),
                    'attachments' => $obs->attachments->map(fn ($att) => [
                        'id' => $att->id,
                        'file_name' => $att->file_name,
                        'file_path' => $att->file_path,
                        'mime_type' => $att->mime_type,
                    ]),
                    'can_resolve' => $canResolveObservation($obs),
                    'can_review' => $canReviewObservation($obs),
                ]),
        ];
    }
}
