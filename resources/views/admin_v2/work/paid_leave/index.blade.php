<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 有休管理</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('css/admin_v2/paid_leave.css') }}"> -->
    <style>
        .paid-leave-filter-panel {
            margin-bottom: 10px;
        }

        .paid-leave-filter-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: end;
        }

        .paid-leave-field {
            display: grid;
            gap: 6px;
            min-width: 180px;
        }

        .paid-leave-field-staff {
            flex: 0 1 420px;
            min-width: 320px;
        }

        .paid-leave-field span {
            font-size: 12px;
            color: #667085;
            font-weight: 700;
        }

        .paid-leave-field input,
        .paid-leave-field select {
            width: 100%;
        }

        .paid-leave-actions {
            min-width: auto;
        }

        .paid-leave-summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 18px;
            align-items: start;
        }

        .paid-leave-summary-panel {
            padding: 16px;
        }

        .paid-leave-summary-head {
            font-size: 13px;
            font-weight: 800;
            color: #1f4f8f;
            margin-bottom: 12px;
        }

        .paid-leave-kv-list {
            display: grid;
            gap: 10px;
            margin: 0;
        }

        .paid-leave-kv-list div {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 10px;
            align-items: baseline;
        }

        .paid-leave-kv-list dt {
            color: #667085;
            font-size: 12px;
        }

        .paid-leave-kv-list dd {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
        }

        .paid-leave-history-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 220px;
            gap: 16px;
            align-items: start;
        }

        .paid-leave-schedule-side-panel {
            padding: 14px 12px;
        }

        .paid-leave-grant-schedule {
            display: grid;
            gap: 8px;
        }

        .paid-leave-grant-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 14px;
            color: #2b66b1;
        }

        .paid-leave-grant-row strong {
            color: #2b66b1;
            font-weight: 800;
        }

        .paid-leave-table-panel {
            padding: 14px;
        }

        .paid-leave-history-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 12px;
        }

        .paid-leave-history-head-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .history-add-panel {
            margin-bottom: 12px;
            padding: 12px;
            background: #f9fbfe;
            border: 1px solid #d7e0eb;
            border-radius: 10px;
        }

        .paid-leave-table-wrap {
            overflow-x: auto;
        }

        .paid-leave-history-table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
        }

        .paid-leave-history-table th,
        .paid-leave-history-table td {
            border: 1px solid #d7e0eb;
            padding: 8px 10px;
            font-size: 13px;
            text-align: left;
            white-space: nowrap;
        }

        .paid-leave-history-table th {
            background: #f4f7fb;
        }

        .paid-leave-history-action-cell {
            width: 92px;
            text-align: center;
        }

        .btn-small {
            padding: 6px 10px;
            font-size: 12px;
            min-width: 64px;
        }

        .btn-danger {
            border-color: #e4bcbc;
            color: #a64242;
            background: #fff5f5;
        }

        .history-edit-row td {
            background: #f9fbfe;
            padding: 12px;
        }

        .history-edit-form {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr)) auto;
            gap: 10px;
            align-items: end;
        }

        .history-edit-stack {
            display: grid;
            gap: 10px;
        }

        .history-edit-form label {
            display: grid;
            gap: 6px;
        }

        .history-edit-form span {
            font-size: 12px;
            color: #667085;
            font-weight: 700;
        }

        .history-edit-form input {
            width: 100%;
        }

        .history-edit-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: flex-end;
        }

        .history-delete-form {
            display: flex;
            justify-content: flex-end;
        }

        .history-delete-form .btn-danger {
            min-width: 120px;
        }

        .paid-leave-empty-cell {
            color: #667085;
            text-align: center;
            padding: 28px 12px;
        }

        .paid-leave-history-pager {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
        }

        .paid-leave-history-page-label {
            color: #667085;
            font-size: 13px;
            font-weight: 700;
        }

        .btn.is-disabled {
            pointer-events: none;
            opacity: .45;
        }

        @media (max-width: 1100px) {

            .paid-leave-summary-grid,
            .paid-leave-history-layout {
                grid-template-columns: 1fr;
            }

            .history-edit-form {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        <div class="top">
            <h1 class="title">TCPG SYSTEM 有休管理</h1>
        </div>

        <section class="panel paid-leave-filter-panel">
            <form method="GET" action="{{ route('admin.paid-leave.index') }}" class="paid-leave-filter-form">
                <label class="paid-leave-field paid-leave-field-staff">
                    <span>スタッフ</span>
                    <select name="staff_id">
                        <option value="">選択してください</option>
                        @foreach ($staffOptions as $option)
                        <option value="{{ $option['staff_id'] }}" {{ $selectedStaffId === $option['staff_id'] ? 'selected' : '' }}>
                            {{ $option['label'] }}
                        </option>
                        @endforeach
                    </select>
                </label>
                <div class="paid-leave-field paid-leave-actions">
                    <button type="submit" class="btn btn-primary">表示</button>
                </div>
            </form>
        </section>

        <section class="paid-leave-summary-grid">
            <div class="panel paid-leave-summary-panel">
                <div class="paid-leave-summary-head">基本情報</div>
                <dl class="paid-leave-kv-list">
                    <div>
                        <dt>スタッフID</dt>
                        <dd>{{ $summary['staff_id'] !== '' ? $summary['staff_id'] : '-' }}</dd>
                    </div>
                    <div>
                        <dt>氏名</dt>
                        <dd>{{ $summary['staff_name'] ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>入社年月日</dt>
                        <dd>{{ $summary['join_date'] ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>初回付与日</dt>
                        <dd>{{ $summary['first_grant_date'] ?: '-' }}</dd>
                    </div>
                </dl>
            </div>
            <div class="panel paid-leave-summary-panel">
                <div class="paid-leave-summary-head">状況</div>
                <dl class="paid-leave-kv-list">
                    <div>
                        <dt>残日数</dt>
                        <dd>{{ $summary['remaining_days'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt>消滅日数</dt>
                        <dd>{{ $summary['extinguish_days'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt>次回付与予定日</dt>
                        <dd>{{ $summary['next_grant_date'] ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>勤続年数</dt>
                        <dd>{{ $summary['service_years_label'] ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>次回付与日数</dt>
                        <dd>{{ $summary['next_grant_days'] ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="paid-leave-history-layout">
            <div class="panel paid-leave-table-panel">
                <div class="paid-leave-history-head">
                    <div class="paid-leave-summary-head">履歴</div>
                    <div class="paid-leave-history-head-right">
                        <div class="meta-count">
                            {{ ($historyPager['from'] ?? 0) }} - {{ ($historyPager['to'] ?? 0) }} / {{ ($historyPager['total'] ?? 0) }}件
                        </div>
                        <button type="button" class="btn btn-small js-history-add" {{ $selectedStaffId === '' ? 'disabled' : '' }}>新規追加</button>
                    </div>
                </div>

                <div class="history-add-panel" hidden>
                    <form method="POST" action="{{ route('admin.paid-leave.store') }}" class="history-edit-form">
                        @csrf
                        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                        <label>
                            <span>付与日</span>
                            <input type="date" name="addition_day" value="">
                        </label>
                        <label>
                            <span>付与日数</span>
                            <input type="number" step="0.5" name="remaining_day" value="">
                        </label>
                        <label>
                            <span>消滅数</span>
                            <input type="number" step="0.5" name="lost_num" value="">
                        </label>
                        <label>
                            <span>使用日</span>
                            <input type="date" name="date_use" value="">
                        </label>
                        <label>
                            <span>使用日数</span>
                            <input type="number" step="0.5" name="days_used" value="">
                        </label>
                        <div class="history-edit-actions">
                            <button type="submit" class="btn btn-primary btn-small">追加</button>
                            <button type="button" class="btn btn-small js-history-add-cancel">キャンセル</button>
                        </div>
                    </form>
                </div>

                <div class="paid-leave-table-wrap">
                    <table class="paid-leave-history-table">
                        <thead>
                            <tr>
                                <th>消滅期限日</th>
                                <th>付与日</th>
                                <th>付与日数</th>
                                <th>消滅数</th>
                                <th>使用日</th>
                                <th>使用日数</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($historyRows as $row)
                            <tr class="history-view-row" data-history-row="{{ $row['yukyu_no'] }}">
                                <td>{{ $row['expire_date'] ?? '-' }}</td>
                                <td>{{ $row['grant_date'] ?? '-' }}</td>
                                <td>{{ $row['grant_days'] ?? '-' }}</td>
                                <td>{{ $row['expire_days'] ?? '-' }}</td>
                                <td>{{ $row['used_date'] ?? '-' }}</td>
                                <td>{{ $row['used_days'] ?? '-' }}</td>
                                <td class="paid-leave-history-action-cell">
                                    <button type="button" class="btn btn-small js-history-edit" data-history-edit="{{ $row['yukyu_no'] }}">編集</button>
                                </td>
                            </tr>
                            <tr class="history-edit-row" data-history-edit-row="{{ $row['yukyu_no'] }}" hidden>
                                <td colspan="7">
                                    <div class="history-edit-stack">
                                        <form method="POST" action="{{ route('admin.paid-leave.update', ['yukyuNo' => $row['yukyu_no']]) }}" class="history-edit-form">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                            <input type="hidden" name="page" value="{{ $historyPager['page'] ?? 1 }}">
                                            <label>
                                                <span>付与日</span>
                                                <input type="date" name="addition_day" value="{{ $row['edit_addition_day'] }}">
                                            </label>
                                            <label>
                                                <span>付与日数</span>
                                                <input type="number" step="0.5" name="remaining_day" value="{{ $row['edit_remaining_day'] }}">
                                            </label>
                                            <label>
                                                <span>消滅数</span>
                                                <input type="number" step="0.5" name="lost_num" value="{{ $row['edit_lost_num'] }}">
                                            </label>
                                            <label>
                                                <span>使用日</span>
                                                <input type="date" name="date_use" value="{{ $row['edit_date_use'] }}">
                                            </label>
                                            <label>
                                                <span>使用日数</span>
                                                <input type="number" step="0.5" name="days_used" value="{{ $row['edit_days_used'] }}">
                                            </label>
                                            <div class="history-edit-actions">
                                                <button type="submit" class="btn btn-primary btn-small">保存</button>
                                                <button type="button" class="btn btn-small js-history-cancel" data-history-cancel="{{ $row['yukyu_no'] }}">キャンセル</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('admin.paid-leave.destroy', ['yukyuNo' => $row['yukyu_no']]) }}" class="history-delete-form" onsubmit="return confirm('この履歴を削除しますか？');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                            <input type="hidden" name="page" value="{{ $historyPager['page'] ?? 1 }}">
                                            <button type="submit" class="btn btn-small btn-danger">この履歴を削除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="paid-leave-empty-cell">スタッフを選択すると、付与・使用・消滅の履歴を表示します。</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="paid-leave-history-pager">
                    @php
                    $baseParams = ['staff_id' => $selectedStaffId];
                    $currentPage = $historyPager['page'] ?? 1;
                    @endphp
                    <a
                        href="{{ ($historyPager['has_prev'] ?? false) ? route('admin.paid-leave.index', $baseParams + ['page' => $currentPage - 1]) : '#' }}"
                        class="btn {{ ($historyPager['has_prev'] ?? false) ? '' : 'is-disabled' }}">前へ</a>
                    <span class="paid-leave-history-page-label">{{ $currentPage }} / {{ $historyPager['last_page'] ?? 1 }}</span>
                    <a
                        href="{{ ($historyPager['has_next'] ?? false) ? route('admin.paid-leave.index', $baseParams + ['page' => $currentPage + 1]) : '#' }}"
                        class="btn {{ ($historyPager['has_next'] ?? false) ? '' : 'is-disabled' }}">次へ</a>
                </div>
            </div>

            <aside class="panel paid-leave-schedule-side-panel">
                <div class="paid-leave-summary-head">勤続年数別付与日数</div>
                <div class="paid-leave-grant-schedule">
                    @foreach (($summary['grant_schedule'] ?? []) as $item)
                    <div class="paid-leave-grant-row">
                        <span>{{ $item['label'] }}</span>
                        <strong>{{ $item['days'] }}</strong>
                    </div>
                    @endforeach
                </div>
            </aside>
        </section>
    </div>

    @include('admin_v2.work.paid_leave.page_script')
</body>

</html>
