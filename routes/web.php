<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| EaszyPay - Complete Routes
| Domain: https://easzypay.lead365.in
|--------------------------------------------------------------------------
*/

// =====================================================================
// HEALTH CHECK
// =====================================================================
Route::get('/health', function () {
    return response()->json([
        'status'  => 'ok',
        'app'     => 'EaszyPay',
        'time'    => now()->toIso8601String(),
        'php'     => phpversion(),
        'laravel' => app()->version(),
    ]);
})->name('health');

// =====================================================================
// HOME - Catch Shopify install OR show marketing page
// =====================================================================
Route::get('/', function (Request $request) {
    if ($request->has('shop') && $request->has('hmac')) {
        return redirect()->to('/shopify/install?' . http_build_query($request->query()));
    }
    if ($request->has('shop')) {
        return redirect()->to('/shopify/install?shop=' . $request->get('shop'));
    }
    return app(\App\Http\Controllers\Marketing\HomeController::class)->index();
})->name('home');

// =====================================================================
// SHOPIFY OAUTH
// =====================================================================
Route::get('/shopify/install', [\App\Http\Controllers\Shopify\InstallController::class, 'install'])
    ->name('shopify.install');

Route::get('/shopify/callback', [\App\Http\Controllers\Shopify\InstallController::class, 'callback'])
    ->name('shopify.callback');

// =====================================================================
// SHOPIFY CART RECEIVER (from buy button on store)
// =====================================================================
Route::match(['get', 'post', 'options'], '/shopify/cart', function (Request $request) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');

    if ($request->isMethod('options')) {
        return response('', 200);
    }

    if ($request->isMethod('get')) {
        return response()->json(['status' => 'active', 'message' => 'EaszyPay Cart API']);
    }

    return app(\App\Http\Controllers\CheckoutController::class)->receiveCart($request);
})->name('checkout.receive');

// =====================================================================
// CHECKOUT PAGES
// =====================================================================
Route::get('/checkout/success', [\App\Http\Controllers\CheckoutController::class, 'success'])
    ->name('checkout.success');

Route::get('/checkout/cancel', function () {
    return view('checkout.cancel');
})->name('checkout.cancel');

Route::get('/checkout/{sessionId}', [\App\Http\Controllers\CheckoutController::class, 'show'])
    ->name('checkout.show')
    ->where('sessionId', '[a-f0-9]{64}');

// =====================================================================
// API ENDPOINTS (no CSRF)
// =====================================================================
Route::prefix('api')->group(function () {
    Route::post('/payment-intent', [\App\Http\Controllers\PaymentController::class, 'createIntent']);
    Route::get('/detect-location', [\App\Http\Controllers\LocationController::class, 'detect']);
    Route::get('/exchange-rate', [\App\Http\Controllers\LocationController::class, 'exchangeRate']);
    Route::get('/location/states', [\App\Http\Controllers\LocationController::class, 'getStates']);
    Route::get('/location/cities', [\App\Http\Controllers\LocationController::class, 'getCities']);
    Route::get('/location/validate-postcode', [\App\Http\Controllers\LocationController::class, 'validatePostcode']);
    Route::get('/checkout-policies', function (Request $request) {
        $sessionId = $request->get('session_id', '');
        $session = \App\Models\CheckoutSession::where('session_id', $sessionId)->first();
        $store = null;
        if ($session && $session->store_id) {
            $store = \App\Models\Store::find($session->store_id);
        }
        if (!$store && $session && $session->shop_domain) {
            $store = \App\Models\Store::where('myshopify_domain', $session->shop_domain)
                ->orWhere('shop_domain', $session->shop_domain)->first();
        }
        if (!$store || empty($store->access_token)) {
            return response()->json(['policies' => []]);
        }
        $shopify = new \App\Services\ShopifyService($store->myshopify_domain, $store->access_token);
        return response()->json(['policies' => $shopify->getShopPolicies()]);
    });
    Route::get('/shipping-rates', function (Request $request) {
        $sessionId = $request->get('session_id', '');
        $country = strtoupper((string) $request->get('country', 'US'));
        $state = $request->get('state');
        $session = \App\Models\CheckoutSession::where('session_id', $sessionId)->first();
        $store = null;
        if ($session && $session->store_id) {
            $store = \App\Models\Store::find($session->store_id);
        }
        if (!$store && $session && $session->shop_domain) {
            $store = \App\Models\Store::where('myshopify_domain', $session->shop_domain)
                ->orWhere('shop_domain', $session->shop_domain)->first();
        }
        if (!$store || empty($store->access_token)) {
            return response()->json(['rates' => [[
                'title' => 'Standard Shipping',
                'code' => 'standard',
                'price' => 0,
            ]]]);
        }
        $shopify = new \App\Services\ShopifyService($store->myshopify_domain, $store->access_token);
        $subtotal = (int) ($session->subtotal ?? 0);
        return response()->json(['rates' => $shopify->getShippingRates($country, $state, $subtotal)]);
    });
});

