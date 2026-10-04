<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'managed_team_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Determine whether this account may use the shared intramurals login.
     */
    public function canUseIntramuralsLogin(): bool
    {
        return $this->status === 'active'
            && in_array($this->role, ['admin', 'gam', 'tabulator', 'coordinator'], true);
    }

    public function managedTeam()
    {
        return $this->belongsTo(Team::class, 'managed_team_id');
    }

    /** @return HasMany<CoordinatorAssignment> */
    public function coordinatorAssignments(): HasMany
    {
        return $this->hasMany(CoordinatorAssignment::class, 'coordinator_id');
    }

    /** @return HasMany<CoordinatorRequest> */
    public function coordinatorRequests(): HasMany
    {
        return $this->hasMany(CoordinatorRequest::class, 'coordinator_id');
    }

    /** @return HasMany<CoordinatorDevice> */
    public function coordinatorDevices(): HasMany
    {
        return $this->hasMany(CoordinatorDevice::class);
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];
}
