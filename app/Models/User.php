<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @use HasFactory<UserFactory>
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'project_id',
        'sub_contractor_id',
        'phone',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /**
     * @return BelongsTo<Project, User>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<SubContractor, User>
     */
    public function subContractor(): BelongsTo
    {
        return $this->belongsTo(SubContractor::class);
    }

    /**
     * Sites this user is assigned to (as Site Manager or HSE Officer).
     *
     * @return BelongsToMany<Site, User>
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'site_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<WorkPermit, User>
     */
    public function supervisedPermits(): HasMany
    {
        return $this->hasMany(WorkPermit::class, 'supervisor_id');
    }

    /**
     * @return HasMany<WorkPermit, User>
     */
    public function engineeredPermits(): HasMany
    {
        return $this->hasMany(WorkPermit::class, 'engineer_id');
    }

    /**
     * Check if user belongs to main contractor (no sub_contractor_id).
     */
    public function isMainContractor(): bool
    {
        return $this->sub_contractor_id === null;
    }

    /**
     * Check if user has access to all projects.
     */
    public function hasGlobalAccess(): bool
    {
        return in_array($this->role, [Role::SystemAdmin, Role::QaInspector]);
    }
}
