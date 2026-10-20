<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'admin_subrole', 'phone', 'address', 'avatar',
        'is_blocked', 'blocked_reason', 'deleted_reason', 'last_seen_at', 'two_factor_enabled',
        'email_verified_at', 'verification_code', 'verification_code_expires_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'             => 'datetime',
            'verification_code_expires_at'  => 'datetime',
            'last_seen_at'                  => 'datetime',
            'password'                      => 'hashed',
            'is_blocked'                    => 'boolean',
            'two_factor_enabled'            => 'boolean',
        ];
    }

    public function generateVerificationCode(): string
    {
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(30),
        ]);
        return $code;
    }

    public function verifyCode(string $code): bool
    {
        if (empty($this->verification_code) || empty($code)) {
            return false;
        }

        if ($this->verification_code_expires_at && $this->verification_code_expires_at->isPast()) {
            return false;
        }

        if (trim($this->verification_code) !== trim($code)) {
            return false;
        }

        $this->update([
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        return true;
    }

    // role: consumer | merchant | analyst
    public function isConsumer(): bool { return $this->role === 'consumer'; }
    public function isMerchant(): bool { return $this->role === 'merchant'; }
    public function isAnalyst(): bool  { return $this->role === 'analyst'; }

    // Granular analyst subroles: super_admin | analyst_readonly | support
    public function isSuperAdmin(): bool { return $this->isAnalyst() && ($this->admin_subrole ?? 'super_admin') === 'super_admin'; }
    public function isSupportAdmin(): bool { return $this->isAnalyst() && in_array($this->admin_subrole ?? 'super_admin', ['super_admin', 'support'], true); }
    public function isReadOnlyAnalyst(): bool { return $this->isAnalyst() && ($this->admin_subrole ?? 'super_admin') === 'analyst_readonly'; }

    public function adminSubroleLabel(): string
    {
        if (!$this->isAnalyst()) {
            return ucfirst($this->role);
        }
        return match ($this->admin_subrole ?? 'super_admin') {
            'analyst_readonly' => 'Analista (Lectura)',
            'support'          => 'Soporte (PQRS & KYC)',
            default            => 'Super Admin',
        };
    }

    public function hasVerifiedEmail()
    {
        if ($this->isAnalyst()) {
            return true;
        }
        return ! is_null($this->email_verified_at);
    }

    public function avatarUrl(): ?string
    {
        if (!$this->avatar) {
            return null;
        }
        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }
        return asset('storage/' . ltrim($this->avatar, '/'));
    }

    public function products() { return $this->hasMany(Product::class, 'merchant_id'); }
    public function orders()   { return $this->hasMany(Order::class); }
    public function cart()     { return $this->hasOne(Cart::class); }
    public function companyProfile() { return $this->hasOne(CompanyProfile::class, 'merchant_id'); }
    public function shippingAddresses() { return $this->hasMany(ShippingAddress::class); }
    public function loyaltyPoints() { return $this->hasMany(LoyaltyPoint::class); }
    public function totalPoints(): int { return (int) $this->loyaltyPoints()->sum('points'); }
}
