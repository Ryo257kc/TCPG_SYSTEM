<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 帳票一覧</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('css/admin_v2/report.css') }}"> -->
    <style>
        :root {
            --bg: #f5f8fd;
            --panel: #ffffff;
            --line: #d7e0eb;
            --text: #253043;
            --muted: #667085;
            --primary: #1f4f8f;
            --soft: #eef4ff;
        }

        * {
            box-sizing: border-box;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            text-decoration: none;
            padding: 9px 14px;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .btn.is-disabled {
            pointer-events: none;
            opacity: .5;
            background: #f8fafc;
        }

        .report-grid {
            display: grid;
            gap: 16px;
        }

        .category-panel {
            padding: 18px;
        }

        .category-head {
            margin-bottom: 14px;
        }

        .category-head h2 {
            margin: 0;
            color: var(--primary);
            font-size: 18px;
        }

        .category-head p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .report-cards {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .report-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
            background: #fff;
        }

        .report-card-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .report-card h3 {
            margin: 0;
            font-size: 16px;
        }

        .report-desc {
            margin: 10px 0 14px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .report-actions {
            display: flex;
            justify-content: flex-start;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-ready {
            background: #e9f4ea;
            color: #207245;
        }

        .badge-soon {
            background: var(--soft);
            color: var(--primary);
        }

        @media (max-width: 900px) {
            .report-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')
    <div class="wrap">
        <div class="top">
            <div class="title">TCPG SYSTEM 帳票一覧</div>
        </div>

        <section class="report-main-panel">
            <div class="report-grid">
                @foreach ($categories as $category)
                <section class="panel category-panel">
                    <div class="category-head">
                        <h2>{{ $category['title'] }}</h2>
                        <p>{{ $category['description'] }}</p>
                    </div>

                    <div class="report-cards">
                        @foreach ($category['items'] as $item)
                        @php
                        $isAvailable = (($item['status'] ?? '') === 'available');
                        $itemUrl = $item['url'] ?? null;
                        $itemAction = $item['action'] ?? '開く';
                        @endphp
                        <article class="report-card {{ $isAvailable ? 'is-available' : 'is-planned' }}">
                            <div class="report-card-top">
                                <h3>{{ $item['title'] }}</h3>
                                <span class="badge {{ $isAvailable ? 'badge-ready' : 'badge-soon' }}">
                                    {{ $isAvailable ? '利用可' : '準備中' }}
                                </span>
                            </div>
                            <p class="report-desc">{{ $item['description'] }}</p>

                            <div class="report-actions">
                                @if ($isAvailable && $itemUrl)
                                <a class="btn btn-primary" href="{{ $itemUrl }}">{{ $itemAction }}</a>
                                @else
                                <span class="btn is-disabled">これから追加</span>
                                @endif
                            </div>
                        </article>
                        @endforeach
                    </div>
                </section>
                @endforeach
            </div>
        </section>
    </div>
</body>

</html>
