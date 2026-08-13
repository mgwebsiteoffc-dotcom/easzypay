<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\CheckoutSession;
use App\Services\ShopifyService;
use App\Services\StripeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAllStores extends Command
{
    protected $signature   = 'stores:sync {--store=}';
    protected $description = 'Sync all active stores: pending orders, webhooks, button status';

    public function handle(): void
    {
        $this->info('🔄 Starting store sync at ' . now()->format('Y-m-d H:i:s'));

        $query = Store::with('tenant')
            ->where('is_installed', true)
            ->where('is_active', true)
            ->whereHas('tenant', fn($q) => $q->where('is_active', true));

        // Sync specific store if requested
        if ($storeId = $this->option('store')) {
            $query->where('id', $storeId);
        }

        $stores = $query->get();

        $this->info("📦 Found {$stores->count()} active stores to sync");

        $synced  = 0;
        $errors  = 0;
        $orders  = 0;

        foreach ($stores as $store) {
            try {
                $result = $this->syncStore($store);
                $synced++;
                $orders += $result['orders_synced'];

                $this->line("  ✅ {$store->shop_name} — {$result['orders_synced']} orders synced");

            } catch (\Throwable $e) {
                $errors++;
                $this->error("  ❌ {$store->shop_name}: {$e->getMessage()}");
                Log::error('Store sync failed', [
                    'store_id'   => $store->id,
                    'shop'       => $store->myshopify_domain,
                    'error'      => $e->getMessage(),
                ]);
            }

            // Small delay between stores to avoid rate limiting
            usleep(200000); // 200ms
        }

        $this->line('');
        $this->info("✅ Sync complete: {$synced} stores synced, {$orders} orders created, {$errors} errors");

        Log::info('Store sync completed', [
            'stores_synced' => $synced,
            'orders_synced' => $orders,
            'errors'        => $errors,
        ]);
    }

    private function syncStore(Store $store): array
    {
        $ordersSynced = 0;
        $shopify      = $store->getShopifyService();
        $stripe       = app(StripeService::class);

        // 1. Sync pending paid sessions to Shopify
        $pendingSessions = CheckoutSession::where('store_id', $store->id)
            ->where('status', 'paid')
            ->whereNull('shopify_order_id')
            ->where('created_at', '>=', now()->subHours(48))
            ->get();

        foreach ($pendingSessions as $session) {
            try {
                if (!$session->stripe_payment_intent_id) continue;

                $pi = $stripe->retrievePaymentIntent($session->stripe_payment_intent_id);
                if (!$pi) continue;

                $charge      = $pi->charges->data[0] ?? null;
                $paymentData = [
                    'payment_intent_id' => $pi->id,
                    'charge_id'         => $charge?->id ?? '',
                    'card_brand'        => $charge?->payment_method_details?->card?->brand ?? '',
                    'card_last4'        => $charge?->payment_method_details?->card?->last4 ?? '',
                    'wallet_type'       => $charge?->payment_method_details?->card?->wallet?->type ?? 'card',
                ];

                $result = $shopify->createOrder($session, $paymentData);

                if ($result['success']) {
                    $session->update([
                        'shopify_order_id'     => $result['shopify_id'],
                        'shopify_order_number' => $result['order_number'],
                        'status'               => 'completed',
                    ]);
                    $ordersSynced++;

                    Log::info("Order synced during store sync", [
                        'session_id'   => $session->session_id,
                        'store'        => $store->shop_name,
                        'order_number' => $result['order_number'],
                    ]);
                }

                usleep(300000); // 300ms between API calls

            } catch (\Throwable $e) {
                Log::error("Session sync failed", [
                    'session_id' => $session->session_id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // 2. Verify webhooks are registered
        if (!$store->webhooks_registered_at
            || $store->webhooks_registered_at->diffInHours(now()) >= 24) {
            $this->reRegisterWebhooks($store, $shopify);
        }

        // 3. Verify button is injected
        if (!$store->button_active
            || !$store->button_injected_at
            || $store->button_injected_at->diffInHours(now()) >= 168) { // Weekly re-inject
            $this->reInjectButton($store, $shopify);
        }

        // 4. Update store stats
        $totalOrders = CheckoutSession::where('store_id', $store->id)
            ->where('status', 'completed')
            ->count();

        $totalRevenue = CheckoutSession::where('store_id', $store->id)
            ->where('status', 'completed')
            ->sum('charged_amount') / 100;

        $store->update([
            'last_synced_at' => now(),
            'total_orders'   => $totalOrders,
            'total_revenue'  => $totalRevenue,
        ]);

        return ['orders_synced' => $ordersSynced];
    }

    private function reRegisterWebhooks(Store $store, ShopifyService $shopify): void
    {
        try {
            $results = $shopify->registerWebhooks();
            $store->update([
                'registered_webhooks'    => $results,
                'webhooks_registered_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Webhook re-registration failed", [
                'store' => $store->shop_name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function reInjectButton(Store $store, ShopifyService $shopify): void
    {
        try {
            $snippet = $this->getButtonSnippet($store);
            $result  = $shopify->injectThemeSnippet($snippet);

            if ($result['success']) {
                $store->update([
                    'button_active'      => true,
                    'button_injected_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Button re-injection failed", [
                'store' => $store->shop_name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getButtonSnippet(Store $store): string
    {
        return app(\App\Http\Controllers\Shopify\InstallController::class)
            ->getButtonSnippet($store);
    }
}