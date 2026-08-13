<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {

            // Add shop_domain if missing
            if (!Schema::hasColumn('checkout_sessions', 'shop_domain')) {
                $table->string('shop_domain')->nullable()->after('session_id');
            }

            // Add tenant_id if missing
            if (!Schema::hasColumn('checkout_sessions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }

            // Add store_id if missing
            if (!Schema::hasColumn('checkout_sessions', 'store_id')) {
                $table->unsignedBigInteger('store_id')->nullable()->after('tenant_id');
            }

            // Add charged_amount if missing
            if (!Schema::hasColumn('checkout_sessions', 'charged_amount')) {
                $table->unsignedBigInteger('charged_amount')->default(0)->after('total_amount');
            }

            // Add charged_currency if missing
            if (!Schema::hasColumn('checkout_sessions', 'charged_currency')) {
                $table->string('charged_currency', 3)->nullable()->after('charged_amount');
            }

            // Add detected_currency if missing
            if (!Schema::hasColumn('checkout_sessions', 'detected_currency')) {
                $table->string('detected_currency', 3)->nullable()->after('charged_currency');
            }

            // Add exchange_rate if missing
            if (!Schema::hasColumn('checkout_sessions', 'exchange_rate')) {
                $table->decimal('exchange_rate', 10, 6)->default(1.000000)->after('detected_currency');
            }

            // Add customer fields if missing
            if (!Schema::hasColumn('checkout_sessions', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('exchange_rate');
            }

            if (!Schema::hasColumn('checkout_sessions', 'customer_phone')) {
                $table->string('customer_phone')->nullable()->after('customer_email');
            }

            if (!Schema::hasColumn('checkout_sessions', 'customer_first_name')) {
                $table->string('customer_first_name')->nullable()->after('customer_phone');
            }

            if (!Schema::hasColumn('checkout_sessions', 'customer_last_name')) {
                $table->string('customer_last_name')->nullable()->after('customer_first_name');
            }

            // Add shipping fields if missing
            if (!Schema::hasColumn('checkout_sessions', 'shipping_address1')) {
                $table->string('shipping_address1')->nullable()->after('customer_last_name');
            }

            if (!Schema::hasColumn('checkout_sessions', 'shipping_address2')) {
                $table->string('shipping_address2')->nullable()->after('shipping_address1');
            }

            if (!Schema::hasColumn('checkout_sessions', 'shipping_city')) {
                $table->string('shipping_city')->nullable()->after('shipping_address2');
            }

            if (!Schema::hasColumn('checkout_sessions', 'shipping_state')) {
                $table->string('shipping_state')->nullable()->after('shipping_city');
            }

            if (!Schema::hasColumn('checkout_sessions', 'shipping_zip')) {
                $table->string('shipping_zip')->nullable()->after('shipping_state');
            }

            if (!Schema::hasColumn('checkout_sessions', 'shipping_country')) {
                $table->string('shipping_country', 2)->nullable()->after('shipping_zip');
            }

            // Add Stripe fields if missing
            if (!Schema::hasColumn('checkout_sessions', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id')->nullable()->after('shipping_country');
            }

            if (!Schema::hasColumn('checkout_sessions', 'stripe_client_secret')) {
                $table->string('stripe_client_secret')->nullable()->after('stripe_payment_intent_id');
            }

            if (!Schema::hasColumn('checkout_sessions', 'stripe_charge_id')) {
                $table->string('stripe_charge_id')->nullable()->after('stripe_client_secret');
            }

            // Add Shopify fields if missing
            if (!Schema::hasColumn('checkout_sessions', 'shopify_order_id')) {
                $table->string('shopify_order_id')->nullable()->after('stripe_charge_id');
            }

            if (!Schema::hasColumn('checkout_sessions', 'shopify_order_number')) {
                $table->string('shopify_order_number')->nullable()->after('shopify_order_id');
            }

            if (!Schema::hasColumn('checkout_sessions', 'shopify_order_gid')) {
                $table->string('shopify_order_gid')->nullable()->after('shopify_order_number');
            }

            // Add payment method fields if missing
            if (!Schema::hasColumn('checkout_sessions', 'payment_method_type')) {
                $table->string('payment_method_type')->nullable()->after('shopify_order_gid');
            }

            if (!Schema::hasColumn('checkout_sessions', 'card_brand')) {
                $table->string('card_brand')->nullable()->after('payment_method_type');
            }

            if (!Schema::hasColumn('checkout_sessions', 'card_last4')) {
                $table->string('card_last4')->nullable()->after('card_brand');
            }

            if (!Schema::hasColumn('checkout_sessions', 'wallet_type')) {
                $table->string('wallet_type')->nullable()->after('card_last4');
            }

            // Add tracking fields if missing
            if (!Schema::hasColumn('checkout_sessions', 'country_code')) {
                $table->string('country_code', 2)->nullable()->after('wallet_type');
            }

            if (!Schema::hasColumn('checkout_sessions', 'referrer')) {
                $table->string('referrer')->nullable()->after('country_code');
            }

            if (!Schema::hasColumn('checkout_sessions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('referrer');
            }

            // Add shipping_amount if missing
            if (!Schema::hasColumn('checkout_sessions', 'shipping_amount')) {
                $table->unsignedBigInteger('shipping_amount')->default(0)->after('subtotal');
            }

            // Add tax_amount if missing
            if (!Schema::hasColumn('checkout_sessions', 'tax_amount')) {
                $table->unsignedBigInteger('tax_amount')->default(0)->after('shipping_amount');
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty - don't drop columns
    }
};