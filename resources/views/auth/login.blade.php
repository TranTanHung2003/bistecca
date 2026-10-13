<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập nhân viên</title>
    <style>
        :root {
            --bg: #edf3ee;
            --card: #fffdf8;
            --primary: #246044;
            --primary-dark: #174a34;
            --border: #d5ddd5;
            --text: #1f2a24;
            --muted: #6e7c72;
            --error: #a63c2d;
            --error-bg: #fbe8e5;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(135deg, #edf3ee 0%, #f6f1e8 100%);
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
        }

        .login-box {
            width: min(100%, 430px);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(30, 52, 42, 0.09);
            padding: 32px 28px;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #e8f2eb;
            color: var(--primary);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        h1 {
            margin: 18px 0 8px;
            font-size: 2rem;
            color: #1c4738;
        }

        .subtitle {
            margin: 0 0 24px;
            color: var(--muted);
            line-height: 1.5;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #31473d;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #bcc8c0;
            border-radius: 10px;
            background: #fff;
            font-size: 1rem;
            margin-bottom: 18px;
        }

        input:focus {
            outline: 3px solid rgba(36, 96, 68, 0.18);
            border-color: var(--primary);
        }

        button {
            width: 100%;
            border: none;
            border-radius: 10px;
            background: var(--primary);
            color: white;
            font-size: 1rem;
            font-weight: 700;
            padding: 13px 16px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        button:hover {
            background: var(--primary-dark);
        }

        .demo {
            margin-top: 18px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #f5f3ef;
            border: 1px solid #e6ddd0;
            color: #5f5a52;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .demo strong {
            color: #2b3d35;
        }

        .error-message {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid #edc0b9;
            border-radius: 10px;
            background: var(--error-bg);
            color: var(--error);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <span class="badge">Staff Portal</span>
        <h1>Đăng nhập</h1>
        <p class="subtitle">Nhân viên cần đăng nhập để xem và check-in buffet cho khách.</p>

        @if ($errors->any())
            <div class="error-message">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="staff@example.com" required autofocus>
            </div>

            <div>
                <label for="password">Mật khẩu</label>
                <input id="password" type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit">Đăng nhập</button>
        </form>

    </div>
</body>
</html>
