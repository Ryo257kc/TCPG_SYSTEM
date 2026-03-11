<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 給与計算</title>
    <style>
        body { margin:0; font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif; background:#ecf2fb; color:#1f2937; }
        .wrap { width:min(1760px,99vw); margin:14px auto; }
        .top { display:flex; gap:10px; align-items:center; justify-content:space-between; margin-bottom:10px; }
        .title { font-size:24px; font-weight:800; color:#1f4f8f; }
        .btn { display:inline-block; text-decoration:none; border:1px solid #d3dff0; background:#fff; border-radius:10px; padding:8px 12px; color:#1f2937; cursor:pointer; }
        .btn.primary { background:#1f4f8f; border-color:#1f4f8f; color:#fff; }
        .btn.danger { border-color:#fecaca; background:#fff1f2; color:#b42318; }
        .panel { background:#fff; border:1px solid #d3dff0; border-radius:14px; padding:10px; }
        .filters { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:10px; }
        .filters input, .filters select, .filters button { height:32px; border:1px solid #cfd9e8; border-radius:8px; padding:0 10px; background:#fff; }
        .seed-ops { display:none; border:1px solid #d7e4f8; background:#f8fbff; border-radius:10px; padding:10px; margin:0 0 10px; }
        .seed-ops.is-open { display:block; }
        .seed-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:6px 10px; max-height:180px; overflow:auto; border:1px solid #dce7f7; background:#fff; border-radius:8px; padding:8px; }
        .seed-check { display:flex; align-items:center; gap:6px; font-size:12px; color:#344054; }
        .seed-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:8px; }
        .flash { margin-bottom:10px; border:1px solid #cfe0fb; background:#edf4ff; color:#1f4f8f; border-radius:8px; padding:8px 10px; font-size:13px; }
        .flash.error { border-color:#fecaca; background:#fff1f2; color:#b42318; }
        .meta { color:#667085; font-size:13px; margin-bottom:10px; }

        .layout { display:grid; grid-template-columns:220px 1fr; gap:10px; min-height:72vh; align-items:start; }
        .staff-list { background:#f7faff; border:1px solid #d9e5f7; border-radius:10px; padding:6px; overflow:auto; }
        .staff-item { display:block; text-decoration:none; color:inherit; border:1px solid #dce6f5; background:#fff; border-radius:8px; padding:6px 8px; margin-bottom:6px; }
        .staff-item.active { border-color:#6aa0f0; box-shadow:0 0 0 2px rgba(106,160,240,.18); }
        .staff-id { display:flex; gap:4px; align-items:center; flex-wrap:wrap; font-size:11px; color:#475467; }
        .staff-name { margin-top:4px; font-size:14px; font-weight:700; color:#1f2937; }
        .lock-pill { font-size:10px; padding:1px 6px; border-radius:999px; border:1px solid transparent; }
        .lock-pill.locked { color:#0f5132; background:#d1fae5; border-color:#a7f3d0; }
        .lock-pill.unlocked { color:#9a3412; background:#ffedd5; border-color:#fed7aa; }
        .lock-pill.att-checked { color:#0f5132; background:#dcfce7; border-color:#86efac; }
        .lock-pill.att-unchecked { color:#b42318; background:#fee4e2; border-color:#fecaca; }

        .detail-pane { background:#f8fbff; border:1px solid #dbe7f7; border-radius:12px; padding:8px; }
        .summary-cards { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:8px; margin-bottom:8px; }
        .card { background:#fff; border:1px solid #d9e5f7; border-radius:10px; padding:8px; min-height:52px; }
        .card .k { font-size:11px; color:#667085; margin-bottom:4px; }
        .card .v { font-size:15px; font-weight:700; color:#1f2937; line-height:1.35; }

        .actions { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:8px; }
        .btn-act { border:1px solid #cfd9e8; border-radius:8px; padding:6px 10px; background:#fff; color:#1f2937; cursor:pointer; font-size:12px; }
        .btn-act.primary { background:#1f4f8f; border-color:#1f4f8f; color:#fff; }

        .section-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:6px; align-items:start; }
        .section-stack { display:grid; gap:8px; }
        .section { background:#fff; border:1px solid #d9e5f7; border-radius:10px; padding:7px; }
        .section.reference { background:#f6f8fc; }
        .section h3 { margin:0 0 6px; font-size:13px; font-weight:800; color:#1f2937; }

        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th, td { border-bottom:1px solid #edf2fb; padding:4px 5px; font-size:12px; vertical-align:top; }
        th { width:44%; text-align:left; color:#334155; font-weight:700; }
        td { text-align:right; color:#111827; font-variant-numeric:tabular-nums; }

        .cell-view { display:inline; }
        .num-input { display:none; width:100%; box-sizing:border-box; text-align:right; border:1px solid #cfd9e8; border-radius:6px; height:28px; padding:0 8px; }
        .edit-mode .cell-view { display:none; }
        .edit-mode .num-input { display:inline-block; }

        .totals { margin-top:8px; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; }
        .total-card { background:#eaf2ff; border:1px solid #cfe0fb; border-radius:10px; padding:8px; }
        .total-card .k { font-size:12px; color:#375a8c; }
        .total-card .v { margin-top:4px; font-size:18px; font-weight:800; color:#1f4f8f; text-align:right; }

        .empty { text-align:center; color:#667085; padding:22px; }

        @media (max-width: 1100px) {
            .layout { grid-template-columns:200px 1fr; }
            .summary-cards { grid-template-columns:repeat(3,minmax(0,1fr)); }
            .section-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
            .totals { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
        @media (max-width: 760px) {
            .layout { grid-template-columns:1fr; }
            .summary-cards { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .section-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .totals { grid-template-columns:1fr; }
        }
        @media (max-width: 560px) {
            .section-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    @php
        $attendanceMonth = date('Y-m', strtotime($selectedMonth . '-01 -1 month'));
    @endphp

    <div class="top">
        <div class="title">TCPG SYSTEM 給与計算</div>
        <div style="display:flex; gap:8px; align-items:center;">
            <a class="btn" href="{{ route('admin.attendance.index', ['month' => $attendanceMonth, 'company_id' => $selectedCompanyId, 'staff_id' => $selectedStaffId]) }}">勤怠管理</a>
            <a class="btn" href="{{ route('admin.bonus.index') }}">賞与計算</a>
            <a class="btn" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
        </div>
    </div>

    <section class="panel">
        <form method="GET" action="{{ route('admin.payroll-v2.index') }}" class="filters">
            <label for="month">対象月</label>
            <input id="month" name="month" type="month" value="{{ $selectedMonth }}">

            <label for="company_id">会社</label>
            <select id="company_id" name="company_id">
                <option value="">全社</option>
                @foreach($companyOptions as $co)
                    <option value="{{ $co['company_id'] }}" @selected($co['company_id'] === $selectedCompanyId)>{{ $co['company_name'] }}</option>
                @endforeach
            </select>

            <label for="staff_id">スタッフ</label>
            <select id="staff_id" name="staff_id">
                <option value="">全員</option>
                @foreach($staffOptions as $st)
                    <option value="{{ $st['staff_id'] }}" @selected($st['staff_id'] === $selectedStaffId)>{{ $st['staff_id'] }} {{ $st['staff_name'] }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn primary">表示</button>
            <button type="button" class="btn" id="payroll-seed-toggle">給与データ作成</button>
        </form>

        <div id="payroll-seed-ops" class="seed-ops">
            <div class="seed-grid">
                @forelse($bulkStaffRows as $s)
                    <label class="seed-check">
                        <input type="checkbox" class="seed-target" value="{{ $s['staff_id'] }}" @checked($selectedStaffId !== '' && $selectedStaffId === $s['staff_id'])>
                        <span>{{ $s['staff_id'] }} {{ $s['staff_name'] }}</span>
                    </label>
                @empty
                    <div style="font-size:12px; color:#667085;">対象スタッフがいません。</div>
                @endforelse
            </div>
            <div class="seed-actions">
                <button type="button" class="btn" onclick="seedAll(true)">全選択</button>
                <button type="button" class="btn" onclick="seedAll(false)">全解除</button>
                <form id="seed-create" method="POST" action="{{ route('admin.payroll-v2.seed-bulk-create') }}" style="margin:0;">
                    @csrf
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                    <button type="submit" class="btn primary" onclick="return injectTargets('seed-create')">給与データ一括作成</button>
                </form>
                <form id="seed-delete" method="POST" action="{{ route('admin.payroll-v2.seed-bulk-delete') }}" style="margin:0;" onsubmit="return confirm('選択スタッフの対象月給与データを削除します。よろしいですか？') && injectTargets('seed-delete');">
                    @csrf
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                    <button type="submit" class="btn danger">給与データ一括削除</button>
                </form>
            </div>
        </div>

        @if(session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="flash error">{{ session('error') }}</div>
        @endif

        <div class="meta">対象者件数 {{ count($summaryRows) }}</div>

        <div class="layout">
            <aside class="staff-list">
                @forelse($summaryRows as $row)
                    <a class="staff-item @if(($selectedSummary['staff_id'] ?? '') === $row['staff_id']) active @endif" href="{{ route('admin.payroll-v2.index', ['month' => $selectedMonth, 'company_id' => $selectedCompanyId, 'staff_id' => $row['staff_id']]) }}">
                        <div class="staff-id">
                            <span>{{ $row['staff_id'] }}</span>
                            @if($row['is_edit_locked'])<span class="lock-pill locked">確定済</span>@else<span class="lock-pill unlocked">未確定</span>@endif
                            @if($row['attendance_checked'])<span class="lock-pill att-checked">勤怠確定</span>@else<span class="lock-pill att-unchecked">勤怠未確定</span>@endif
                        </div>
                        <div class="staff-name">{{ $row['staff_name'] !== '' ? $row['staff_name'] : '-' }}</div>
                        <div class="staff-id">{{ $row['staff_division'] !== '' ? $row['staff_division'] : '-' }} / {{ $row['store_short_name'] !== '' ? $row['store_short_name'] : '-' }}</div>
                    </a>
                @empty
                    <div class="empty">対象データがありません</div>
                @endforelse
            </aside>

            <section class="detail-pane">
                @if($selectedSummary === null)
                    <div class="empty">スタッフを選択してください</div>
                @else
                    <div class="summary-cards">
                        <div class="card"><div class="k">対象者</div><div class="v">{{ $selectedSummary['staff_id'] }} {{ $selectedSummary['staff_name'] }}</div></div>
                        <div class="card"><div class="k">支給日</div><div class="v">{{ isset($selectedSummary['supply_month']) && trim((string)$selectedSummary['supply_month']) !== '' ? date('Y/m/d', strtotime((string)$selectedSummary['supply_month'])) : '-' }}</div></div>
                        <div class="card"><div class="k">雇用区分</div><div class="v">{{ $selectedSummary['staff_division'] !== '' ? $selectedSummary['staff_division'] : '-' }}</div></div>
                        <div class="card"><div class="k">社名 / 店舗</div><div class="v">{{ $selectedSummary['company_name'] }}<br>{{ $selectedSummary['store_short_name'] }}</div></div>
                        <div class="card"><div class="k">勤怠状態</div><div class="v">{{ $selectedSummary['attendance_checked'] ? '確定済' : '未確定' }}</div></div>
                    </div>

                    <div class="actions">
                        <button type="button" class="btn-act" id="btn-edit">編集</button>
                        <button type="submit" class="btn-act primary" id="btn-save" form="payload-save" style="display:none;">保存</button>
                        <button type="button" class="btn-act" id="btn-cancel" style="display:none;">キャンセル</button>

                        <form method="POST" action="{{ route('admin.payroll-v2.sync-attendance') }}" style="margin:0;">@csrf
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                            <input type="hidden" name="staff_id" value="{{ $selectedSummary['staff_id'] }}">
                            <button type="submit" class="btn-act">勤怠反映</button>
                        </form>

                        <form method="POST" action="{{ route('admin.payroll-v2.recalc-defaults') }}" style="margin:0;">@csrf
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                            <input type="hidden" name="staff_id" value="{{ $selectedSummary['staff_id'] }}">
                            <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] }}">
                            <button type="submit" class="btn-act">再計算（初期値）</button>
                        </form>

                        <form method="POST" action="{{ route('admin.payroll-v2.recalc-koyou') }}" style="margin:0;">@csrf
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                            <input type="hidden" name="staff_id" value="{{ $selectedSummary['staff_id'] }}">
                            <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] }}">
                            <button type="submit" class="btn-act">雇用保険↩</button>
                        </form>

                        <form method="POST" action="{{ route('admin.payroll-v2.recalc-premium-deduction') }}" style="margin:0;">@csrf
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                            <input type="hidden" name="staff_id" value="{{ $selectedSummary['staff_id'] }}">
                            <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] }}">
                            <button type="submit" class="btn-act">割増・控除↩</button>
                        </form>

                        <form method="POST" action="{{ route('admin.payroll-v2.recalc-income-tax') }}" style="margin:0;">@csrf
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                            <input type="hidden" name="staff_id" value="{{ $selectedSummary['staff_id'] }}">
                            <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] }}">
                            <button type="submit" class="btn-act">所得税↩</button>
                        </form>

                        @if($selectedSummary['is_edit_locked'])
                            <form method="POST" action="{{ route('admin.payroll-v2.unlock') }}" style="margin:0;">@csrf
                                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                <input type="hidden" name="staff_id" value="{{ $selectedSummary['staff_id'] }}">
                                <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] }}">
                                <button type="submit" class="btn-act">未確定に戻す</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.payroll-v2.lock') }}" style="margin:0;">@csrf
                                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                <input type="hidden" name="staff_id" value="{{ $selectedSummary['staff_id'] }}">
                                <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] }}">
                                <button type="submit" class="btn-act primary">確定</button>
                            </form>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.payroll-v2.save') }}" id="payload-save">@csrf
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                        <input type="hidden" name="staff_id" value="{{ $selectedSummary['staff_id'] }}">
                        <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] }}">
                        <input type="hidden" name="row" value="{{ $selectedSummary['staff_id'] }}">

                        <div id="payload-box">
                            @php
                                $attendanceRows = (array)($selectedPayloadSections['Attendance'] ?? []);
                                $earningRows = (array)($selectedPayloadSections['Earnings'] ?? []);
                                $deductionRows = (array)($selectedPayloadSections['Deductions'] ?? []);
                                $otherRows = (array)($selectedPayloadSections['Others'] ?? []);
                                $baseRows = (array)($selectedPayloadSections['BaseInfo'] ?? []);
                                $salesRows = (array)($selectedPayloadSections['Sales'] ?? []);
                                $referenceRows = (array)($selectedPayloadSections['ReferenceTotals'] ?? []);
                                $masterInfo = (array)($selectedMasterInfo ?? []);
                                $kihonRows = (array)($masterInfo['kihon'] ?? []);
                                $syahoRows = (array)($masterInfo['syaho'] ?? []);
                                $residentRows = (array)($masterInfo['resident'] ?? []);
                                $masterLabelMap = [
                                    'monthly_salary' => '月給',
                                    'hourly_salary' => '時給',
                                    'hourly_pay' => '時給(調整後)',
                                    'executive_remu' => '役員報酬',
                                    'position_allow' => '役職手当',
                                    'duties_allow' => '職務手当',
                                    'qualification_allow' => '資格手当',
                                    'claim_allow' => '請求手当',
                                    'traffic_pay' => '課税通勤費',
                                    'adjustment_add' => '調整手当',
                                    'rent_subsidies' => '家賃補助',
                                    'rent_pay' => '社宅家賃',
                                    'adjustment_pay' => '調整控除',
                                    'fixed_overtime' => '固定残業',
                                    'kenpo_grade' => '健保等級',
                                    'kenpo_amo' => '健康保険料',
                                    'kaigo_amo' => '介護保険料',
                                    'kounen_grade' => '厚年等級',
                                    'kounen_amo' => '厚生年金保険料',
                                    'raise_year' => '社保改定月',
                                    'resident_tax_month' => '住民税対象月',
                                ];
                                $masterDisplayLabelMap = array_merge($displayLabelMap, $masterLabelMap);
                                $baseInfoLabelMap = array_merge($displayLabelMap, [
                                    'tax_amount' => '税額表',
                                    'working_time' => '所定労働時間',
                                    'year_working_time' => '月所定労働時間',
                                    'traffic_day' => '日額交通費',
                                    'yukyu' => '有休対象',
                                    'syaho' => '社保加入',
                                    'koyou' => '雇用加入',
                                    'memo' => 'メモ',
                                ]);
                            @endphp

                            <div class="section-grid">
                                <div class="section-stack">
                                    <div class="section"><h3>勤怠</h3>@include('admin.payroll_v2.partials.section_table', ['rows' => $attendanceRows, 'displayLabelMap' => $displayLabelMap, 'editablePayloadKeys' => $editablePayloadKeys])</div>
                                    <div class="section"><h3>売上</h3>@include('admin.payroll_v2.partials.section_table', ['rows' => $salesRows, 'displayLabelMap' => $displayLabelMap, 'editablePayloadKeys' => $editablePayloadKeys])</div>
                                </div>
                                <div class="section"><h3>支給</h3>@include('admin.payroll_v2.partials.section_table', ['rows' => $earningRows, 'displayLabelMap' => $displayLabelMap, 'editablePayloadKeys' => $editablePayloadKeys])</div>
                                <div class="section-stack">
                                    <div class="section"><h3>控除</h3>@include('admin.payroll_v2.partials.section_table', ['rows' => $deductionRows, 'displayLabelMap' => $displayLabelMap, 'editablePayloadKeys' => $editablePayloadKeys])</div>
                                    <div class="section"><h3>その他</h3>@include('admin.payroll_v2.partials.section_table', ['rows' => $otherRows, 'displayLabelMap' => $displayLabelMap, 'editablePayloadKeys' => $editablePayloadKeys])</div>
                                </div>
                                <div class="section-stack">
                                    <div class="section reference"><h3>基本情報</h3>@include('admin.payroll_v2.partials.section_table', ['rows' => $baseRows, 'displayLabelMap' => $baseInfoLabelMap, 'editablePayloadKeys' => []])</div>
                                    <div class="section reference"><h3>参照集計</h3>@include('admin.payroll_v2.partials.section_table', ['rows' => $referenceRows, 'displayLabelMap' => $displayLabelMap, 'editablePayloadKeys' => []])</div>
                                </div>
                                <div class="section-stack">
                                    <div class="section reference"><h3>給与マスタ</h3>@include('admin.payroll_v2.partials.section_table', ['rows' => $kihonRows, 'displayLabelMap' => $masterDisplayLabelMap, 'editablePayloadKeys' => []])</div>
                                    <div class="section reference"><h3>社会保険</h3>@include('admin.payroll_v2.partials.section_table', ['rows' => $syahoRows, 'displayLabelMap' => $masterDisplayLabelMap, 'editablePayloadKeys' => []])</div>
                                    <div class="section reference"><h3>住民税</h3>@include('admin.payroll_v2.partials.section_table', ['rows' => $residentRows, 'displayLabelMap' => $masterDisplayLabelMap, 'editablePayloadKeys' => []])</div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="totals">
                        <div class="total-card"><div class="k">支給合計</div><div class="v">{{ $selectedSummary['pay_total'] }}</div></div>
                        <div class="total-card"><div class="k">控除合計</div><div class="v">{{ $selectedSummary['deduction_total'] }}</div></div>
                        <div class="total-card"><div class="k">その他合計</div><div class="v">{{ $selectedSummary['other_total'] ?? '-' }}</div></div>
                        <div class="total-card"><div class="k">差引支給額</div><div class="v">{{ $selectedSummary['net_pay'] }}</div></div>
                    </div>
                @endif
            </section>
        </div>
    </section>
</div>

<script>
(function(){
    var seedToggle = document.getElementById('payroll-seed-toggle');
    var seedOps = document.getElementById('payroll-seed-ops');
    if(seedToggle && seedOps){
        seedToggle.addEventListener('click', function(){
            var open = seedOps.classList.toggle('is-open');
            seedToggle.textContent = open ? '給与データ作成を閉じる' : '給与データ作成';
        });
    }
})();

function seedAll(on){
    document.querySelectorAll('.seed-target').forEach(function(el){ el.checked = !!on; });
}

function injectTargets(formId){
    var form = document.getElementById(formId);
    if(!form){ return false; }
    form.querySelectorAll('input[name="target_staff_ids[]"]').forEach(function(el){ el.remove(); });
    var checked = Array.from(document.querySelectorAll('.seed-target:checked')).map(function(el){ return el.value; });
    if(checked.length === 0){ alert('対象スタッフを選択してください。'); return false; }
    checked.forEach(function(id){
        var h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'target_staff_ids[]';
        h.value = id;
        form.appendChild(h);
    });
    return true;
}

(function(){
    var payloadBox = document.getElementById('payload-box');
    var btnEdit = document.getElementById('btn-edit');
    var btnSave = document.getElementById('btn-save');
    var btnCancel = document.getElementById('btn-cancel');
    if(!payloadBox || !btnEdit || !btnSave || !btnCancel){ return; }

    btnEdit.addEventListener('click', function(){
        payloadBox.classList.add('edit-mode');
        btnEdit.style.display = 'none';
        btnSave.style.display = 'inline-block';
        btnCancel.style.display = 'inline-block';
    });

    btnCancel.addEventListener('click', function(){
        payloadBox.classList.remove('edit-mode');
        btnEdit.style.display = 'inline-block';
        btnSave.style.display = 'none';
        btnCancel.style.display = 'none';
        var form = document.getElementById('payload-save');
        if(form){ form.reset(); }
    });
})();
</script>
</body>
</html>
