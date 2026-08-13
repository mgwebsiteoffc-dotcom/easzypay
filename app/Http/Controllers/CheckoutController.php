<?php

namespace App\Http\Controllers;

use App\Models\CheckoutSession;
use App\Models\Store;
use App\Models\PaymentLog;
use App\Services\ExchangeRateService;
use App\Services\ShopifyService;
use App\Services\GeoLocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    // ================================================================
    // RECEIVE CART FROM SHOPIFY
    // ================================================================
    public function receiveCart(Request $request): JsonResponse
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');

        if ($request->isMethod('options')) {
            return response()->json(['status' => 'ok']);
        }

        if ($request->isMethod('get')) {
            return response()->json(['status' => 'active', 'message' => 'EaszyPay Cart API']);
        }

        try {
            $data = $request->json()->all();

            if (empty($data['items'])) {
                return response()->json(['error' => ['message' => 'No items']], 400);
            }

            $storeId  = $data['store_id'] ?? null;
            $store    = null;
            $tenantId = null;

            if ($storeId) {
                $store = Store::find($storeId);
            }

            if (!$store && !empty($data['shop_domain'])) {
                $domain = strtolower(str_replace(['https://', 'http://'], '', $data['shop_domain']));
                $store = Store::where('myshopify_domain', $domain)
                    ->orWhere('shop_domain', $domain)
                    ->first();
            }

            if ($store) {
                $tenantId = $store->tenant_id;
            }

            $items    = [];
            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $price = (int) ($item['price'] ?? $item['price_cents'] ?? 0);
                $qty   = max(1, (int) ($item['quantity'] ?? 1));

                $items[] = [
                    'product_id'    => (int) ($item['product_id'] ?? 0),
                    'variant_id'    => (int) ($item['variant_id'] ?? 0),
                    'quantity'      => $qty,
                    'title'         => trim($item['title'] ?? 'Product'),
                    'variant_title' => trim($item['variant_title'] ?? ''),
                    'price'         => $price,
                    'image'         => $item['image'] ?? '',
                ];

                $subtotal += $price * $qty;
            }

            $sessionId = bin2hex(random_bytes(32));

            CheckoutSession::create([
                'session_id'   => $sessionId,
                'tenant_id'    => $tenantId,
                'store_id'     => $store?->id,
                'shop_domain'  => strtolower(str_replace(['https://', 'http://'], '', $data['shop_domain'] ?? '')),
                'cart_token'   => $data['cart_token'] ?? null,
                'items'        => $items,
                'currency'     => strtoupper($data['currency'] ?? 'USD'),
                'subtotal'     => $subtotal,
                'total_amount' => $subtotal,
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'status'       => 'pending',
                'expires_at'   => now()->addHour(),
            ]);

            return response()->json([
                'success'      => true,
                'session_id'   => $sessionId,
                'checkout_url' => route('checkout.show', $sessionId),
                'subtotal'     => $subtotal,
                'currency'     => strtoupper($data['currency'] ?? 'USD'),
                'item_count'   => count($items),
            ]);

        } catch (\Throwable $e) {
            Log::error('Cart error', ['error' => $e->getMessage()]);
            return response()->json(['error' => ['message' => $e->getMessage()]], 500);
        }
    }

    // ================================================================
    // SHOW CHECKOUT PAGE
    // ================================================================
    public function show(Request $request, string $sessionId)
    {
        $session = CheckoutSession::where('session_id', $sessionId)->active()->first();

        if (!$session) {
            return view('checkout.expired');
        }

        $baseCurrency = strtoupper($session->currency ?? 'USD');
        $geoService = app(GeoLocationService::class);
        $detected = $geoService->detect($request->ip());
        $detectedCurrency = strtoupper($detected['currency'] ?? $baseCurrency);

        $exchangeService = app(ExchangeRateService::class);
        $displayCurrency = $baseCurrency;
        $displayRate = 1.0;
        $displaySubtotal = (int) $session->subtotal;

        if ($detectedCurrency !== $baseCurrency) {
            $rateData = $exchangeService->convert($baseCurrency, $detectedCurrency, $session->subtotal);
            $displayRate = $rateData['rate'] ?? 1.0;
            $displaySubtotal = $rateData['converted_amount'] ?? $session->subtotal;
            $displayCurrency = ($rateData['fallback'] ?? false) ? $baseCurrency : $detectedCurrency;
        }

        return view('checkout.show', [
            'session'          => $session,
            'currencies'       => $geoService->getCurrencyMeta(),
            'stripeKey'        => config('services.stripe.key'),
            'displayCurrency'  => $displayCurrency,
            'displayRate'      => $displayRate,
            'displaySubtotal'  => $displaySubtotal,
            'detectedCurrency' => $detectedCurrency,
        ]);
    }

    // ================================================================
    // SUCCESS PAGE - THIS IS WHERE SHOPIFY ORDER IS CREATED
    // ================================================================
