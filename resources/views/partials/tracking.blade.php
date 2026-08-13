@php
    $store = $store ?? null;
    $page = $page ?? 'checkout';
    $session = $session ?? null;
    $settings = is_array($store?->checkout_settings) ? $store->checkout_settings : [];
    $ga4 = trim((string) ($settings['ga4_id'] ?? ''));
    $custom = $page === 'thankyou'
        ? (string) ($settings['thankyou_tracking'] ?? '')
        : (string) ($settings['checkout_tracking'] ?? '');
    $value = 0;
    $currency = 'USD';
    $txn = '';
    $items = [];
    if ($session) {
        $currency = strtoupper($session->charged_currency ?? $session->currency ?? 'USD');
        $cents = (int) ($session->charged_amount ?? $session->total_amount ?? $session->subtotal ?? 0);
        $value = round($cents / 100, 2);
        $txn = (string) ($session->shopify_order_number ?: $session->session_id);
        foreach ((array) ($session->items ?? []) as $item) {
            $items[] = [
                'item_id' => (string) ($item['variant_id'] ?? $item['product_id'] ?? ''),
                'item_name' => (string) ($item['title'] ?? 'Product'),
                'price' => round(((int) ($item['price'] ?? 0)) / 100, 2),
                'quantity' => (int) ($item['quantity'] ?? 1),
            ];
        }
    }
@endphp
@if($ga4 !== '')
<script async src="https://www.googletagmanager.com/gtag/js?id={{ e($ga4) }}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', @json($ga4));
@if($page === 'checkout')
gtag('event', 'begin_checkout', {
    currency: @json($currency),
    value: {{ $value }},
    items: @json($items)
});
@elseif($page === 'thankyou')
gtag('event', 'purchase', {
    transaction_id: @json($txn),
    currency: @json($currency),
    value: {{ $value }},
    items: @json($items)
});
@endif
</script>
@endif
@if($custom !== '')
{!! $custom !!}
@endif
