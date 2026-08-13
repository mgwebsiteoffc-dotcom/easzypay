<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_sessions_old', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->unique()->index();
            $table->string('shop_domain')->index();
            $table->string('cart_token')->nullable();
            $table->json('items');
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('shipping_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);

            // Customer info
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_first_name')->nullable();
            $table->string('customer_last_name')->nullable();

            // Shipping address
            $table->string('shipping_address1')->nullable();
            $table->string('shipping_address2')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_zip')->nullable();
            $table->string('shipping_country', 2)->nullable();

            // Stripe
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_client_secret')->nullable();

            // Shopify
            $table->string('shopify_order_id')->nullable()->index();
            $table->string('shopify_order_number')->nullable();

            // Currency conversion
            $table->string('detected_currency', 3)->nullable();
            $table->string('charged_currency', 3)->nullable();
            $table->decimal('exchange_rate', 10, 6)->default(1.000000);
            $table->unsignedBigInteger('charged_amount')->default(0);

            // Status & tracking
            $table->enum('status', [
                'pending',
                'payment_created',
                'paid',
                'shopify_order_created',
                'completed',
                'expired',
                'failed',
                'refunded',
            ])->default('pending')->index();

            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions_old');
    }
};