<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Role constants
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_DATA_ADMIN = 'data_admin';
    public const ROLE_FORECAST_ADMIN = 'forecast_admin';
    public const ROLE_CONTENT_ADMIN = 'content_admin';
    public const ROLE_SUPPORT_ADMIN = 'support_admin';
    public const ROLE_FARMER = 'farmer';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'preferred_language',
        'district_id',
        'password',
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
        ];
    }

    /**
     * Check if user has administrative privileges.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_DATA_ADMIN,
            self::ROLE_FORECAST_ADMIN,
            self::ROLE_CONTENT_ADMIN,
            self::ROLE_SUPPORT_ADMIN,
        ], true);
    }

    /**
     * Check if user is Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user has a specific role or any in an array of roles.
     */
    public function hasRole(string|array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (is_array($roles)) {
            return in_array($this->role, $roles, true);
        }

        return $this->role === $roles;
    }

    /**
     * User's assigned or preferred district.
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
