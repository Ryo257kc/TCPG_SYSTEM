<?php

$root = dirname(__DIR__);

$bladePath = $root . '/resources/views/staff_portal/dashboard/index.blade.php';
$cssPath = $root . '/public/css/staff_portal/dashboard-index.css';

$blade = <<<'BLADE'
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/dashboard-index.css') }}">
</head>
<body class="dashboard-page">
<main class="container">
    @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel dashboard-info-panel">
        <div class="dashboard-section-head">
            <span class="dashboard-section-badge">INFO</span>
            <h2 class="dashboard-section-title">Information</h2>
        </div>
        <p class="dashboard-info-text">お知らせはありません。</p>
        @if ($needsCorrection)
            <div class="warn">修正が必要な勤怠があります。</div>
        @endif
    </section>

    <section class="panel dashboard-office-panel">
        <div class="dashboard-section-head">
            <span class="dashboard-section-badge">STAFF</span>
            <h2 class="dashboard-section-title">MENU</h2>
        </div>

        <div class="office-menu-grid">
            <a class="office-menu-card" href="{{ route('dashboard') }}">
                <span class="office-menu-card-title">マイページ</span>
                <span class="office-menu-card-sub">システム情報の確認</span>
            </a>
            <a class="office-menu-card" href="{{ route('dashboard') }}">
                <span class="office-menu-card-title">各種申請</span>
                <span class="office-menu-card-sub">個人情報変更の申請</span>
            </a>
            <a class="office-menu-card" href="{{ route('dashboard') }}">
                <span class="office-menu-card-title">月末調整</span>
                <span class="office-menu-card-sub">月末調整対象者の申請</span>
            </a>
        </div>
    </section>

    @if (!empty($isAdmin))
        <section class="panel dashboard-office-panel">
            <div class="dashboard-section-head">
                <span class="dashboard-section-badge">ADMIN</span>
                <h2 class="dashboard-section-title">管理者MENU</h2>
            </div>

            <div class="office-menu-grid">
                <a class="office-menu-card" href="{{ route('attendance.management') }}">
                    <span class="office-menu-card-title">勤怠管理</span>
                    <span class="office-menu-card-sub">勤怠データの確認</span>
                </a>
                <a class="office-menu-card" href="{{ route('attendance.punch-list') }}">
                    <span class="office-menu-card-title">打刻一覧</span>
                    <span class="office-menu-card-sub">打刻スタッフを確認</span>
                </a>
                <a class="office-menu-card" href="{{ route('admin.paid-leave.index') }}">
                    <span class="office-menu-card-title">有休管理</span>
                    <span class="office-menu-card-sub">有休申請の確認</span>
                </a>
                <a class="office-menu-card" href="{{ route('admin.sales') }}">
                    <span class="office-menu-card-title">売上</span>
                    <span class="office-menu-card-sub">売上情報の確認</span>
                </a>
                <a class="office-menu-card" href="{{ route('admin.report.index') }}">
                    <span class="office-menu-card-title">書類</span>
                    <span class="office-menu-card-sub">必要な書類を開く</span>
                </a>
            </div>
        </section>
    @endif

    @if (!empty($isOfficeAdmin))
        <section class="panel dashboard-office-panel">
            <div class="dashboard-section-head">
                <span class="dashboard-section-badge">OFFICE</span>
                <h2 class="dashboard-section-title">事務所MENU</h2>
            </div>
            <div class="office-menu-grid">
                <a class="office-menu-card" href="{{ route('dashboard') }}">
                    <span class="office-menu-card-title">売上関連</span>
                    <span class="office-menu-card-sub">準備中</span>
                    <span class="office-menu-card-links">
                        <span class="office-menu-chip">売上</span>
                        <span class="office-menu-chip">未収入金</span>
                    </span>
                </a>
                <a class="office-menu-card" href="{{ route('dashboard') }}">
                    <span class="office-menu-card-title">レセ管理</span>
                    <span class="office-menu-card-sub">準備中</span>
                </a>
                <a class="office-menu-card" href="{{ route('dashboard') }}">
                    <span class="office-menu-card-title">日報</span>
                    <span class="office-menu-card-sub">準備中</span>
                </a>
                <a class="office-menu-card" href="{{ route('dashboard') }}">
                    <span class="office-menu-card-title">売上集計</span>
                    <span class="office-menu-card-sub">準備中</span>
                </a>
                <a class="office-menu-card" href="{{ route('dashboard') }}">
                    <span class="office-menu-card-title">事務所確認</span>
                    <span class="office-menu-card-sub">準備中</span>
                </a>
                <a class="office-menu-card" href="{{ route('attendance.punch-list') }}">
                    <span class="office-menu-card-title">打刻一覧</span>
                    <span class="office-menu-card-sub">打刻スタッフを確認</span>
                </a>
            </div>
        </section>
    @endif
</main>
</body>
</html>
BLADE;

$css = file_get_contents($cssPath);

if ($css === false) {
    fwrite(STDERR, "failed to read css\n");
    exit(1);
}

if (strpos($css, '.office-menu-card-links') === false) {
    $css .= "\n.office-menu-card-links {\n"
        . "    display: flex;\n"
        . "    flex-wrap: wrap;\n"
        . "    gap: 6px;\n"
        . "    margin-top: auto;\n"
        . "}\n\n"
        . ".office-menu-chip {\n"
        . "    display: inline-flex;\n"
        . "    align-items: center;\n"
        . "    padding: 4px 8px;\n"
        . "    border: 1px solid rgba(241, 164, 102, 0.35);\n"
        . "    border-radius: 999px;\n"
        . "    background: rgba(255, 247, 238, 0.9);\n"
        . "    color: var(--card-title);\n"
        . "    font-size: 11px;\n"
        . "    font-weight: 700;\n"
        . "    line-height: 1;\n"
        . "}\n";
}

file_put_contents($bladePath, $blade);
file_put_contents($cssPath, $css);
