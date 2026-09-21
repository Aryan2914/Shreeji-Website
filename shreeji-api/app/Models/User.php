<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'account_type',
        'is_active',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'account_type' => AccountType::class,
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function defaultAddress()
    {
        return $this->addresses()->where('is_default', true)->first();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBusiness($query)
    {
        return $query->where('account_type', AccountType::BUSINESS);
    }

    public function scopePersonal($query)
    {
        return $query->where('account_type', AccountType::PERSONAL);
    }

    // ── Helpers ────────────────────────────────────────

    public function isBusiness(): bool
    {
        return $this->account_type === AccountType::BUSINESS;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Get the price tier for this user (retail for personal, or from business profile).
     */
    public function getPriceTier(): string
    {
        if ($this->isBusiness() && $this->businessProfile) {
            return $this->businessProfile->price_tier->value;
        }

        return 'retail';
    }
}
