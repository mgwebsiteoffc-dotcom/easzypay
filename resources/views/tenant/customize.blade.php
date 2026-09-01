@extends('tenant.layout')
@section('title', 'Customize Button & Checkout')

@section('content')

<div style="margin-bottom:20px;">
    <a href="{{ route('tenant.stores') }}" style="color:var(--p);text-decoration:none;font-size:14px;">← Back to Stores</a>
</div>

<form method="POST" action="{{ route('tenant.customize.save', $store->id) }}" id="customForm">
    @csrf

    <!-- Two column layout: Settings | Preview -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

        <!-- LEFT: SETTINGS -->
        <div>
            <h2 style="font-size:20px;font-weight:700;margin-bottom:16px;">🎨 Customize {{ $store->shop_name }}</h2>

            <!-- Button Style -->
            <div class="stat" style="margin-bottom:20px;">
                <div class="stat-label" style="margin-bottom:16px;font-size:14px;">⚡ Buy Now Button</div>

                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--g700);margin-bottom:6px;">Button Text</label>
                    <input type="text" name="button_text" id="btnText" value="{{ $store->button_text ?? '⚡ Buy Now — Secure Checkout' }}" style="width:100%;padding:10px 14px;border:1.5px solid var(--g200);border-radius:8px;font-size:14px;">
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--g700);margin-bottom:6px;">Button Style</label>
                    <div style="display:flex;gap:8px;">
                        @foreach(['gradient'=>'🌈 Gradient', 'solid'=>'⬛ Solid', 'outline'=>'⭕ Outline'] as $val => $lbl)
                        <label style="flex:1;padding:10px;border:1.5px solid var(--g200);border-radius:8px;cursor:pointer;text-align:center;font-size:13px;font-weight:600;">
                            <input type="radio" name="button_style" value="{{ $val }}" {{ ($store->button_style ?? 'gradient') === $val ? 'checked' : '' }} onchange="updatePreview()" style="margin-right:6px;"> {{ $lbl }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--g700);margin-bottom:6px;">Background Start</label>
                        <div style="display:flex;gap:6px;">
                            <input type="color" name="button_bg_start" id="bgStart" value="{{ $store->button_bg_start ?? '#667eea' }}" onchange="updatePreview()" style="width:46px;height:38px;border:1.5px solid var(--g200);border-radius:6px;cursor:pointer;">
                            <input type="text" id="bgStartHex" value="{{ $store->button_bg_start ?? '#667eea' }}" onchange="document.getElementById('bgStart').value=this.value;updatePreview()" style="flex:1;padding:8px 12px;border:1.5px solid var(--g200);border-radius:6px;font-size:13px;font-family:monospace;">
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--g700);margin-bottom:6px;">Background End (gradient)</label>
                        <div style="display:flex;gap:6px;">
                            <input type="color" name="button_bg_end" id="bgEnd" value="{{ $store->button_bg_end ?? '#764ba2' }}" onchange="updatePreview()" style="width:46px;height:38px;border:1.5px solid var(--g200);border-radius:6px;cursor:pointer;">
                            <input type="text" id="bgEndHex" value="{{ $store->button_bg_end ?? '#764ba2' }}" onchange="document.getElementById('bgEnd').value=this.value;updatePreview()" style="flex:1;padding:8px 12px;border:1.5px solid var(--g200);border-radius:6px;font-size:13px;font-family:monospace;">
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--g700);margin-bottom:6px;">Text Color</label>
                        <div style="display:flex;gap:6px;">
                            <input type="color" name="button_text_color" id="txtColor" value="{{ $store->button_text_color ?? '#ffffff' }}" onchange="updatePreview()" style="width:46px;height:38px;border:1.5px solid var(--g200);border-radius:6px;cursor:pointer;">
                            <input type="text" id="txtColorHex" value="{{ $store->button_text_color ?? '#ffffff' }}" onchange="document.getElementById('txtColor').value=this.value;updatePreview()" style="flex:1;padding:8px 12px;border:1.5px solid var(--g200);border-radius:6px;font-size:13px;font-family:monospace;">
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--g700);margin-bottom:6px;">Border Radius</label>
                        <select name="button_border_radius" id="radius" onchange="updatePreview()" style="width:100%;padding:9px 14px;border:1.5px solid var(--g200);border-radius:8px;font-size:13px;">
                            <option value="0px" {{ ($store->button_border_radius ?? '8px') === '0px' ? 'selected' : '' }}>0px (Sharp)</option>
                            <option value="4px" {{ ($store->button_border_radius ?? '8px') === '4px' ? 'selected' : '' }}>4px (Subtle)</option>
                            <option value="8px" {{ ($store->button_border_radius ?? '8px') === '8px' ? 'selected' : '' }}>8px (Medium)</option>
                            <option value="12px" {{ ($store->button_border_radius ?? '8px') === '12px' ? 'selected' : '' }}>12px (Rounded)</option>
                            <option value="24px" {{ ($store->button_border_radius ?? '8px') === '24px' ? 'selected' : '' }}>24px (Pill)</option>
                            <option value="50px" {{ ($store->button_border_radius ?? '8px') === '50px' ? 'selected' : '' }}>50px (Round)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:8px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
                        <input type="checkbox" name="show_trust_badges" value="1" {{ ($store->show_trust_badges ?? true) ? 'checked' : '' }} onchange="updatePreview()">
                        Show trust badges (SSL, Cards, Apple Pay, G Pay)
                    </label>
                </div>
            </div>

            <!-- Quick Presets -->
            <div class="stat" style="margin-bottom:20px;">
                <div class="stat-label" style="margin-bottom:12px;font-size:14px;">🎨 Quick Presets</div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                    <button type="button" onclick="applyPreset('purple')" style="padding:10px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px;">Purple</button>
                    <button type="button" onclick="applyPreset('black')" style="padding:10px;background:#000;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px;">Black</button>
                    <button type="button" onclick="applyPreset('green')" style="padding:10px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px;">Green</button>
                    <button type="button" onclick="applyPreset('pink')" style="padding:10px;background:linear-gradient(135deg,#ec4899,#be185d);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px;">Pink</button>
                    <button type="button" onclick="applyPreset('blue')" style="padding:10px;background:linear-gradient(135deg,#3b82f6,#1e40af);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px;">Blue</button>
                    <button type="button" onclick="applyPreset('orange')" style="padding:10px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px;">Orange</button>
                    <button type="button" onclick="applyPreset('red')" style="padding:10px;background:linear-gradient(135deg,#ef4444,#b91c1c);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px;">Red</button>
                    <button type="button" onclick="applyPreset('teal')" style="padding:10px;background:linear-gradient(135deg,#14b8a6,#0f766e);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px;">Teal</button>
                    <button type="button" onclick="applyPreset('gold')" style="padding:10px;background:linear-gradient(135deg,#d4af37,#b8860b);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px;">Gold</button>
                </div>
            </div>

            <!-- Checkout Page Customization -->
            <div class="stat" style="margin-bottom:20px;">
                <div class="stat-label" style="margin-bottom:16px;font-size:14px;">🛒 Checkout Page Style</div>

                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--g700);margin-bottom:6px;">Logo URL (optional)</label>
                    <input type="url" name="checkout_logo" value="{{ $store->checkout_logo ?? '' }}" placeholder="https://yourstore.com/logo.png" style="width:100%;padding:10px 14px;border:1.5px solid var(--g200);border-radius:8px;font-size:14px;">
                    <p style="font-size:12px;color:var(--g400);margin-top:4px;">Leave blank to show "⚡ EaszyPay"</p>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--g700);margin-bottom:6px;">Primary Color</label>
                        <div style="display:flex;gap:6px;">
                            <input type="color" name="checkout_primary_color" id="chkPrimary" value="{{ $store->checkout_primary_color ?? '#667eea' }}" onchange="document.getElementById('chkPrimaryHex').value=this.value;updateCheckoutPreview()" style="width:46px;height:38px;border:1.5px solid var(--g200);border-radius:6px;cursor:pointer;">
                            <input type="text" id="chkPrimaryHex" value="{{ $store->checkout_primary_color ?? '#667eea' }}" onchange="document.getElementById('chkPrimary').value=this.value;updateCheckoutPreview()" style="flex:1;padding:8px 12px;border:1.5px solid var(--g200);border-radius:6px;font-size:13px;font-family:monospace;">
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--g700);margin-bottom:6px;">Accent Color</label>
                        <div style="display:flex;gap:6px;">
                            <input type="color" name="checkout_accent_color" id="chkAccent" value="{{ $store->checkout_accent_color ?? '#764ba2' }}" onchange="document.getElementById('chkAccentHex').value=this.value;updateCheckoutPreview()" style="width:46px;height:38px;border:1.5px solid var(--g200);border-radius:6px;cursor:pointer;">
                            <input type="text" id="chkAccentHex" value="{{ $store->checkout_accent_color ?? '#764ba2' }}" onchange="document.getElementById('chkAccent').value=this.value;updateCheckoutPreview()" style="flex:1;padding:8px 12px;border:1.5px solid var(--g200);border-radius:6px;font-size:13px;font-family:monospace;">
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--g700);margin-bottom:6px;">Header Tagline (optional)</label>
                    <input type="text" name="checkout_header_text" value="{{ $store->checkout_header_text ?? '' }}" placeholder="Free shipping on orders over $50" style="width:100%;padding:10px 14px;border:1.5px solid var(--g200);border-radius:8px;font-size:14px;">
                </div>

                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--g700);margin-bottom:6px;">Footer Text (optional)</label>
                    <textarea name="checkout_footer_text" rows="2" placeholder="Returns within 30 days. Customer support: support@example.com" style="width:100%;padding:10px 14px;border:1.5px solid var(--g200);border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;">{{ $store->checkout_footer_text ?? '' }}</textarea>
                </div>
            </div>
            
            <!-- Manual Install Code -->
