<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CheckoutSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'session_id'           => bin2hex(random_bytes(32)),
            'shop_domain'          => 'test-store.myshopify.com',
            'cart_token'           => $this->faker->uuid(),
            'items'                => [
                [
                    'product_id'    => $this->faker->numberBetween(1000, 9999),
                    'variant_id'    => $this->faker->numberBetween(1000, 9999),
                    'quantity'      => 1,
                    'title'         => $this->faker->words(3, true),
                    'variant_title' => 'Default Title',
                    'price'         => $this->faker->numberBetween(999, 9999),
                    'image'         => null,
                ],
            ],
            'currency'             => 'USD',
            'subtotal'             => $this->faker->numberBetween(999, 9999),
            'total_amount'         => $this->faker->numberBetween(999, 9999),
            'customer_email'       => $this->faker->safeEmail(),
            'customer_first_name'  => $this->faker->firstName(),
            'customer_last_name'   => $this->faker->lastName(),
            'shipping_address1'    => $this->faker->streetAddress(),
            'shipping_city'        => $this->faker->city(),
            'shipping_state'       => 'NY',
            'shipping_zip'         => $this->faker->postcode(),
            'shipping_country'     => 'US',
            'charged_currency'     => 'USD',
            'charged_amount'       => $this->faker->numberBetween(999, 9999),
            'exchange_rate'        => 1.0,
            'ip_address'           => $this->faker->ipv4(),
            'status'               => 'completed',
            'expires_at'           => now()->addHour(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function paid(): static
    {
        return $this->state(['status' => 'paid']);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'failed']);
    }

    public function expired(): static
    {
        return $this->state([
            'status'     => 'pending',
            'expires_at' => now()->subHour(),
        ]);
    }
}