<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Shopify
            $table->string('shop_domain')->unique();
            $table->string('myshopify_domain')->unique();
            $table->string('shop_name');
            $table->string('shop_email')->nullable();
            $table->string('shop_phone')->nullable();
            $table->string('access_token', 500);
            $table->string('scopes')->nullable();
            $table->string('api_version')->default('2025-01');
            $table->string('shopify_plan')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('country_code', 2)->nullable();
            $table->string('timezone')->nullable();
            $table->string('shop_owner')->nullable();

            // App installation
            $table->boolean('is_installed')->default(true);
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();

            // Webhooks
            $table->json('registered_webhooks')->nullable();
            $table->timestamp('webhooks_registered_at')->nullable();

            // Sync tracking
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('button_injected_at')->nullable();
            $table->boolean('button_active')->default(false);

            // Stripe
            $table->string('stripe_account_id')->nullable();

            // Settings
            $table->json('checkout_settings')->nullable();
            $table->string('primary_color')->default('#667eea');
            $table->boolean('is_active')->default(true);

            // Stats
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index('shop_domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};