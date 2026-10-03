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
        'is_active',
        'last_login_at',
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
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get human-readable title for role.
     */
    public function getRoleTitle(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super Administrator',
            self::ROLE_DATA_ADMIN => 'Data & Mandi Administrator',
            self::ROLE_CONTENT_ADMIN => 'Agricultural Content Editor',
            self::ROLE_SUPPORT_ADMIN => 'Farmer Support Officer',
            self::ROLE_FORECAST_ADMIN => 'Price & Market Analyst',
            self::ROLE_FARMER => 'Farmer',
            default => ucfirst(str_replace('_', ' ', $this->role ?? 'User')),
        };
    }

    /**
     * Get Tailwind badge classes for role.
     */
    public function getRoleBadgeClass(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'bg-rose-950/80 text-rose-300 border-rose-700/60',
            self::ROLE_DATA_ADMIN => 'bg-emerald-950/80 text-emerald-300 border-emerald-700/60',
            self::ROLE_CONTENT_ADMIN => 'bg-amber-950/80 text-amber-300 border-amber-700/60',
            self::ROLE_SUPPORT_ADMIN => 'bg-cyan-950/80 text-cyan-300 border-cyan-700/60',
            self::ROLE_FORECAST_ADMIN => 'bg-purple-950/80 text-purple-300 border-purple-700/60',
            default => 'bg-slate-800 text-slate-300 border-slate-700',
        };
    }

    /**
     * All available administrative roles for selection.
     */
    public static function getAdminRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Administrator (Full Unrestricted Access)',
            self::ROLE_DATA_ADMIN => 'Data & Mandi Administrator (Prices, Mandis, Ingestion)',
            self::ROLE_CONTENT_ADMIN => 'Agricultural Content Editor (Schemes, News, Videos, Menus)',
            self::ROLE_SUPPORT_ADMIN => 'Farmer Support Officer (Helpdesk, Inquiries, Notes)',
            self::ROLE_FORECAST_ADMIN => 'Price & Market Analyst (Prices, Freshness, Weather, Logs)',
        ];
    }

    /**
     * Check if user can access a specific administrative module.
     */
    public function canAccessModule(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return match ($module) {
            'data' => in_array($this->role, [self::ROLE_DATA_ADMIN], true),
            'content' => in_array($this->role, [self::ROLE_CONTENT_ADMIN], true),
            'support' => in_array($this->role, [self::ROLE_SUPPORT_ADMIN], true),
            'forecast' => in_array($this->role, [self::ROLE_FORECAST_ADMIN, self::ROLE_DATA_ADMIN], true),
            'prices' => in_array($this->role, [self::ROLE_DATA_ADMIN, self::ROLE_FORECAST_ADMIN], true),
            'settings' => false, // Only super admin
            'users' => false,    // Only super admin
            default => false,
        };
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
