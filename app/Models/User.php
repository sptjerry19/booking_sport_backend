<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'level',
        'preferred_sports',
        'preferred_position',
        'avatar',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'preferred_sports' => 'array',
        'preferred_position' => 'array',
        'password' => 'hashed',
    ];

    /**
     * Relationship với DeviceToken
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Lấy các device token đang active
     */
    public function activeDeviceTokens(): HasMany
    {
        return $this->deviceTokens()->active();
    }

    /**
     * Thêm hoặc cập nhật device token
     */
    public function addDeviceToken(string $token, string $deviceType = null, string $deviceName = null): DeviceToken
    {
        return $this->deviceTokens()->updateOrCreate(
            ['token' => $token],
            [
                'device_type' => $deviceType,
                'device_name' => $deviceName,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Xóa device token
     */
    public function removeDeviceToken(string $token): bool
    {
        return $this->deviceTokens()->where('token', $token)->delete();
    }

    /**
     * Relationship với Booking (placeholder)
     */
    public function bookings()
    {
        // Placeholder - chưa có Booking model
        return collect([]);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
