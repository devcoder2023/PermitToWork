<?php

namespace App\Models;

use App\Enums\ObservationStatus;
use Database\Factories\ObservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @use HasFactory<ObservationFactory>
 */
class Observation extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'work_permit_id',
        'created_by',
        'description',
        'status',
        'resolution_note',
        'rejection_reason',
        'resolved_at',
        'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ObservationStatus::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WorkPermit, Observation>
     */
    public function workPermit(): BelongsTo
    {
        return $this->belongsTo(WorkPermit::class);
    }

    /**
     * @return BelongsTo<User, Observation>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ObservationAttachment, Observation>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ObservationAttachment::class);
    }

    public function isOpen(): bool
    {
        return $this->status === ObservationStatus::Open;
    }

    public function isInProgress(): bool
    {
        return $this->status === ObservationStatus::InProgress;
    }

    public function isResolved(): bool
    {
        return $this->status === ObservationStatus::Resolved;
    }

    public function isClosed(): bool
    {
        return $this->status === ObservationStatus::Closed;
    }

    public function isRejected(): bool
    {
        return $this->status === ObservationStatus::Rejected;
    }

    public function canBeResolvedBy(User $user): bool
    {
        return in_array($this->status, [ObservationStatus::Open, ObservationStatus::InProgress]) &&
            ($user->id === $this->workPermit->engineer_id || $user->id === $this->workPermit->supervisor_id);
    }

    public function canBeReviewedBy(User $user): bool
    {
        return $this->status === ObservationStatus::Resolved &&
            $this->workPermit->site->users()->wherePivot('role', 'hse_officer')->exists();
    }
}