Route::post('/api/validate-discount', function (\Illuminate\Http\Request $request) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    $code        = trim($request->json('code', ''));
    $shopDomain  = $request->json('shop_domain', '');
    $sessionId   = $request->json('session_id', '');

    if (empty($code)) {
        return response()->json(['valid' => false, 'error' => 'Empty code']);
    }

    $session = \App\Models\CheckoutSession::where('session_id', $sessionId)->first();

    // Find store
    $store = \App\Models\Store::where('myshopify_domain', $shopDomain)
        ->orWhere('shop_domain', $shopDomain)
        ->first();

    if (!$store || empty($store->access_token)) {
        // Fallback: get from session
        if ($session && $session->store_id) {
            $store = \App\Models\Store::find($session->store_id);
        }
    }

    if (!$store || empty($store->access_token)) {
        return response()->json(['valid' => false, 'error' => 'Store not found']);
    }

    try {
        $items = is_array($session?->items) ? $session->items : [];
        $shopify = new \App\Services\ShopifyService($store->myshopify_domain, $store->access_token);
        $result = $shopify->lookupDiscountCode($code, (int) ($session->subtotal ?? 0), $items);

        unset($result['handled']);

        if (!empty($result['valid']) && $session) {
            $session->update([
                'discount_code'    => $code,
                'discount_percent' => (float) ($result['discount_percent'] ?? 0),
                'discount_amount'  => (int) ($result['discount_amount'] ?? 0),
                'total_amount'     => max(0, (int) ($session->subtotal ?? 0) - (int) ($result['discount_amount'] ?? 0)),
            ]);
        }

        return response()->json($result);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Discount validation error', ['error' => $e->getMessage()]);
        return response()->json(['valid' => false, 'error' => 'Validation failed: ' . $e->getMessage()]);
    }
})->name('api.validate-discount');
// =====================================================================
// STRIPE WEBHOOK (no CSRF)
// =====================================================================
Route::post('/webhook/stripe', [\App\Http\Controllers\WebhookController::class, 'stripe'])
    ->name('webhook.stripe');

// =====================================================================
// SHOPIFY WEBHOOKS (no CSRF)
// =====================================================================
Route::post('/webhook/shopify/orders-create', [\App\Http\Controllers\ShopifyWebhookController::class, 'ordersCreate']);
Route::post('/webhook/shopify/orders-updated', [\App\Http\Controllers\ShopifyWebhookController::class, 'ordersUpdated']);
Route::post('/webhook/shopify/orders-paid', [\App\Http\Controllers\ShopifyWebhookController::class, 'ordersPaid']);
Route::post('/webhook/shopify/refunds-create', [\App\Http\Controllers\ShopifyWebhookController::class, 'refundsCreate']);
Route::post('/webhook/shopify/app-uninstalled', [\App\Http\Controllers\ShopifyWebhookController::class, 'appUninstalled']);

// =====================================================================
// APPLE PAY DOMAIN VERIFICATION
// =====================================================================
Route::get('/.well-known/apple-developer-merchantid-domain-association', function () {
    $path = public_path('.well-known/apple-developer-merchantid-domain-association');
    if (file_exists($path)) {
        return response()->file($path, ['Content-Type' => 'text/plain']);
    }
    abort(404);
});

// =====================================================================
// SUPER ADMIN PANEL
// =====================================================================
Route::get('/admin', function () {
    return redirect('/admin/login');
});

Route::get('/admin/login', function () {
    if (Auth::guard('admin')->check()) {
        return redirect('/admin/dashboard');
    }
    return view('admin.auth.login');
})->name('admin.login');

