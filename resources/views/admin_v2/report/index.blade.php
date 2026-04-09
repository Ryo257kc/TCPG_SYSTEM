<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 帳票一覧</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/report.css') }}">
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
