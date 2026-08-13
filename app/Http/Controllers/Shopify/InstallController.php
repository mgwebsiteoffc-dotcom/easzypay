<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InstallController extends Controller
{
    private const SCOPES = [
        'write_orders', 'read_orders',
        'read_products', 'write_products',
        'read_inventory', 'write_inventory',
        'read_customers', 'write_customers',
        'read_discounts', 'write_discounts',
        'read_themes', 'write_themes',
        'read_shipping',
        'write_draft_orders',
        'read_draft_orders',
    ];

    public function install(Request $request)
    {
        $shop = $this->cleanShop($request->get('shop', ''));

        if (!$shop) {
            return view('shopify.invalid-shop');
        }

        $state = Str::random(40);
        session(['shopify_state' => $state, 'shopify_shop' => $shop]);

        $clientId = config('services.shopify.app_key');

        if (empty($clientId)) {
            return view('shopify.error', [
                'message' => 'SHOPIFY_APP_KEY not configured in .env file.'
            ]);
        }

        $scopes     = implode(',', self::SCOPES);
        $redirectUri = route('shopify.callback');

        $url = "https://{$shop}/admin/oauth/authorize"
             . "?client_id=" . urlencode($clientId)
             . "&scope=" . urlencode($scopes)
             . "&redirect_uri=" . urlencode($redirectUri)
             . "&state=" . urlencode($state);

        Log::info('Shopify OAuth start', ['shop' => $shop]);

        return redirect($url);
    }

    public function callback(Request $request)
    {
        $shop  = $this->cleanShop($request->get('shop', ''));
        $code  = $request->get('code');
        $state = $request->get('state');

        if (!$state || $state !== session('shopify_state')) {
            return view('shopify.error', ['message' => 'Invalid state. Please try again.']);
        }

        if (!$shop || !$code) {
            return view('shopify.error', ['message' => 'Missing shop or code.']);
        }

        try {
            // 1. Get access token
            $tokenRes = Http::post("https://{$shop}/admin/oauth/access_token", [
                'client_id'     => config('services.shopify.app_key'),
                'client_secret' => config('services.shopify.app_secret'),
                'code'          => $code,
            ]);

            if (!$tokenRes->ok()) {
                throw new \Exception('Token exchange failed: ' . $tokenRes->body());
            }

            $accessToken = $tokenRes->json('access_token');
            $scopes      = $tokenRes->json('scope', '');

            if (!$accessToken) {
                throw new \Exception('No access token received');
            }

            // 2. Get shop info
            $shopInfo = $this->getShopInfo($shop, $accessToken);

            // 3. Create tenant
            $tenant = $this->findOrCreateTenant($shopInfo, $shop);

            // 4. Create store
            $store = $this->createStore($tenant, $shop, $accessToken, $scopes, $shopInfo);

            // 5. Register webhooks (non-blocking)
            try {
                $this->registerWebhooks($shop, $accessToken);
                $store->update([
                    'webhooks_registered_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Webhook registration failed', ['error' => $e->getMessage()]);
            }

            // 6. Inject button snippet (non-blocking)
            try {
                $this->injectButton($shop, $accessToken, $store);
                $store->update([
                    'button_active'      => true,
                    'button_injected_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Button injection failed', ['error' => $e->getMessage()]);
            }

            // 7. Login tenant
            Auth::guard('tenant')->login($tenant);
            session()->forget(['shopify_state', 'shopify_shop']);

            Log::info('Shopify app installed', [
                'shop'     => $shop,
                'tenant'   => $tenant->id,
                'store'    => $store->id,
                'button'   => $store->button_active ? 'injected' : 'manual',
            ]);

            return redirect()->route('tenant.dashboard')
                ->with('install_success', true)
                ->with('store_name', $store->shop_name)
                ->with('store_id', $store->id);

        } catch (\Throwable $e) {
            Log::error('Shopify install error', [
                'shop'  => $shop,
                'error' => $e->getMessage(),
            ]);

            return view('shopify.error', [
                'message' => 'Installation failed: ' . $e->getMessage()
            ]);
        }
    }

    private function getShopInfo(string $shop, string $token): array
    {
        $version = config('services.shopify.api_version', '2025-01');

        try {
            $res = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
                'Content-Type' => 'application/json',
            ])->post("https://{$shop}/admin/api/{$version}/graphql.json", [
                'query' => '{ shop { name email myshopifyDomain currencyCode plan { displayName } billingAddress { countryCodeV2 } contactEmail shopOwnerName ianaTimezone } }',
            ]);

            if ($res->ok()) {
                return $res->json('data.shop', []);
            }
        } catch (\Throwable $e) {
            Log::warning('Shop info fetch failed', ['error' => $e->getMessage()]);
        }

        return ['name' => $shop, 'email' => null, 'myshopifyDomain' => $shop, 'currencyCode' => 'USD'];
    }

    private function findOrCreateTenant(array $info, string $shop): Tenant
    {
        $email = $info['email'] ?? $info['contactEmail'] ?? null;

        if (!$email) {
            $slug  = str_replace('.myshopify.com', '', $shop);
            $email = $slug . '@shopify-merchant.com';
        }

        $tenant = Tenant::where('email', $email)->first();

        if (!$tenant) {
            $tenant = Tenant::create([
                'name'          => $info['shopOwnerName'] ?? $info['name'] ?? $shop,
                'slug'          => Str::slug($info['name'] ?? $shop) . '-' . Str::random(4),
                'email'         => $email,
                'password'      => Hash::make(Str::random(24)),
                'company_name'  => $info['name'] ?? $shop,
                'plan'          => 'trial',
                'trial_ends_at' => now()->addDays(14),
                'is_active'     => true,
            ]);
        }

        return $tenant;
    }

    private function createStore(Tenant $tenant, string $shop, string $token, string $scopes, array $info): Store
    {
        $store = Store::withTrashed()->where('myshopify_domain', $shop)->first();

        $data = [
            'tenant_id'        => $tenant->id,
            'shop_domain'      => $info['myshopifyDomain'] ?? $shop,
            'myshopify_domain' => $shop,
            'shop_name'        => $info['name'] ?? $shop,
            'shop_email'       => $info['email'] ?? null,
            'access_token'     => $token,
            'scopes'           => $scopes,
            'api_version'      => config('services.shopify.api_version', '2025-01'),
            'shopify_plan'     => $info['plan']['displayName'] ?? null,
            'currency'         => $info['currencyCode'] ?? 'USD',
            'country_code'     => $info['billingAddress']['countryCodeV2'] ?? null,
            'timezone'         => $info['ianaTimezone'] ?? null,
            'shop_owner'       => $info['shopOwnerName'] ?? null,
            'is_installed'     => true,
            'installed_at'     => now(),
            'uninstalled_at'   => null,
            'is_active'        => true,
            'deleted_at'       => null,
        ];

        if ($store) {
            $store->restore();
            $store->update($data);
        } else {
            $store = Store::create($data);
        }

        $tenant->update(['total_stores' => $tenant->stores()->count()]);

        return $store;
    }

    private function registerWebhooks(string $shop, string $token): void
    {
        $version = config('services.shopify.api_version', '2025-01');
        $appUrl  = config('app.url');

        $topics = [
            'ORDERS_CREATE'   => '/webhook/shopify/orders-create',
            'ORDERS_PAID'     => '/webhook/shopify/orders-paid',
            'REFUNDS_CREATE'  => '/webhook/shopify/refunds-create',
            'APP_UNINSTALLED' => '/webhook/shopify/app-uninstalled',
        ];

        foreach ($topics as $topic => $path) {
            try {
                Http::withHeaders([
                    'X-Shopify-Access-Token' => $token,
                    'Content-Type' => 'application/json',
                ])->post("https://{$shop}/admin/api/{$version}/graphql.json", [
                    'query' => 'mutation webhookSubscriptionCreate($topic: WebhookSubscriptionTopic!, $webhookSubscription: WebhookSubscriptionInput!) { webhookSubscriptionCreate(topic: $topic, webhookSubscription: $webhookSubscription) { userErrors { field message } webhookSubscription { id } } }',
                    'variables' => [
                        'topic' => $topic,
                        'webhookSubscription' => [
                            'callbackUrl' => $appUrl . $path,
                            'format'      => 'JSON',
                        ],
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::warning("Webhook {$topic} failed", ['error' => $e->getMessage()]);
            }
        }
    }

private function injectButton(string $shop, string $token, Store $store): void
{
    $appUrl  = config('app.url');
    $version = config('services.shopify.api_version', '2026-01');

    Log::info('Starting theme button injection', [
        'shop'    => $shop,
        'version' => $version,
    ]);

    // ============================================
    // Step 1: Get active theme
    // ============================================
    $themeResponse = Http::withHeaders([
        'X-Shopify-Access-Token' => $token,
    ])->timeout(15)->get("https://{$shop}/admin/api/{$version}/themes.json");

    if (!$themeResponse->ok()) {
        throw new \Exception('Cannot fetch themes: HTTP ' . $themeResponse->status() . ' - ' . substr($themeResponse->body(), 0, 200));
    }

    $activeTheme = null;
    foreach ($themeResponse->json('themes', []) as $theme) {
        if ($theme['role'] === 'main') {
            $activeTheme = $theme;
            break;
        }
    }

    if (!$activeTheme) {
        throw new \Exception('No active main theme found');
    }

    $themeId = $activeTheme['id'];

    Log::info('Active theme found', [
        'theme_id'      => $themeId,
        'theme_name'    => $activeTheme['name'] ?? '?',
        'theme_role'    => $activeTheme['role'] ?? '?',
        'theme_updated' => $activeTheme['updated_at'] ?? '?',
    ]);

    // Build snippet content
    $snippet = $this->buildSnippet($store->id, $appUrl, $store);

    // ============================================
    // Step 2: Try GraphQL themeFilesUpsert (PRIMARY METHOD for 2026)
    // ============================================
    $themeGid = "gid://shopify/OnlineStoreTheme/{$themeId}";

    $mutation = <<<'GQL'
    mutation themeFilesUpsert($themeId: ID!, $files: [OnlineStoreThemeFilesUpsertFileInput!]!) {
        themeFilesUpsert(themeId: $themeId, files: $files) {
            upsertedThemeFiles {
                filename
                size
            }
            userErrors {
                field
                message
                code
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
                    'value' => $snippet,
                ],
            ],
        ],
    ];

    Log::info('Calling GraphQL themeFilesUpsert', [
        'theme_gid' => $themeGid,
        'snippet_size' => strlen($snippet),
    ]);

    $graphqlResponse = Http::withHeaders([
        'X-Shopify-Access-Token' => $token,
        'Content-Type'           => 'application/json',
    ])->timeout(30)->post(
        "https://{$shop}/admin/api/{$version}/graphql.json",
        ['query' => $mutation, 'variables' => $variables]
    );

    $body = $graphqlResponse->json();

    Log::info('GraphQL response', [
        'status' => $graphqlResponse->status(),
        'body'   => $body,
    ]);

    if (!$graphqlResponse->ok()) {
        throw new \Exception('GraphQL HTTP error: ' . $graphqlResponse->status() . ' - ' . substr($graphqlResponse->body(), 0, 300));
    }

    // Check for top-level GraphQL errors
    if (!empty($body['errors'])) {
        $errs = collect($body['errors'])->map(fn($e) => $e['message'] ?? json_encode($e))->implode('; ');
        throw new \Exception("GraphQL errors: {$errs}");
    }

    $upsertData = $body['data']['themeFilesUpsert'] ?? [];
    $userErrors = $upsertData['userErrors'] ?? [];
    $uploaded   = $upsertData['upsertedThemeFiles'] ?? [];

    if (!empty($userErrors)) {
        $errMsg = collect($userErrors)->map(function ($e) {
            $field = $e['field'] ?? '?';
            if (is_array($field)) $field = implode('.', $field);
            return "{$field}: " . ($e['message'] ?? '?') . " [" . ($e['code'] ?? '?') . "]";
        })->implode('; ');

        throw new \Exception("GraphQL userErrors: {$errMsg}");
    }

    if (empty($uploaded)) {
        throw new \Exception('GraphQL returned no uploaded files');
    }

    Log::info('✅ Button uploaded successfully via GraphQL themeFilesUpsert', [
        'theme_id'    => $themeId,
        'shop'        => $shop,
        'files'       => $uploaded,
    ]);
}
  public function buildSnippet(int $storeId, string $appUrl, ?\App\Models\Store $store = null): string
{
    // Load store if not provided
    if (!$store) {
        $store = \App\Models\Store::find($storeId);
    }

    // Get customization with defaults
    $btnText    = $store->button_text          ?? '⚡ Buy Now — Secure Checkout';
    $bgStart    = $store->button_bg_start      ?? '#667eea';
    $bgEnd      = $store->button_bg_end        ?? '#764ba2';
    $txtColor   = $store->button_text_color    ?? '#ffffff';
    $radius     = $store->button_border_radius ?? '8px';
    $style      = $store->button_style         ?? 'gradient';
    $showBadges = $store->show_trust_badges ?? true;

    // Build background based on style
    if ($style === 'gradient') {
        $bgCss = "background:linear-gradient(135deg,{$bgStart},{$bgEnd});border:none;";
    } elseif ($style === 'solid') {
        $bgCss = "background:{$bgStart};border:none;";
    } else { // outline
        $bgCss = "background:transparent;border:2px solid {$bgStart};color:{$bgStart} !important;";
        $txtColor = $bgStart;
    }

    // Hover shadow color (with opacity)
    $shadowColor = ltrim($bgStart, '#');
    $r = hexdec(substr($shadowColor, 0, 2));
    $g = hexdec(substr($shadowColor, 2, 2));
    $b = hexdec(substr($shadowColor, 4, 2));
    $shadowRgba = "rgba({$r},{$g},{$b},.4)";

    // Trust badges HTML
    $badgesHtml = '';
    if ($showBadges) {
        $badgesHtml = <<<HTML
<div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-top:10px;">
  <span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">🔒 SSL</span>
  <span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">💳 All Cards</span>
  <span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">🍎 Apple Pay</span>
  <span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">G Pay</span>
</div>
HTML;
    }

    return <<<LIQUID
{%- comment -%}EaszyPay Checkout Button - Store #{$storeId}{%- endcomment -%}
<div id="easzypay-wrap" style="margin-top:12px;">
  <button type="button" id="easzypay-btn"
    data-store-id="{$storeId}"
    data-product-id="{{ product.id }}"
    data-variant-id="{{ product.selected_or_first_available_variant.id }}"
    data-title="{{ product.title | escape }}"
    data-variant="{{ product.selected_or_first_available_variant.title | escape }}"
    data-price="{{ product.selected_or_first_available_variant.price }}"
    data-currency="{{ cart.currency.iso_code }}"
    data-image="{{ product.featured_image | img_url: '400x400' }}"
    {% unless product.selected_or_first_available_variant.available %}disabled{% endunless %}
    style="width:100%;padding:16px 24px;{$bgCss}color:{$txtColor};border-radius:{$radius};font-size:16px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:8px;transition:all 0.3s ease;"
    onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px {$shadowRgba}'"
    onmouseout="this.style.transform='';this.style.boxShadow=''">{% if product.selected_or_first_available_variant.available %}{$btnText}{% else %}Sold Out{% endif %}</button>
  <div id="ep-sts" style="display:none;text-align:center;padding:10px;color:{$bgStart};font-size:14px;font-weight:500;"></div>
  <div id="ep-err" style="display:none;padding:10px 14px;margin-top:8px;background:#fff5f5;border:1px solid #fc8181;border-radius:6px;color:#c53030;font-size:13px;text-align:center;"></div>
  {$badgesHtml}
</div>
<script>
(function(){var EP='{$appUrl}';var SID={$storeId};var btn=document.getElementById('easzypay-btn');var sts=document.getElementById('ep-sts');var err=document.getElementById('ep-err');if(!btn)return;function show(m){sts.textContent=m;sts.style.display='block';err.style.display='none';btn.disabled=true;btn.style.opacity='0.7';}function fail(m){err.textContent='⚠️ '+m;err.style.display='block';sts.style.display='none';btn.disabled=false;btn.style.opacity='1';}document.addEventListener('variant:changed',function(e){if(!e.detail||!e.detail.variant)return;var v=e.detail.variant;btn.dataset.variantId=v.id;btn.dataset.price=v.price;btn.dataset.variant=v.title;btn.disabled=!v.available;});function q(){var e=document.querySelector('[name="quantity"]');return e?Math.max(1,parseInt(e.value)||1):1;}function shop(){return(window.Shopify&&window.Shopify.shop)?window.Shopify.shop:window.location.hostname;}function xhr(m,u,d){return new Promise(function(ok,no){var x=new XMLHttpRequest();x.open(m,u,true);x.setRequestHeader('Content-Type','application/json');x.setRequestHeader('Accept','application/json');x.timeout=30000;x.onreadystatechange=function(){if(x.readyState!==4)return;try{ok(JSON.parse(x.responseText));}catch(e){no(new Error('Bad response'));}};x.onerror=function(){no(new Error('Network error'));};x.ontimeout=function(){no(new Error('Timeout'));};x.send(d?JSON.stringify(d):null);});}btn.addEventListener('click',function(){var vid=btn.dataset.variantId,pid=btn.dataset.productId;var title=btn.dataset.title||'Product',vt=btn.dataset.variant||'';var price=parseInt(btn.dataset.price||'0'),cur=btn.dataset.currency||'USD';var img=btn.dataset.image||'',qty=q();if(!vid||price<=0){fail('Select an option');return;}show('⏳ Preparing checkout...');xhr('POST','/cart/add.js',{id:parseInt(vid),quantity:qty}).then(function(){return xhr('GET','/cart.js');}).then(function(cart){return xhr('POST',EP+'/shopify/cart',{store_id:SID,shop_domain:shop(),cart_token:cart.token||'',currency:cur,subtotal:price*qty,items:[{product_id:parseInt(pid)||0,variant_id:parseInt(vid),quantity:qty,title:title,variant_title:vt,price:price,image:img}]});}).then(function(d){if(d.error){fail(d.error.message);return;}if(!d.checkout_url){fail('No checkout URL');return;}show('✅ Redirecting...');setTimeout(function(){window.location.href=d.checkout_url;},300);}).catch(function(e){fail(e.message||'Error');});});}());
</script>
LIQUID;
}

    private function cleanShop(?string $shop): ?string
    {
        if (empty($shop)) return null;
        $shop = strtolower(trim($shop));
        $shop = str_replace(['https://', 'http://', '/'], '', $shop);
        if (!str_contains($shop, '.myshopify.com')) {
            $shop .= '.myshopify.com';
        }
        if (!preg_match('/^[a-z0-9][a-z0-9\-]*\.myshopify\.com$/', $shop)) {
            return null;
        }
        return $shop;
    }
}