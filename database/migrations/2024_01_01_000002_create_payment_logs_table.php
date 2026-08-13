<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->index();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_charge_id')->nullable()->index();
            $table->string('shopify_order_id')->nullable()->index();

            $table->string('event_type');
            $table->string('status');
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method_type')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_last4')->nullable();
            $table->string('wallet_type')->nullable();
            $table->string('card_country')->nullable();

            $table->json('stripe_data')->nullable();
            $table->json('shopify_data')->nullable();
            $table->text('error_message')->nullable();

            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};