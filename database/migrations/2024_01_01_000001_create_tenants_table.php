<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('company_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('country', 2)->default('US');
            $table->string('timezone')->default('UTC');

            // Subscription
            $table->enum('plan', ['trial', 'starter', 'growth', 'enterprise'])
                  ->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->boolean('is_active')->default(true);

            // Billing
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('billing_email')->nullable();

            // Settings
            $table->json('settings')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->default('#667eea');

            // Stats
            $table->unsignedInteger('total_stores')->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};