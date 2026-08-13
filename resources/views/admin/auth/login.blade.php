<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin sign in — EaszyPay</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f6f4ef;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #0b1220;
        }
        .wrap { width: 100%; max-width: 400px; }
        .brand {
            text-align: center;
            font-family: "Iowan Old Style", Palatino, Georgia, serif;
            font-size: 26px; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 6px;
        }
        .brand span { color: #0f766e; }
        .subtitle { text-align: center; color: #5b6578; font-size: 14px; margin-bottom: 22px; }
        .card {
            background: #fff;
            border: 1px solid #e6e9f0;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 20px 50px rgba(15,23,42,0.06);
        }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 650; margin-bottom: 6px; }
        input {
            width: 100%; height: 46px; padding: 0 14px;
            border: 1.5px solid #e6e9f0; border-radius: 12px; font-size: 15px; outline: none; font-family: inherit;
        }
        input:focus { border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,0.12); }
        .btn {
            width: 100%; height: 48px; background: #0b1220; color: #fff; border: none;
            border-radius: 999px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 8px;
        }
        .btn:hover { background: #134e4a; }
        .error { background: #fdecea; border: 1px solid #f5c2c0; border-radius: 12px; padding: 10px 14px; color: #b42318; font-size: 13px; margin-bottom: 16px; }
        .remember { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #5b6578; margin: 12px 0; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">Easzy<span>Pay</span></div>
    <div class="subtitle">Admin</div>
    <div class="card">
        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@easzypay.com" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="remember">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember" style="margin:0;cursor:pointer;font-weight:400;">Remember me</label>
            </div>
            <button type="submit" class="btn">Sign in</button>
        </form>
    </div>
</div>
</body>
</html>
