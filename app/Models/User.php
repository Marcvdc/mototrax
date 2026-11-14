<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\MotorType;
use App\Services\ProfileService;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'location', 'motor_type', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
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
            'is_admin' => 'boolean',
            'motor_type' => MotorType::class,
        ];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function bikes(): HasMany
    {
        return $this->hasMany(Bike::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function getTotalKmAttribute(): int
    {
        return $this->bikes()->sum('km_current');
    }

    public function getBikesCountAttribute(): int
    {
        return $this->bikes()->count();
    }

    public function getRoutesCountAttribute(): int
    {
        return $this->routes()->count();
    }

    public function getMaintenanceLogsCountAttribute(): int
    {
        return $this->maintenanceLogs()->count();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar === null) {
            return null;
        }

        return Storage::disk(ProfileService::DISK)->url($this->avatar);
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', (string) $this->name))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
