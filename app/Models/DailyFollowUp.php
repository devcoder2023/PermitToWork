<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyFollowUp extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'work_permit_id',
        'user_id',
        'follow_up_date',
        'notes',
        'is_first_follow_up',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date:Y-m-d',
            'is_first_follow_up' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<WorkPermit, DailyFollowUp>
     */
    public function workPermit(): BelongsTo
    {
        return $this->belongsTo(WorkPermit::class);
    }

    /**
     * @return BelongsTo<User, DailyFollowUp>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