<div class="stat" style="margin-bottom:20px;">
    <div class="stat-label" style="margin-bottom:12px;font-size:14px;">📋 Manual Install Code (If Auto-Install Failed)</div>
    <p style="font-size:13px;color:var(--g500);margin-bottom:12px;">
        If the button didn't appear automatically on your product page, copy this code and add it manually:
    </p>

    <div style="background:#1e1b4b;color:#a5b4fc;border-radius:8px;padding:14px;font-family:monospace;font-size:12px;position:relative;margin-bottom:12px;">
        <button type="button" onclick="copySnippet()" style="position:absolute;top:8px;right:8px;background:rgba(255,255,255,0.1);color:#fff;border:none;padding:4px 10px;border-radius:4px;font-size:11px;cursor:pointer;">📋 Copy</button>
        <code id="snippetCode">&#123;%- render 'easzypay-button', product: product -%&#125;</code>
    </div>

    <p style="font-size:12px;color:var(--g500);line-height:1.7;">
        <strong>Steps:</strong><br>
        1. Save your customization above<br>
        2. Open <strong>Shopify Admin → Online Store → Themes → Edit code</strong><br>
        3. Find <code>sections/main-product.liquid</code><br>
        4. Add the code above after the "Add to Cart" button<br>
        5. Save the file
    </p>
