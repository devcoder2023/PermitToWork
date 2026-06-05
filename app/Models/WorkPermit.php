<?php

namespace App\Models;

use App\Enums\PermitStatus;
use App\Enums\Role;
use Database\Factories\WorkPermitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @use HasFactory<WorkPermitFactory>
 */
class WorkPermit extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'permit_number',
        'permit_type_id',
        'project_id',
        'sub_contractor_id',
        'site_id',
        'engineer_id',
        'supervisor_id',
        'status',
        'location_area',
        'location_floor',
        'location_description',
        'work_description',
        'request_date',
        'start_date',
        'end_date',
        'shift',
        'rejection_reason',
        'suspension_reason',
        'resumption_requested',
        'resumption_note',
        'resumption_approved_by',
        'resumption_approved_at',
        'resumption_rejection_reason',
        'termination_reason',
        'terminated_by',
        'terminated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PermitStatus::class,
            'request_date' => 'date:Y-m-d',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'resumption_requested' => 'boolean',
            'resumption_approved_at' => 'datetime',
            'terminated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PermitType, WorkPermit>
     */
    public function permitType(): BelongsTo
    {
        return $this->belongsTo(PermitType::class);
    }

    /**
     * @return BelongsTo<Project, WorkPermit>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<SubContractor, WorkPermit>
     */
    public function subContractor(): BelongsTo
    {
        return $this->belongsTo(SubContractor::class);
    }

    /**
     * @return BelongsTo<Site, WorkPermit>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<User, WorkPermit>
     */
    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }

    /**
     * @return BelongsTo<User, WorkPermit>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * @return HasMany<ApprovalRecord, WorkPermit>
     */
    public function approvalRecords(): HasMany
    {
        return $this->hasMany(ApprovalRecord::class);
    }

    /**
     * @return HasMany<PermitAttachment, WorkPermit>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(PermitAttachment::class);
    }

    /**
     * @return HasMany<DailyClosure, WorkPermit>
     */
    public function dailyClosures(): HasMany
    {
        return $this->hasMany(DailyClosure::class);
    }

    /**
     * @return HasMany<DailyFollowUp, WorkPermit>
     */
    public function dailyFollowUps(): HasMany
    {
        return $this->hasMany(DailyFollowUp::class);
    }

    /**
     * @return HasMany<Observation, WorkPermit>
     */
    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [PermitStatus::New, PermitStatus::Rejected]);
    }

    public function isCancellable(): bool
    {
        return $this->status === PermitStatus::New;
    }

    public function isApproved(): bool
    {
        return $this->status === PermitStatus::Approved;
    }

    public function isActive(): bool
    {
        return $this->status === PermitStatus::Active;
    }

    public function isDailyClosed(): bool
    {
        return $this->status === PermitStatus::DailyClosed;
    }

    public function isExpired(): bool
    {
        return $this->status === PermitStatus::Expired;
    }

    public function isArchived(): bool
    {
        return $this->status === PermitStatus::Archived;
    }

    public function isSuspended(): bool
    {
        return $this->status === PermitStatus::Suspended;
    }

    public function isTerminated(): bool
    {
        return $this->status === PermitStatus::Terminated;
    }

    public function hasWorkSupervisorAccepted(): bool
    {
        return $this->approvalRecords()
            ->where('stage', 'work_supervisor')
            ->where('decision', 'approved')
            ->exists();
    }

    public function hasWorkSupervisorFollowUp(): bool
    {
        return $this->dailyFollowUps()
            ->where('user_id', $this->supervisor_id)
            ->exists();
    }

    public function hasHseFirstFollowUp(): bool
    {
        return $this->dailyFollowUps()
            ->where('is_first_follow_up', true)
            ->exists();
    }

    public function hasFirstFollowUp(): bool
    {
        return $this->dailyFollowUps()
            ->where('is_first_follow_up', true)
            ->exists();
    }

    public function hasSupervisorFollowUpToday(): bool
    {
        return $this->dailyFollowUps()
            ->where('user_id', $this->supervisor_id)
            ->where('follow_up_date', today())
            ->exists();
    }

    public function hasHseFollowUpToday(): bool
    {
        $hseOfficers = $this->site->users()->wherePivot('role', 'hse_officer')->pluck('users.id');

        return $this->dailyFollowUps()
            ->whereIn('user_id', $hseOfficers)
            ->where('follow_up_date', today())
            ->exists();
    }

    public function hasSupervisorClosureToday(): bool
    {
        return $this->dailyClosures()
            ->where('user_id', $this->supervisor_id)
            ->where('closure_date', today())
            ->exists();
    }

    public function hasHseClosureToday(): bool
    {
        $hseOfficers = $this->site->users()->wherePivot('role', 'hse_officer')->pluck('users.id');

        return $this->dailyClosures()
            ->whereIn('user_id', $hseOfficers)
            ->where('closure_date', today())
            ->exists();
    }

    public function isLastDay(): bool
    {
        $today = today();

        return $today->isSameDay($this->end_date);
    }

    public function isWithinPermitPeriod(): bool
    {
        $today = today();

        return $today->greaterThanOrEqualTo($this->start_date) &&
            $today->lessThanOrEqualTo($this->end_date);
    }

    public function canBeSuspendedBy(User $user): bool
    {
        if (! in_array($user->role, [Role::HseOfficer, Role::Consultant])) {
            return false;
        }

        if ($this->status !== PermitStatus::Active) {
            return false;
        }

        if ($user->role === Role::HseOfficer) {
            return $this->site->users()
                ->where('users.id', $user->id)
                ->wherePivot('role', 'hse_officer')
                ->exists();
        }

        return true;
    }

    public function canBeTerminatedBy(User $user): bool
    {
        if (! in_array($user->role, [Role::HseOfficer, Role::Consultant])) {
            return false;
        }

        if (! in_array($this->status, [PermitStatus::Active, PermitStatus::Suspended])) {
            return false;
        }

        if ($user->role === Role::HseOfficer) {
            return $this->site->users()
                ->where('users.id', $user->id)
                ->wherePivot('role', 'hse_officer')
                ->exists();
        }

        return true;
    }

    public function canRequestResume(User $user): bool
    {
        if ($this->status !== PermitStatus::Suspended) {
            return false;
        }

        return $user->id === $this->engineer_id || $user->id === $this->supervisor_id;
    }

    public function canApproveResume(User $user): bool
    {
        if ($user->role !== Role::HseOfficer) {
            return false;
        }

        if ($this->status !== PermitStatus::Suspended) {
            return false;
        }

        return $this->site->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'hse_officer')
            ->exists();
    }
}
