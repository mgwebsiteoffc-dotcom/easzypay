<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price_monthly', 'price_yearly',
        'transaction_fee', 'max_stores', 'max_orders_per_month',
        'features', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'features'         => 'array',
        'price_monthly'    => 'decimal:2',
        'price_yearly'     => 'decimal:2',
        'transaction_fee'  => 'decimal:4',
        'is_active'        => 'boolean',
    ];

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function getTransactionFeePercent(): string
    {
        return ($this->transaction_fee * 100) . '%';
    }
}