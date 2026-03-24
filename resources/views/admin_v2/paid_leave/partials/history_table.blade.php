<section class="history-layout">
    <div class="panel table-panel">
        <div class="history-head">
            <div class="summary-head">履歴</div>
            <div class="history-head-right">
                <div class="history-meta">
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
                    <span>加算日</span>
                    <input type="date" name="addition_day" value="">
                </label>
                <label>
                    <span>加算数</span>
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
        <div class="table-wrap">
            <table class="history-table">
                <thead>
                <tr>
                    <th>消滅予定日</th>
                    <th>加算日</th>
                    <th>加算数</th>
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
                        <td class="history-action-cell">
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
                                        <span>加算日</span>
                                        <input type="date" name="addition_day" value="{{ $row['edit_addition_day'] }}">
                                    </label>
                                    <label>
                                        <span>加算数</span>
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
                        <td colspan="7" class="empty-cell">スタッフを選択すると、加算・使用・消滅の履歴を表示します。</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="history-pager">
            @php
                $baseParams = ['staff_id' => $selectedStaffId];
                $currentPage = $historyPager['page'] ?? 1;
            @endphp
            <a
                href="{{ ($historyPager['has_prev'] ?? false) ? route('admin.paid-leave.index', $baseParams + ['page' => $currentPage - 1]) : '#' }}"
                class="btn {{ ($historyPager['has_prev'] ?? false) ? '' : 'is-disabled' }}"
            >前へ</a>
            <span class="history-page-label">{{ $currentPage }} / {{ $historyPager['last_page'] ?? 1 }}</span>
            <a
                href="{{ ($historyPager['has_next'] ?? false) ? route('admin.paid-leave.index', $baseParams + ['page' => $currentPage + 1]) : '#' }}"
                class="btn {{ ($historyPager['has_next'] ?? false) ? '' : 'is-disabled' }}"
            >次へ</a>
        </div>
    </div>
    <aside class="panel schedule-side-panel">
        <div class="summary-head">有給加算年数</div>
        <div class="grant-schedule">
            @foreach (($summary['grant_schedule'] ?? []) as $item)
                <div class="grant-row">
                    <span>{{ $item['label'] }}</span>
                    <strong>{{ $item['days'] }}</strong>
                </div>
            @endforeach
        </div>
    </aside>
</section>
