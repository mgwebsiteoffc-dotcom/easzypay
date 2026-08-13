<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'email',
        'password',
        'company_name',
        'phone',
        'website',
        'country',
        'timezone',
        'plan',
        'trial_ends_at',
        'subscription_ends_at',
        'is_active',
        'stripe_customer_id',
        'stripe_subscription_id',
        'billing_email',
        'settings',
        'logo_url',
        'primary_color',
        'total_stores',
        'total_orders',
        'total_revenue',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'trial_ends_at'        => 'datetime',
        'subscription_ends_at' => 'datetime',
        'is_active'            => 'boolean',
        'settings'             => 'array',
        'password'             => 'hashed',
    ];

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function checkoutSessions()
    {
        return $this->hasMany(CheckoutSession::class);
    }

    public function activeStores()
    {
        return $this->stores()
            ->where('is_installed', true)
            ->where('is_active', true);
    }

    public function isOnTrial(): bool
    {
        return $this->plan === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function isTrialExpired(): bool
    {
        return $this->plan === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isPast();
    }

    public function canAddStore(): bool
    {
        return true; // simplified for now
    }
}