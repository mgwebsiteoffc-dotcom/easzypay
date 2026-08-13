<?php

namespace App\Services;

use App\Models\CheckoutSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    private string $shopDomain;
    private string $accessToken;
    private string $apiVersion;

    public function __construct(?string $shopDomain = null, ?string $accessToken = null)
    {
        $this->shopDomain  = (string) ($shopDomain  ?? config('services.shopify.store_domain', ''));
        $this->accessToken = (string) ($accessToken ?? config('services.shopify.access_token', ''));
        $this->apiVersion  = config('services.shopify.api_version', '2025-01');
    }

    // ================================================================
    // REST API Helper
    // ================================================================
    private function rest(string $method, string $path, array $body = []): array
    {
        $url = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/{$path}";

        $request = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type'           => 'application/json',
        ])->timeout(30);

        $response = match (strtoupper($method)) {
            'GET'  => $request->get($url),
            'POST' => $request->post($url, $body),
            'PUT'  => $request->put($url, $body),
            default => throw new \RuntimeException("Unknown: {$method}"),
        };

        if (!$response->ok()) {
            throw new \RuntimeException("Shopify REST {$response->status()}: " . substr($response->body(), 0, 300));
        }

        return $response->json() ?? [];
    }

    // ================================================================
    // GraphQL Helper (for webhooks only)
    // ================================================================
    private function graphql(string $query, array $variables = []): array
    {
        $url = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/graphql.json";

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type'           => 'application/json',
        ])->timeout(30)->post($url, ['query' => $query, 'variables' => $variables]);

        if (!$response->ok()) {
            throw new \RuntimeException("Shopify GraphQL HTTP {$response->status()}");
        }

        $json = $response->json();

        if (!empty($json['errors'])) {
            $msg = collect($json['errors'])->map(fn($e) => is_array($e) ? ($e['message'] ?? json_encode($e)) : $e)->implode('; ');
            throw new \RuntimeException("Shopify GraphQL: {$msg}");
        }

        return $json['data'] ?? [];
    }

    // ================================================================
    // Get Shop Info
    // ================================================================
    public function getShopInfo(): ?array
    {
        if (empty($this->shopDomain) || empty($this->accessToken)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
            ])->timeout(15)->get("https://{$this->shopDomain}/admin/api/{$this->apiVersion}/shop.json");

            if (!$response->ok()) {
                Log::error('Shopify shop.json failed', ['status' => $response->status()]);
                return null;
            }

            return $response->json('shop');
        } catch (\Throwable $e) {
            Log::error('getShopInfo error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ================================================================
    // Validate Cart Prices
    // ================================================================
    public function validateCartPrices(array $items): array
    {
        $validated = [];

        foreach ($items as $item) {
            $validated[] = [
                'variant_id'    => (int) ($item['variant_id'] ?? 0),
                'title'         => (string) ($item['title'] ?? 'Product'),
                'variant_title' => (string) ($item['variant_title'] ?? ''),
                'price'         => (int) ($item['price'] ?? $item['price_cents'] ?? 0),
                'price_cents'   => (int) ($item['price'] ?? $item['price_cents'] ?? 0),
                'quantity'      => (int) ($item['quantity'] ?? 1),
                'product_id'    => (int) ($item['product_id'] ?? 0),
                'image'         => (string) ($item['image'] ?? ''),
            ];
        }

        return ['valid' => true, 'items' => $validated, 'errors' => []];
    }

    // ================================================================
    // CREATE ORDER (REST API - No Protected Data Issues)
    // ================================================================
    public function createOrder(CheckoutSession $session, array $paymentData): array
    {
        try {
            // ============================================
            // DUPLICATE CHECK - Prevent double orders
            // ============================================
            if (!empty($session->shopify_order_id)) {
                Log::info('Order already exists in Shopify', [
                    'shopify_order_id'     => $session->shopify_order_id,
                    'shopify_order_number' => $session->shopify_order_number,
                    'session_id'           => $session->session_id,
                ]);

                return [
                    'success'      => true,
                    'shopify_id'   => $session->shopify_order_id,
                    'order_number' => $session->shopify_order_number ?? '#' . $session->shopify_order_id,
                    'order'        => null,
                    'duplicate'    => true,
                ];
            }

            // Check by Stripe PI in Shopify (search existing orders)
            $piId = (string) ($paymentData['payment_intent_id'] ?? '');
            if (!empty($piId)) {
                try {
                    $searchUrl = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/orders.json?status=any&limit=5&tag=easzypay";
                    $searchResponse = Http::withHeaders([
                        'X-Shopify-Access-Token' => $this->accessToken,
                    ])->timeout(15)->get($searchUrl);

                    if ($searchResponse->ok()) {
                        $existingOrders = $searchResponse->json('orders', []);
                        foreach ($existingOrders as $existing) {
                            $existingNote = (string) ($existing['note'] ?? '');
                            if (str_contains($existingNote, $piId)) {
                                Log::info('Duplicate order found in Shopify by PI', [
                                    'existing_order' => $existing['name'] ?? $existing['id'],
                                    'pi_id'          => $piId,
                                ]);

                                // Update session with existing order
                                $session->update([
                                    'shopify_order_id'     => (string) $existing['id'],
                                    'shopify_order_number' => (string) ($existing['name'] ?? '#' . $existing['id']),
                                    'status'               => 'completed',
                                ]);

                                return [
                                    'success'      => true,
                                    'shopify_id'   => (string) $existing['id'],
                                    'order_number' => (string) ($existing['name'] ?? '#' . $existing['id']),
                                    'order'        => $existing,
                                    'duplicate'    => true,
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Non-critical, continue with order creation
                    Log::warning('Duplicate search failed', ['error' => $e->getMessage()]);
                }
            }

            // ============================================
            // PARSE ITEMS
            // ============================================
            $rawItems = $session->items;
            if (is_string($rawItems)) {
                $rawItems = json_decode($rawItems, true) ?? [];
            }
            if (!is_array($rawItems) || empty($rawItems)) {
                throw new \RuntimeException('No items in session');
            }

            $lineItems = [];
            foreach ($rawItems as $item) {
                if (!is_array($item)) continue;
                $vid = (int) ($item['variant_id'] ?? 0);
                if (!$vid) continue;

                $lineItems[] = [
                    'variant_id'       => $vid,
                    'quantity'         => (int) ($item['quantity'] ?? 1),
                    'requires_shipping'=> true,
                ];
            }

            if (empty($lineItems)) {
                throw new \RuntimeException('No valid line items');
            }

            // ============================================
            // CURRENCY & TOTAL
            // ============================================
            $shopCurrency = strtoupper((string) ($session->currency ?? 'USD'));
            try {
                $storeRecord = \App\Models\Store::where('myshopify_domain', $this->shopDomain)->first();
                if ($storeRecord && !empty($storeRecord->currency)) {
                    $shopCurrency = strtoupper((string) $storeRecord->currency);
                }
            } catch (\Throwable $e) {}

            $discountCode   = trim((string) ($session->discount_code ?? ''));
            $discountPercent= (int) ($session->discount_percent ?? 0);
            $discountAmount = (int) ($session->discount_amount ?? 0);

            $paidCurrency   = strtoupper((string) ($session->charged_currency ?? $session->currency ?? $shopCurrency));
            $orderCurrency  = $shopCurrency;
            $total         = (int) ($session->total_amount ?? $session->subtotal ?? 0);

            // If the checkout was paid in a local currency, reverse the conversion using
            // the stored exchange rate to keep the Shopify order total in shop currency.
            if (!empty($session->charged_amount) && !empty($session->exchange_rate) && $session->exchange_rate > 0) {
                $reverseTotal = (int) round($session->charged_amount / $session->exchange_rate);
                Log::info('Reversing local currency total for Shopify order', [
                    'charged_amount'  => $session->charged_amount,
                    'charged_currency'=> $session->charged_currency,
                    'exchange_rate'   => $session->exchange_rate,
                    'reverse_total'   => $reverseTotal,
                    'shop_currency'   => $shopCurrency,
                ]);
                if ($reverseTotal > 0) {
                    $total = $reverseTotal;
                }
            }

            if ($total === 0) {
                $total = (int) ($session->charged_amount ?? $session->subtotal ?? 0);
            }

            $orderTotalString = number_format($total / 100, 2, '.', '');

            // ============================================
            // BUILD STRINGS
            // ============================================
            $firstName = trim((string) ($session->customer_first_name ?? ''));
            $lastName  = trim((string) ($session->customer_last_name  ?? ''));
            $email     = trim((string) ($session->customer_email      ?? ''));
            $phone     = trim((string) ($session->customer_phone      ?? ''));
            $address1  = trim((string) ($session->shipping_address1   ?? ''));
            $address2  = trim((string) ($session->shipping_address2   ?? ''));
            $city      = trim((string) ($session->shipping_city       ?? ''));
            $state     = trim((string) ($session->shipping_state      ?? ''));
            $zip       = trim((string) ($session->shipping_zip        ?? ''));
            $country   = trim((string) ($session->shipping_country    ?? 'US'));

            $piStr     = trim((string) ($paymentData['payment_intent_id'] ?? ''));
            $chargeStr = trim((string) ($paymentData['charge_id'] ?? ''));
            $brandStr  = trim(strtoupper((string) ($paymentData['card_brand'] ?? '')));
            $last4Str  = trim((string) ($paymentData['card_last4'] ?? ''));
            $walletStr = trim((string) ($paymentData['wallet_type'] ?? 'card'));
            $paidCur   = trim(strtoupper((string) ($session->charged_currency ?? $session->currency ?? 'USD')));

            $note = implode(' | ', array_filter([
                'EaszyPay Checkout',
                $piStr ? "Stripe PI: {$piStr}" : null,
                ($brandStr || $last4Str) ? "{$brandStr} ****{$last4Str}" : null,
                "Wallet: {$walletStr}",
                "Customer paid in: {$paidCur}",
            ]));

            Log::info('Creating Shopify order', [
                'session_id'    => $session->session_id,
                'items'         => count($lineItems),
                'order_total'   => $orderTotalString,
                'order_currency'=> $orderCurrency,
                'paid_currency' => $paidCurrency,
                'email'         => $email,
            ]);
            
            $noteAttributes = [];

if (!empty($piStr)) {
    $noteAttributes[] = ['name' => 'Stripe Payment Intent', 'value' => $piStr];
}
if (!empty($chargeStr)) {
    $noteAttributes[] = ['name' => 'Stripe Charge', 'value' => $chargeStr];
}
if (!empty($brandStr) && !empty($last4Str)) {
    $noteAttributes[] = ['name' => 'Card', 'value' => $brandStr . ' ****' . $last4Str];
} elseif (!empty($brandStr)) {
    $noteAttributes[] = ['name' => 'Card', 'value' => $brandStr];
}
if (!empty($walletStr) && $walletStr !== 'card') {
    $noteAttributes[] = ['name' => 'Wallet', 'value' => $walletStr];
}
if (!empty($paidCur)) {
    $noteAttributes[] = ['name' => 'Local Currency', 'value' => $paidCur];
}
if (!empty($session->exchange_rate) && !empty($session->charged_amount)) {
    $noteAttributes[] = ['name' => 'Local Payment Amount', 'value' => number_format(($session->charged_amount / 100), 2) . ' ' . strtoupper($session->charged_currency ?? '')];
    $noteAttributes[] = ['name' => 'Conversion Rate', 'value' => '1 ' . ($session->detected_currency ?? strtoupper($session->charged_currency ?? '')) . ' = ' . number_format($session->exchange_rate, 6) . ' ' . $shopCurrency];
}
$noteAttributes[] = ['name' => 'Checkout', 'value' => 'EaszyPay'];

            $discountCodes = [];
            if (!empty($discountCode) && ($discountPercent > 0 || $discountAmount > 0)) {
                $discountCodes[] = [
                    'code'   => $discountCode,
                    'amount' => $discountPercent > 0 ? number_format($discountPercent, 2, '.', '') : number_format($discountAmount / 100, 2, '.', ''),
                    'type'   => $discountPercent > 0 ? 'percentage' : 'fixed_amount',
                ];
            }

            // ============================================
            // BUILD ORDER PAYLOAD
            // ============================================
            $orderPayload = [
                'currency'         => $orderCurrency,
                'line_items'       => $lineItems,
                'financial_status' => 'paid',
                'tags'             => 'easzypay, stripe, paid',
                'note'             => $note,

                'send_receipt'             => true,
                'send_fulfillment_receipt' => false,
                'inventory_behaviour'      => 'decrement_ignoring_policy',

                'transactions' => [
                    [
                        'kind'    => 'sale',
                        'status'  => 'success',
                        'amount'  => $orderTotalString,
                        'gateway' => 'stripe',
                    ],
                ],

                // Discount code details
                'discount_codes' => $discountCodes,
                'total_discounts' => count($discountCodes) ? number_format($discountAmount / 100, 2, '.', '') : '0.00',

                // Notes for backend reconciliation
                'note_attributes' => $noteAttributes,

                // Force shipping required
                'shipping_lines' => [
                    [
                        'title'  => 'Standard Shipping',
                        'price'  => '0.00',
                        'code'   => 'standard',
                        'source' => 'easzypay',
                    ],
                ],
            ];

            // Email
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $orderPayload['email'] = $email;
                $orderPayload['customer'] = [
                    'first_name' => $firstName ?: 'Customer',
                    'last_name'  => $lastName,
                    'email'      => $email,
                ];
                if (!empty($phone)) {
                    $orderPayload['customer']['phone'] = $phone;
                }
            }

            // Shipping address
            if (!empty($address1)) {
                $shippingAddr = [
                    'first_name' => $firstName ?: 'Customer',
                    'last_name'  => $lastName,
                    'address1'   => $address1,
                    'address2'   => $address2,
                    'city'       => $city,
                    'province'   => $state,
                    'zip'        => $zip,
                    'country'    => $country,
                    'phone'      => $phone,
                ];
                $orderPayload['shipping_address'] = $shippingAddr;

                $billingFirstName = trim((string) ($session->billing_first_name ?? '')) ?: $firstName;
                $billingLastName  = trim((string) ($session->billing_last_name ?? '')) ?: $lastName;
                $billingAddress1  = trim((string) ($session->billing_address1 ?? '')) ?: $address1;
                $billingAddress2  = trim((string) ($session->billing_address2 ?? '')) ?: $address2;
                $billingCity      = trim((string) ($session->billing_city ?? '')) ?: $city;
                $billingState     = trim((string) ($session->billing_state ?? '')) ?: $state;
                $billingZip       = trim((string) ($session->billing_zip ?? '')) ?: $zip;
                $billingCountry   = trim((string) ($session->billing_country ?? '')) ?: $country;

                $billingAddr = [
                    'first_name' => $billingFirstName ?: 'Customer',
                    'last_name'  => $billingLastName,
                    'address1'   => $billingAddress1,
                    'address2'   => $billingAddress2,
                    'city'       => $billingCity,
                    'province'   => $billingState,
                    'zip'        => $billingZip,
                    'country'    => $billingCountry,
                    'phone'      => $phone,
                ];
                $orderPayload['billing_address'] = $billingAddr;
            }

            // Metafields - only non-empty values
            $metafields = [];
            $mfData = [
                'payment_intent_id' => $piStr,
                'charge_id'         => $chargeStr,
                'card_brand'        => $brandStr,
                'card_last4'        => $last4Str,
                'wallet_type'       => $walletStr,
                'session_id'        => (string) $session->session_id,
                'charged_currency'  => $paidCur,
                'exchange_rate'     => (string) ($session->exchange_rate ?? '1.0'),
            ];

            foreach ($mfData as $key => $val) {
                $val = trim((string) $val);
                if ($val !== '' && $val !== '0' || $key === 'exchange_rate') {
                    if ($val === '') $val = 'N/A';
                    $metafields[] = [
                        'namespace' => 'easzypay',
                        'key'       => $key,
                        'value'     => $val,
                        'type'      => 'single_line_text_field',
                    ];
                }
            }

            if (!empty($metafields)) {
                $orderPayload['metafields'] = $metafields;
            }

            // ============================================
            // SEND TO SHOPIFY
            // ============================================
            $url = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/orders.json";

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type'           => 'application/json',
            ])->timeout(30)->post($url, ['order' => $orderPayload]);

            if (!$response->ok()) {
                $errBody = $response->json();
                $errors  = $errBody['errors'] ?? $response->body();

                if (is_array($errors)) {
                    $errorMsg = collect($errors)->map(function ($v, $k) {
                        if (is_array($v)) return "{$k}: " . implode(', ', $v);
                        return "{$k}: {$v}";
                    })->implode('; ');
                } else {
                    $errorMsg = (string) $errors;
                }

                throw new \RuntimeException("Shopify ({$response->status()}): {$errorMsg}");
            }

            $orderData = $response->json('order');

            if (!$orderData) {
                throw new \RuntimeException('No order in response');
            }

    $shopifyId   = (string) $orderData['id'];
$orderNumber = (string) ($orderData['name'] ?? '#' . $shopifyId);
$thankYouUrl = $orderData['order_status_url'] ?? null;

// ADD: Get thank you URL
$thankYouUrl = $orderData['order_status_url'] ?? null;

Log::info('✅ Shopify order created', [
    'shopify_id'      => $shopifyId,
    'order_number'    => $orderNumber,
    'financial'       => $orderData['financial_status'] ?? '?',
    'fulfillment'     => $orderData['fulfillment_status'] ?? 'unfulfilled',
    'total'           => $orderData['total_price'] ?? '?',
    'thank_you_url'   => $thankYouUrl,
    'items'           => count($orderData['line_items'] ?? []),
]);

return [
    'success'        => true,
    'shopify_id'     => $shopifyId,
    'order_number'   => $orderNumber,
    'thank_you_url'  => $thankYouUrl,
    'order'          => $orderData,
];

        } catch (\Throwable $e) {
            Log::error('createOrder failed', [
                'error'      => $e->getMessage(),
                'session_id' => $session->session_id,
                'shop'       => $this->shopDomain,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ================================================================
    // Register Webhooks
    // ================================================================
    public function registerWebhooks(): array
    {
        $appUrl = config('app.url');
        $topics = [
            'ORDERS_CREATE'   => '/webhook/shopify/orders-create',
            'ORDERS_PAID'     => '/webhook/shopify/orders-paid',
            'REFUNDS_CREATE'  => '/webhook/shopify/refunds-create',
            'APP_UNINSTALLED' => '/webhook/shopify/app-uninstalled',
        ];

        $results = [];
        foreach ($topics as $topic => $path) {
            try {
                $this->graphql(
                    'mutation($topic: WebhookSubscriptionTopic!, $sub: WebhookSubscriptionInput!) { webhookSubscriptionCreate(topic: $topic, webhookSubscription: $sub) { userErrors { message } } }',
                    ['topic' => $topic, 'sub' => ['callbackUrl' => $appUrl . $path, 'format' => 'JSON']]
                );
                $results[$topic] = ['success' => true];
            } catch (\Throwable $e) {
                $results[$topic] = ['success' => false, 'error' => $e->getMessage()];
            }
        }
        return $results;
    }

    // ================================================================
    // Inject Theme Button
    // ================================================================
public function injectThemeSnippet(string $snippetContent): array
{
    try {
        // Step 1: Get active theme via REST
        $themes = $this->rest('GET', 'themes.json');

        $activeTheme = null;
        foreach ($themes['themes'] ?? [] as $theme) {
            if ($theme['role'] === 'main') {
                $activeTheme = $theme;
                break;
            }
        }

        if (!$activeTheme) {
            throw new \RuntimeException('No active theme found');
        }

        $themeId = $activeTheme['id'];

        Log::info('Found active theme', [
            'theme_id'   => $themeId,
            'theme_name' => $activeTheme['name'] ?? 'unknown',
        ]);
        Log::info('Shopify API version', [
    'version' => $this->apiVersion,
]);

        // Step 2: Try REST API first (faster)
        try {
            $restResult = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type'           => 'application/json',
            ])->timeout(30)->put(
                "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/themes/{$themeId}/assets.json",
                [
                    'asset' => [
                        'key'   => 'snippets/easzypay-button.liquid',
                        'value' => $snippetContent,
                    ],
                ]
            );

            if ($restResult->ok()) {
                Log::info('Button uploaded via REST', ['theme_id' => $themeId]);
                return ['success' => true, 'theme_id' => $themeId, 'method' => 'rest'];
            }

            Log::warning('REST upload failed, trying GraphQL', [
                'status' => $restResult->status(),
                'body'   => substr($restResult->body(), 0, 200),
            ]);
        } catch (\Throwable $e) {
            Log::warning('REST upload exception', ['error' => $e->getMessage()]);
        }

        // Step 3: Fallback to GraphQL themeFilesUpsert (new API)
        $themeGid = "gid://shopify/OnlineStoreTheme/{$themeId}";

        $mutation = <<<'GQL'
        mutation themeFilesUpsert($themeId: ID!, $files: [OnlineStoreThemeFilesUpsertFileInput!]!) {
            themeFilesUpsert(themeId: $themeId, files: $files) {
                upsertedThemeFiles {
                    filename
                }
                userErrors {
                    field
                    message
                }
            }
        }
GQL;

        $variables = [
            'themeId' => $themeGid,
            'files'   => [
                [
                    'filename' => 'snippets/easzypay-button.liquid',
                    'body'     => [
                        'type'  => 'TEXT',
                        'value' => $snippetContent,
                    ],
                ],
            ],
        ];

        $result      = $this->graphql($mutation, $variables);
        $upsertData  = $result['themeFilesUpsert'] ?? [];
        $userErrors  = $upsertData['userErrors'] ?? [];

        if (!empty($userErrors)) {
            $msg = collect($userErrors)->map(fn($e) => ($e['field'] ?? '?') . ': ' . $e['message'])->implode('; ');
            throw new \RuntimeException("GraphQL theme upsert: {$msg}");
        }

        $uploaded = $upsertData['upsertedThemeFiles'] ?? [];

        Log::info('Button uploaded via GraphQL', [
            'theme_id'  => $themeId,
            'files'     => count($uploaded),
        ]);

        return ['success' => true, 'theme_id' => $themeId, 'method' => 'graphql'];

    } catch (\Throwable $e) {
        Log::error('Theme snippet injection failed', [
            'error' => $e->getMessage(),
            'shop'  => $this->shopDomain,
        ]);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

    // ================================================================
    // Country Name
    // ================================================================
    private function getCountryName(string $code): string
    {
        $map = [
            'US'=>'United States','GB'=>'United Kingdom','CA'=>'Canada','AU'=>'Australia',
            'DE'=>'Germany','FR'=>'France','IT'=>'Italy','ES'=>'Spain','NL'=>'Netherlands',
            'AE'=>'United Arab Emirates','SG'=>'Singapore','IN'=>'India','JP'=>'Japan',
            'SE'=>'Sweden','NO'=>'Norway','DK'=>'Denmark','CH'=>'Switzerland','NZ'=>'New Zealand',
            'IE'=>'Ireland','ZA'=>'South Africa','BR'=>'Brazil','MX'=>'Mexico',
            'AT'=>'Austria','BE'=>'Belgium','PT'=>'Portugal','FI'=>'Finland',
            'HK'=>'Hong Kong','KR'=>'South Korea','MY'=>'Malaysia','TH'=>'Thailand',
            'SA'=>'Saudi Arabia','PH'=>'Philippines','ID'=>'Indonesia',
        ];
        return $map[strtoupper(trim($code))] ?? $code;
    }
}