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
    <title>Sign in — EaszyPay</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f6f4ef;
            color: #0b1220;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .wrap { width: 100%; max-width: 420px; }
        .brand {
            text-align: center;
            font-family: "Iowan Old Style", Palatino, Georgia, serif;
            font-size: 28px; font-weight: 800; letter-spacing: -0.03em;
            margin-bottom: 8px;
        }
        .brand span { color: #0f766e; }
        .sub { text-align: center; color: #5b6578; font-size: 14px; margin-bottom: 24px; line-height: 1.5; }
        .card {
            background: #fff;
            border: 1px solid #e6e9f0;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 20px 50px rgba(15,23,42,0.06);
        }
        .msg-box { background: #e7f6f3; border: 1px solid #b7e0d8; border-radius: 12px; padding: 12px; color: #134e4a; font-size: 13px; margin-bottom: 16px; text-align: center; }
        .err-box { background: #fdecea; border: 1px solid #f5c2c0; border-radius: 12px; padding: 12px; color: #b42318; font-size: 13px; margin-bottom: 16px; }
        .fg { margin-bottom: 16px; }
        .fg label { display: block; font-size: 13px; font-weight: 650; color: #0b1220; margin-bottom: 6px; }
        .fg input { width: 100%; height: 46px; padding: 0 14px; border: 1.5px solid #e6e9f0; border-radius: 12px; font-size: 15px; outline: none; font-family: inherit; background: #fff; }
        .fg input:focus { border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,0.12); }
        .remember { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #5b6578; margin: 12px 0; }
        .remember input { width: auto; height: auto; }
        .remember label { margin: 0; cursor: pointer; font-size: 14px; font-weight: 400; }
        .btn-submit { width: 100%; height: 48px; background: #0b1220; color: #fff; border: none; border-radius: 999px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 8px; font-family: inherit; }
        .btn-submit:hover { background: #134e4a; }
        .links { text-align: center; margin-top: 20px; font-size: 14px; color: #5b6578; }
        .links a { color: #0f766e; font-weight: 650; text-decoration: none; margin: 0 8px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand"><a href="<?php echo htmlspecialchars($home_url); ?>" style="color:inherit;text-decoration:none;">Easzy<span>Pay</span></a></div>
    <div class="sub">Merchant sign in — charge checkout in the currency they browsed.</div>
    <div class="card">
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
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($old_email); ?>" placeholder="you@store.com" required autofocus>
            </div>
            <div class="fg">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="remember">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Remember me for 30 days</label>
            </div>
            <button type="submit" class="btn-submit">Sign in</button>
        </form>
        <div class="links">
            <a href="<?php echo htmlspecialchars($register_url); ?>">Create account</a>
            ·
            <a href="<?php echo htmlspecialchars($home_url); ?>">Home</a>
        </div>
    </div>
</div>
</body>
</html>
