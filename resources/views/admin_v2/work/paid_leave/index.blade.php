<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 有休管理</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/paid_leave.css') }}">
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
                <div><dt>スタッフID</dt><dd>{{ $summary['staff_id'] !== '' ? $summary['staff_id'] : '-' }}</dd></div>
                <div><dt>氏名</dt><dd>{{ $summary['staff_name'] ?: '-' }}</dd></div>
                <div><dt>入社年月日</dt><dd>{{ $summary['join_date'] ?: '-' }}</dd></div>
                <div><dt>初回付与日</dt><dd>{{ $summary['first_grant_date'] ?: '-' }}</dd></div>
            </dl>
        </div>
        <div class="panel paid-leave-summary-panel">
            <div class="paid-leave-summary-head">状況</div>
            <dl class="paid-leave-kv-list">
                <div><dt>残日数</dt><dd>{{ $summary['remaining_days'] ?? '-' }}</dd></div>
                <div><dt>消滅日数</dt><dd>{{ $summary['extinguish_days'] ?? '-' }}</dd></div>
                <div><dt>次回付与予定日</dt><dd>{{ $summary['next_grant_date'] ?: '-' }}</dd></div>
                <div><dt>勤続年数</dt><dd>{{ $summary['service_years_label'] ?: '-' }}</dd></div>
                <div><dt>次回付与日数</dt><dd>{{ $summary['next_grant_days'] ?? '-' }}</dd></div>
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
                    class="btn {{ ($historyPager['has_prev'] ?? false) ? '' : 'is-disabled' }}"
                >前へ</a>
                <span class="paid-leave-history-page-label">{{ $currentPage }} / {{ $historyPager['last_page'] ?? 1 }}</span>
                <a
                    href="{{ ($historyPager['has_next'] ?? false) ? route('admin.paid-leave.index', $baseParams + ['page' => $currentPage + 1]) : '#' }}"
                    class="btn {{ ($historyPager['has_next'] ?? false) ? '' : 'is-disabled' }}"
                >次へ</a>
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