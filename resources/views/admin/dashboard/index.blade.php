<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 管理ダッシュボード</title>
    <style>
        :root {
            --bg: #ecf2fb;
            --panel: #ffffff;
            --line: #d3dff0;
            --primary: #1f4f8f;
            --primary-soft: #e7effc;
            --text: #1f2937;
            --muted: #667085;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
            color: var(--text);
            background: radial-gradient(900px 300px at 10% -10%, #dce8fa 0%, transparent 65%), var(--bg);
        }
        .layout { display: grid; grid-template-columns: 290px 1fr; min-height: 100vh; }
        .sidebar {
            border-right: 1px solid var(--line);
            background: #f7fafe;
            padding: 18px 14px 20px;
            overflow-y: auto;
        }
        .brand {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.03em;
            margin: 0 0 12px;
            color: var(--primary);
        }
        .profile {
            margin: 0 0 14px;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            font-size: 13px;
            color: var(--muted);
        }
        .menu-group { margin: 0 0 14px; }
        .menu-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: 0.06em;
            margin: 0 0 6px;
        }
        .menu-item {
            display: block;
            width: 100%;
            text-decoration: none;
            color: var(--text);
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 9px 10px;
            font-size: 14px;
            margin: 0 0 6px;
            background: transparent;
        }
        .menu-item:hover { border-color: var(--line); background: #fff; }
        .menu-item.active {
            border-color: #b7ccef;
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 700;
        }
        .content { padding: 24px; }
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
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 12px 28px rgba(20, 61, 113, 0.09);
        }
        h1 { margin: 0 0 8px; font-size: 26px; }
        .desc { margin: 0; color: var(--muted); font-size: 14px; }
        .note {
            margin-top: 18px;
            border-top: 1px solid var(--line);
            padding-top: 14px;
            font-size: 13px;
            color: var(--muted);
        }
        @media (max-width: 980px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { border-right: 0; border-bottom: 1px solid var(--line); }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <p class="brand">TCPG SYSTEM</p>
            <div class="profile">
                ログイン中: {{ session('admin_staff_id') }} {{ session('admin_staff_name') }}
            </div>

            @foreach ($menuGroups as $groupName => $items)
                <section class="menu-group">
                    <p class="menu-title">{{ $groupName }}</p>
                    @foreach ($items as $item)
                        <a
                            class="menu-item {{ $selectedPage === $item['key'] ? 'active' : '' }}"
                            href="{{ isset($item['route_name']) ? route($item['route_name']) : ($item['key'] === 'salary-detail' ? route('admin.payroll.index') : route('admin.dashboard', ['page' => $item['key']])) }}"
                        >
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
                <p class="desc">{{ $pageData['description'] }}</p>
                <p class="note">
                    マスタ系は左メニューから直接遷移できます。
                </p>
            </section>
        </main>
    </div>
</body>
</html>
