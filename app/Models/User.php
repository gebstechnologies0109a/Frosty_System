<?php

namespace App\Models;

use App\Enums\PriceRegion;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'first_name',
    'last_name',
    'profile_photo_path',
    'email',
    'password',
    'role',
    'sponsor_id',
    'genealogy_level',
    'genealogy_path',
    'distributor_id',
    'status',
    'region',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'region' => PriceRegion::class,
            'genealogy_level' => 'integer',
        ];
    }

    public function priceRegion(): PriceRegion
    {
        return $this->region ?? PriceRegion::Luzon;
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'sponsor_id');
    }

    public function assignedDistributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class, 'distributor_id');
    }

    public function distributorProfile(): HasOne
    {
        return $this->hasOne(Distributor::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class);
    }

    public function pointsLedger(): HasMany
    {
        return $this->hasMany(PointsLedger::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function operatorInventory(): HasMany
    {
        return $this->hasMany(OperatorInventory::class, 'operator_id');
    }

    public function operatorProducts(): HasMany
    {
        return $this->hasMany(OperatorProduct::class, 'operator_id');
    }

    public function isOperator(): bool
    {
        return $this->role === UserRole::Operator;
    }

    public function isDistributor(): bool
    {
        return $this->role === UserRole::Distributor;
    }

    public function isAdmin(): bool
    {
        return $this->role?->isAdmin() ?? false;
    }

    public function earnsRebates(): bool
    {
        return $this->isOperator();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function displayName(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : (string) $this->name;
    }

    public function initials(): string
    {
        $first = Str::substr($this->first_name ?? $this->name, 0, 1);
        $last = Str::substr($this->last_name ?? '', 0, 1);

        return strtoupper($first.$last) ?: '?';
    }

    public function profilePhotoUrl(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->profile_photo_path);
    }

    public static function fullNameFromParts(string $firstName, string $lastName = ''): string
    {
        return trim($firstName.' '.$lastName);
    }
}
