<?php

namespace App\Models;

use App\Enums\DurationType;
use Database\Factories\PermitTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @use HasFactory<PermitTypeFactory>
 */
class PermitType extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_en',
        'name_ar',
        'duration_type',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_type' => DurationType::class,
            'is_active' => 'boolean',
        ];
    }
}