public function success(Request $request)
{
    $sessionId      = $request->get('session', '');
    $piId           = $request->get('payment_intent', '');
    $redirectStatus = $request->get('redirect_status', '');

    Log::info('=== SUCCESS PAGE HIT ===', [
        'session_id'      => $sessionId,
        'payment_intent'  => $piId,
        'redirect_status' => $redirectStatus,
    ]);

    $session     = null;
    $paymentData = null;
    $orderResult = null;

    if (!empty($sessionId)) {
        $session = CheckoutSession::where('session_id', $sessionId)->first();
    }

    if ($piId && $redirectStatus === 'succeeded' && $session) {

        try {
            $stripeSecret = config('services.stripe.secret');
            if (empty($stripeSecret)) {
                throw new \Exception('Stripe not configured');
            }

            $stripe = new \Stripe\StripeClient($stripeSecret);

            $paymentData = $stripe->paymentIntents->retrieve($piId, [
                'expand' => ['latest_charge', 'latest_charge.payment_method_details'],
            ]);

            $charge = $paymentData->latest_charge;

            $cardBrand   = $charge?->payment_method_details?->card?->brand ?? null;
            $cardLast4   = $charge?->payment_method_details?->card?->last4 ?? null;
            $walletType  = $charge?->payment_method_details?->card?->wallet?->type ?? null;
            $cardCountry = $charge?->payment_method_details?->card?->country ?? null;

            $session->update([
                'status'                   => 'paid',
                'stripe_payment_intent_id' => $piId,
                'stripe_charge_id'         => $charge?->id,
                'payment_method_type'      => $charge?->payment_method_details?->type ?? 'card',
                'card_brand'               => $cardBrand,
                'card_last4'               => $cardLast4,
                'wallet_type'              => $walletType,
                'card_country'             => $cardCountry,
                'paid_at'                  => now(),
            ]);

            // Reload to get fresh data
            $session = CheckoutSession::where('session_id', $sessionId)->first();

            Log::info('Session updated to paid', [
                'session_id' => $sessionId,
                'card'       => ($cardBrand ?? '') . ' ' . ($cardLast4 ?? ''),
            ]);

            // CREATE SHOPIFY ORDER
            if (!$session->shopify_order_id) {
                $orderResult = $this->createShopifyOrder($session, $paymentData);

                // CRITICAL: Reload session from DB after Shopify order creation
                $session = CheckoutSession::where('session_id', $sessionId)->first();

                Log::info('After Shopify order creation', [
                    'session_id'           => $sessionId,
                    'shopify_order_id'     => $session->shopify_order_id ?? 'NONE',
                    'shopify_order_number' => $session->shopify_order_number ?? 'NONE',
                    'thank_you_url'        => $session->shopify_thank_you_url ?? 'NONE',
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Success page error', [
                'error'      => $e->getMessage(),
                'session_id' => $sessionId,
            ]);
        }
    }

    $thankYou = $session->shopify_thank_you_url
        ?? ($orderResult['thank_you_url'] ?? null)
        ?? ($orderResult['order']['order_status_url'] ?? null);

    if ($redirectStatus === 'succeeded' && !empty($thankYou)) {
        return redirect()->away($thankYou);
    }

    return view('checkout.success', [
        'session'        => $session,
        'paymentData'    => $paymentData,
        'redirectStatus' => $redirectStatus,
        'piId'           => $piId,
        'orderResult'    => $orderResult,
    ]);
}
    public function cancel()
    {
        return view('checkout.cancel');
    }

    // ================================================================
    // CREATE SHOPIFY ORDER
    // ================================================================
private function createShopifyOrder(CheckoutSession $session, $paymentIntent): array
{
    $store = null;

    if ($session->store_id) {
        $store = \App\Models\Store::find($session->store_id);
    }

    if (!$store && $session->shop_domain) {
        $store = \App\Models\Store::where('myshopify_domain', $session->shop_domain)
            ->orWhere('shop_domain', $session->shop_domain)
            ->first();
    }

    if (!$store) {
        $store = \App\Models\Store::where('is_active', true)->first();
    }

    if (!$store || empty($store->access_token)) {
        Log::error('No store with valid token', [
            'session_id'  => $session->session_id,
            'store_id'    => $session->store_id,
            'shop_domain' => $session->shop_domain,
        ]);
        return ['success' => false, 'error' => 'No store with valid token'];
    }

    // Get charge details
    $charge = $paymentIntent->latest_charge ?? null;

    $paymentData = [
        'payment_intent_id' => $paymentIntent->id,
        'charge_id'         => $charge?->id ?? '',
        'card_brand'        => $charge?->payment_method_details?->card?->brand ?? 'card',
        'card_last4'        => $charge?->payment_method_details?->card?->last4 ?? '',
        'wallet_type'       => $charge?->payment_method_details?->card?->wallet?->type ?? 'card',
    ];

    Log::info('=== ATTEMPTING SHOPIFY ORDER ===', [
        'session_id'    => $session->session_id,
        'store'         => $store->shop_name,
        'shop'          => $store->myshopify_domain,
        'pi_id'         => $paymentData['payment_intent_id'],
        'charge_id'     => $paymentData['charge_id'],
        'card'          => $paymentData['card_brand'] . ' ' . $paymentData['card_last4'],
        'wallet'        => $paymentData['wallet_type'],
    ]);

    $shopify = new \App\Services\ShopifyService($store->myshopify_domain, $store->access_token);
    $result  = $shopify->createOrder($session, $paymentData);

    if ($result['success']) {
        $session->update([
            'shopify_order_id'      => $result['shopify_id'],
            'shopify_order_number'  => $result['order_number'],
            'shopify_thank_you_url' => $result['thank_you_url'] ?? null,
            'store_id'              => $store->id,
            'status'                => 'completed',
        ]);

        try {
            $store->increment('total_orders');
            $store->increment('total_revenue', ($session->charged_amount ?? $session->subtotal) / 100);
        } catch (\Throwable $e) {}

        Log::info('✅ SHOPIFY ORDER SAVED TO DB', [
            'session_id'    => $session->session_id,
            'order_number'  => $result['order_number'],
            'shopify_id'    => $result['shopify_id'],
            'thank_you_url' => $result['thank_you_url'] ?? 'NONE',
        ]);

        try {
            \App\Models\PaymentLog::create([
                'session_id'               => $session->session_id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_charge_id'         => $charge?->id,
                'shopify_order_id'         => $result['shopify_id'],
                'event_type'               => 'shopify.order.created',
                'status'                   => 'success',
                'amount'                   => $session->charged_amount ?? $session->subtotal,
                'currency'                 => $session->charged_currency ?? $session->currency,
                'payment_method_type'      => 'card',
                'card_brand'               => $paymentData['card_brand'],
                'card_last4'               => $paymentData['card_last4'],
                'wallet_type'              => $paymentData['wallet_type'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('PaymentLog save failed', ['error' => $e->getMessage()]);
        }

    } else {
        Log::error('❌ Shopify order failed', [
            'session_id' => $session->session_id,
            'error'      => $result['error'] ?? 'unknown',
        ]);

        try {
            \App\Models\PaymentLog::create([
                'session_id'               => $session->session_id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'event_type'               => 'shopify.order.failed',
                'status'                   => 'failed',
                'error_message'            => $result['error'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {}
    }

    return $result;
}
}