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

    public function getShopPolicies(): array
    {
        $policies = [
            'shipping' => null,
            'refund'   => null,
            'privacy'  => null,
            'terms'    => null,
        ];

        try {
            $shop = $this->getShopInfo();
            if (is_array($shop)) {
                $policies['shipping'] = $shop['shipping_policy']['url'] ?? $shop['shipping_policy_url'] ?? null;
                $policies['refund']   = $shop['refund_policy']['url'] ?? $shop['refund_policy_url'] ?? null;
                $policies['privacy']  = $shop['privacy_policy']['url'] ?? $shop['privacy_policy_url'] ?? null;
                $policies['terms']    = $shop['terms_of_service']['url'] ?? $shop['terms_of_service_url'] ?? null;
            }
        } catch (\Throwable $e) {
            Log::warning('getShopPolicies shop.json failed', ['error' => $e->getMessage()]);
        }

        try {
            $data = $this->rest('GET', 'policies.json');
            foreach (($data['policies'] ?? []) as $policy) {
                $url = $policy['url'] ?? null;
                $handle = strtolower((string) ($policy['handle'] ?? $policy['title'] ?? ''));
                if (str_contains($handle, 'shipping')) {
                    $policies['shipping'] = $policies['shipping'] ?: $url;
                }
                if (str_contains($handle, 'refund') || str_contains($handle, 'return')) {
                    $policies['refund'] = $policies['refund'] ?: $url;
                }
                if (str_contains($handle, 'privacy')) {
                    $policies['privacy'] = $policies['privacy'] ?: $url;
                }
                if (str_contains($handle, 'term')) {
                    $policies['terms'] = $policies['terms'] ?: $url;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('getShopPolicies policies.json failed', ['error' => $e->getMessage()]);
        }

        $base = 'https://' . $this->shopDomain;
        $policies['shipping'] = $policies['shipping'] ?: $base . '/policies/shipping-policy';
        $policies['refund']   = $policies['refund'] ?: $base . '/policies/refund-policy';
        $policies['privacy']  = $policies['privacy'] ?: $base . '/policies/privacy-policy';
        $policies['terms']    = $policies['terms'] ?: $base . '/policies/terms-of-service';

        return $policies;
    }

    public function lookupDiscountCode(string $code, int $subtotalCents, array $items = []): array
    {
        $code = trim($code);
        if ($code === '') {
            return ['valid' => false, 'error' => 'Enter a discount code'];
        }

        $graphql = $this->lookupDiscountViaGraphql($code, $subtotalCents, $items);
        if (!empty($graphql['handled'])) {
            return $graphql;
        }

        return $this->lookupDiscountViaRest($code, $subtotalCents, $items);
    }

    private function lookupDiscountViaGraphql(string $code, int $subtotalCents, array $items): array
    {
        $query = <<<'GQL'
        query DiscountByCode($code: String!) {
            codeDiscountNodeByCode(code: $code) {
                id
                codeDiscount {
                    __typename
                    ... on DiscountCodeBasic {
                        title
                        status
                        startsAt
                        endsAt
                        usageLimit
                        asyncUsageCount
                        minimumRequirement {
                            __typename
                            ... on DiscountMinimumSubtotal {
                                greaterThanOrEqualToSubtotal { amount }
                            }
                            ... on DiscountMinimumQuantity {
                                greaterThanOrEqualToQuantity
                            }
                        }
                        customerGets {
                            value {
                                __typename
                                ... on DiscountPercentage { percentage }
                                ... on DiscountAmount {
                                    amount { amount }
                                    appliesOnEachItem
                                }
                            }
                            items {
                                __typename
                                ... on AllDiscountItems { allItems }
                                ... on DiscountProducts {
                                    products(first: 100) { nodes { id } }
                                    productVariants(first: 100) { nodes { id } }
                                }
                                ... on DiscountCollections {
                                    collections(first: 50) { nodes { id } }
                                }
                            }
                        }
                    }
                    ... on DiscountCodeFreeShipping {
                        title
                        status
                        startsAt
                        endsAt
                        usageLimit
                        asyncUsageCount
                        minimumRequirement {
                            __typename
                            ... on DiscountMinimumSubtotal {
                                greaterThanOrEqualToSubtotal { amount }
                            }
                            ... on DiscountMinimumQuantity {
                                greaterThanOrEqualToQuantity
                            }
                        }
                    }
                    ... on DiscountCodeBxgy {
                        title
                        status
                    }
                    ... on DiscountCodeApp {
                        title
                        status
                    }
                }
            }
        }
GQL;

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(20)->post(
                "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/graphql.json",
                ['query' => $query, 'variables' => ['code' => $code]]
            );

            if (!$response->ok()) {
                Log::warning('GraphQL discount HTTP failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 300),
                ]);
                return ['handled' => false];
            }

            $json = $response->json() ?? [];
            if (!empty($json['errors'])) {
                Log::warning('GraphQL discount errors', ['errors' => $json['errors']]);
            }
            $data = $json['data'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('GraphQL discount lookup failed, will try REST', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
            return ['handled' => false];
        }

        $node = $data['codeDiscountNodeByCode']['codeDiscount'] ?? null;
        if (!$node) {
            return ['handled' => false];
        }

        $type = (string) ($node['__typename'] ?? '');
        $status = strtoupper((string) ($node['status'] ?? ''));
        if ($status !== '' && $status !== 'ACTIVE') {
            return ['handled' => true, 'valid' => false, 'error' => 'This discount code is not active'];
        }

        $now = now();
        if (!empty($node['startsAt']) && $now->lt($node['startsAt'])) {
            return ['handled' => true, 'valid' => false, 'error' => 'This discount code is not active yet'];
        }
        if (!empty($node['endsAt']) && $now->gt($node['endsAt'])) {
            return ['handled' => true, 'valid' => false, 'error' => 'This discount code has expired'];
        }

        $usageLimit = $node['usageLimit'] ?? null;
        $usageCount = (int) ($node['asyncUsageCount'] ?? 0);
        if ($usageLimit !== null && $usageCount >= (int) $usageLimit) {
            return ['handled' => true, 'valid' => false, 'error' => 'This discount code has reached its usage limit'];
        }

        $qty = 0;
        foreach ($items as $item) {
            $qty += max(1, (int) ($item['quantity'] ?? 1));
        }

        $min = $node['minimumRequirement'] ?? null;
        if (is_array($min)) {
            $minAmount = (float) ($min['greaterThanOrEqualToSubtotal']['amount'] ?? 0);
            if ($minAmount > 0 && ($subtotalCents / 100) < $minAmount) {
                return [
                    'handled' => true,
                    'valid' => false,
                    'error' => 'Add ' . number_format($minAmount - ($subtotalCents / 100), 2) . ' more to use this code',
                ];
            }
            $minQty = (int) ($min['greaterThanOrEqualToQuantity'] ?? 0);
            if ($minQty > 0 && $qty < $minQty) {
                return [
                    'handled' => true,
                    'valid' => false,
                    'error' => 'Add ' . ($minQty - $qty) . ' more item(s) to use this code',
                ];
            }
        }

        if ($type === 'DiscountCodeFreeShipping') {
            return [
                'handled' => true,
                'valid' => true,
                'code' => $code,
                'title' => $node['title'] ?? $code,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'free_shipping' => true,
            ];
        }

        if ($type === 'DiscountCodeBxgy' || $type === 'DiscountCodeApp') {
            return [
                'handled' => true,
                'valid' => false,
                'error' => 'This discount type can only be used on Shopify checkout',
            ];
        }

        $eligibleCents = $this->eligibleDiscountSubtotal($items, $subtotalCents, $node['customerGets']['items'] ?? []);
        if ($eligibleCents <= 0) {
            return ['handled' => true, 'valid' => false, 'error' => 'This code does not apply to the items in your cart'];
        }

        $value = $node['customerGets']['value'] ?? [];
        $discountPercent = 0.0;
        $discountAmount = 0;
        $valueType = (string) ($value['__typename'] ?? '');

        if ($valueType === 'DiscountPercentage' || isset($value['percentage'])) {
            $pct = (float) ($value['percentage'] ?? 0);
            if ($pct > 0 && $pct <= 1) {
                $pct *= 100;
            }
            $discountPercent = $pct;
            $discountAmount = (int) round($eligibleCents * ($pct / 100));
        } elseif (isset($value['amount']['amount'])) {
            $fixed = (float) $value['amount']['amount'];
            $each = !empty($value['appliesOnEachItem']);
            $discountAmount = (int) round($fixed * 100 * ($each ? max(1, $qty) : 1));
            $discountAmount = min($discountAmount, $eligibleCents);
        }

        if ($discountAmount <= 0 && $discountPercent <= 0) {
            return ['handled' => true, 'valid' => false, 'error' => 'This discount code cannot be applied'];
        }

        return [
            'handled' => true,
            'valid' => true,
            'code' => $code,
            'title' => $node['title'] ?? $code,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'free_shipping' => false,
        ];
    }

    private function lookupDiscountViaRest(string $code, int $subtotalCents, array $items): array
    {
        try {
            $lookup = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
            ])->timeout(15)->get(
                "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/discount_codes/lookup.json",
                ['code' => $code]
            );

            if ($lookup->status() === 404 || !$lookup->ok()) {
                return ['valid' => false, 'error' => 'Enter a valid discount code'];
            }

            $discount = $lookup->json('discount_code');
            $priceRuleId = $discount['price_rule_id'] ?? null;
            if (!$priceRuleId) {
                return ['valid' => false, 'error' => 'Enter a valid discount code'];
            }

            $ruleRes = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
            ])->timeout(15)->get(
                "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/price_rules/{$priceRuleId}.json"
            );

            if (!$ruleRes->ok()) {
                return ['valid' => false, 'error' => 'Could not load discount'];
            }

            $rule = $ruleRes->json('price_rule') ?? [];
            $now = now();
            if (!empty($rule['starts_at']) && $now->lt($rule['starts_at'])) {
                return ['valid' => false, 'error' => 'This discount code is not active yet'];
            }
            if (!empty($rule['ends_at']) && $now->gt($rule['ends_at'])) {
                return ['valid' => false, 'error' => 'This discount code has expired'];
            }

            $prerequisite = (float) ($rule['prerequisite_subtotal_range']['greater_than_or_equal_to'] ?? 0);
            if ($prerequisite > 0 && ($subtotalCents / 100) < $prerequisite) {
                return ['valid' => false, 'error' => 'Cart does not meet the minimum for this code'];
            }

            $valueType = $rule['value_type'] ?? 'percentage';
            $value = (float) ($rule['value'] ?? 0);
            $abs = abs($value);
            $freeShipping = ($rule['target_type'] ?? '') === 'shipping_line';

            $eligibleIds = [];
            foreach (($rule['entitled_product_ids'] ?? []) as $id) {
                $eligibleIds['p' . $id] = true;
            }
            foreach (($rule['entitled_variant_ids'] ?? []) as $id) {
                $eligibleIds['v' . $id] = true;
            }
            $eligibleCents = $this->eligibleSubtotalFromIds($items, $subtotalCents, $eligibleIds);

            if ($freeShipping) {
                return [
                    'valid' => true,
                    'code' => $code,
                    'title' => $rule['title'] ?? $code,
                    'discount_percent' => 0,
                    'discount_amount' => 0,
                    'free_shipping' => true,
                ];
            }

            $discountPercent = 0;
            $discountAmount = 0;
            if ($valueType === 'percentage') {
                $discountPercent = $abs;
                $discountAmount = (int) round($eligibleCents * ($discountPercent / 100));
            } else {
                $discountAmount = min($eligibleCents, (int) round($abs * 100));
            }

            if ($discountAmount <= 0) {
                return ['valid' => false, 'error' => 'This code does not apply to the items in your cart'];
            }

            return [
                'valid' => true,
                'code' => $code,
                'title' => $rule['title'] ?? $code,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'free_shipping' => false,
            ];
        } catch (\Throwable $e) {
            Log::error('lookupDiscountCode REST failed', ['error' => $e->getMessage()]);
            return ['valid' => false, 'error' => 'Could not validate discount code'];
        }
    }

    private function eligibleDiscountSubtotal(array $items, int $fallbackCents, array $getsItems): int
    {
        $type = (string) ($getsItems['__typename'] ?? '');
        if ($type === '' || $type === 'AllDiscountItems' || !empty($getsItems['allItems'])) {
            return max(0, $fallbackCents);
        }

        $productIds = [];
        $variantIds = [];
        foreach (($getsItems['products']['nodes'] ?? []) as $node) {
            $productIds[] = $this->gidId($node['id'] ?? '');
        }
        foreach (($getsItems['productVariants']['nodes'] ?? []) as $node) {
            $variantIds[] = $this->gidId($node['id'] ?? '');
        }

        $collectionIds = [];
        foreach (($getsItems['collections']['nodes'] ?? []) as $node) {
            $collectionIds[] = $this->gidId($node['id'] ?? '');
        }

        if (empty($productIds) && empty($variantIds) && empty($collectionIds)) {
            return max(0, $fallbackCents);
        }

        $eligible = 0;
        foreach ($items as $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $vid = (int) ($item['variant_id'] ?? 0);
            $line = (int) ($item['price'] ?? $item['price_cents'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1));
            $ok = in_array($pid, $productIds, true) || in_array($vid, $variantIds, true);
            if (!$ok && $pid && $collectionIds) {
                $ok = $this->productInCollections($pid, $collectionIds);
            }
            if ($ok) {
                $eligible += $line;
            }
        }

        return $eligible;
    }

    private function eligibleSubtotalFromIds(array $items, int $fallbackCents, array $eligibleIds): int
    {
        if (empty($eligibleIds)) {
            return max(0, $fallbackCents);
        }
        $eligible = 0;
        foreach ($items as $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $vid = (int) ($item['variant_id'] ?? 0);
            if (isset($eligibleIds['p' . $pid]) || isset($eligibleIds['v' . $vid])) {
                $eligible += (int) ($item['price'] ?? $item['price_cents'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1));
            }
        }
        return $eligible;
    }

    private function productInCollections(int $productId, array $collectionIds): bool
    {
        if (!$productId || empty($collectionIds)) {
            return false;
        }
        try {
            $res = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
            ])->timeout(15)->get(
                "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/products/{$productId}/collections.json"
            );
            if (!$res->ok()) {
                return false;
            }
            foreach (($res->json('collections') ?? []) as $col) {
                if (in_array((int) ($col['id'] ?? 0), $collectionIds, true)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }
        return false;
    }

    private function gidId(string $gid): int
    {
        if (preg_match('/\/(\d+)$/', $gid, $m)) {
            return (int) $m[1];
        }
        return (int) $gid;
    }

    public function getShippingRates(string $countryCode, ?string $provinceCode, int $subtotalCents, array $context = []): array
    {
        $countryCode = strtoupper($countryCode);
        $items = is_array($context['items'] ?? null) ? $context['items'] : [];
        $zip = trim((string) ($context['zip'] ?? ''));
        $city = trim((string) ($context['city'] ?? ''));

        $tax = 0;
        $duties = 0;
        $rates = [];

        $calculated = $this->calculateShippingViaDraftOrder($countryCode, $provinceCode, $items, $zip, $city);
        if (!empty($calculated['rates'])) {
            $rates = array_merge($rates, $calculated['rates']);
            $tax = (int) ($calculated['tax_amount'] ?? 0);
            $duties = (int) ($calculated['duties_amount'] ?? 0);
        }

        $rates = array_merge($rates, $this->shippingRatesFromDeliveryProfiles($countryCode, $provinceCode, $subtotalCents));
        $rates = array_merge($rates, $this->shippingRatesFromZones($countryCode, $provinceCode, $subtotalCents));

        if (empty($rates)) {
            $rates = array_merge(
                $this->shippingRatesFromDeliveryProfiles($countryCode, $provinceCode, $subtotalCents, true),
                $this->shippingRatesFromZones('*', null, $subtotalCents)
            );
        }

        $unique = [];
        $out = [];
        foreach ($rates as $rate) {
            $key = ($rate['title'] ?? '') . '|' . ($rate['price'] ?? 0);
            if (isset($unique[$key])) {
                continue;
            }
            $unique[$key] = true;
            $out[] = $rate;
        }

        return [
            'rates' => $out,
            'tax_amount' => $tax,
            'duties_amount' => $duties,
        ];
    }

    private function calculateShippingViaDraftOrder(string $country, ?string $province, array $items, string $zip, string $city): array
    {
        $lineItems = [];
        foreach ($items as $item) {
            $vid = (int) ($item['variant_id'] ?? 0);
            if (!$vid) {
                continue;
            }
            $lineItems[] = [
                'variantId' => "gid://shopify/ProductVariant/{$vid}",
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
            ];
        }
        if (empty($lineItems)) {
            return ['rates' => [], 'tax_amount' => 0, 'duties_amount' => 0];
        }

        $address = [
            'countryCode' => $country,
            'country' => $this->getCountryName($country),
            'address1' => 'Address',
        ];
        if ($province) {
            $address['provinceCode'] = strtoupper($province);
        }
        if ($zip !== '') {
            $address['zip'] = $zip;
        }
        if ($city !== '') {
            $address['city'] = $city;
        }

        $mutation = <<<'GQL'
        mutation CalculateDraft($input: DraftOrderInput!) {
            draftOrderCalculate(input: $input) {
                calculatedDraftOrder {
                    availableShippingRates {
                        title
                        handle
                        price { amount currencyCode }
                    }
                    totalTaxSet { shopMoney { amount } }
                }
                userErrors { field message }
            }
        }
GQL;

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(25)->post(
                "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/graphql.json",
                ['query' => $mutation, 'variables' => ['input' => [
                    'lineItems' => $lineItems,
                    'shippingAddress' => $address,
                ]]]
            );

            $json = $response->json() ?? [];
            if (!empty($json['errors'])) {
                Log::warning('draftOrderCalculate GraphQL errors', ['errors' => $json['errors']]);
            }

            $calc = $json['data']['draftOrderCalculate']['calculatedDraftOrder'] ?? null;
            $errors = $json['data']['draftOrderCalculate']['userErrors'] ?? [];
            if (!empty($errors)) {
                Log::info('draftOrderCalculate userErrors', ['errors' => $errors]);
            }
            if (!$calc) {
                return ['rates' => [], 'tax_amount' => 0, 'duties_amount' => 0];
            }

            $rates = [];
            foreach (($calc['availableShippingRates'] ?? []) as $rate) {
                $amount = (float) ($rate['price']['amount'] ?? 0);
                $rates[] = [
                    'title' => $rate['title'] ?? 'Shipping',
                    'code' => $rate['handle'] ?? strtolower(str_replace(' ', '_', $rate['title'] ?? 'shipping')),
                    'price' => (int) round($amount * 100),
                ];
            }

            return [
                'rates' => $rates,
                'tax_amount' => (int) round(((float) ($calc['totalTaxSet']['shopMoney']['amount'] ?? 0)) * 100),
                'duties_amount' => 0,
            ];
        } catch (\Throwable $e) {
            Log::warning('draftOrderCalculate failed', ['error' => $e->getMessage()]);
            return ['rates' => [], 'tax_amount' => 0, 'duties_amount' => 0];
        }
    }

    private function shippingRatesFromDeliveryProfiles(string $countryCode, ?string $provinceCode, int $subtotalCents, bool $includeAll = false): array
    {
        $query = <<<'GQL'
        {
            deliveryProfiles(first: 5) {
                nodes {
                    profileLocationGroups {
                        locationGroupZones(first: 20) {
                            nodes {
                                zone {
                                    name
                                    countries {
                                        code {
                                            countryCode
                                            restOfWorld
                                        }
                                        provinces { code }
                                    }
                                }
                                methodDefinitions(first: 20) {
                                    nodes {
                                        name
                                        active
                                        rateProvider {
                                            __typename
                                            ... on DeliveryRateDefinition {
                                                price { amount }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
GQL;

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(20)->post(
                "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/graphql.json",
                ['query' => $query]
            );
            $json = $response->json() ?? [];
            if (!empty($json['errors'])) {
                Log::warning('deliveryProfiles errors', ['errors' => $json['errors']]);
                return $this->shippingRatesFromDeliveryProfilesSimple();
            }

            $matched = [];
            $all = [];
            foreach (($json['data']['deliveryProfiles']['nodes'] ?? []) as $profile) {
                foreach (($profile['profileLocationGroups'] ?? []) as $group) {
                    foreach (($group['locationGroupZones']['nodes'] ?? []) as $zoneNode) {
                        $zoneMatches = $includeAll || $this->deliveryZoneMatches($zoneNode['zone'] ?? [], $countryCode, $provinceCode);
                        foreach (($zoneNode['methodDefinitions']['nodes'] ?? []) as $method) {
                            if (isset($method['active']) && !$method['active']) {
                                continue;
                            }
                            $provider = $method['rateProvider'] ?? [];
                            if (($provider['__typename'] ?? '') !== 'DeliveryRateDefinition') {
                                continue;
                            }
                            $row = [
                                'title' => $method['name'] ?? 'Shipping',
                                'code' => strtolower(str_replace(' ', '_', $method['name'] ?? 'shipping')),
                                'price' => (int) round(((float) ($provider['price']['amount'] ?? 0)) * 100),
                            ];
                            $all[] = $row;
                            if ($zoneMatches) {
                                $matched[] = $row;
                            }
                        }
                    }
                }
            }
            return !empty($matched) ? $matched : $all;
        } catch (\Throwable $e) {
            Log::warning('deliveryProfiles failed', ['error' => $e->getMessage()]);
            return $this->shippingRatesFromDeliveryProfilesSimple();
        }
    }

    private function shippingRatesFromDeliveryProfilesSimple(): array
    {
        $query = <<<'GQL'
        {
            deliveryProfiles(first: 5) {
                nodes {
                    profileLocationGroups {
                        locationGroupZones(first: 20) {
                            nodes {
                                methodDefinitions(first: 20) {
                                    nodes {
                                        name
                                        active
                                        rateProvider {
                                            __typename
                                            ... on DeliveryRateDefinition {
                                                price { amount }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
GQL;
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(20)->post(
                "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/graphql.json",
                ['query' => $query]
            );
            $json = $response->json() ?? [];
            $rates = [];
            foreach (($json['data']['deliveryProfiles']['nodes'] ?? []) as $profile) {
                foreach (($profile['profileLocationGroups'] ?? []) as $group) {
                    foreach (($group['locationGroupZones']['nodes'] ?? []) as $zoneNode) {
                        foreach (($zoneNode['methodDefinitions']['nodes'] ?? []) as $method) {
                            if (isset($method['active']) && !$method['active']) {
                                continue;
                            }
                            $provider = $method['rateProvider'] ?? [];
                            if (($provider['__typename'] ?? '') !== 'DeliveryRateDefinition') {
                                continue;
                            }
                            $rates[] = [
                                'title' => $method['name'] ?? 'Shipping',
                                'code' => strtolower(str_replace(' ', '_', $method['name'] ?? 'shipping')),
                                'price' => (int) round(((float) ($provider['price']['amount'] ?? 0)) * 100),
                            ];
                        }
                    }
                }
            }
            return $rates;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function deliveryZoneMatches(array $zone, string $countryCode, ?string $provinceCode): bool
    {
        $countries = $zone['countries'] ?? [];
        if (empty($countries)) {
            return true;
        }
        foreach ($countries as $country) {
            $codeObj = is_array($country['code'] ?? null) ? $country['code'] : [];
            if (!empty($country['restOfWorld']) || !empty($codeObj['restOfWorld'])) {
                return true;
            }
            $code = strtoupper((string) ($codeObj['countryCode'] ?? (is_string($country['code'] ?? null) ? $country['code'] : '')));
            if ($code === '' || $code === '*' || $code === $countryCode) {
                $provinces = $country['provinces'] ?? [];
                if (empty($provinces) || empty($provinceCode)) {
                    return true;
                }
                foreach ($provinces as $p) {
                    $pcode = strtoupper((string) ($p['code'] ?? ''));
                    if ($pcode === strtoupper((string) $provinceCode)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function shippingRatesFromZones(string $countryCode, ?string $provinceCode, int $subtotalCents): array
    {
        $rates = [];

        try {
            $data = $this->rest('GET', 'shipping_zones.json');
            foreach (($data['shipping_zones'] ?? []) as $zone) {
                $countries = $zone['countries'] ?? [];
                $matches = empty($countries);
                foreach ($countries as $country) {
                    $code = strtoupper((string) ($country['code'] ?? ''));
                    if ($code === '' || $code === '*' || $code === $countryCode) {
                        $provinces = $country['provinces'] ?? [];
                        if (empty($provinces) || empty($provinceCode)) {
                            $matches = true;
                            break;
                        }
                        foreach ($provinces as $p) {
                            if (strtoupper((string) ($p['code'] ?? '')) === strtoupper((string) $provinceCode)) {
                                $matches = true;
                                break 2;
                            }
                        }
                    }
                }

                if (!$matches) {
                    continue;
                }

                $subtotal = $subtotalCents / 100;
                foreach (($zone['price_based_shipping_rates'] ?? []) as $rate) {
                    $min = (float) ($rate['min_order_subtotal'] ?? 0);
                    $maxRaw = $rate['max_order_subtotal'] ?? null;
                    $max = ($maxRaw !== null && $maxRaw !== '') ? (float) $maxRaw : null;
                    if ($subtotal < $min) {
                        continue;
                    }
                    if ($max !== null && $subtotal > $max) {
                        continue;
                    }
                    $rates[] = [
                        'title' => $rate['name'] ?? 'Shipping',
                        'code'  => strtolower(str_replace(' ', '_', $rate['name'] ?? 'shipping')),
                        'price' => (int) round(((float) ($rate['price'] ?? 0)) * 100),
                    ];
                }

                foreach (($zone['weight_based_shipping_rates'] ?? []) as $rate) {
                    $rates[] = [
                        'title' => $rate['name'] ?? 'Shipping',
                        'code'  => strtolower(str_replace(' ', '_', $rate['name'] ?? 'shipping')),
                        'price' => (int) round(((float) ($rate['price'] ?? 0)) * 100),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('getShippingRates zones failed', ['error' => $e->getMessage()]);
        }

        return $rates;
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

                'shipping_lines' => [
                    [
                        'title'  => trim((string) ($session->referrer ?? '')) ?: 'Standard Shipping',
                        'price'  => number_format(max(0, (int) ($session->shipping_amount ?? 0)) / 100, 2, '.', ''),
                        'code'   => 'easzypay',
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