<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutSession extends Model
{
    protected $table = 'checkout_sessions';

   protected $fillable = [
    'tenant_id',
    'store_id',
    'session_id',
    'shop_domain',
    'cart_token',
    'items',
    'currency',
    'subtotal',
    'shipping_amount',
    'tax_amount',
    'total_amount',
    'charged_amount',
    'charged_currency',
    'detected_currency',
    'exchange_rate',
    'discount_code',
    'discount_percent',
    'discount_amount',
    'customer_email',
    'customer_phone',
    'customer_first_name',
    'customer_last_name',
    'shipping_address1',
    'shipping_address2',
    'shipping_city',
    'shipping_state',
    'shipping_zip',
    'shipping_country',
    'stripe_payment_intent_id',
    'stripe_client_secret',
    'stripe_charge_id',
    'stripe_customer_id',
    'shopify_order_id',
    'shopify_order_number',
    'shopify_order_gid',
    'shopify_thank_you_url',
    'payment_method_type',
    'card_brand',
    'card_last4',
    'wallet_type',
    'card_country',
    'status',
    'ip_address',
    'user_agent',
    'country_code',
    'referrer',
    'paid_at',
    'expires_at',
];
    protected $casts = [
        'items'           => 'array',
        'expires_at'      => 'datetime',
        'paid_at'         => 'datetime',
        'exchange_rate'   => 'decimal:6',
        'discount_percent'=> 'integer',
        'discount_amount' => 'integer',
    ];

    // ================================================================
    // Scopes
    // ================================================================
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
                     ->whereNotIn('status', ['expired', 'failed']);
    }

    // ================================================================
    // Relationships
    // ================================================================
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function logs()
    {
        return $this->hasMany(PaymentLog::class, 'session_id', 'session_id');
    }

    // ================================================================
    // Helpers
    // ================================================================
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return $this->formatMoney($this->subtotal, $this->currency);
    }

    public function getFormattedTotalAttribute(): string
    {
        $amount   = $this->charged_amount ?: $this->total_amount ?: $this->subtotal;
        $currency = $this->charged_currency ?? $this->currency ?? 'USD';
        return $this->formatMoney($amount, $currency);
    }

    private function formatMoney(int $cents, string $currency): string
    {
        $symbols = [
            'USD' => '$',   'GBP' => '£',   'EUR' => '€',
            'AUD' => 'A$',  'CAD' => 'CA$', 'SGD' => 'S$',
            'AED' => 'AED ','INR' => '₹',   'JPY' => '¥',
            'NZD' => 'NZ$', 'HKD' => 'HK$', 'SEK' => 'kr',
            'NOK' => 'kr',  'DKK' => 'kr',  'CHF' => 'CHF ',
        ];
        $symbol = $symbols[strtoupper($currency)] ?? (strtoupper($currency) . ' ');
        return $symbol . number_format($cents / 100, 2);
    }
}