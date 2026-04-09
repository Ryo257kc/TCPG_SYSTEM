<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/landing.css') }}">
</head>
<body class="landing-page">
    <main class="panel">
        <h1>TCPG SYSTEM</h1>
        <!-- <p>Please sign in with your staff account.</p> -->

        @if (!empty($errorMessage))
            <div class="error-box">{{ $errorMessage }}</div>
        @endif

        @if ($isMaintenance)
            <div class="maintenance">
                現在メンテナンス中です。
            </div>
        @else
            <form class="login-form" method="post" action="{{ route('login.portal.submit') }}" autocomplete="off">
                @csrf
                <div class="field">
                    <label for="staff_id">ID</label>
                    <input
                        id="staff_id"
                        name="staff_id"
                        type="text"
                        value="{{ old('staff_id') }}"
                        autocomplete="username"
                        required
                    >
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" type="submit">Login</button>
                    <a class="btn btn-secondary" href="{{ route('login.portal') }}">Clear</a>
                </div>
            </form>
        @endif
    </main>
</body>
</html>
