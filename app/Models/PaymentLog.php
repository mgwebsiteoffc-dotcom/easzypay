<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $fillable = [
        'session_id', 'stripe_payment_intent_id', 'stripe_charge_id',
        'shopify_order_id', 'event_type', 'status', 'amount', 'currency',
        'payment_method_type', 'card_brand', 'card_last4', 'wallet_type',
        'card_country', 'stripe_data', 'shopify_data', 'error_message', 'ip_address',
    ];

    protected $casts = [
        'stripe_data'  => 'array',
        'shopify_data' => 'array',
    ];
}