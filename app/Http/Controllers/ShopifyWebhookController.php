<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use App\Models\CheckoutSession;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    public function __construct(
        private StripeService $stripeService,
    ) {}

    // ================================================================
    // Verify HMAC signature
    // ================================================================
    private function verifySignature(Request $request): bool
    {
        $hmac    = $request->header('X-Shopify-Hmac-Sha256', '');
        $data    = $request->getContent();
        $secret  = config('services.shopify.app_secret');

        $calculated = base64_encode(hash_hmac('sha256', $data, $secret, true));

        return hash_equals($calculated, $hmac);
    }

    // ================================================================
    // Orders Create
    // ================================================================
    public function ordersCreate(Request $request): Response
    {
        return $this->handleWebhook($request, 'orders/create', function ($payload) {
            Log::info('Shopify order created webhook', [
                'order_id'     => $payload['id'] ?? null,
                'order_number' => $payload['name'] ?? null,
                'total'        => $payload['total_price'] ?? null,
            ]);
        });
    }

    // ================================================================
    // Orders Updated
    // ================================================================
    public function ordersUpdated(Request $request): Response
    {
        return $this->handleWebhook($request, 'orders/updated', function ($payload) {
            Log::info('Shopify order updated webhook', [
                'order_id'         => $payload['id'] ?? null,
                'financial_status' => $payload['financial_status'] ?? null,
            ]);
        });
    }

    // ================================================================
    // Orders Paid
    // ================================================================
    public function ordersPaid(Request $request): Response
    {
        return $this->handleWebhook($request, 'orders/paid', function ($payload) {
            Log::info('Shopify order paid webhook', [
                'order_id'     => $payload['id'] ?? null,
                'order_number' => $payload['name'] ?? null,
            ]);
        });
    }

    // ================================================================
    // Refunds Create (Shopify → Stripe refund sync)
    // ================================================================
    public function refundsCreate(Request $request): Response
    {
        return $this->handleWebhook($request, 'refunds/create', function ($payload) {
            $orderId = $payload['order_id'] ?? null;

            if (!$orderId) return;

            Log::info('Shopify refund webhook received', [
                'order_id'  => $orderId,
                'refund_id' => $payload['id'] ?? null,
            ]);

            // Find our checkout session for this order
            $session = CheckoutSession::where('shopify_order_id', (string) $orderId)->first();

            if (!$session) {
                Log::warning('No checkout session found for Shopify refund', [
                    'order_id' => $orderId,
                ]);
                return;
            }

            // Calculate refund amount from Shopify refund data
            $refundTransactions = $payload['transactions'] ?? [];
            $totalRefund        = 0;

            foreach ($refundTransactions as $tx) {
                if (($tx['kind'] ?? '') === 'refund' && ($tx['status'] ?? '') === 'success') {
                    $totalRefund += (int) round(((float) ($tx['amount'] ?? 0)) * 100);
                }
            }

            if ($totalRefund <= 0) {
                // Try from refund line items
                $refundLineItems = $payload['refund_line_items'] ?? [];
                foreach ($refundLineItems as $rli) {
                    $totalRefund += (int) round(((float) ($rli['subtotal'] ?? 0)) * 100);
                }
            }

            if ($totalRefund <= 0) {
                Log::warning('Shopify refund has zero amount', ['order_id' => $orderId]);
                return;
            }

            // Find Stripe charge
            if (!$session->stripe_payment_intent_id) {
                Log::warning('No Stripe PI for refund sync', ['session_id' => $session->session_id]);
                return;
            }

            // Get charge from PI
            $pi = $this->stripeService->retrievePaymentIntent($session->stripe_payment_intent_id);

            if (!$pi || empty($pi->charges->data)) {
                Log::error('Cannot retrieve Stripe charges for refund', [
                    'pi_id' => $session->stripe_payment_intent_id,
                ]);
                return;
            }

            $chargeId = $pi->charges->data[0]->id;

            // Create Stripe refund
            $result = $this->stripeService->createRefund($chargeId, $totalRefund);

            if ($result['success']) {
                $session->update(['status' => 'refunded']);

                Log::info('Stripe refund created from Shopify webhook', [
                    'order_id'   => $orderId,
                    'charge_id'  => $chargeId,
                    'amount'     => $totalRefund,
                    'session_id' => $session->session_id,
                ]);
            } else {
                Log::error('Stripe refund failed from Shopify webhook', [
                    'order_id' => $orderId,
                    'error'    => $result['error'],
                ]);
            }
        });
    }

    // ================================================================
    // App Uninstalled
    // ================================================================
    public function appUninstalled(Request $request): Response
    {
        return $this->handleWebhook($request, 'app/uninstalled', function ($payload) {
            $shop = $payload['myshopify_domain'] ?? 'unknown';

            Log::warning('⚠️ Shopify app uninstalled', ['shop' => $shop]);

            // TODO: Clean up shop data
            // TODO: Notify admin
        });
    }

    // ================================================================
    // Common webhook handler
    // ================================================================
    private function handleWebhook(Request $request, string $topic, callable $handler): Response
    {
        // Verify HMAC
        if (!$this->verifySignature($request)) {
            Log::warning('Invalid Shopify webhook signature', ['topic' => $topic]);
            return response('Invalid signature', 401);
        }

        $payload = $request->json()->all();

        // Log webhook
        $log = WebhookLog::create([
            'source'     => 'shopify',
            'event_id'   => $request->header('X-Shopify-Webhook-Id', null),
            'event_type' => $topic,
            'status'     => 'received',
            'payload'    => $payload,
            'attempts'   => 1,
        ]);

        try {
            $handler($payload);

            $log->update([
                'status'       => 'processed',
                'processed_at' => now(),
            ]);

            return response('OK', 200);

        } catch (\Throwable $e) {
            Log::error("Shopify webhook failed: {$topic}", [
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);

            return response('Error', 500);
        }
    }
}