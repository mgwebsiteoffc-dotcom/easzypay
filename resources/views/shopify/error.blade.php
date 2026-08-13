<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Error — EaszyPay</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:linear-gradient(135deg,#0f0c29,#302b63);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .card{background:white;border-radius:20px;padding:48px 40px;max-width:480px;width:100%;text-align:center;box-shadow:0 24px 64px rgba(0,0,0,0.3)}
        .icon{font-size:64px;margin-bottom:20px}
        h1{font-size:24px;font-weight:800;color:#111827;margin-bottom:12px}
        p{color:#6b7280;font-size:15px;line-height:1.7;margin-bottom:12px}
        .error-box{background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:16px;color:#dc2626;font-size:14px;margin:20px 0;text-align:left;line-height:1.6}
        .btn{display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;border-radius:10px;font-weight:700;font-size:15px;text-decoration:none;transition:all 0.2s;margin-top:12px}
        .btn:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(102,126,234,0.35)}
        .btn-outline{background:transparent;border:2px solid #667eea;color:#667eea}
        .btn-outline:hover{background:#667eea;color:white}
    </style>
</head>
<body>
<div class="card">
    <div class="icon">⚠️</div>
    <h1>Installation Error</h1>
    <p>Something went wrong during the Shopify app installation.</p>

    @if(isset($message))
    <div class="error-box">
        {{ $message }}
    </div>
    @endif

    <p style="color:#9ca3af;font-size:13px;">
        If this error persists, please contact support.
    </p>

    <div style="display:flex;gap:12px;justify-content:center;margin-top:20px;">
        <a href="{{ route('shopify.install') }}" class="btn">Try Again</a>
        <a href="{{ route('home') }}" class="btn btn-outline">Home</a>
    </div>
</div>
</body>
</html>