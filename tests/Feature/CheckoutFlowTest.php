<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\CheckoutSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    // ================================================================
    // Test 1: Shopify cart endpoint
    // ================================================================
    public function test_shopify_cart_endpoint_creates_session(): void
    {
        // Mock ShopifyService to avoid real API calls
        $this->mock(\App\Services\ShopifyService::class, function ($mock) {
            $mock->shouldReceive('validateCartPrices')
                ->once()
                ->andReturn([
                    'valid'  => true,
                    'items'  => [[
                        'variant_id'    => 123456,
                        'variant_gid'   => 'gid://shopify/ProductVariant/123456',
                        'title'         => 'Test Product',
                        'variant_title' => 'Default Title',
                        'price_cents'   => 2999,
                        'quantity'      => 1,
                        'sku'           => 'TEST-001',
                        'image'         => null,
                    ]],
                    'errors' => [],
                ]);
        });

        $response = $this->postJson('/shopify/cart', [
            'shop_domain' => 'test-store.myshopify.com',
            'cart_token'  => 'abc123',
            'currency'    => 'USD',
            'subtotal'    => 2999,
            'items'       => [[
                'product_id'    => 789,
                'variant_id'    => 123456,
                'quantity'      => 1,
                'title'         => 'Test Product',
                'variant_title' => 'Default Title',
                'price'         => 2999,
                'image'         => '',
            ]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'session_id',
            'checkout_url',
            'subtotal',
            'currency',
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('session_id'));
        $this->assertDatabaseHas('checkout_sessions', [
            'shop_domain' => 'test-store.myshopify.com',
            'currency'    => 'USD',
            'subtotal'    => 2999,
            'status'      => 'pending',
        ]);
    }

    // ================================================================
    // Test 2: Cart rejects price manipulation
    // ================================================================
    public function test_cart_rejects_price_manipulation(): void
    {
        $this->mock(\App\Services\ShopifyService::class, function ($mock) {
            $mock->shouldReceive('validateCartPrices')
                ->once()
                ->andReturn([
                    'valid'  => false,
                    'items'  => [],
                    'errors' => ['Price mismatch for Test Product'],
                ]);
        });

        $response = $this->postJson('/shopify/cart', [
            'shop_domain' => 'test-store.myshopify.com',
            'cart_token'  => 'abc123',
            'currency'    => 'USD',
            'subtotal'    => 1,  // manipulated!
            'items'       => [[
                'product_id'    => 789,
                'variant_id'    => 123456,
                'quantity'      => 1,
                'title'         => 'Test Product',
                'variant_title' => 'Default Title',
                'price'         => 1,  // manipulated!
                'image'         => '',
            ]],
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('error.message', fn($msg) => str_contains($msg, 'validation failed'));
    }

    // ================================================================
    // Test 3: Checkout page loads correctly
    // ================================================================
    public function test_checkout_page_loads_valid_session(): void
    {
        $session = CheckoutSession::factory()->create([
            'status'     => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->get("/checkout/{$session->session_id}");

        $response->assertStatus(200);
        $response->assertViewIs('checkout.show');
        $response->assertViewHas('session');
    }

    // ================================================================
    // Test 4: Expired session shows expired view
    // ================================================================
    public function test_expired_session_shows_expired_page(): void
    {
        $session = CheckoutSession::factory()->create([
            'status'     => 'pending',
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->get("/checkout/{$session->session_id}");

        $response->assertStatus(200);
        $response->assertViewIs('checkout.expired');
    }

    // ================================================================
    // Test 5: Payment intent creation
    // ================================================================
    public function test_payment_intent_creation(): void
    {
        $session = CheckoutSession::factory()->create([
            'currency'    => 'USD',
            'subtotal'    => 4999,
            'status'      => 'pending',
            'expires_at'  => now()->addHour(),
        ]);

        $this->mock(\App\Services\StripeService::class, function ($mock) {
            $mock->shouldReceive('createPaymentIntent')
                ->once()
                ->andReturn([
                    'success'           => true,
                    'client_secret'     => 'pi_test_secret_abc123',
                    'payment_intent_id' => 'pi_test_abc123',
                    'amount'            => 4999,
                    'currency'          => 'usd',
                ]);
        });

        $this->mock(\App\Services\ExchangeRateService::class, function ($mock) {
            $mock->shouldReceive('convert')
                ->andReturn([
                    'from_currency'    => 'USD',
                    'to_currency'      => 'USD',
                    'original_amount'  => 4999,
                    'converted_amount' => 4999,
                    'rate'             => 1.0,
                ]);
        });

        $response = $this->postJson('/api/payment-intent', [
            'session_id' => $session->session_id,
            'amount'     => 4999,
            'currency'   => 'usd',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'client_secret',
            'payment_intent_id',
            'amount',
            'currency',
        ]);
    }

    // ================================================================
    // Test 6: Stripe webhook signature validation
    // ================================================================
    public function test_stripe_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/webhook/stripe', [
            'type' => 'payment_intent.succeeded',
        ], [
            'Stripe-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(400);
    }

    // ================================================================
    // Test 7: Location detection API
    // ================================================================
    public function test_location_detection_returns_currency(): void
    {
        $response = $this->getJson('/api/detect-location');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'country_code',
            'currency',
            'symbol',
            'flag',
        ]);
    }

    // ================================================================
    // Test 8: Exchange rate API
    // ================================================================
    public function test_exchange_rate_api(): void
    {
        $this->mock(\App\Services\ExchangeRateService::class, function ($mock) {
            $mock->shouldReceive('convert')
                ->with('USD', 'EUR', 4999)
                ->once()
                ->andReturn([
                    'from_currency'    => 'USD',
                    'to_currency'      => 'EUR',
                    'original_amount'  => 4999,
                    'converted_amount' => 4624,
                    'rate'             => 0.9249,
                ]);
        });

        $response = $this->getJson('/api/exchange-rate?from=USD&to=EUR&amount=4999');

        $response->assertStatus(200);
        $response->assertJsonPath('to_currency', 'EUR');
    }

    // ================================================================
    // Test 9: Admin login
    // ================================================================
    public function test_admin_login(): void
    {
        \App\Models\AdminUser::create([
            'name'      => 'Test Admin',
            'email'     => 'admin@test.com',
            'password'  => \Illuminate\Support\Facades\Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('admin.login.post'), [
            'email'    => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(\Illuminate\Support\Facades\Auth::guard('admin')->check());
    }

    // ================================================================
    // Test 10: States API
    // ================================================================
    public function test_states_api_returns_us_states(): void
    {
        $response = $this->getJson('/api/location/states?country=US');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'states' => [
                '*' => ['code', 'name'],
            ],
        ]);

        $states = $response->json('states');
        $this->assertGreaterThan(40, count($states));
    }
}