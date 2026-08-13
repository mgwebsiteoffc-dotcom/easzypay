<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invalid Store — EaszyPay</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f6f4ef;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .card{background:white;border:1px solid #e6e9f0;border-radius:20px;padding:48px 40px;max-width:480px;width:100%;text-align:center;box-shadow:0 20px 50px rgba(15,23,42,0.06)}
        .icon{font-size:64px;margin-bottom:20px}
        h1{font-size:24px;font-weight:800;color:#111827;margin-bottom:12px}
        p{color:#6b7280;font-size:15px;line-height:1.7;margin-bottom:28px}
        .form-group{margin-bottom:16px;text-align:left}
        label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        input{width:100%;height:48px;padding:0 16px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:15px;outline:none;font-family:inherit}
        input:focus{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.12)}
        .btn{width:100%;height:50px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;transition:all 0.2s}
        .btn:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(102,126,234,0.35)}
        .hint{font-size:12px;color:#9ca3af;margin-top:8px}
        a{color:#0f766e;text-decoration:none;font-weight:600}
    </style>
</head>
<body>
<div class="card">
    <div class="icon">🏪</div>
    <h1>Connect Your Shopify Store</h1>
    <p>Enter your Shopify store URL to install EaszyPay and start accepting Stripe payments.</p>

    <form method="GET" action="{{ route('shopify.install') }}">
        <div class="form-group">
            <label>Your Shopify Store URL</label>
            <input
                type="text"
                name="shop"
                placeholder="yourstore.myshopify.com"
                required
                autofocus
            >
            <div class="hint">Enter your .myshopify.com domain</div>
        </div>
        <button type="submit" class="btn">Install EaszyPay →</button>
    </form>

    <p style="margin-top:24px;font-size:13px;">
        <a href="{{ route('home') }}">← Back to Home</a>
    </p>
</div>
</body>
</html>