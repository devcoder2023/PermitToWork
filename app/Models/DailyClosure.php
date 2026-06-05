<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyClosure extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'work_permit_id',
        'user_id',
        'closure_date',
        'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closure_date' => 'date:Y-m-d',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WorkPermit, DailyClosure>
     */
    public function workPermit(): BelongsTo
    {
        return $this->belongsTo(WorkPermit::class);
    }

    /**
     * @return BelongsTo<User, DailyClosure>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
