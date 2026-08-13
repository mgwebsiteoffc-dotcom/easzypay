<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\PaymentLog;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Stats for today
        $today = now()->startOfDay();

        $stats = [
            // Orders
            'total_orders'     => CheckoutSession::where('status', 'completed')->count(),
            'today_orders'     => CheckoutSession::where('status', 'completed')
                                    ->where('created_at', '>=', $today)->count(),

            // Revenue (in cents → dollars)
            'total_revenue'    => CheckoutSession::where('status', 'completed')
                                    ->sum('charged_amount') / 100,
            'today_revenue'    => CheckoutSession::where('status', 'completed')
                                    ->where('created_at', '>=', $today)
                                    ->sum('charged_amount') / 100,

            // Pending
            'pending_sessions' => CheckoutSession::whereIn('status', ['pending', 'payment_created'])
                                    ->where('expires_at', '>', now())->count(),

            // Failed
            'failed_payments'  => CheckoutSession::where('status', 'failed')
                                    ->where('created_at', '>=', $today)->count(),

            // Webhooks
            'failed_webhooks'  => WebhookLog::where('status', 'failed')
                                    ->where('created_at', '>=', $today)->count(),

            // Shopify sync
            'pending_shopify'  => CheckoutSession::where('status', 'paid')
                                    ->whereNull('shopify_order_id')->count(),
        ];

        // Recent orders (last 10)
        $recentOrders = CheckoutSession::whereIn('status', ['completed', 'shopify_order_created'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Revenue chart (last 7 days)
        $revenueChart = CheckoutSession::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(charged_amount) / 100 as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Currency breakdown
        $currencyBreakdown = CheckoutSession::where('status', 'completed')
            ->select('charged_currency', DB::raw('COUNT(*) as count'), DB::raw('SUM(charged_amount) / 100 as total'))
            ->groupBy('charged_currency')
            ->orderByDesc('count')
            ->get();

        // Payment method breakdown
        $paymentMethods = PaymentLog::where('event_type', 'payment_intent.succeeded')
            ->select('wallet_type', DB::raw('COUNT(*) as count'))
            ->groupBy('wallet_type')
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentOrders',
            'revenueChart',
            'currencyBreakdown',
            'paymentMethods'
        ));
    }

    public function orders(Request $request)
    {
        $query = CheckoutSession::query();

        // Filters
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('session_id', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('shopify_order_number', 'like', "%{$search}%")
                  ->orWhere('stripe_payment_intent_id', 'like', "%{$search}%");
            });
        }

        if ($currency = $request->get('currency')) {
            $query->where('charged_currency', strtoupper($currency));
        }

        if ($date = $request->get('date')) {
            $query->whereDate('created_at', $date);
        }

        $orders = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.orders', compact('orders'));
    }

    public function orderDetail(string $sessionId)
    {
        $session = CheckoutSession::where('session_id', $sessionId)
            ->with('logs')
            ->firstOrFail();

        return view('admin.order-detail', compact('session'));
    }

    public function webhooks(Request $request)
    {
        $query = WebhookLog::query();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($source = $request->get('source')) {
            $query->where('source', $source);
        }

        $webhooks = $query->orderByDesc('created_at')->paginate(25);

        return view('admin.webhooks', compact('webhooks'));
    }

    public function retryWebhook(int $id)
    {
        $log = WebhookLog::findOrFail($id);

        // TODO: Implement retry logic

        $log->update([
            'status'   => 'received',
            'error'    => null,
            'attempts' => $log->attempts + 1,
        ]);

        return back()->with('success', "Webhook #{$id} queued for retry.");
    }
}