<?php

namespace App\Http\Controllers;

use App\Models\CheckoutSession;
use App\Models\PaymentLog;
use App\Models\WebhookLog;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function __construct(
        private ShopifyService $shopifyService
    ) {}

    public function stripe(Request $request): Response
    {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        // Log incoming webhook
        $log = WebhookLog::create([
            'source'     => 'stripe',
            'event_type' => 'unknown',
            'status'     => 'received',
            'attempts'   => 1,
        ]);

        try {
            // Verify signature
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );

            // Update log
            $log->update([
                'event_id'   => $event->id,
                'event_type' => $event->type,
                'payload'    => json_decode($payload, true),
            ]);

            Log::info('Stripe webhook received', [
                'event_id'   => $event->id,
                'event_type' => $event->type,
            ]);

            // Handle event
            $this->handleStripeEvent($event, $log);

            $log->update(['status' => 'processed', 'processed_at' => now()]);

            return response('OK', 200);

        } catch (SignatureVerificationException $e) {
            Log::warning('Webhook signature invalid', ['error' => $e->getMessage()]);
            $log->update(['status' => 'failed', 'error' => 'Invalid signature: ' . $e->getMessage()]);
            return response('Invalid signature', 400);

        } catch (\Throwable $e) {
            Log::error('Webhook processing failed', ['error' => $e->getMessage()]);
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            return response('Webhook error', 500);
        }
    }

    private function handleStripeEvent(object $event, WebhookLog $log): void
    {
        match($event->type) {
            'payment_intent.succeeded'     => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed'=> $this->handlePaymentFailed($event->data->object),
            'checkout.session.completed'   => $this->handleCheckoutCompleted($event->data->object),
            'charge.refunded'              => $this->handleChargeRefunded($event->data->object),
            'charge.dispute.created'       => $this->handleDisputeCreated($event->data->object),
            default => Log::info('Unhandled Stripe event', ['type' => $event->type]),
        };
    }

    private function handlePaymentSucceeded(object $pi): void
    {
        $sessionId = $pi->metadata->session_id ?? null;
        if (!$sessionId) return;

        $session = CheckoutSession::where('session_id', $sessionId)->first();
        if (!$session) return;

        // Get charge details
        $chargeId   = null;
        $cardBrand  = null;
        $cardLast4  = null;
        $walletType = null;

        if (!empty($pi->charges->data)) {
            $charge     = $pi->charges->data[0];
            $chargeId   = $charge->id;
            $cardBrand  = $charge->payment_method_details->card->brand  ?? null;
            $cardLast4  = $charge->payment_method_details->card->last4  ?? null;
            $walletType = $charge->payment_method_details->card->wallet->type ?? null;
        }

        // Update session status
        $session->update(['status' => 'paid']);

        // Log payment
        PaymentLog::create([
            'session_id'               => $sessionId,
            'stripe_payment_intent_id' => $pi->id,
            'stripe_charge_id'         => $chargeId,
            'event_type'               => 'payment_intent.succeeded',
            'status'                   => 'succeeded',
            'amount'                   => $pi->amount,
            'currency'                 => strtoupper($pi->currency),
            'payment_method_type'      => 'card',
            'card_brand'               => $cardBrand,
            'card_last4'               => $cardLast4,
            'wallet_type'              => $walletType,
            'stripe_data'              => (array) $pi,
        ]);

        // Create Shopify order if not already created
        if (!$session->shopify_order_id) {
            $paymentData = [
                'payment_intent_id' => $pi->id,
                'charge_id'         => $chargeId ?? '',
                'card_brand'        => $cardBrand ?? '',
                'card_last4'        => $cardLast4 ?? '',
                'wallet_type'       => $walletType ?? 'card',
            ];

            $result = $this->shopifyService->createOrder($session, $paymentData);

            if ($result['success']) {
                $session->update([
                    'shopify_order_id'     => $result['shopify_id'],
                    'shopify_order_number' => $result['order_number'],
                    'status'               => 'shopify_order_created',
                ]);

                Log::info('Shopify order created via webhook', [
                    'session_id'   => $sessionId,
                    'shopify_id'   => $result['shopify_id'],
                    'order_number' => $result['order_number'],
                ]);
            }
        }
    }

    private function handlePaymentFailed(object $pi): void
    {
        $sessionId = $pi->metadata->session_id ?? null;
        if (!$sessionId) return;

        $session = CheckoutSession::where('session_id', $sessionId)->first();
        $session?->update(['status' => 'failed']);

        PaymentLog::create([
            'session_id'               => $sessionId,
            'stripe_payment_intent_id' => $pi->id,
            'event_type'               => 'payment_intent.payment_failed',
            'status'                   => 'failed',
            'amount'                   => $pi->amount,
            'currency'                 => strtoupper($pi->currency),
            'error_message'            => $pi->last_payment_error->message ?? 'Unknown error',
        ]);
    }

    private function handleCheckoutCompleted(object $session): void
    {
        Log::info('Checkout session completed', ['session_id' => $session->id]);
    }

    private function handleChargeRefunded(object $charge): void
    {
        Log::info('Charge refunded', [
            'charge_id'      => $charge->id,
            'amount_refunded'=> $charge->amount_refunded,
        ]);

        PaymentLog::create([
            'session_id'      => $charge->metadata->session_id ?? 'unknown',
            'stripe_charge_id'=> $charge->id,
            'event_type'      => 'charge.refunded',
            'status'          => 'refunded',
            'amount'          => $charge->amount_refunded,
            'currency'        => strtoupper($charge->currency),
        ]);
    }

    private function handleDisputeCreated(object $dispute): void
    {
        Log::warning('⚠️ Dispute created', [
            'dispute_id' => $dispute->id,
            'charge_id'  => $dispute->charge,
            'amount'     => $dispute->amount,
            'reason'     => $dispute->reason,
        ]);
    }
}