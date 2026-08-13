<?php

namespace App\Console\Commands;

use App\Models\CheckoutSession;
use App\Models\WebhookLog;
use App\Services\ShopifyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorSystem extends Command
{
    protected $signature   = 'system:monitor';
    protected $description = 'Run system health checks and sync pending orders';

    public function handle(): void
    {
        $this->info('🔍 Running system health check...');
        $this->line('');

        // 1. Check database connection
        $this->checkDatabase();

        // 2. Check Stripe connection
        $this->checkStripe();

        // 3. Check Shopify connection
        $this->checkShopify();

        // 4. Sync pending Shopify orders
        $this->syncPendingOrders();

        // 5. Clean expired sessions
        $this->cleanExpiredSessions();

        // 6. Alert on failed webhooks
        $this->checkFailedWebhooks();

        $this->line('');
        $this->info('✅ Health check complete');
    }

    private function checkDatabase(): void
    {
        try {
            DB::select('SELECT 1');
            $this->info('✅ Database: Connected');
        } catch (\Throwable $e) {
            $this->error('❌ Database: ' . $e->getMessage());
            Log::critical('Database connection failed', ['error' => $e->getMessage()]);
        }
    }

    private function checkStripe(): void
    {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $stripe->balance->retrieve();
            $this->info('✅ Stripe: Connected');
        } catch (\Throwable $e) {
            $this->error('❌ Stripe: ' . $e->getMessage());
            Log::error('Stripe health check failed', ['error' => $e->getMessage()]);
        }
    }

    private function checkShopify(): void
    {
        try {
            $shopify = app(ShopifyService::class);
            $shop    = $shopify->getShopInfo();

            if ($shop) {
                $this->info('✅ Shopify: Connected (' . ($shop['name'] ?? 'unknown') . ')');
            } else {
                $this->error('❌ Shopify: Connection failed');
            }
        } catch (\Throwable $e) {
            $this->error('❌ Shopify: ' . $e->getMessage());
        }
    }

    private function syncPendingOrders(): void
    {
        $pending = CheckoutSession::where('status', 'paid')
            ->whereNull('shopify_order_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        if ($pending->isEmpty()) {
            $this->info('✅ Shopify Sync: No pending orders');
            return;
        }

        $this->warn("⚠️ Shopify Sync: {$pending->count()} orders need syncing");

        $shopify = app(ShopifyService::class);
        $stripe  = app(\App\Services\StripeService::class);

        foreach ($pending as $session) {
            try {
                $pi = $stripe->retrievePaymentIntent($session->stripe_payment_intent_id);

                if (!$pi) {
                    $this->error("   ❌ Cannot retrieve PI for {$session->session_id}");
                    continue;
                }

                $charge = $pi->charges->data[0] ?? null;

                $result = $shopify->createOrder($session, [
                    'payment_intent_id' => $pi->id,
                    'charge_id'         => $charge?->id ?? '',
                    'card_brand'        => $charge?->payment_method_details?->card?->brand ?? '',
                    'card_last4'        => $charge?->payment_method_details?->card?->last4 ?? '',
                    'wallet_type'       => $charge?->payment_method_details?->card?->wallet?->type ?? 'card',
                ]);

                if ($result['success']) {
                    $session->update([
                        'shopify_order_id'     => $result['shopify_id'],
                        'shopify_order_number' => $result['order_number'],
                        'status'               => 'completed',
                    ]);
                    $this->info("   ✅ Synced: {$result['order_number']} for {$session->customer_email}");
                } else {
                    $this->error("   ❌ Failed: {$result['error']}");
                }

                usleep(250000); // 250ms between requests

            } catch (\Throwable $e) {
                $this->error("   ❌ Error: {$e->getMessage()}");
                Log::error('Monitor sync error', [
                    'session_id' => $session->session_id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    private function cleanExpiredSessions(): void
    {
        $count = CheckoutSession::where('expires_at', '<', now())
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $this->info("✅ Cleaned: {$count} expired sessions");
    }

    private function checkFailedWebhooks(): void
    {
        $failed = WebhookLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($failed > 0) {
            $this->warn("⚠️ Failed Webhooks: {$failed} in last hour");
            Log::warning("Failed webhooks detected", ['count' => $failed]);
        } else {
            $this->info('✅ Webhooks: All healthy');
        }
    }
}