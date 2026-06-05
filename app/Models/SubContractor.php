<?php

namespace App\Models;

use Database\Factories\SubContractorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @use HasFactory<SubContractorFactory>
 */
class SubContractor extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'contact_email',
        'contact_phone',
        'address',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<User, SubContractor>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return BelongsToMany<Project, SubContractor>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_sub_contractor');
    }
}
