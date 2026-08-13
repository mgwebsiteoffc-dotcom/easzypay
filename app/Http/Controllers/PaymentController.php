<?php

namespace App\Http\Controllers;

use App\Models\CheckoutSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createIntent(Request $request): JsonResponse
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');

        if ($request->isMethod('options')) {
            return response()->json(['status' => 'ok']);
        }

        try {
            $data             = $request->json()->all();
            $sessionId        = $data['session_id'] ?? '';
            $currency         = strtolower($data['currency'] ?? 'usd');
            $amount           = (int) ($data['amount'] ?? 0);
            $exchangeRate     = (float) ($data['exchange_rate'] ?? 1.0);
            $detectedCurrency = strtoupper(trim((string) ($data['detected_currency'] ?? $currency)));
            $email            = $data['email'] ?? null;
            $shipping         = $data['shipping'] ?? null;
            $billing          = $data['billing'] ?? null;
            $shippingAmount   = isset($data['shipping_amount']) ? (int) $data['shipping_amount'] : null;
            $shippingTitle    = trim((string) ($data['shipping_title'] ?? ''));

            // Find session
            $session = CheckoutSession::where('session_id', $sessionId)->active()->first();

            if (!$session) {
                return response()->json([
                    'error' => ['message' => 'Checkout session expired.']
                ], 400);
            }

            if ($amount < 50) {
                $amount = (int) $session->subtotal;
            }

            if ($amount < 50) {
                return response()->json([
                    'error' => ['message' => 'Amount too small.']
                ], 400);
            }

            $stripeSecret = config('services.stripe.secret');
            if (empty($stripeSecret)) {
                return response()->json([
                    'error' => ['message' => 'Stripe not configured.']
                ], 500);
            }

            $stripe = new \Stripe\StripeClient($stripeSecret);

            // ============================================
            // REUSE EXISTING PI IF SAME CURRENCY & AMOUNT
            // ============================================
            $existingPiId = $session->stripe_payment_intent_id;
            $existingSecret = $session->stripe_client_secret;
            $needNewPi = true;

            if ($existingPiId && $existingSecret) {
                try {
                    $existingPi = $stripe->paymentIntents->retrieve($existingPiId);

                    // Reuse if same currency and amount, and PI is still usable
                    $reusableStatuses = ['requires_payment_method', 'requires_confirmation', 'requires_action'];

                    if (
                        in_array($existingPi->status, $reusableStatuses) &&
                        $existingPi->currency === $currency &&
                        $existingPi->amount === $amount
                    ) {
                        // Same currency and amount - reuse
                        $needNewPi = false;

                        Log::info('Reusing existing PaymentIntent', [
                            'pi_id'    => $existingPiId,
                            'amount'   => $existingPi->amount,
                            'currency' => $existingPi->currency,
                            'status'   => $existingPi->status,
                        ]);

                        // Update with email/shipping if provided
                        $updateParams = [];

                        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $updateParams['receipt_email'] = $email;
                        }

                        if (!empty($shipping['name'])) {
                            $addr = $shipping['address'] ?? [];
                            $updateParams['shipping'] = [
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

                        if (!empty($billing['name'])) {
                            $addr = $billing['address'] ?? [];
                            $updateParams['receipt_email'] = $email;
                            $updateParams['shipping'] = $updateParams['shipping'] ?? [];
                            // Stripe PaymentIntent update does not accept billing_details directly.
                            // Billing details are provided at confirmation time via the payment method.
                        }

                        if (!empty($updateParams)) {
                            $stripe->paymentIntents->update($existingPiId, $updateParams);
                        }

                        // Update session
                        $this->updateSession($session, $email, $shipping, $billing, $amount, $currency, $exchangeRate, $detectedCurrency, $shippingAmount, $shippingTitle);

                        return response()->json([
                            'client_secret'     => $existingSecret,
                            'payment_intent_id' => $existingPiId,
                            'amount'            => $existingPi->amount,
                            'currency'          => $existingPi->currency,
                            'reused'            => true,
                        ]);

                    } elseif (in_array($existingPi->status, $reusableStatuses)) {
                        // Different currency or amount - cancel old PI
                        try {
                            $stripe->paymentIntents->cancel($existingPiId);
                            Log::info('Cancelled old PaymentIntent', [
                                'pi_id'        => $existingPiId,
                                'old_currency' => $existingPi->currency,
                                'new_currency' => $currency,
                            ]);
                        } catch (\Throwable $e) {
                            Log::warning('Could not cancel old PI', ['error' => $e->getMessage()]);
                        }
                    }
                    // If PI is in terminal state (succeeded, canceled), create new one

                } catch (\Throwable $e) {
                    Log::warning('Could not retrieve existing PI', [
                        'pi_id' => $existingPiId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ============================================
            // CREATE NEW PI (only if needed)
            // ============================================
            if ($needNewPi) {
                $params = [
                    'amount'   => $amount,
                    'currency' => $currency,
                    'automatic_payment_methods' => [
                        'enabled'         => true,
                        'allow_redirects' => 'always',
                    ],
                    'description' => 'EaszyPay Checkout',
                    'metadata'    => [
                        'session_id' => $sessionId,
                        'store_id'   => (string) ($session->store_id ?? ''),
                        'shop'       => (string) ($session->shop_domain ?? ''),
                        'source'     => 'easzypay',
                    ],
                ];

                if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
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

                if (!empty($billing['name'])) {
                    // Stripe PaymentIntent create does not accept billing_details at the top level.
                    // Billing details should be provided when confirming the payment method.
                }

                $pi = $stripe->paymentIntents->create($params);

                Log::info('New PaymentIntent created', [
                    'pi_id'    => $pi->id,
                    'amount'   => $pi->amount,
                    'currency' => $pi->currency,
                ]);

                // Update session with new PI
                $session->update([
                    'stripe_payment_intent_id' => $pi->id,
                    'stripe_client_secret'     => $pi->client_secret,
                    'charged_amount'           => $amount,
                    'charged_currency'         => strtoupper($currency),
                    'status'                   => 'payment_created',
                ]);

                $this->updateSession($session, $email, $shipping, $billing, $amount, $currency, $exchangeRate, $detectedCurrency);

                return response()->json([
                    'client_secret'     => $pi->client_secret,
                    'payment_intent_id' => $pi->id,
                    'amount'            => $pi->amount,
                    'currency'          => $pi->currency,
                    'reused'            => false,
                ]);
            }

        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::error('Stripe auth error', ['error' => $e->getMessage()]);
            return response()->json(['error' => ['message' => 'Invalid Stripe key.']], 401);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API error', ['error' => $e->getMessage()]);
            return response()->json(['error' => ['message' => $e->getMessage()]], 400);

        } catch (\Throwable $e) {
            Log::error('PaymentIntent error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
            return response()->json(['error' => ['message' => 'Payment setup failed.']], 500);
        }

        return response()->json(['error' => ['message' => 'Could not create or reuse payment intent.']], 500);
    }

    private function updateSession(CheckoutSession $session, ?string $email, ?array $shipping, ?array $billing, int $amount, string $currency, float $exchangeRate = 1.0, string $detectedCurrency = 'USD', ?int $shippingAmount = null, string $shippingTitle = ''): void
    {
        $update = [
            'charged_amount'   => $amount,
            'charged_currency' => strtoupper($currency),
            'exchange_rate'    => $exchangeRate,
            'detected_currency'=> strtoupper($detectedCurrency),
        ];

        if ($shippingAmount !== null) {
            $update['shipping_amount'] = max(0, $shippingAmount);
        }
        if ($shippingTitle !== '') {
            $update['referrer'] = $shippingTitle;
        }

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $update['customer_email'] = $email;
        }

        if (!empty($shipping['name'])) {
            $names = explode(' ', $shipping['name'], 2);
            $addr  = $shipping['address'] ?? [];

            $update['customer_first_name'] = $names[0] ?? '';
            $update['customer_last_name']  = $names[1] ?? '';
            $update['shipping_address1']   = $addr['line1'] ?? '';
            $update['shipping_address2']   = $addr['line2'] ?? null;
            $update['shipping_city']       = $addr['city'] ?? '';
            $update['shipping_state']      = $addr['state'] ?? '';
            $update['shipping_zip']        = $addr['postal_code'] ?? '';
            $update['shipping_country']    = $addr['country'] ?? '';
        }

        if (!empty($billing['name'])) {
            $billNames = explode(' ', $billing['name'], 2);
            $billAddr  = $billing['address'] ?? [];

            $update['billing_first_name'] = $billNames[0] ?? '';
            $update['billing_last_name']  = $billNames[1] ?? '';
            $update['billing_address1']   = $billAddr['line1'] ?? '';
            $update['billing_address2']   = $billAddr['line2'] ?? null;
            $update['billing_city']       = $billAddr['city'] ?? '';
            $update['billing_state']      = $billAddr['state'] ?? '';
            $update['billing_zip']        = $billAddr['postal_code'] ?? '';
            $update['billing_country']    = $billAddr['country'] ?? '';
        }

        $session->update($update);
    }
}