Route::post('/admin/login', function (Request $request) {
    $request->validate(['email' => 'required|email', 'password' => 'required']);

    if (!Auth::guard('admin')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
        return back()->withInput()->withErrors(['email' => 'Invalid credentials.']);
    }

    $u = Auth::guard('admin')->user();
    $u->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);

    return redirect('/admin/dashboard');
})->name('admin.login.post');

Route::post('/admin/logout', function (Request $request) {
    Auth::guard('admin')->logout();
    $request->session()->invalidate();
    return redirect('/admin/login');
})->name('admin.logout');

// Admin protected routes
Route::middleware(\App\Http\Middleware\AdminAuth::class)->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders', [\App\Http\Controllers\Admin\DashboardController::class, 'orders'])->name('orders');
    Route::get('/orders/{session}', [\App\Http\Controllers\Admin\DashboardController::class, 'orderDetail'])->name('orders.detail');
    Route::get('/webhooks', [\App\Http\Controllers\Admin\DashboardController::class, 'webhooks'])->name('webhooks');
    Route::post('/webhooks/{id}/retry', [\App\Http\Controllers\Admin\DashboardController::class, 'retryWebhook'])->name('webhooks.retry');

    // ============================================================
    // ADMIN TRANSACTION LOGS
    // ============================================================
    Route::get('/transaction-logs', function (Request $request) {
        $query = \App\Models\CheckoutSession::query();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_email', 'like', "%{$search}%")
                  ->orWhere('shopify_order_number', 'like', "%{$search}%")
                  ->orWhere('stripe_payment_intent_id', 'like', "%{$search}%")
                  ->orWhere('session_id', 'like', "%{$search}%");
            });
        }
        if ($tenant = $request->get('tenant')) {
            $query->where('tenant_id', $tenant);
        }

        $logs    = $query->with('store')->orderByDesc('created_at')->paginate(30);
        $tenants = \App\Models\Tenant::orderBy('name')->get();

        return view('admin.transaction-logs', compact('logs', 'tenants'));
    })->name('transaction-logs');

    // ============================================================
    // ADMIN DEBUG SHOPIFY
    // ============================================================
    Route::get('/debug/shopify', function () {
        $stores  = \App\Models\Store::all();
        $results = [];

        foreach ($stores as $store) {
            $test = [
                'id'          => $store->id,
                'store'       => $store->shop_name,
                'domain'      => $store->myshopify_domain,
                'active'      => $store->is_active,
                'token_len'   => strlen($store->access_token),
                'token_start' => substr($store->access_token, 0, 15) . '...',
            ];

            try {
                $r = Http::withHeaders([
                    'X-Shopify-Access-Token' => $store->access_token,
                    'Content-Type'           => 'application/json',
                ])->timeout(10)->get("https://{$store->myshopify_domain}/admin/api/2025-01/shop.json");

                $test['http_status'] = $r->status();
                $test['connected']   = $r->ok();

                if ($r->ok()) {
                    $data = $r->json();
                    $test['shop_name'] = $data['shop']['name'] ?? 'unknown';
                    $test['plan']      = $data['shop']['plan_display_name'] ?? 'unknown';
                    $test['currency']  = $data['shop']['currency'] ?? 'unknown';
                } else {
                    $test['error'] = substr($r->body(), 0, 200);
                }
            } catch (\Throwable $e) {
                $test['error'] = $e->getMessage();
            }

            $results[] = $test;
        }

        return response()->json([
            'stores'           => $results,
            'pending_sessions' => \App\Models\CheckoutSession::where('status', 'paid')
                ->whereNull('shopify_order_id')->count(),
            'total_sessions'   => \App\Models\CheckoutSession::count(),
        ], 200, [], JSON_PRETTY_PRINT);
    })->name('debug.shopify');

    Route::get('/debug/sessions', function () {
        return response()->json(
            \App\Models\CheckoutSession::orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(fn($s) => [
                    'session_id' => $s->session_id,
                    'status'     => $s->status,
                    'store_id'   => $s->store_id,
                    'shop'       => $s->shop_domain,
                    'pi'         => $s->stripe_payment_intent_id,
                    'charge'     => $s->stripe_charge_id,
                    'shopify_id' => $s->shopify_order_id,
                    'order_num'  => $s->shopify_order_number,
                    'amount'     => ($s->charged_amount ?? $s->subtotal) / 100,
                    'currency'   => $s->charged_currency ?? $s->currency,
                    'email'      => $s->customer_email,
                    'card'       => ($s->card_brand ?? '') . ' ' . ($s->card_last4 ?? ''),
                    'wallet'     => $s->wallet_type,
                    'created'    => $s->created_at->format('Y-m-d H:i:s'),
                ]),
            200, [], JSON_PRETTY_PRINT
        );
    })->name('debug.sessions');

    // ============================================================
    // ADMIN MANUAL SYNC
    // ============================================================
