<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'                  => 'Free Trial',
                'slug'                  => 'trial',
                'description'           => '14-day free trial with all features',
                'price_monthly'         => 0,
                'price_yearly'          => 0,
                'transaction_fee'       => 0.00,
                'max_stores'            => 1,
                'max_orders_per_month'  => 50,
                'features'              => json_encode([
                    'stripe_payments', 'apple_pay', 'google_pay',
                    'multi_currency', 'basic_analytics',
                ]),
                'is_active'  => true,
                'sort_order' => 0,
            ],
            [
                'name'                  => 'Starter',
                'slug'                  => 'starter',
                'description'           => 'Perfect for small stores getting started',
                'price_monthly'         => 29,
                'price_yearly'          => 290,
                'transaction_fee'       => 0.015,  // 1.5%
                'max_stores'            => 1,
                'max_orders_per_month'  => 300,
                'features'              => json_encode([
                    'stripe_payments', 'apple_pay', 'google_pay',
                    'link_payments', 'multi_currency', 'basic_analytics',
                    'email_support',
                ]),
                'is_active'  => true,
                'sort_order' => 1,
            ],
            [
                'name'                  => 'Growth',
                'slug'                  => 'growth',
                'description'           => 'Scale your business across multiple stores',
                'price_monthly'         => 79,
                'price_yearly'          => 790,
                'transaction_fee'       => 0.01,   // 1%
                'max_stores'            => 5,
                'max_orders_per_month'  => 2000,
                'features'              => json_encode([
                    'stripe_payments', 'apple_pay', 'google_pay',
                    'link_payments', 'multi_currency', 'advanced_analytics',
                    'priority_support', 'custom_branding', 'refund_sync',
                    'webhook_monitoring',
                ]),
                'is_active'  => true,
                'sort_order' => 2,
            ],
            [
                'name'                  => 'Enterprise',
                'slug'                  => 'enterprise',
                'description'           => 'Unlimited stores and orders for large brands',
                'price_monthly'         => 199,
                'price_yearly'          => 1990,
                'transaction_fee'       => 0.005,  // 0.5%
                'max_stores'            => 999,
                'max_orders_per_month'  => 999999,
                'features'              => json_encode([
                    'stripe_payments', 'apple_pay', 'google_pay',
                    'link_payments', 'multi_currency', 'advanced_analytics',
                    'dedicated_support', 'custom_branding', 'refund_sync',
                    'webhook_monitoring', 'white_label', 'api_access',
                    'sla_guarantee', 'custom_integrations',
                ]),
                'is_active'  => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->command->info('Plans seeded successfully');
    }
}