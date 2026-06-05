<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @use HasFactory<SiteFactory>
 */
class Site extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * @return BelongsTo<Project, Site>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Site Managers and HSE Officers assigned to this site.
     *
     * @return BelongsToMany<User, Site>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'site_user')
            ->withPivot('role')
            ->withTimestamps();
    }
}
