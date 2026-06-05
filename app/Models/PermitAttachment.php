<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermitAttachment extends Model
{
    protected $fillable = [
        'work_permit_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function workPermit(): BelongsTo
    {
        return $this->belongsTo(WorkPermit::class);
    }
}
