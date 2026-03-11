<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 管理者賞与計算</title>
    <style>
        body { margin:0; font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif; background:#ecf2fb; color:#1f2937; }
        .wrap { width:min(1680px,98vw); margin:14px auto; }
        .top { display:flex; gap:10px; align-items:center; justify-content:space-between; margin-bottom:10px; }
        .title { font-size:24px; font-weight:800; color:#1f4f8f; }
        .btn { display:inline-block; text-decoration:none; border:1px solid #d3dff0; background:#fff; border-radius:10px; padding:8px 12px; color:#1f2937; }
        .panel { background:#fff; border:1px solid #d3dff0; border-radius:14px; padding:10px; }
        .filters { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:10px; }
        .filters input, .filters select, .filters button { height:32px; border:1px solid #cfd9e8; border-radius:8px; padding:0 10px; background:#fff; }
        .filters button { cursor:pointer; }
        .flash { margin-bottom:10px; border:1px solid #cfe0fb; background:#edf4ff; color:#1f4f8f; border-radius:8px; padding:8px 10px; font-size:13px; }
        .empty { text-align:center; color:#667085; padding:22px; }

        .seed-ops { border:1px solid #d7e4f8; background:#f8fbff; border-radius:10px; padding:10px; margin:0 0 12px; }
        .seed-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:6px 10px; max-height:180px; overflow:auto; border:1px solid #dce7f7; background:#fff; border-radius:8px; padding:8px; margin-bottom:8px; }
        .seed-check { display:flex; align-items:center; gap:6px; font-size:12px; color:#344054; }
        .seed-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

        .layout { display:grid; grid-template-columns: 220px 1fr; gap:10px; align-items:start; }
        .staff-list { background:#f7faff; border:1px solid #d9e5f7; border-radius:10px; padding:6px; max-height:72vh; overflow:auto; }
        .staff-item { display:block; text-decoration:none; color:inherit; border:1px solid #dce6f5; background:#fff; border-radius:8px; padding:6px 8px; margin-bottom:6px; }
        .staff-item.active { border-color:#6aa0f0; box-shadow:0 0 0 2px rgba(106,160,240,.18); }
        .staff-id { display:flex; gap:4px; align-items:center; flex-wrap:wrap; font-size:11px; color:#475467; }
        .staff-name { margin-top:4px; font-size:14px; font-weight:700; color:#1f2937; }
        .lock-pill { font-size:10px; padding:1px 6px; border-radius:999px; border:1px solid transparent; }
        .lock-pill.locked { color:#0f5132; background:#d1fae5; border-color:#a7f3d0; }
        .lock-pill.unlocked { color:#9a3412; background:#ffedd5; border-color:#fed7aa; }

        .detail-pane { background:#f8fbff; border:1px solid #dbe7f7; border-radius:12px; padding:8px; }
        .summary-cards { display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap:8px; margin-bottom:8px; }
        .card { background:#fff; border:1px solid #d9e5f7; border-radius:10px; padding:8px; min-height:52px; }
        .card .k { font-size:11px; color:#667085; margin-bottom:4px; }
        .card .v { font-size:15px; font-weight:700; color:#1f2937; line-height:1.35; }
        .btn-act { border:1px solid #cfd9e8; border-radius:8px; padding:6px 10px; background:#fff; color:#1f2937; cursor:pointer; font-size:12px; }
        .btn-act.primary { background:#1f4f8f; border-color:#1f4f8f; color:#fff; }

        .section-grid { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:8px; align-items:start; }
        .section { background:#fff; border:1px solid #d9e5f7; border-radius:10px; padding:8px; }
        .section h3 { margin:0 0 6px; font-size:13px; font-weight:800; color:#1f2937; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th, td { border-bottom:1px solid #edf2fb; padding:4px 6px; font-size:12px; vertical-align:top; }
        th { width:48%; text-align:left; color:#334155; font-weight:700; }
        td { text-align:right; color:#111827; }
        .num-input { width:100%; box-sizing:border-box; text-align:right; border:1px solid #cfd9e8; border-radius:6px; height:28px; padding:0 8px; }

        .totals { margin-top:8px; display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap:8px; }
        .total-card { background:#eaf2ff; border:1px solid #cfe0fb; border-radius:10px; padding:8px; }
        .total-card .k { font-size:12px; color:#375a8c; }
        .total-card .v { margin-top:4px; font-size:18px; font-weight:800; color:#1f4f8f; text-align:right; }

        @media (max-width: 1100px) {
            .layout { grid-template-columns: 200px 1fr; }
            .summary-cards { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .section-grid { grid-template-columns: 1fr; }
            .totals { grid-template-columns: repeat(2, minmax(0,1fr)); }
        }
        @media (max-width: 760px) {
            .layout { grid-template-columns: 1fr; }
            .staff-list { max-height:none; }
            .summary-cards { grid-template-columns: 1fr; }
            .totals { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM 管理者賞与計算</div>
        <div style="display:flex; gap:8px; align-items:center;">
            <a class="btn" href="{{ route('admin.attendance.index', ['month' => $selectedMonth, 'company_id' => $selectedCompanyId, 'staff_id' => $selectedStaffId]) }}">勤怠管理</a>
            <a class="btn" href="{{ route('admin.payroll.index', ['month' => $selectedMonth, 'company_id' => $selectedCompanyId, 'staff_id' => $selectedStaffId]) }}">給与計算</a>
            <a class="btn" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
        </div>
    </div>

    <section class="panel">
        <form method="GET" class="filters">
            <label for="month">対象月</label>
            <input id="month" name="month" type="month" value="{{ $selectedMonth }}">

            <label for="company_id">会社</label>
            <select id="company_id" name="company_id">
                <option value="">全社</option>
                @foreach ($companyOptions as $company)
                    <option value="{{ $company['company_id'] }}" @selected($selectedCompanyId === $company['company_id'])>{{ $company['company_name'] }}</option>
                @endforeach
            </select>

            <label for="staff_id">スタッフ</label>
            <select id="staff_id" name="staff_id">
                <option value="">全員</option>
                @foreach ($staffOptions as $staff)
                    <option value="{{ $staff['staff_id'] }}" @selected($selectedStaffId === $staff['staff_id'])>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</option>
                @endforeach
            </select>

            <button type="submit">表示</button>
        </form>

        <div class="seed-ops">
            <div style="font-weight:700; margin-bottom:8px;">賞与対象者より賞与データ作成</div>
            <form method="POST" action="{{ route('admin.bonus.seed-bulk-create') }}">
                @csrf
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                <div class="seed-grid">
                    @foreach ($seedStaffOptions as $staff)
                        <label class="seed-check">
                            <input type="checkbox" name="target_staff_ids[]" value="{{ $staff['staff_id'] }}">
                            <span>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="seed-actions">
                    <button class="btn-act primary" type="submit">賞与データ作成</button>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.bonus.seed-bulk-delete') }}" style="margin-top:8px;">
                @csrf
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                <div class="seed-grid">
                    @foreach ($seedStaffOptions as $staff)
                        <label class="seed-check">
                            <input type="checkbox" name="target_staff_ids[]" value="{{ $staff['staff_id'] }}">
                            <span>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="seed-actions">
                    <button class="btn-act" type="submit">未確定の賞与データ削除</button>
                </div>
            </form>
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if (empty($summaryRows))
            <div class="empty">対象月の賞与データがありません。</div>
        @else
            <div class="layout">
                <aside class="staff-list">
                    @foreach ($summaryRows as $row)
                        @php
                            $query = request()->query();
                            $query['row'] = $row['key'];
                        @endphp
                        <a class="staff-item {{ $selectedRowKey === $row['key'] ? 'active' : '' }}" href="{{ route('admin.bonus.index', $query) }}">
                            <div class="staff-id">
                                <span>{{ $row['staff_id'] }}</span>
                                <span class="lock-pill {{ $row['is_locked'] ? 'locked' : 'unlocked' }}">{{ $row['is_locked'] ? '確定済' : '未確定' }}</span>
                            </div>
                            <div class="staff-name">{{ $row['staff_name'] }}</div>
                        </a>
                    @endforeach
                </aside>

                <section class="detail-pane">
                    <div class="summary-cards">
                        <div class="card"><div class="k">名前</div><div class="v">{{ $selectedSummary['staff_name'] ?? '-' }}</div></div>
                        <div class="card"><div class="k">支給月</div><div class="v">{{ $selectedSummary['supply_month'] ?? '-' }}</div></div>
                        <div class="card"><div class="k">雇用形態</div><div class="v">{{ $selectedSummary['division'] ?? '-' }}</div></div>
                        <div class="card">
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                <form method="POST" action="{{ route('admin.bonus.save') }}">
                                    @csrf
                                    <input type="hidden" name="entry_id" value="{{ $selectedSummary['entry_id'] ?? 0 }}">
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                    <input type="hidden" name="row" value="{{ $selectedRowKey }}">

                                    <div class="section-grid">
                                        <section class="section">
                                            <h3>支給項目</h3>
                                            <table>
                                                <tbody>
                                                @foreach ($detailRows['earnings'] as $r)
                                                    <tr>
                                                        <th>{{ $r['label'] }}</th>
                                                        <td><input class="num-input" type="text" name="fields[{{ $r['key'] }}]" value="{{ number_format((float)$r['value']) }}"></td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </section>

                                        <section class="section">
                                            <h3>控除項目</h3>
                                            <table>
                                                <tbody>
                                                @foreach ($detailRows['deductions'] as $r)
                                                    <tr>
                                                        <th>{{ $r['label'] }}</th>
                                                        <td><input class="num-input" type="text" name="fields[{{ $r['key'] }}]" value="{{ number_format((float)$r['value']) }}"></td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </section>

                                        <section class="section">
                                            <h3>その他項目</h3>
                                            <table>
                                                <tbody>
                                                @foreach ($detailRows['others'] as $r)
                                                    <tr>
                                                        <th>{{ $r['label'] }}</th>
                                                        <td><input class="num-input" type="text" name="fields[{{ $r['key'] }}]" value="{{ number_format((float)$r['value']) }}"></td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </section>
                                    </div>

                                    <div style="display:flex; gap:6px; margin-top:10px;">
                                        <button class="btn-act primary" type="submit">保存</button>
                                </form>
                                @if (!empty($selectedSummary['is_locked']))
                                    <form method="POST" action="{{ route('admin.bonus.unlock') }}">
                                        @csrf
                                        <input type="hidden" name="entry_id" value="{{ $selectedSummary['entry_id'] ?? 0 }}">
                                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                        <input type="hidden" name="row" value="{{ $selectedRowKey }}">
                                        <button class="btn-act" type="submit">未確定に戻す</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.bonus.lock') }}">
                                        @csrf
                                        <input type="hidden" name="entry_id" value="{{ $selectedSummary['entry_id'] ?? 0 }}">
                                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                        <input type="hidden" name="row" value="{{ $selectedRowKey }}">
                                        <button class="btn-act" type="submit">確定</button>
                                    </form>
                                @endif
                                    </div>
                        </div>
                    </div>

                    <div class="totals">
                        <div class="total-card"><div class="k">支給合計</div><div class="v">{{ number_format((float)$detailRows['totals']['pay_total']) }}</div></div>
                        <div class="total-card"><div class="k">控除合計</div><div class="v">{{ number_format((float)$detailRows['totals']['deduction_total']) }}</div></div>
                        <div class="total-card"><div class="k">その他合計</div><div class="v">{{ number_format((float)$detailRows['totals']['others_total']) }}</div></div>
                        <div class="total-card"><div class="k">差引支給額</div><div class="v">{{ number_format((float)$detailRows['totals']['net_pay']) }}</div></div>
                    </div>
                </section>
            </div>
        @endif
    </section>
</div>
</body>
</html>

