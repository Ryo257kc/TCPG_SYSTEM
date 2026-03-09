<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 管理者ログイン</title>
    <style>
        :root {
            --bg: #f3f6fb;
            --card: #ffffff;
            --primary: #1f4f8f;
            --text: #1f2937;
            --muted: #6b7280;
            --danger: #cc3344;
            --border: #d6deea;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
            color: var(--text);
            background:
                radial-gradient(1200px 420px at 10% -10%, #dce9fb 0%, transparent 62%),
                radial-gradient(1000px 350px at 100% 120%, #dff4e9 0%, transparent 55%),
                var(--bg);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .login-card {
            width: min(440px, 100%);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 18px 42px rgba(31, 79, 143, 0.12);
            padding: 28px 24px;
        }

        .eyebrow {
            margin: 0 0 10px;
            font-size: 13px;
            color: var(--muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 25px;
            line-height: 1.3;
        }

        .field { margin-bottom: 14px; }

        label {
            display: block;
            font-size: 13px;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 15px;
            padding: 11px 12px;
            outline: none;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(31, 79, 143, 0.14);
        }

        .error {
            margin: 0 0 14px;
            padding: 10px 12px;
            border-radius: 10px;
            background: #ffecef;
            border: 1px solid #f6b9c1;
            color: var(--danger);
            font-size: 13px;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(120deg, var(--primary), #286fbe);
            color: #fff;
            padding: 12px 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <main class="login-card">
        <p class="eyebrow">Admin Portal</p>
        <h1>TCPG SYSTEM<br>管理者ページ</h1>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="field">
                <label for="staff_id">ログインID</label>
                <input id="staff_id" type="text" name="staff_id" value="{{ old('staff_id') }}" required autocomplete="username">
            </div>

            <div class="field">
                <label for="password">パスワード</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit">ログイン</button>
        </form>
    </main>
</body>
</html>