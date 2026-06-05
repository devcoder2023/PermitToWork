<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'work_permit_id',
        'user_id',
        'stage',
        'decision',
        'reason',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => ApprovalStage::class,
            'decision' => ApprovalDecision::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $record) {
            $record->created_at = $record->created_at ?? now();
        });
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            return false;
        }

        return parent::save($options);
    }

    public function delete(): bool
    {
        return false;
    }

    /**
     * @return BelongsTo<WorkPermit, ApprovalRecord>
     */
    public function workPermit(): BelongsTo
    {
        return $this->belongsTo(WorkPermit::class);
    }

    /**
     * @return BelongsTo<User, ApprovalRecord>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