</div>

<script>
function copySnippet() {
    var code = document.getElementById('snippetCode').textContent;
    navigator.clipboard.writeText(code).then(function(){
        alert('Code copied! Paste in Shopify theme editor.');
    });
}
</script>

            <button type="submit" class="btn btn-primary" style="width:100%;height:54px;font-size:16px;">
                💾 Save Customization & Update Shopify
            </button>
        </div>

        <!-- RIGHT: LIVE PREVIEW -->
        <div style="position:sticky;top:80px;align-self:start;">
            <h3 style="font-size:14px;font-weight:600;color:var(--g500);margin-bottom:12px;text-transform:uppercase;letter-spacing:.05em;">👁️ Live Preview</h3>

            <!-- Mock Shopify Product Page -->
            <div style="background:#fff;border:1px solid var(--g200);border-radius:12px;padding:24px;margin-bottom:16px;">
                <div style="font-size:11px;color:var(--g400);margin-bottom:8px;">PRODUCT PAGE PREVIEW</div>
                <div style="display:flex;gap:16px;margin-bottom:16px;">
                    <div style="width:100px;height:100px;background:linear-gradient(135deg,#f3f4f6,#e5e7eb);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:40px;">🛍️</div>
                    <div style="flex:1;">
                        <h4 style="font-size:18px;font-weight:700;margin-bottom:4px;">Product Title</h4>
                        <p style="font-size:14px;color:var(--g500);margin-bottom:8px;">Premium quality</p>
                        <p style="font-size:20px;font-weight:800;color:var(--g900);">$99.99</p>
                    </div>
                </div>

                <button type="button" style="width:100%;padding:14px;background:#000;color:#fff;border:none;border-radius:4px;font-weight:600;cursor:pointer;margin-bottom:8px;">
                    Add to Cart
                </button>

                <!-- THIS IS THE BUTTON PREVIEW -->
                <button type="button" id="previewBtn" style="width:100%;padding:16px 24px;background:linear-gradient(135deg,{{ $store->button_bg_start ?? '#667eea' }},{{ $store->button_bg_end ?? '#764ba2' }});color:{{ $store->button_text_color ?? '#ffffff' }};border:none;border-radius:{{ $store->button_border_radius ?? '8px' }};font-size:16px;font-weight:600;cursor:pointer;margin-top:8px;transition:all .3s ease;">
                    {{ $store->button_text ?? '⚡ Buy Now — Secure Checkout' }}
                </button>

                <div id="previewBadges" style="display:{{ ($store->show_trust_badges ?? true) ? 'flex' : 'none' }};gap:6px;flex-wrap:wrap;justify-content:center;margin-top:10px;">
                    <span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">🔒 SSL</span>
                    <span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">💳 All Cards</span>
                    <span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">🍎 Apple Pay</span>
                    <span style="font-size:11px;color:#718096;background:#f7fafc;border:1px solid #e2e8f0;border-radius:20px;padding:3px 10px;">G Pay</span>
                </div>
            </div>

            <!-- Checkout Preview -->
            <div style="background:#fff;border:1px solid var(--g200);border-radius:12px;padding:0;overflow:hidden;">
                <div style="font-size:11px;color:var(--g400);padding:8px 16px;background:var(--g50);">CHECKOUT PAGE PREVIEW</div>

                <!-- Mock checkout header -->
                <div id="chkHeader" style="padding:12px 24px;border-bottom:1px solid var(--g200);display:flex;justify-content:space-between;align-items:center;">
                    @if($store->checkout_logo)
                    <img src="{{ $store->checkout_logo }}" style="max-height:30px;">
                    @else
                    <span id="chkLogoText" style="font-size:18px;font-weight:800;background:linear-gradient(135deg,{{ $store->checkout_primary_color ?? '#667eea' }},{{ $store->checkout_accent_color ?? '#764ba2' }});-webkit-background-clip:text;-webkit-text-fill-color:transparent;">⚡ EaszyPay</span>
                    @endif
                    <span style="font-size:12px;color:#10b981;font-weight:600;">🔒 Secure</span>
                </div>

                <!-- Mock checkout body -->
                <div style="padding:20px;">
                    <div style="font-size:13px;color:var(--g500);margin-bottom:12px;">Contact</div>
                    <div style="height:36px;background:#f9fafb;border:1.5px solid var(--g200);border-radius:6px;margin-bottom:12px;"></div>
                    <div style="font-size:13px;color:var(--g500);margin-bottom:12px;">Payment</div>
                    <div style="height:60px;background:#f9fafb;border:1.5px solid var(--g200);border-radius:6px;margin-bottom:12px;"></div>

                    <button type="button" id="chkPayBtn" style="width:100%;padding:14px;background:linear-gradient(135deg,{{ $store->checkout_primary_color ?? '#667eea' }},{{ $store->checkout_accent_color ?? '#764ba2' }});color:#fff;border:none;border-radius:8px;font-weight:700;font-size:15px;">
                        🔒 Pay $99.99 Securely
                    </button>
                </div>
            </div>
        </div>

    </div>
