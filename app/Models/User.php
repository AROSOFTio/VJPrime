<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    public const ROLE_CONTENT_MANAGER = 'content_manager';

    public const ROLE_CONTRIBUTOR = 'contributor';

    public const ROLE_FINANCE_MANAGER = 'finance_manager';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_USER,
        self::ROLE_CONTENT_MANAGER,
        self::ROLE_CONTRIBUTOR,
        self::ROLE_FINANCE_MANAGER,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'google_id',
        'role',
        'subscription_status',
        'subscription_expires_at',
        'daily_free_seconds_used',
        'last_reset_at',
        'device_hash',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
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
            'last_reset_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteMovies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'favorites')->withPivot('created_at');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function watchProgress(): HasMany
    {
        return $this->hasMany(WatchProgress::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(View::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canAccessAdminPanel(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_CONTENT_MANAGER,
            self::ROLE_CONTRIBUTOR,
            self::ROLE_FINANCE_MANAGER,
        ], true);
    }

    public function canManageUsers(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canManageContent(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_CONTENT_MANAGER,
            self::ROLE_CONTRIBUTOR,
        ], true);
    }

    public function canDeleteContent(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_CONTENT_MANAGER,
        ], true);
    }

    public function canViewReports(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_FINANCE_MANAGER,
        ], true);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_CONTENT_MANAGER => 'Content Manager',
            self::ROLE_CONTRIBUTOR => 'Contributor',
            self::ROLE_FINANCE_MANAGER => 'Finance Manager',
            default => 'Viewer / Customer',
        };
    }

    public function isPremium(): bool
    {
        if ($this->subscription_status !== 'premium') {
            return false;
        }

        return ! $this->subscription_expires_at || $this->subscription_expires_at->isFuture();
    }
}
