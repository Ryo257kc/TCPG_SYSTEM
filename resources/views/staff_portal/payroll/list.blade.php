<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - {{ $pageTitle }}</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/payslip_item.css') }}">
    <style>
        :root {
            --bg-start: #fffaf3;
            --bg-end: #ffe9d2;
            --card: rgba(255, 255, 255, 0.74);
            --line: rgba(241, 164, 102, 0.35);
            --text: #6b3f1f;
            --sub: #8f5d36;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "Hiragino Kaku Gothic ProN", "Noto Sans JP", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 16% 14%, rgba(255, 203, 138, 0.24), transparent 40%),
                radial-gradient(circle at 84% 86%, rgba(255, 173, 120, 0.18), transparent 34%),
                linear-gradient(140deg, var(--bg-start), var(--bg-end));
            padding: clamp(8px, 2vw, 20px);
        }
        .container { width: min(100%, 1360px); margin: 0 auto; display: grid; gap: 14px; }
        .panel {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--card);
            backdrop-filter: blur(8px);
            box-shadow: 0 14px 36px rgba(157, 101, 48, 0.15);
            padding: clamp(12px, 1.6vw, 20px);
        }
        .header-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 2px;
        }
        h1 { margin: 0; font-size: clamp(20px, 3.2vw, 28px); white-space: nowrap; }
        .welcome-name { margin: 0; color: var(--sub); font-weight: 700; font-size: 14px; white-space: nowrap; }
        .quick-menu {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
            flex: 1 1 auto;
            min-width: max-content;
        }
        .quick-link {
            text-decoration: none;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 16px;
            color: #6b3f1f;
            background: linear-gradient(120deg, #ffd6ad, #ffc48f);
            font-weight: 700;
            font-size: 17px;
            line-height: 1.1;
            white-space: nowrap;
        }
        .quick-link.logout {
            color: #fff;
            background: linear-gradient(120deg, #e8795f, #d94848);
            border-color: #cf6a4f;
            margin-left: auto;
        }
        .filter-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 12px;
        }
        label { color: var(--sub); font-weight: 700; }
        select {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px 10px;
            background: rgba(255, 255, 255, 0.9);
            color: #5f320f;
        }
        button {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px 12px;
            background: linear-gradient(120deg, #ffd6ad, #ffc48f);
            color: #6f4322;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
        }
        .title { text-align: center; margin: 10px 0 6px; }
        .title h2 { margin: 0; font-size: 24px; }
        .subtitle {
            margin: 0 auto 10px;
            width: min(100%, 1020px);
            border: 1px solid #d8c4b0;
            background: rgba(255,255,255,0.82);
            border-radius: 10px;
            padding: 8px 12px;
            display: flex;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            font-weight: 700;
        }
        .table-wrap { overflow-x: auto; width: 100%; }
        table {
            width: min(100%, 1020px);
            margin: 0 auto;
            border-collapse: collapse;
            background: rgba(255,255,255,0.92);
            font-size: 12px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #cdb8a3;
            padding: 6px 8px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        td:empty::before, th:empty::before { content: "\00a0"; }
        th { background: #fff4e8; }
        .sum-row td { font-weight: 700; background: #fff4e8; }
        .memo {
            width: min(100%, 1020px);
            margin: 8px auto 0;
            border: 1px solid #cdb8a3;
            background: rgba(255,255,255,0.9);
            border-radius: 8px;
            padding: 10px 12px;
        }
        .transfer {
            width: min(100%, 1020px);
            margin: 12px auto 0;
            text-align: right;
            font-size: 24px;
            font-weight: 800;
            color: #6b3f1f;
        }
        .empty { text-align: center; color: var(--sub); padding: 24px 0; }
        .admin-menu-title { margin: 0 0 8px; font-size: 22px; text-align: center; }
        .admin-menu-links {
            margin-top: 10px;
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
            justify-content: center;
            overflow-x: auto;
            padding-bottom: 2px;
        }
        .company-line { margin: 10px 0 0; text-align: center; color: #8f5d36; font-weight: 700; }
        @media (max-width: 1200px) {
            .quick-link { font-size: 15px; padding: 9px 14px; }
        }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: #fff; padding: 0; color: #000; }
            * { color: #000 !important; background: transparent !important; box-shadow: none !important; text-shadow: none !important; }
            .container { width: 100%; max-width: none; margin: 0; display: block; gap: 0; }
            .panel { border: 0; border-radius: 0; background: #fff; box-shadow: none; backdrop-filter: none; padding: 0; }
            .panel:first-child, .filter-row, .admin-panel { display: none !important; }
            .title h2 { font-size: 20px; }
            .subtitle, table, .memo, .transfer { width: 100%; max-width: none; }
            table { font-size: 10px; }
            th, td { padding: 3px 4px; border-color: #000 !important; }
            .subtitle, .memo { font-size: 11px; border-color: #000 !important; }
            .sum-row td { background: transparent !important; }
            .transfer { font-size: 20px; }
        }
    </style>
</head>
<body>
<main class="container">
    <section class="panel">
        <div class="header-row">
            <h1>TCPG SYSTEM</h1>
            <p class="welcome-name">ようこそ、{{ $displayName }} さん</p>
            <nav class="quick-menu" aria-label="header-menu">
                <a class="quick-link" href="{{ route('dashboard') }}">TOP</a>
                <a class="quick-link" href="{{ route('attendance.monthly') }}">月間勤怠</a>
                <a class="quick-link" href="{{ route('payslip') }}">給与明細</a>
                <a class="quick-link" href="{{ route('bonus') }}">賞与明細</a>
            </nav>
            <a class="quick-link logout" href="{{ route('login.portal') }}">ログアウト</a>
        </div>
    </section>

    <section class="panel">
        <form method="get" class="filter-row">
            <label for="month">日付:</label>
            <select id="month" name="month">
                @forelse (($availableMonths ?? []) as $month)
                    <option value="{{ $month }}" @selected($selectedMonth === $month)>
                        {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('Y年n月') }}
                    </option>
                @empty
                    <option value="{{ $selectedMonth }}">{{ $selectedMonth }}</option>
                @endforelse
            </select>
            <button type="submit">表示</button>
            <button type="button" onclick="window.print()">印刷</button>
        </form>

        @include('shared.payroll.payslip_item', [
            'rawRows' => $rawRows,
            'targetStaffName' => $targetStaffName,
            'storeName' => $storeName,
            'companyName' => $companyName,
            'targetTaxCategory' => $targetTaxCategory ?? '',
            'isBonus' => $isBonus ?? false,
        ])
    </section>
</main>
</body>
</html>