</form>

<script>
function updatePreview(){
    var text   = document.getElementById('btnText').value;
    var bg1    = document.getElementById('bgStart').value;
    var bg2    = document.getElementById('bgEnd').value;
    var txt    = document.getElementById('txtColor').value;
    var radius = document.getElementById('radius').value;
    var style  = document.querySelector('input[name="button_style"]:checked').value;
    var badges = document.querySelector('input[name="show_trust_badges"]').checked;

    var btn = document.getElementById('previewBtn');
    btn.textContent = text;
    btn.style.color = txt;
    btn.style.borderRadius = radius;

    if(style === 'gradient'){
        btn.style.background = 'linear-gradient(135deg,' + bg1 + ',' + bg2 + ')';
        btn.style.border = 'none';
    } else if(style === 'solid'){
        btn.style.background = bg1;
        btn.style.border = 'none';
    } else { // outline
        btn.style.background = 'transparent';
        btn.style.border = '2px solid ' + bg1;
        btn.style.color = bg1;
    }

    // Update hex inputs
    document.getElementById('bgStartHex').value  = bg1;
    document.getElementById('bgEndHex').value    = bg2;
    document.getElementById('txtColorHex').value = txt;

    // Toggle badges
    document.getElementById('previewBadges').style.display = badges ? 'flex' : 'none';
}