Route::post('/sync-order/{sessionId}', function (string $sessionId) {
    $session = \App\Models\CheckoutSession::where('session_id', $sessionId)->first();

    if (!$session) {
        return back()->with('error', 'Session not found.');
    }

    if ($session->shopify_order_id) {
        return back()->with('error', 'Already synced: ' . $session->shopify_order_number);
    }

    if (!$session->stripe_payment_intent_id) {
        return back()->with('error', 'No Stripe payment found.');
    }

    $store = null;
    if ($session->store_id) $store = \App\Models\Store::find($session->store_id);
    if (!$store && $session->shop_domain) {
        $store = \App\Models\Store::where('myshopify_domain', $session->shop_domain)
            ->orWhere('shop_domain', $session->shop_domain)->first();
    }
    if (!$store) $store = \App\Models\Store::where('is_active', true)->first();

    if (!$store || empty($store->access_token)) {
        return back()->with('error', 'No valid store found.');
    }

    try {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        $pi     = $stripe->paymentIntents->retrieve($session->stripe_payment_intent_id, [
            'expand' => ['latest_charge', 'latest_charge.payment_method_details'],
        ]);

        $charge = $pi->latest_charge ?? null;

        $shopify = new \App\Services\ShopifyService($store->myshopify_domain, $store->access_token);
        $result  = $shopify->createOrder($session, [
            'payment_intent_id' => $pi->id,
            'charge_id'         => $charge?->id ?? '',
            'card_brand'        => $charge?->payment_method_details?->card?->brand ?? 'unknown',
            'card_last4'        => $charge?->payment_method_details?->card?->last4 ?? '0000',
            'wallet_type'       => $charge?->payment_method_details?->card?->wallet?->type ?? 'card',
        ]);

        if ($result['success']) {
            $session->update([
                'shopify_order_id'      => $result['shopify_id'],
                'shopify_order_number'  => $result['order_number'],
                'shopify_thank_you_url' => $result['thank_you_url'] ?? null,
                'status'                => 'completed',
                'store_id'              => $store->id,
            ]);
            try { $store->increment('total_orders'); } catch (\Throwable $e) {}

            $msg = '✅ Synced: ' . $result['order_number'];
            if (!empty($result['thank_you_url'])) {
                $msg .= ' | Thank you URL saved';
            }
            return back()->with('success', $msg);
        }

        return back()->with('error', '❌ Failed: ' . ($result['error'] ?? 'Unknown'));

    } catch (\Throwable $e) {
        Log::error('Admin sync error', ['error' => $e->getMessage()]);
        return back()->with('error', '❌ Error: ' . $e->getMessage());
    }
})->name('sync.order');
});

// =====================================================================
// TENANT (MERCHANT) - AUTH ROUTES
// =====================================================================
Route::get('/app', function () {
    return redirect('/app/login');
});

Route::get('/app/register', function () {
    if (Auth::guard('tenant')->check()) {
        return redirect('/app/dashboard');
    }
    return view('tenant.auth.register');
})->name('tenant.register');

Route::post('/app/register', function (Request $request) {
    $request->validate([
        'name'         => 'required|string|max:100',
        'email'        => 'required|email|unique:tenants,email',
        'password'     => 'required|string|min:8|confirmed',
        'company_name' => 'nullable|string|max:100',
    ]);

    $tenant = \App\Models\Tenant::create([
        'name'          => $request->name,
        'slug'          => Str::slug($request->name) . '-' . Str::random(4),
        'email'         => $request->email,
        'password'      => Hash::make($request->password),
        'company_name'  => $request->company_name,
        'plan'          => 'trial',
        'trial_ends_at' => now()->addDays(14),
        'is_active'     => true,
    ]);

    Auth::guard('tenant')->login($tenant);

    return redirect('/app/dashboard')->with('welcome', true);
})->name('tenant.register.post');

