<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public const ROLE_MEAL_OFFICER = 'MEAL_OFFICER';
    public const ROLE_PROJECT_OFFICER = 'PROJECT_OFFICER';
    public const ROLE_AUDITOR = 'AUDITOR';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'roles',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Determine whether the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) ($this->is_active ?? true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function hasRole(string $role): bool
    {
        if (empty($this->roles)) {
            return false;
        }

        return in_array($role, (array) $this->roles, true);
    }

    public function isMealOfficer(): bool
    {
        return $this->hasRole(self::ROLE_MEAL_OFFICER);
    }

    public function isProjectOfficer(): bool
    {
        return $this->hasRole(self::ROLE_PROJECT_OFFICER);
    }

    public function isAuditor(): bool
    {
        return $this->hasRole(self::ROLE_AUDITOR);
    }

    public function managedProjects()
    {
        return $this->hasMany(Project::class, 'project_officer_id');
    }

    public function assignedAudits()
    {
        return $this->hasMany(Audit::class, 'auditor_id');
    }
}
