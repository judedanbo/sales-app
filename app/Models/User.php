<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use AuditableTrait, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'school_id',
        'phone',
        'department',
        'bio',
        'is_active',
        'last_login_at',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'user_type' => UserType::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Audit configuration
     */
    protected $auditExclude = [
        'password',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * Relationships
     */

    /**
     * Get the school that the user belongs to (for school-specific users).
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the sales records where this user was the cashier.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    /**
     * Get the school official record if this user is a school official.
     */
    public function schoolOfficial(): HasOne
    {
        return $this->hasOne(SchoolOfficial::class);
    }

    /**
     * Scopes
     */

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter users by type.
     */
    public function scopeOfType($query, UserType $type)
    {
        return $query->where('user_type', $type);
    }

    /**
     * Scope a query to filter users by school.
     */
    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Helper methods
     */

    /**
     * Check if user is a school-specific user.
     */
    public function isSchoolUser(): bool
    {
        return in_array($this->user_type, [
            UserType::SCHOOL_ADMIN,
            UserType::PRINCIPAL,
            UserType::TEACHER,
        ]);
    }

    /**
     * Check if user is a system-wide user.
     */
    public function isSystemUser(): bool
    {
        return in_array($this->user_type, [
            UserType::STAFF,
            UserType::ADMIN,
            UserType::AUDIT,
            UserType::SYSTEM_ADMIN,
        ]);
    }

    /**
     * Get the user's full display name with title.
     */
    public function getDisplayNameAttribute(): string
    {
        $title = match ($this->user_type) {
            UserType::PRINCIPAL => 'Principal',
            UserType::TEACHER => 'Teacher',
            UserType::ADMIN, UserType::SCHOOL_ADMIN, UserType::SYSTEM_ADMIN => 'Admin',
            UserType::AUDIT => 'Auditor',
            default => ''
        };

        return $title ? "{$title} {$this->name}" : $this->name;
    }

    /**
     * Update the last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Assign role based on user type.
     */
    public function assignRoleFromUserType(): void
    {
        $roleName = $this->user_type->value;

        if (! $this->hasRole($roleName)) {
            $this->assignRole($roleName);
        }
    }

    /**
     * Check if user can manage schools.
     */
    public function canManageSchools(): bool
    {
        return $this->hasAnyPermission([
            'create_schools',
            'edit_schools',
            'delete_schools',
            'manage_school_officials',
        ]);
    }

    /**
     * Check if user can manage users.
     */
    public function canManageUsers(): bool
    {
        return $this->hasAnyPermission([
            'create_users',
            'edit_users',
            'delete_users',
        ]);
    }

    // public function audits():HasMany
    // {
    //     return $this->hasMany(Audit::class);
    // }
}