Route::get('/app/login', function () {
    if (Auth::guard('tenant')->check()) {
        return redirect('/app/dashboard');
    }
    return view('tenant.auth.login');
})->name('tenant.login');

Route::post('/app/login', function (Request $request) {
    $request->validate(['email' => 'required|email', 'password' => 'required']);

    if (!Auth::guard('tenant')->attempt(
        $request->only('email', 'password'),
        $request->boolean('remember')
    )) {
        return back()->withInput()->withErrors(['email' => 'Invalid email or password.']);
    }

    $request->session()->regenerate();
    return redirect()->intended('/app/dashboard');
})->name('tenant.login.post');

Route::post('/app/logout', function (Request $request) {
    Auth::guard('tenant')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/app/login');
})->name('tenant.logout');

// =====================================================================
// TENANT PROTECTED ROUTES
// =====================================================================
Route::middleware(\App\Http\Middleware\TenantAuth::class)->prefix('app')->name('tenant.')->group(function () {
    
    Route::post('/mark-setup-done/{storeId}', function (int $storeId) {
    $tenant = Auth::guard('tenant')->user();
    $store  = \App\Models\Store::where('id', $storeId)->where('tenant_id', $tenant->id)->first();

    if (!$store) {
        return back()->with('error', 'Store not found');
    }

    $store->update([
        'button_active'      => true,
        'button_injected_at' => now(),
    ]);

    return back()->with('success', '✅ Setup marked as complete! Your store is ready to accept payments.');
})->name('mark-setup-done');

    // ============================================================
    // DASHBOARD
    // ============================================================
    Route::get('/dashboard', function () {
        $tenant = Auth::guard('tenant')->user();

        $stores       = collect();
        $recentOrders = collect();
        $stats = [
            'total_stores' => 0, 'total_revenue' => 0, 'total_orders' => 0,
            'today_orders' => 0, 'today_revenue' => 0, 'pending_sync' => 0,
        ];

        try { $stores = $tenant->activeStores()->get(); $stats['total_stores'] = $stores->count(); } catch (\Throwable $e) {}

        try {
            $base = \App\Models\CheckoutSession::where('tenant_id', $tenant->id);
            $stats['total_revenue'] = (clone $base)->where('status', 'completed')->sum('charged_amount') / 100;
            $stats['total_orders']  = (clone $base)->where('status', 'completed')->count();
            $stats['today_orders']  = (clone $base)->where('status', 'completed')->whereDate('created_at', today())->count();
            $stats['today_revenue'] = (clone $base)->where('status', 'completed')->whereDate('created_at', today())->sum('charged_amount') / 100;
            $stats['pending_sync']  = (clone $base)->where('status', 'paid')->whereNull('shopify_order_id')->count();

            $recentOrders = (clone $base)
                ->whereIn('status', ['completed', 'shopify_order_created', 'paid'])
                ->orderByDesc('created_at')
                ->limit(15)
                ->get();
        } catch (\Throwable $e) {}

        return view('tenant.dashboard', compact('tenant', 'stores', 'stats', 'recentOrders'));
    })->name('dashboard');

    // ============================================================
    // STORES
    // ============================================================
    Route::get('/stores', function () {
        $tenant = Auth::guard('tenant')->user();
        $stores = collect();
        try { $stores = $tenant->stores()->orderByDesc('created_at')->get(); } catch (\Throwable $e) {}
        return view('tenant.stores', compact('tenant', 'stores'));
    })->name('stores');

    // ============================================================
    // ORDERS
    // ============================================================
    Route::get('/orders', function (Request $request) {
        $tenant = Auth::guard('tenant')->user();
        $orders = collect();
        $stores = collect();

        try {
            $query = \App\Models\CheckoutSession::where('tenant_id', $tenant->id);

            if ($s = $request->get('status')) $query->where('status', $s);
            if ($q = $request->get('search')) {
                $query->where(function ($qb) use ($q) {
                    $qb->where('customer_email', 'like', "%{$q}%")
                       ->orWhere('shopify_order_number', 'like', "%{$q}%")
                       ->orWhere('stripe_payment_intent_id', 'like', "%{$q}%");
                });
            }

            $orders = $query->orderByDesc('created_at')->paginate(20);
            $stores = $tenant->stores()->get();
        } catch (\Throwable $e) {}

        return view('tenant.orders', compact('tenant', 'orders', 'stores'));
    })->name('orders');

    // ============================================================
    // TRANSACTION LOGS
    // ============================================================
    Route::get('/logs', function (Request $request) {
        $tenant = Auth::guard('tenant')->user();

        $query = \App\Models\CheckoutSession::where('tenant_id', $tenant->id);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_email', 'like', "%{$search}%")
                  ->orWhere('shopify_order_number', 'like', "%{$search}%")
                  ->orWhere('stripe_payment_intent_id', 'like', "%{$search}%")
                  ->orWhere('session_id', 'like', "%{$search}%");
            });
        }
        if ($date = $request->get('date')) {
            $query->whereDate('created_at', $date);
        }

        $logs = $query->orderByDesc('created_at')->paginate(25);

        $stats = [
            'total'     => \App\Models\CheckoutSession::where('tenant_id', $tenant->id)->count(),
            'completed' => \App\Models\CheckoutSession::where('tenant_id', $tenant->id)->where('status', 'completed')->count(),
            'paid'      => \App\Models\CheckoutSession::where('tenant_id', $tenant->id)->where('status', 'paid')->whereNull('shopify_order_id')->count(),
            'pending'   => \App\Models\CheckoutSession::where('tenant_id', $tenant->id)->whereIn('status', ['pending', 'payment_created'])->count(),
            'failed'    => \App\Models\CheckoutSession::where('tenant_id', $tenant->id)->where('status', 'failed')->count(),
            'expired'   => \App\Models\CheckoutSession::where('tenant_id', $tenant->id)->where('status', 'expired')->count(),
        ];

        return view('tenant.logs', compact('tenant', 'logs', 'stats'));
    })->name('logs');

    Route::get('/logs/{sessionId}', function (string $sessionId) {
        $tenant  = Auth::guard('tenant')->user();
        $session = \App\Models\CheckoutSession::where('session_id', $sessionId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $paymentLogs = \App\Models\PaymentLog::where('session_id', $sessionId)
            ->orderByDesc('created_at')
            ->get();

        $stripeData = null;
        if ($session->stripe_payment_intent_id) {
            try {
                $stripe     = new \Stripe\StripeClient(config('services.stripe.secret'));
                $stripeData = $stripe->paymentIntents->retrieve($session->stripe_payment_intent_id, [
                    'expand' => ['latest_charge', 'latest_charge.payment_method_details'],
                ]);
            } catch (\Throwable $e) {}
        }

        return view('tenant.log-detail', compact('tenant', 'session', 'paymentLogs', 'stripeData'));
    })->name('logs.detail');

    // ============================================================
    // SETTINGS
    // ============================================================
    Route::get('/settings', function () {
        $tenant = Auth::guard('tenant')->user();
        return view('tenant.settings', compact('tenant'));
    })->name('settings');

    // ============================================================
    // CONNECT STORE SHORTCUT
    // ============================================================
    Route::get('/connect-store', function (Request $request) {
        $shop = $request->get('shop', '');
        return redirect('/shopify/install?shop=' . urlencode($shop));
    })->name('connect-store');

    // ============================================================
    // RECONNECT STORE
    // ============================================================
    Route::get('/reconnect-store/{storeId}', function (int $storeId) {
        $tenant = Auth::guard('tenant')->user();
        $store  = \App\Models\Store::where('id', $storeId)->where('tenant_id', $tenant->id)->first();

        if (!$store) {
            return back()->with('error', 'Store not found.');
        }

        return redirect('/shopify/install?shop=' . urlencode($store->myshopify_domain));
    })->name('reconnect.store');

    // ============================================================
    // CUSTOMIZE BUTTON & CHECKOUT
    // ============================================================
    Route::get('/customize/{storeId}', function (int $storeId) {
        $tenant = Auth::guard('tenant')->user();
        $store  = \App\Models\Store::where('id', $storeId)->where('tenant_id', $tenant->id)->firstOrFail();
        return view('tenant.customize', compact('tenant', 'store'));
    })->name('customize');

    Route::post('/customize/{storeId}', function (int $storeId, Request $request) {
        $tenant = Auth::guard('tenant')->user();
        $store  = \App\Models\Store::where('id', $storeId)->where('tenant_id', $tenant->id)->firstOrFail();

        $data = $request->validate([
            'button_text'             => 'nullable|string|max:100',
            'button_bg_start'         => 'nullable|string|max:7',
            'button_bg_end'           => 'nullable|string|max:7',
            'button_text_color'       => 'nullable|string|max:7',
            'button_border_radius'    => 'nullable|string|max:10',
            'button_style'            => 'nullable|in:gradient,solid,outline',
            'show_trust_badges'       => 'nullable|boolean',
            'checkout_logo'           => 'nullable|url|max:500',
            'checkout_primary_color'  => 'nullable|string|max:7',
            'checkout_accent_color'   => 'nullable|string|max:7',
            'checkout_header_text'    => 'nullable|string|max:200',
            'checkout_footer_text'    => 'nullable|string|max:500',
        ]);

        $data['show_trust_badges'] = $request->boolean('show_trust_badges');

        $store->update($data);

        // Re-inject button with new style
        try {
            $shopify = new \App\Services\ShopifyService($store->myshopify_domain, $store->access_token);
            $snippet = app(\App\Http\Controllers\Shopify\InstallController::class)->buildSnippet($store->id, config('app.url'), $store);
            $shopify->injectThemeSnippet($snippet);

            $store->update(['button_injected_at' => now()]);

            return back()->with('success', '✅ Customization saved and button updated on your Shopify theme!');
        } catch (\Throwable $e) {
            return back()->with('success', '✅ Settings saved. Button update on Shopify failed: ' . $e->getMessage());
        }
    })->name('customize.save');

    // ============================================================
    // MANUAL SYNC ORDER
    // ============================================================
    Route::post('/sync-order/{sessionId}', function (string $sessionId) {
        $tenant = Auth::guard('tenant')->user();

        $session = \App\Models\CheckoutSession::where('session_id', $sessionId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$session) {
            return back()->with('error', 'Session not found.');
        }

        if ($session->shopify_order_id) {
            return back()->with('error', 'Already synced: ' . $session->shopify_order_number);
        }

        if (!$session->stripe_payment_intent_id) {
            return back()->with('error', 'No Stripe payment found for this session.');
        }

        // Find store
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
            $store = \App\Models\Store::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->first();
        }

        if (!$store) {
            return back()->with('error', 'No store found. Please reconnect your Shopify store.');
        }

        if (empty($store->access_token)) {
            return back()->with('error', 'Store token expired. Please reinstall the app from Shopify.');
        }

        try {
            // Get payment details from Stripe
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $pi     = $stripe->paymentIntents->retrieve($session->stripe_payment_intent_id, [
                'expand' => ['latest_charge', 'latest_charge.payment_method_details'],
            ]);

            $charge = $pi->latest_charge ?? null;

            $paymentData = [
                'payment_intent_id' => $pi->id,
                'charge_id'         => $charge?->id ?? '',
                'card_brand'        => $charge?->payment_method_details?->card?->brand ?? 'unknown',
                'card_last4'        => $charge?->payment_method_details?->card?->last4 ?? '0000',
                'wallet_type'       => $charge?->payment_method_details?->card?->wallet?->type ?? 'card',
            ];

            // Update store_id if missing
            if (!$session->store_id) {
                $session->update(['store_id' => $store->id]);
            }

            // Update session with payment details if missing
            if (!$session->stripe_charge_id && $charge) {
                $session->update([
                    'stripe_charge_id'    => $charge->id,
                    'card_brand'          => $paymentData['card_brand'],
                    'card_last4'          => $paymentData['card_last4'],
                    'wallet_type'         => $paymentData['wallet_type'],
                    'payment_method_type' => $charge->payment_method_details->type ?? 'card',
                ]);
            }

            // Create Shopify order
            $shopify = new \App\Services\ShopifyService($store->myshopify_domain, $store->access_token);
            $result  = $shopify->createOrder($session, $paymentData);

            if ($result['success']) {
                $session->update([
                    'shopify_order_id'      => $result['shopify_id'],
                    'shopify_order_number'  => $result['order_number'],
                    'shopify_thank_you_url' => $result['thank_you_url'] ?? null,
                    'status'                => 'completed',
                ]);

                try {
                    $store->increment('total_orders');
                    $store->increment('total_revenue', ($session->charged_amount ?? $session->subtotal) / 100);
                    $tenant->increment('total_orders');
                    $tenant->increment('total_revenue', ($session->charged_amount ?? $session->subtotal) / 100);
                } catch (\Throwable $e) {}

                Log::info('Manual sync success', [
                    'session_id'   => $sessionId,
                    'order_number' => $result['order_number'],
                ]);

                return back()->with('success', '✅ Order synced to Shopify! Order: ' . $result['order_number']);
            }

            return back()->with('error', '❌ Shopify order failed: ' . ($result['error'] ?? 'Unknown error'));

        } catch (\Stripe\Exception\InvalidRequestException $e) {
            return back()->with('error', '❌ Stripe error: ' . $e->getMessage());

        } catch (\Throwable $e) {
            Log::error('Manual sync error', [
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ]);
            return back()->with('error', '❌ Sync error: ' . $e->getMessage());
        }
    })->name('sync.order');

    // ============================================================
    // SYNC ALL PENDING ORDERS (bulk)
    // ============================================================
