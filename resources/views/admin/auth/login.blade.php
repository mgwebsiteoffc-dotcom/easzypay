<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — QuickPay</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        .subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 32px;
        }
        .form-group { margin-bottom: 16px; }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        input {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.15s;
            font-family: inherit;
        }
        input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.12); }
        .btn {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.2s;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(102,126,234,0.35); }
        .error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 10px 14px;
            color: #dc2626;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #4b5563;
            margin: 12px 0;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">⚡ QuickPay</div>
    <div class="subtitle">Admin Dashboard Login</div>

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
            <label>Email Address</label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="admin@example.com"
                required
                autofocus
            >
        </div>

        <div class="form-group">
            <label>Password</label>
            <input
                type="password"
                name="password"
                placeholder="••••••••"
                required
            >
        </div>

        <div class="remember">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember" style="margin:0;cursor:pointer;">Remember me</label>
        </div>

        <button type="submit" class="btn">Sign In to Dashboard</button>
    </form>
</div>
</body>
</html>