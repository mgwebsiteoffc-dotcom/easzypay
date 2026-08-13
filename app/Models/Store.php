<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_domain', 'myshopify_domain', 'shop_name',
        'shop_email', 'shop_phone', 'access_token', 'scopes',
        'api_version', 'shopify_plan', 'currency', 'country_code',
        'timezone', 'shop_owner', 'is_installed', 'installed_at',
        'uninstalled_at', 'registered_webhooks', 'webhooks_registered_at',
        'last_synced_at', 'button_injected_at', 'button_active',
        'stripe_account_id', 'checkout_settings', 'primary_color',
        'is_active', 'total_orders', 'total_revenue','button_text', 'button_bg_start', 'button_bg_end', 'button_text_color',
'button_border_radius', 'button_style', 'show_trust_badges',
'checkout_logo', 'checkout_primary_color', 'checkout_accent_color',
'checkout_font_family', 'checkout_header_text', 'checkout_footer_text',
    ];

    protected $casts = [
        'is_installed'          => 'boolean',
        'button_active'         => 'boolean',
        'is_active'             => 'boolean',
        'registered_webhooks'   => 'array',
        'checkout_settings'     => 'array',
        'installed_at'          => 'datetime',
        'uninstalled_at'        => 'datetime',
        'webhooks_registered_at'=> 'datetime',
        'last_synced_at'        => 'datetime',
        'button_injected_at'    => 'datetime',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

public function getSnippetCode(): string
{
    $appUrl   = config('app.url');
    $storeId  = $this->id;

    $btnText    = $this->button_text          ?? '⚡ Buy Now — Secure Checkout';
    $bgStart    = $this->button_bg_start      ?? '#667eea';
    $bgEnd      = $this->button_bg_end        ?? '#764ba2';
    $txtColor   = $this->button_text_color    ?? '#ffffff';
    $radius     = $this->button_border_radius ?? '8px';
    $style      = $this->button_style         ?? 'gradient';
    $showBadges = $this->show_trust_badges    ?? true;

    if ($style === 'gradient') {
        $bgCss = "background:linear-gradient(135deg,{$bgStart},{$bgEnd});border:none;";
    } elseif ($style === 'solid') {
        $bgCss = "background:{$bgStart};border:none;";
    } else {
        $bgCss = "background:transparent;border:2px solid {$bgStart};";
        $txtColor = $bgStart;
    }

    $shadowColor = ltrim($bgStart, '#');
    $r = hexdec(substr($shadowColor, 0, 2));
    $g = hexdec(substr($shadowColor, 2, 2));
    $b = hexdec(substr($shadowColor, 4, 2));
    $shadowRgba = "rgba({$r},{$g},{$b},.4)";

    $badgesHtml = '';
    if ($showBadges) {
        $badgesHtml = '<div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-top:10px;">' .
            '<span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">🔒 SSL</span>' .
            '<span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">💳 All Cards</span>' .
            '<span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">🍎 Apple Pay</span>' .
            '<span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">G Pay</span>' .
        '</div>';
    }

    return <<<LIQUID
{%- comment -%} EaszyPay Checkout Button - Store #{$storeId} {%- endcomment -%}
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
    {%- unless product.selected_or_first_available_variant.available -%}disabled{%- endunless -%}
    style="width:100%;padding:16px 24px;{$bgCss}color:{$txtColor};border-radius:{$radius};font-size:16px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:8px;transition:all 0.3s ease;"
    onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px {$shadowRgba}'"
    onmouseout="this.style.transform='';this.style.boxShadow=''">
    {%- if product.selected_or_first_available_variant.available -%}
      {$btnText}
    {%- else -%}
      Sold Out
    {%- endif -%}
  </button>
  <div id="ep-sts" style="display:none;text-align:center;padding:10px;color:{$bgStart};font-size:14px;font-weight:500;"></div>
  <div id="ep-err" style="display:none;padding:10px 14px;margin-top:8px;background:#fff5f5;border:1px solid #fc8181;border-radius:6px;color:#c53030;font-size:13px;text-align:center;"></div>
  {$badgesHtml}
</div>

<script>
(function(){
  var EP='{$appUrl}';
  var SID={$storeId};
  var btn=document.getElementById('easzypay-btn');
  var sts=document.getElementById('ep-sts');
  var err=document.getElementById('ep-err');
  if(!btn)return;

  function show(m){sts.textContent=m;sts.style.display='block';err.style.display='none';btn.disabled=true;btn.style.opacity='0.7';}
  function fail(m){err.textContent='⚠️ '+m;err.style.display='block';sts.style.display='none';btn.disabled=false;btn.style.opacity='1';}

  document.addEventListener('variant:changed',function(e){
    if(!e.detail||!e.detail.variant)return;
    var v=e.detail.variant;
    btn.dataset.variantId=v.id;
    btn.dataset.price=v.price;
    btn.dataset.variant=v.title;
    btn.disabled=!v.available;
  });

  function q(){
    var e=document.querySelector('[name="quantity"]');
    return e?Math.max(1,parseInt(e.value)||1):1;
  }

  function shop(){
    return(window.Shopify&&window.Shopify.shop)?window.Shopify.shop:window.location.hostname;
  }

  function xhr(m,u,d){
    return new Promise(function(ok,no){
      var x=new XMLHttpRequest();
      x.open(m,u,true);
      x.setRequestHeader('Content-Type','application/json');
      x.setRequestHeader('Accept','application/json');
      x.timeout=30000;
      x.onreadystatechange=function(){
        if(x.readyState!==4)return;
        try{ok(JSON.parse(x.responseText));}catch(e){no(new Error('Bad response'));}
      };
      x.onerror=function(){no(new Error('Network error'));};
      x.ontimeout=function(){no(new Error('Timeout'));};
      x.send(d?JSON.stringify(d):null);
    });
  }

  btn.addEventListener('click',function(){
    var vid=btn.dataset.variantId;
    var pid=btn.dataset.productId;
    var title=btn.dataset.title||'Product';
    var vt=btn.dataset.variant||'';
    var price=parseInt(btn.dataset.price||'0');
    var cur=btn.dataset.currency||'USD';
    var img=btn.dataset.image||'';
    var qty=q();

    if(!vid||price<=0){fail('Select an option');return;}
    show('⏳ Preparing checkout...');

    xhr('POST','/cart/add.js',{id:parseInt(vid),quantity:qty})
      .then(function(){return xhr('GET','/cart.js');})
      .then(function(cart){
        return xhr('POST',EP+'/shopify/cart',{
          store_id:SID,
          shop_domain:shop(),
          cart_token:cart.token||'',
          currency:cur,
          subtotal:price*qty,
          items:[{
            product_id:parseInt(pid)||0,
            variant_id:parseInt(vid),
            quantity:qty,
            title:title,
            variant_title:vt,
            price:price,
            image:img
          }]
        });
      })
      .then(function(d){
        if(d.error){fail(d.error.message);return;}
        if(!d.checkout_url){fail('No checkout URL');return;}
        show('✅ Redirecting...');
        setTimeout(function(){window.location.href=d.checkout_url;},300);
      })
      .catch(function(e){fail(e.message||'Error');});
  });
}());
</script>
LIQUID;
}

    public function checkoutSessions()
    {
        return $this->hasMany(CheckoutSession::class);
    }

    // Helpers
    public function getShopifyService(): \App\Services\ShopifyService
    {
        return new \App\Services\ShopifyService(
            $this->myshopify_domain,
            $this->access_token
        );
    }

    public function needsSync(): bool
    {
        return !$this->last_synced_at
            || $this->last_synced_at->diffInMinutes(now()) >= 5;
    }
}