<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class StripeService
{
    private \Stripe\StripeClient $stripe;

    public function __construct()
    {
        $secret = config('services.stripe.secret', '');

        if (empty($secret)) {
            throw new \RuntimeException('STRIPE_SECRET not configured in .env');
        }

        $this->stripe = new \Stripe\StripeClient($secret);
    }

    public function createPaymentIntent(
        int     $amount,
        string  $currency,
        string  $sessionId,
        ?string $email = null,
        ?array  $shipping = null
    ): array {
        try {
            $params = [
                'amount'   => $amount,
                'currency' => strtolower($currency),
                'automatic_payment_methods' => [
                    'enabled'         => true,
                    'allow_redirects' => 'always',
                ],
                'description' => 'EaszyPay Checkout',
                'metadata'    => [
                    'session_id' => $sessionId,
                    'source'     => 'easzypay',
                ],
            ];

            if ($email) {
                $params['receipt_email'] = $email;
            }

            if (!empty($shipping['name'])) {
                $addr = $shipping['address'] ?? [];
                $params['shipping'] = [
                    'name'    => $shipping['name'],
                    'address' => [
                        'line1'       => $addr['line1'] ?? '',
                        'line2'       => $addr['line2'] ?? null,
                        'city'        => $addr['city'] ?? '',
                        'state'       => $addr['state'] ?? null,
                        'postal_code' => $addr['postal_code'] ?? '',
                        'country'     => $addr['country'] ?? 'US',
                    ],
                ];
            }

            $pi = $this->stripe->paymentIntents->create($params);

            return [
                'success'          => true,
                'client_secret'    => $pi->client_secret,
                'payment_intent_id'=> $pi->id,
                'amount'           => $pi->amount,
                'currency'         => $pi->currency,
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe PI error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function retrievePaymentIntent(string $piId): ?object
    {
        try {
            return $this->stripe->paymentIntents->retrieve($piId, [
                'expand' => ['charges.data.payment_method_details'],
            ]);
        } catch (\Throwable $e) {
            Log::error('PI retrieve error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function createRefund(string $chargeId, int $amount): array
    {
        try {
            $refund = $this->stripe->refunds->create([
                'charge' => $chargeId,
                'amount' => $amount,
            ]);
            return ['success' => true, 'refund' => $refund];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}