function updateCheckoutPreview(){
    var primary = document.getElementById('chkPrimary').value;
    var accent  = document.getElementById('chkAccent').value;

    var logo    = document.getElementById('chkLogoText');
    var payBtn  = document.getElementById('chkPayBtn');

    if(logo){
        logo.style.background = 'linear-gradient(135deg,' + primary + ',' + accent + ')';
        logo.style.webkitBackgroundClip = 'text';
        logo.style.backgroundClip = 'text';
    }

    if(payBtn){
        payBtn.style.background = 'linear-gradient(135deg,' + primary + ',' + accent + ')';
    }

    document.getElementById('chkPrimaryHex').value = primary;
    document.getElementById('chkAccentHex').value  = accent;
}

function applyPreset(preset){
    var presets = {
        purple: {bg1:'#667eea', bg2:'#764ba2', txt:'#ffffff'},
        black:  {bg1:'#000000', bg2:'#1f2937', txt:'#ffffff'},
        green:  {bg1:'#10b981', bg2:'#059669', txt:'#ffffff'},
        pink:   {bg1:'#ec4899', bg2:'#be185d', txt:'#ffffff'},
        blue:   {bg1:'#3b82f6', bg2:'#1e40af', txt:'#ffffff'},
        orange: {bg1:'#f59e0b', bg2:'#d97706', txt:'#ffffff'},
        red:    {bg1:'#ef4444', bg2:'#b91c1c', txt:'#ffffff'},
        teal:   {bg1:'#14b8a6', bg2:'#0f766e', txt:'#ffffff'},
        gold:   {bg1:'#d4af37', bg2:'#b8860b', txt:'#ffffff'},
    };

    var p = presets[preset];
    if(!p) return;

    document.getElementById('bgStart').value     = p.bg1;
    document.getElementById('bgEnd').value       = p.bg2;
    document.getElementById('txtColor').value    = p.txt;
    document.getElementById('bgStartHex').value  = p.bg1;
    document.getElementById('bgEndHex').value    = p.bg2;
    document.getElementById('txtColorHex').value = p.txt;

    document.getElementById('chkPrimary').value  = p.bg1;
    document.getElementById('chkAccent').value   = p.bg2;
    document.getElementById('chkPrimaryHex').value = p.bg1;
    document.getElementById('chkAccentHex').value  = p.bg2;

    updatePreview();
    updateCheckoutPreview();
}
</script>

@endsection