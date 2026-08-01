<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - パスワード変更</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/landing.css') }}">
</head>

<body class="landing-page">
    <main class="panel">
        <h1>パスワード変更</h1>
        <p>初期パスワードのため、新しいパスワードを設定してください。</p>

        @if (!empty($staffName))
        <p>氏名：{{ $staffName }}</p>
        @elseif (!empty($staffId))
        <p>{{ $staffId }}</p>
        @endif

        @if (!empty($errorMessage))
        <div class="error-box">{{ $errorMessage }}</div>
        @endif

        <form class="login-form" method="post" action="{{ route('staff_portal.password_change.update') }}" autocomplete="off">
            @csrf
            <div class="field">
                <label for="password">新しいパスワード</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
            </div>
            <div class="field">
                <label for="password_confirm">確認用</label>
                <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">変更</button>
            </div>
        </form>
    </main>
</body>

</html>