Route::post('/sync-all', function () {
    $tenant = Auth::guard('tenant')->user();

    $pending = \App\Models\CheckoutSession::where('tenant_id', $tenant->id)
        ->where('status', 'paid')
        ->whereNull('shopify_order_id')
        ->whereNotNull('stripe_payment_intent_id')
        ->orderByDesc('created_at')
        ->limit(10)
        ->get();

    if ($pending->isEmpty()) {
        return back()->with('error', 'No pending orders to sync.');
    }

    $synced = 0;
    $failed = 0;
    $errors = [];

    foreach ($pending as $session) {
        $store = null;
        if ($session->store_id) $store = \App\Models\Store::find($session->store_id);
        if (!$store) $store = \App\Models\Store::where('tenant_id', $tenant->id)->where('is_active', true)->first();
        if (!$store || empty($store->access_token)) { $failed++; continue; }

        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $pi     = $stripe->paymentIntents->retrieve($session->stripe_payment_intent_id, [
                'expand' => ['latest_charge', 'latest_charge.payment_method_details'],
            ]);

            $charge = $pi->latest_charge ?? null;

            $shopify = new \App\Services\ShopifyService($store->myshopify_domain, $store->access_token);
            $result  = $shopify->createOrder($session, [
                'payment_intent_id' => $pi->id,
                'charge_id'         => $charge?->id ?? '',
                'card_brand'        => $charge?->payment_method_details?->card?->brand ?? 'unknown',
                'card_last4'        => $charge?->payment_method_details?->card?->last4 ?? '0000',
                'wallet_type'       => $charge?->payment_method_details?->card?->wallet?->type ?? 'card',
            ]);

            if ($result['success']) {
                $session->update([
                    'shopify_order_id'      => $result['shopify_id'],
                    'shopify_order_number'  => $result['order_number'],
                    'shopify_thank_you_url' => $result['thank_you_url'] ?? null,
                    'store_id'              => $store->id,
                    'status'                => 'completed',
                ]);
                try { $store->increment('total_orders'); } catch (\Throwable $e) {}
                $synced++;
            } else {
                $failed++;
                $errors[] = ($result['error'] ?? 'Unknown');
            }

            usleep(300000);

        } catch (\Throwable $e) {
            $failed++;
            $errors[] = $e->getMessage();
        }
    }

    $msg = "✅ Synced: {$synced} orders.";
    if ($failed > 0) {
        $msg .= " ❌ Failed: {$failed}.";
        if (!empty($errors)) {
            $msg .= " Errors: " . implode('; ', array_unique(array_slice($errors, 0, 3)));
        }
    }

    return back()->with($synced > 0 ? 'success' : 'error', $msg);
})->name('sync.all');
});

// =====================================================================
// MARKETING PAGES (simple)
// =====================================================================
Route::get('/pricing', function () {
    return view()->exists('marketing.pricing') ? view('marketing.pricing') : redirect('/');
})->name('pricing');

Route::get('/features', function () {
    return view()->exists('marketing.features') ? view('marketing.features') : redirect('/');
})->name('features');

Route::get('/about',     function () { return redirect('/'); })->name('about');
Route::get('/contact',   function () { return redirect('/'); })->name('contact');
Route::get('/blog',      function () { return redirect('/'); })->name('blog');
Route::get('/privacy',   function () { return redirect('/'); })->name('privacy');
Route::get('/terms',     function () { return redirect('/'); })->name('terms');
Route::get('/changelog', function () { return redirect('/'); })->name('changelog');