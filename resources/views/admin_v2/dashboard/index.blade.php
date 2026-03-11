<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM ダッシュボード</title>
    <style>
        :root {
            --bg: #eef3fb;
            --panel: #ffffff;
            --line: #d6e1f0;
            --text: #1f2937;
            --muted: #667085;
            --primary: #1f4f8f;
            --active: #e8f0fd;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
            color: var(--text);
            background: radial-gradient(900px 300px at 10% -10%, #dce7fa 0%, transparent 65%), var(--bg);
        }
        .layout { display: grid; grid-template-columns: 300px 1fr; min-height: 100vh; }
        .side {
            border-right: 1px solid var(--line);
            background: #f8fbff;
            padding: 16px 14px 20px;
            overflow-y: auto;
        }
        .brand {
            margin: 0 0 10px;
            color: var(--primary);
            font-weight: 800;
            font-size: 20px;
        }
        .note {
            margin: 0 0 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px;
            background: #fff;
            color: var(--muted);
            font-size: 12px;
        }
        .group { margin-bottom: 14px; }
        .group-title {
            margin: 0 0 6px;
            color: var(--muted);
            font-size: 12px;
            letter-spacing: .06em;
            font-weight: 700;
        }
        .item {
            display: block;
            text-decoration: none;
            color: var(--text);
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 9px 10px;
            margin-bottom: 6px;
            font-size: 14px;
        }
        .item:hover { border-color: var(--line); background: #fff; }
        .item.active {
            background: var(--active);
            border-color: #b7caec;
            color: var(--primary);
            font-weight: 700;
        }
        .content { padding: 22px; }
        .toolbar { display: flex; justify-content: flex-end; margin-bottom: 12px; }
        .logout-btn {
            border: 0;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            padding: 10px 14px;
            font-size: 13px;
            cursor: pointer;
        }
        .card {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--panel);
            padding: 20px;
            box-shadow: 0 10px 26px rgba(21, 66, 122, 0.08);
        }
        h1 { margin: 0 0 8px; font-size: 24px; }
        p { margin: 0; color: var(--muted); }
        .meta {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
            font-size: 12px;
            color: #475467;
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="side">
            <p class="brand">TCPG SYSTEM</p>
            <p class="note">この画面は新規admin_v2です。右上からログアウトできます。</p>

            @foreach ($menuGroups as $groupName => $items)
                <section class="group">
                    <p class="group-title">{{ $groupName }}</p>
                    @foreach ($items as $item)
                        <a class="item {{ $selectedPage === $item['key'] ? 'active' : '' }}" href="{{ url($item['url']) }}{{ $item['url'] === '/admin' ? '?page=' . $item['key'] : '' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </section>
            @endforeach
        </aside>

        <main class="content">
            <div class="toolbar">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">ログアウト</button>
                </form>
            </div>

            <section class="card">
                <h1>{{ $pageData['title'] }}</h1>
                <p>{{ $pageData['description'] }}</p>
                <p class="meta">ログイン中: {{ session('admin_staff_id') }} {{ session('admin_staff_name') }}</p>
            </section>
        </main>
    </div>
</body>
</html>