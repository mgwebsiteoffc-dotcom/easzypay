<?php
$errors_list = [];
if (isset($errors) && $errors->any()) {
    $errors_list = $errors->all();
}
$old_email = old('email', '');
$login_url = route('tenant.login.post');
$register_url = route('tenant.register');
$home_url = route('home');
$csrf = csrf_token();
$message = session('message', '');
$error_msg = session('error', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - EaszyPay</title>
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
            max-width: 400px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.3);
        }
        .logo { text-align: center; font-size: 28px; font-weight: 800; margin-bottom: 6px; }
        .logo-text { background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .sub { text-align: center; color: #6b7280; font-size: 14px; margin-bottom: 28px; }
        .msg-box { background: #ede9fe; border: 1px solid #c4b5fd; border-radius: 8px; padding: 12px; color: #6d28d9; font-size: 13px; margin-bottom: 16px; text-align: center; }
        .err-box { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px; color: #dc2626; font-size: 13px; margin-bottom: 16px; }
        .fg { margin-bottom: 16px; }
        .fg label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .fg input { width: 100%; height: 46px; padding: 0 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 15px; outline: none; font-family: inherit; }
        .fg input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.12); }
        .remember { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #4b5563; margin: 12px 0; }
        .remember input { width: auto; height: auto; }
        .remember label { margin: 0; cursor: pointer; font-size: 14px; font-weight: 400; }
        .btn-submit { width: 100%; height: 50px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.2s; margin-top: 8px; font-family: inherit; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(102,126,234,0.4); }
        .links { text-align: center; margin-top: 20px; font-size: 14px; color: #6b7280; }
        .links a { color: #667eea; font-weight: 600; text-decoration: none; margin: 0 8px; }
    </style>
</head>
<body>
<div class="card">

    <div class="logo"><span class="logo-text">⚡ EaszyPay</span></div>
    <div class="sub">Merchant Dashboard</div>

    <?php if ($message): ?>
    <div class="msg-box"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
    <div class="err-box"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors_list)): ?>
    <div class="err-box">
        <?php foreach ($errors_list as $err): ?>
            <div><?php echo htmlspecialchars($err); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($login_url); ?>">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf); ?>">

        <div class="fg">
            <label>Email Address</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($old_email); ?>" placeholder="you@example.com" required autofocus>
        </div>

        <div class="fg">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <div class="remember">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember">Remember me for 30 days</label>
        </div>

        <button type="submit" class="btn-submit">Sign In →</button>
    </form>

    <div class="links">
        <a href="<?php echo htmlspecialchars($register_url); ?>">Create account</a>
        ·
        <a href="<?php echo htmlspecialchars($home_url); ?>">← Home</a>
    </div>
</div>
</body>
</html>