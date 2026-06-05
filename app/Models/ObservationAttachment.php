<?php

namespace App\Models;

use Database\Factories\ObservationAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @use HasFactory<ObservationAttachmentFactory>
 */
class ObservationAttachment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'observation_id',
        'file_path',
        'file_name',
        'mime_type',
    ];

    /**
     * @return BelongsTo<Observation, ObservationAttachment>
     */
    public function observation(): BelongsTo
    {
        return $this->belongsTo(Observation::class);
    }
}
