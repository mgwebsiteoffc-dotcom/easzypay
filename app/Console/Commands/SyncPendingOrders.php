<?php

namespace App\Console\Commands;

use App\Models\CheckoutSession;
use App\Models\Store;
use App\Services\ShopifyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPendingOrders extends Command
{
    protected $signature   = 'orders:sync';
    protected $description = 'Sync paid sessions to Shopify';

    public function handle(): void
    {
        $this->info('🔄 Syncing paid sessions to Shopify...');

        $sessions = CheckoutSession::where('status', 'paid')
            ->whereNull('shopify_order_id')
            ->where('created_at', '>=', now()->subHours(48))
            ->get();

        $this->info("Found {$sessions->count()} sessions to sync");

        foreach ($sessions as $session) {
            $this->info("Processing: {$session->session_id}");
            $this->info("  Store ID: {$session->store_id}");
            $this->info("  Shop: {$session->shop_domain}");
            $this->info("  PI: {$session->stripe_payment_intent_id}");

            // Find store
            $store = null;
            if ($session->store_id) {
                $store = Store::find($session->store_id);
            }
            if (!$store && $session->shop_domain) {
                $store = Store::where('myshopify_domain', $session->shop_domain)
                    ->orWhere('shop_domain', $session->shop_domain)
                    ->first();
            }
            if (!$store) {
                $store = Store::where('is_active', true)->first();
            }

            if (!$store) {
                $this->error("  ❌ No store found");
                continue;
            }

            $this->info("  Store: {$store->shop_name}");

            // Test connection
            $http = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Shopify-Access-Token' => $store->access_token,
            ])->get("https://{$store->myshopify_domain}/admin/api/2025-01/shop.json");

            if (!$http->ok()) {
                $this->error("  ❌ Shopify connection failed: HTTP {$http->status()}");
                $this->error("  Response: " . substr($http->body(), 0, 200));
                continue;
            }

            $this->info("  ✅ Shopify connected");

            // Get PI from Stripe
            if (!$session->stripe_payment_intent_id) {
                $this->error("  ❌ No Stripe PI ID");
                continue;
            }

            try {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $pi     = $stripe->paymentIntents->retrieve($session->stripe_payment_intent_id, [
                    'expand' => ['charges.data.payment_method_details'],
                ]);

                $charge = $pi->charges->data[0] ?? null;

                $paymentData = [
                    'payment_intent_id' => $pi->id,
                    'charge_id'         => $charge?->id ?? '',
                    'card_brand'        => $charge?->payment_method_details?->card?->brand ?? 'unknown',
                    'card_last4'        => $charge?->payment_method_details?->card?->last4 ?? '0000',
                    'wallet_type'       => $charge?->payment_method_details?->card?->wallet?->type ?? 'card',
                ];

                // Update store_id if missing
                if (!$session->store_id) {
                    $session->update(['store_id' => $store->id]);
                }

                $shopify = new ShopifyService($store->myshopify_domain, $store->access_token);
                $result  = $shopify->createOrder($session, $paymentData);

                if ($result['success']) {
                    $session->update([
                        'shopify_order_id'     => $result['shopify_id'],
                        'shopify_order_number' => $result['order_number'],
                        'status'               => 'completed',
                    ]);
                    $this->info("  ✅ ORDER CREATED: {$result['order_number']}");
                } else {
                    $this->error("  ❌ Order failed: " . ($result['error'] ?? 'unknown'));
                }

            } catch (\Throwable $e) {
                $this->error("  ❌ Exception: " . $e->getMessage());
            }
        }

        $this->info('Done!');
    }
}