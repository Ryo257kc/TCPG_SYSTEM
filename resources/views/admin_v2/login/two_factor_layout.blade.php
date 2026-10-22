<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>TCPG SYSTEM - 二段階認証</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 32px 16px; background: #f3f7fd; color: #1f2937; font-family: sans-serif; }
        main { max-width: 480px; margin: 0 auto; padding: 28px; background: #fff; border: 1px solid #d7e2f1; border-radius: 14px; }
        h1 { margin-top: 0; font-size: 22px; }
        p { line-height: 1.7; }
        label { display: block; margin: 18px 0 6px; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #a9b9cf; border-radius: 8px; font-size: 20px; }
        input:focus-visible, button:focus-visible, summary:focus-visible { outline: 3px solid #72a6ec; outline-offset: 2px; }
        button { width: 100%; margin-top: 18px; padding: 12px; border: 0; border-radius: 8px; background: #1f4f8f; color: #fff; font-size: 16px; cursor: pointer; }
        .secondary { background: #eef2f7; color: #1f4f8f; }
        .error { padding: 12px; background: #fff2f2; color: #a62424; border: 1px solid #f0bcbc; border-radius: 8px; }
        .qr { text-align: center; }
        .qr svg { max-width: 100%; height: auto; }
        .secret { display: block; padding: 12px; background: #f3f7fd; overflow-wrap: anywhere; user-select: all; }
        details { margin-top: 22px; }
        summary { cursor: pointer; }
        .codes { padding: 14px; background: #f3f7fd; list-style: none; font-family: monospace; font-size: 16px; line-height: 1.9; }
        .saved { display: flex; gap: 8px; align-items: flex-start; line-height: 1.6; }
        @media print { body { background: #fff; padding: 0; } main { border: 0; } form { display: none; } }
    </style>
</head>
<body>
    <main>
        @if ($errors->any())
            <p class="error" role="alert">{{ $errors->first() }}</p>
        @endif
        @yield('content')
    </main>
</body>
</html>
