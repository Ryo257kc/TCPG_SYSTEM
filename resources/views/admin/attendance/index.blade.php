<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 勤怠管理</title>
    <style>
        body { margin: 0; font-family: "Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif; background: #ecf2fb; color: #1f2937; }
        .wrap { width: min(1600px, 98vw); margin: 16px auto; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 12px; }
        .title { font-size: 24px; font-weight: 800; color: #1f4f8f; }
        .panel { background: #fff; border: 1px solid #d3dff0; border-radius: 12px; padding: 10px; }
        .btn { border: 1px solid #d3dff0; border-radius: 8px; padding: 8px 12px; text-decoration: none; color: #1f2937; background: #fff; }
        .toolbar-row { display:flex; gap:8px; align-items:center; margin-bottom:10px; }
        .filters { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; flex:1 1 auto; min-width:0; margin:0; }
        .ops-toggle { margin-left:auto; }
        .shift-ops { display:none; border:1px solid #d7e4f8; background:#f8fbff; border-radius:10px; padding:10px; margin-bottom:10px; }
        .shift-ops.is-open { display:block; }
        .shift-ops-head { display:flex; justify-content:flex-end; margin-bottom:8px; }
        .bulk { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 10px; }
        .bulk-panel { margin-bottom: 10px; border: 1px solid #d7e4f8; background: #f8fbff; border-radius: 10px; padding: 10px; }
        .bulk-title { margin: 0 0 8px; color: #1f4f8f; font-size: 13px; font-weight: 700; }
        .bulk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 6px 10px; max-height: 180px; overflow: auto; border: 1px solid #dce7f7; background: #fff; border-radius: 8px; padding: 8px; margin-bottom: 8px; }
        .bulk-check { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #344054; }
        input, select, button { border: 1px solid #d3dff0; border-radius: 8px; padding: 8px 10px; background: #fff; }
        button { cursor: pointer; }
        .btn-primary { background: #1f4f8f; color: #fff; border-color: #1f4f8f; }
        .btn-danger { background: #fff1f2; color: #b42318; border-color: #e2b3b8; }
        .flash { border: 1px solid #cfe0fb; background: #edf4ff; color: #1f4f8f; border-radius: 8px; padding: 8px 10px; margin-bottom: 10px; }
        .warn { border: 1px solid #ffd7b0; background: #fff4e8; color: #8a4b14; border-radius: 8px; padding: 8px 10px; margin-bottom: 10px; }
        .sub-title { margin: 14px 0 8px; font-size: 16px; font-weight: 700; color: #1f4f8f; }
        .chips { display: flex; gap: 8px; margin-bottom: 8px; }
        .chip { font-size: 12px; border: 1px solid #d3dff0; border-radius: 999px; padding: 4px 10px; background: #f7fafe; }
        .chip.alert { border-color: #f3b0b6; background: #fff0f1; color: #b42318; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM 勤怠管理</div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="{{ route('admin.payroll.index', ['month' => $selectedMonth, 'company_id' => $selectedCompanyId, 'staff_id' => $selectedStaffId]) }}">給与計算</a>
            <a class="btn" href="{{ route('admin.dashboard', ['page' => 'bonus-detail']) }}">賞与計算</a>
            <a class="btn" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
        </div>
    </div>

    <section class="panel">
        <div class="toolbar-row">
            <form method="GET" class="filters">
                <label for="month">対象月</label>
                <input id="month" name="month" type="month" value="{{ $selectedMonth }}">

                <label for="company_id">会社</label>
                <select id="company_id" name="company_id">
                    <option value="">全社</option>
                    @foreach ($companyOptions as $company)
                        <option value="{{ $company }}" @selected($selectedCompanyId === $company)>{{ $company }}</option>
                    @endforeach
                </select>

                <label for="staff_id">スタッフ</label>
                <select id="staff_id" name="staff_id">
                    <option value="">全スタッフ</option>
                    @foreach ($staffRows as $staff)
                        <option value="{{ $staff['staff_id'] }}" @selected($selectedStaffId === $staff['staff_id'])>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</option>
                    @endforeach
                </select>
                <button type="submit">表示</button>
            </form>
            <button type="button" id="shift-ops-toggle" class="btn ops-toggle" aria-expanded="false">シフト作成</button>
        </div>

        <div id="shift-ops" class="shift-ops">
            <div class="shift-ops-head">
                <form method="POST" action="{{ route('admin.payroll.sync-attendance-bulk') }}" onsubmit="return confirm('勤怠集計を一括反映します。実行しますか？');">
                    @csrf
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                    <button type="submit" class="btn-primary">勤怠一括反映</button>
                </form>
            </div>

            @include('admin.attendance.partials.bulk_selector', [
                'displayStaffRows' => $bulkStaffRows,
                'selectedStaffId' => $selectedStaffId,
            ])

            <div class="bulk">
                <form id="shift-bulk-create-form" method="POST" action="{{ route('admin.attendance.shift-bulk-create') }}">
                    @csrf
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                    <button type="submit">シフト一括作成</button>
                </form>
                <form id="shift-bulk-delete-form" method="POST" action="{{ route('admin.attendance.shift-bulk-delete') }}" onsubmit="return confirm('選択スタッフの対象月シフトを削除します。実行しますか？');">
                    @csrf
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                    <label style="display:inline-flex;align-items:center;gap:6px;margin-right:8px;font-size:12px;">
                        <input type="checkbox" name="force_delete_with_punch" value="1">
                        打刻ありも削除
                    </label>
                    <button type="submit" class="btn-danger">シフト一括削除</button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @if (!$timeCardReady)
            <div class="warn">勤怠テーブルが見つからないため、表示のみ可能です。</div>
        @endif

        @include('admin.attendance.partials.summary_table', [
            'rows' => $rows,
            'selectedMonth' => $selectedMonth,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedStaffId' => $selectedStaffId,
        ])

        @if($detailStaffId !== '')
            <div class="sub-title">日別勤怠（{{ $detailStaffName !== '' ? $detailStaffName : $detailStaffId }}）</div>
            <div class="chips">
                <span class="chip">本人申請: {{ !empty($hasRequest) ? '有' : '無' }}</span>
                <span class="chip {{ !empty($hasApproval) ? '' : 'alert' }}">管理者承認: {{ !empty($hasApproval) ? '有' : '無' }}</span>
            </div>

            @include('admin.attendance.partials.daily_table', [
                'dailyRows' => $dailyRows,
                'detailMetrics' => $detailMetrics ?? [],
            ])
        @endif
    </section>
</div>
<script>
(() => {
    const forms = ['shift-bulk-create-form', 'shift-bulk-delete-form'];
    const checks = document.querySelectorAll('.js-target-staff');
    const ops = document.getElementById('shift-ops');
    const toggle = document.getElementById('shift-ops-toggle');

    const syncTargets = () => {
        const selected = Array.from(checks).filter((el) => el.checked).map((el) => el.value);
        forms.forEach((id) => {
            const form = document.getElementById(id);
            if (!form) return;
            form.querySelectorAll('input[name="target_staff_ids[]"]').forEach((n) => n.remove());
            selected.forEach((staffId) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'target_staff_ids[]';
                input.value = staffId;
                form.appendChild(input);
            });
        });
    };

    const selectAllBtn = document.querySelector('.js-select-all');
    const clearAllBtn = document.querySelector('.js-clear-all');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            checks.forEach((el) => { el.checked = true; });
            syncTargets();
        });
    }
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            checks.forEach((el) => { el.checked = false; });
            syncTargets();
        });
    }
    checks.forEach((el) => el.addEventListener('change', syncTargets));
    syncTargets();

    if (toggle && ops) {
        const setOpen = (open) => {
            ops.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.textContent = open ? 'シフト作成（閉じる）' : 'シフト作成';
        };
        setOpen(false);
        toggle.addEventListener('click', () => setOpen(!ops.classList.contains('is-open')));
    }
})();
</script>
</body>
</html>
