<?php
/**
 * Register Page - Using raw PHP to avoid any Blade compilation issues
 */
$errors_list = [];
if (isset($errors) && $errors->any()) {
    $errors_list = $errors->all();
}
$old_name = old('name', '');
$old_email = old('email', '');
$old_company = old('company_name', '');
$register_url = route('tenant.register.post');
$login_url = route('tenant.login');
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - EaszyPay</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .logo-text {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .sub {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 24px;
        }
        .trial {
            background: #d1fae5;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 10px;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            border: 1px solid #a7f3d0;
        }
        .err-box {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 12px 16px;
            color: #dc2626;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .fg { margin-bottom: 16px; }
        .fg label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .fg label .req { color: #ef4444; }
        .fg input {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
            color: #111827;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            font-family: inherit;
            background: white;
        }
        .fg input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.12);
        }
        .fg input::placeholder { color: #9ca3af; }
        .btn-submit {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
            font-family: inherit;
            letter-spacing: 0.3px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102,126,234,0.4);
        }
        .btn-submit:active { transform: translateY(0); }
        .bottom-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6b7280;
        }
        .bottom-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }
        .bottom-link a:hover { text-decoration: underline; }
        .badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 20px;
        }
        .badge-sm {
            font-size: 11px;
            color: #6b7280;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 4px 10px;
        }
    </style>
</head>
<body>
<div class="card">

    <div class="logo"><span class="logo-text">⚡ EaszyPay</span></div>
    <div class="sub">Accept Stripe payments on Shopify Basic</div>

    <div class="trial">🎉 14-Day Free Trial — No Credit Card Required</div>

    <?php if (!empty($errors_list)): ?>
    <div class="err-box">
        <?php foreach ($errors_list as $err): ?>
            <div><?php echo htmlspecialchars($err); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($register_url); ?>">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf); ?>">

        <div class="fg">
            <label>Full Name <span class="req">*</span></label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($old_name); ?>" placeholder="John Doe" required autofocus>
        </div>

        <div class="fg">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($old_email); ?>" placeholder="you@yourstore.com" required>
        </div>

        <div class="fg">
            <label>Company / Store Name</label>
            <input type="text" name="company_name" value="<?php echo htmlspecialchars($old_company); ?>" placeholder="My Shopify Store">
        </div>

        <div class="fg">
            <label>Password <span class="req">*</span></label>
            <input type="password" name="password" placeholder="Min 8 characters" required minlength="8">
        </div>

        <div class="fg">
            <label>Confirm Password <span class="req">*</span></label>
            <input type="password" name="password_confirmation" placeholder="Repeat password" required>
        </div>

        <button type="submit" class="btn-submit">Create Free Account →</button>
    </form>

    <div class="bottom-link">
        Already have an account? <a href="<?php echo htmlspecialchars($login_url); ?>">Sign in</a>
    </div>

    <div class="badges">
        <span class="badge-sm">🔒 SSL Secure</span>
        <span class="badge-sm">💳 Stripe Powered</span>
        <span class="badge-sm">🍎 Apple Pay</span>
        <span class="badge-sm">🤖 Google Pay</span>
        <span class="badge-sm">🌍 Multi-Currency</span>
    </div>
</div>
</body>
</html>