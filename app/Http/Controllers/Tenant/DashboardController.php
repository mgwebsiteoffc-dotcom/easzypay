<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private function tenant()
    {
        return Auth::guard('tenant')->user();
    }

    public function index()
    {
        $tenant = $this->tenant();

        // Load stores safely
        $stores = [];
        try {
            $stores = $tenant->activeStores()->get();
        } catch (\Throwable $e) {
            $stores = collect([]);
        }

        // Stats with safe fallbacks
        $stats = [
            'total_stores'  => $stores->count(),
            'total_revenue' => 0,
            'total_orders'  => 0,
            'today_orders'  => 0,
            'today_revenue' => 0,
            'pending_sync'  => 0,
        ];

        try {
            $stats['total_revenue'] = CheckoutSession::where('tenant_id', $tenant->id)
                ->where('status', 'completed')
                ->sum('charged_amount') / 100;

            $stats['total_orders'] = CheckoutSession::where('tenant_id', $tenant->id)
                ->where('status', 'completed')
                ->count();

            $stats['today_orders'] = CheckoutSession::where('tenant_id', $tenant->id)
                ->where('status', 'completed')
                ->whereDate('created_at', today())
                ->count();

            $stats['today_revenue'] = CheckoutSession::where('tenant_id', $tenant->id)
                ->where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('charged_amount') / 100;

            $stats['pending_sync'] = CheckoutSession::where('tenant_id', $tenant->id)
                ->where('status', 'paid')
                ->whereNull('shopify_order_id')
                ->count();
        } catch (\Throwable $e) {
            // Tables might not exist yet
        }

        $recentOrders = collect([]);
        try {
            $recentOrders = CheckoutSession::where('tenant_id', $tenant->id)
                ->with('store')
                ->whereIn('status', ['completed', 'shopify_order_created'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            // Safe fallback
        }

        return view('tenant.dashboard', compact('tenant', 'stores', 'stats', 'recentOrders'));
    }

    public function stores()
    {
        $tenant = $this->tenant();
        $stores = collect([]);

        try {
            $stores = $tenant->stores()->orderByDesc('created_at')->get();
        } catch (\Throwable $e) {}

        return view('tenant.stores', compact('tenant', 'stores'));
    }

    public function orders(Request $request)
    {
        $tenant = $this->tenant();
        $orders = collect([]);
        $stores = collect([]);

        try {
            $query = CheckoutSession::where('tenant_id', $tenant->id)->with('store');

            if ($storeId = $request->get('store')) {
                $query->where('store_id', $storeId);
            }

            if ($status = $request->get('status')) {
                $query->where('status', $status);
            }

            if ($search = $request->get('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('customer_email', 'like', "%{$search}%")
                      ->orWhere('shopify_order_number', 'like', "%{$search}%");
                });
            }

            $orders = $query->orderByDesc('created_at')->paginate(20);
            $stores = $tenant->stores()->get();

        } catch (\Throwable $e) {}

        return view('tenant.orders', compact('tenant', 'orders', 'stores'));
    }

    public function settings()
    {
        $tenant = $this->tenant();
        return view('tenant.settings', compact('tenant'));
    }
}