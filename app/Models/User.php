<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User roles.
 * - admin  : full access.
 * - fieldworker : ب‌avens restricted to their own records and their notifications.
 */
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_FIELDWORKER = 'fieldworker';

    public const ROLE_USER = 'user';

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'menu_abilities',
        'photo',
    ];

    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function fieldworker(): HasOne
    {
        return $this->hasOne(Fieldworker::class);
    }

    public function alerts(): HasOne
    {
        return $this->hasOne(Alert::class, 'notified_user_id');
    }

    /**
     * Notifications targeted to this user (alerts where notified_user_id = this).
     *
     * @return HasOne<Alert, $this>
     */
    public function notifications(): HasOne
    {
        return $this->hasOne(Alert::class, 'notified_user_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'menu_abilities' => 'array',
        ];
    }

    // ─── Roles ───────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isFieldworker(): bool
    {
        return $this->role === self::ROLE_FIELDWORKER;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * @return array<int, string>
     */
    public function menuAbilities(): array
    {
        $abilities = $this->menu_abilities;

        if (! is_array($abilities)) {
            return [];
        }

        return array_values(array_filter($abilities, static fn (mixed $ability): bool => is_string($ability) && $ability !== ''));
    }

    public function canAccessMenu(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isFieldworker()) {
            return false;
        }

        return in_array($permission, $this->menuAbilities(), true);
    }

    // ─── Query scopes ─────────────────────────

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeFieldworkers(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_FIELDWORKER);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeSuperAdmins(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_SUPER_ADMIN);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeUsers(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_USER);
    }

    /**
     * Find user by email OR username (used for login by username).
     */
    public function findForLogin(string $identifier): ?self
    {
        return self::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();
    }
}
