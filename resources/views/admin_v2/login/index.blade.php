<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM ログイン</title>
    <style>
        :root {
            --bg: #f3f7fd;
            --card: #ffffff;
            --line: #d7e2f1;
            --primary: #1f4f8f;
            --text: #1f2937;
            --danger: #c53030;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
            color: var(--text);
            background:
                radial-gradient(900px 300px at 0% -10%, #dde9fb 0%, transparent 62%),
                radial-gradient(900px 300px at 100% 110%, #e7f6ec 0%, transparent 58%),
                var(--bg);
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .card {
            width: min(420px, 100%);
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 14px 34px rgba(21, 66, 122, 0.12);
            padding: 24px;
        }
        h1 { margin: 0 0 14px; font-size: 24px; line-height: 1.3; }
        .field { margin-bottom: 12px; }
        label { display: block; font-size: 13px; margin-bottom: 6px; }
        input { width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 10px 12px; font-size: 15px; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(31, 79, 143, 0.15); }
        .error { margin: 0 0 12px; border: 1px solid #f6c7c7; background: #fff2f2; color: var(--danger); border-radius: 10px; padding: 10px 12px; font-size: 13px; }
        button { width: 100%; border: 0; border-radius: 10px; padding: 11px 14px; background: linear-gradient(120deg, var(--primary), #2b6dbf); color: #fff; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <main class="card">
        <h1>TCPG SYSTEM<br>管理者ログイン</h1>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="field">
                <label for="staff_id">ID</label>
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