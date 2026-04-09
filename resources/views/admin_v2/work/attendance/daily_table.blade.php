@if (!empty($detailStaff))
    @php
        $hasStaffApproval = collect($dailyRows)->contains(static fn ($row) => ($row['has_staff_approval'] ?? '0') === '1');
        $hasManagerApproval = collect($dailyRows)->contains(static fn ($row) => ($row['has_manager_approval'] ?? '0') === '1');
    @endphp
    <div class="daily-panel">
        <div class="daily-head">
            <div class="daily-title">{{ $detailStaff['staff_id'] }} {{ $detailStaff['staff_name'] }} の日別</div>
            <div class="daily-statuses">
                <span @class(['daily-status-badge', 'is-on' => $hasStaffApproval])>本人承認: {!! $hasStaffApproval ? '有' : '無' !!}</span>
                <span @class(['daily-status-badge', 'is-on' => $hasManagerApproval])>管理者承認: {!! $hasManagerApproval ? '有' : '無' !!}</span>
            </div>
        </div>
        @if (!empty($dailySummary))
            <div class="daily-summary">
                <div class="daily-summary-group">
                    <div class="daily-summary-title">日数</div>
                    <div class="daily-summary-row">
                        <div class="daily-summary-label">出勤日数</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['work_days'] ?? 0), 0) }}</div>
                        <div class="daily-summary-label">休出日数</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['holiday_work_days'] ?? 0), 0) }}</div>
                        <div class="daily-summary-label">欠勤日数</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['absence_days'] ?? 0), 0) }}</div>
                        <div class="daily-summary-label">遅早日数</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['late_early_days'] ?? 0), 0) }}</div>
                        <div class="daily-summary-label">有休日数</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['paid_leave_days'] ?? 0), 2) }}</div>
                    </div>
                </div>
                <div class="daily-summary-group">
                    <div class="daily-summary-title">所定</div>
                    <div class="daily-summary-row">
                        <div class="daily-summary-label">シフト所定</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['shift_scheduled_total'] ?? 0), 2) }}</div>
                        <div class="daily-summary-label">打刻所定</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['actual_scheduled_total'] ?? 0), 2) }}</div>
                        <div class="daily-summary-label">変更実績所定</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['change_scheduled_total'] ?? 0), 2) }}</div>
                    </div>
                </div>
                <div class="daily-summary-group">
                    <div class="daily-summary-title">時間</div>
                    <div class="daily-summary-row">
                        <div class="daily-summary-label">残業集計</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['overtime_total'] ?? 0), 2) }}</div>
                        <div class="daily-summary-label">深夜集計</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['night_overtime_total'] ?? 0), 2) }}</div>
                        @foreach (($dailySummary['category_totals'] ?? []) as $category => $total)
                            <div class="daily-summary-label">{{ $category }}時間</div>
                            <div class="daily-summary-value">{{ number_format((float) $total, 2) }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        @if (!empty($dailyRows))
            @php
                $isEditable = false;
            @endphp
            <div class="daily-wrap">
                <table class="daily-table cen">
                    <thead>
                    <tr>
                        <th rowspan="2">日付</th>
                        <th colspan="3">勤怠</th>
                        <th class="f-sen" rowspan="2">有休<br>取得</th>
                        <th class="daily-group-start f-sen" colspan="5">打刻</th>
                        <th class="daily-group-start f-sen" colspan="5">シフト</th>
                        <th class="daily-group-start f-sen" colspan="7">変更実績</th>
                        <th class="daily-group-start daily-work-store-col" rowspan="2">勤務店舗</th>
                        <th colspan="2">備考</th>
                    </tr>
                    <tr>
                        <th>休日</th>
                        <th>区分</th>
                        <th>時間</th>
                        <th class="daily-group-start">始業</th>
                        <th>退出</th>
                        <th>入出</th>
                        <th>終業</th>
                        <th class="f-sen">所定</th>
                        <th class="daily-group-start">始業</th>
                        <th>退出</th>
                        <th>入出</th>
                        <th>終業</th>
                        <th class="f-sen">所定</th>
                        <th class="daily-group-start">始業</th>
                        <th>退出</th>
                        <th>入出</th>
                        <th>終業</th>
                        <th>所定</th>
                        <th>残業</th>
                        <th class="f-sen">深夜</th>
                        <th>勤怠</th>
                        <th>差戻し</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($dailyRows as $row)
                        @php
                            $hasShift = collect([
                                $row['shift_start'] ?? '',
                                $row['shift_leave'] ?? '',
                                $row['shift_break_out'] ?? '',
                                $row['shift_end'] ?? '',
                            ])->contains(static fn ($value) => trim((string) $value) !== '');
                            $isDimmedRow = !$hasShift;
                            $shiftScheduled = (float) ($row['shift_scheduled'] !== '' ? $row['shift_scheduled'] : 0);
                            $changeScheduled = (float) ($row['change_scheduled'] !== '' ? $row['change_scheduled'] : 0);
                            $isChangeScheduledOver = $shiftScheduled > 0 && $changeScheduled > $shiftScheduled;
                            $formId = 'attendance-daily-' . str_replace('-', '', (string) $row['work_date']);
                        @endphp
                        <tr @class(['attendance-daily-row', 'daily-row-muted' => $isDimmedRow])>
                            <td>{{ $row['date_label'] }}</td>
                            <td>{{ $row['holiday_category'] }}</td>
                            <td>
                                <span class="daily-view">{{ $row['attendance_category'] }}</span>
                                @if ($isEditable)
                                    <select class="daily-edit daily-edit-input daily-edit-input-category" name="attendance_category" form="{{ $formId }}" data-original="{{ $row['attendance_category'] }}">
                                        <option value=""></option>
                                        @foreach ($attendanceCategories as $attendanceCategory)
                                            <option value="{{ $attendanceCategory }}" @selected($row['attendance_category'] === $attendanceCategory)>{{ $attendanceCategory }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['category_time'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input daily-edit-input-num" type="number" step="0.01" name="category_time" value="{{ $row['category_time'] }}" form="{{ $formId }}" data-original="{{ $row['category_time'] }}">
                                @endif
                            </td>
                            <td class="f-sen">
                                <span class="daily-view">{{ $row['paid_leave_used'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input daily-edit-input-num" type="number" step="0.01" name="paid_leave_used" value="{{ $row['paid_leave_used'] }}" form="{{ $formId }}" data-original="{{ $row['paid_leave_used'] }}">
                                @endif
                            </td>
                            <td class="daily-group-start">{{ $row['actual_start'] }}</td>
                            <td>{{ $row['actual_leave'] }}</td>
                            <td>{{ $row['actual_break_out'] }}</td>
                            <td>{{ $row['actual_end'] }}</td>
                            <td class="f-sen">{{ $row['actual_scheduled'] }}</td>
                            <td class="daily-group-start">
                                <span class="daily-view">{{ $row['shift_start'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input" type="text" name="shift_start" value="{{ $row['shift_start'] }}" form="{{ $formId }}" data-original="{{ $row['shift_start'] }}">
                                @endif
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['shift_leave'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input" type="text" name="shift_leave" value="{{ $row['shift_leave'] }}" form="{{ $formId }}" data-original="{{ $row['shift_leave'] }}">
                                @endif
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['shift_break_out'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input" type="text" name="shift_break_out" value="{{ $row['shift_break_out'] }}" form="{{ $formId }}" data-original="{{ $row['shift_break_out'] }}">
                                @endif
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['shift_end'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input" type="text" name="shift_end" value="{{ $row['shift_end'] }}" form="{{ $formId }}" data-original="{{ $row['shift_end'] }}">
                                @endif
                            </td>
                            <td class="f-sen">{{ $row['shift_scheduled'] }}</td>
                            <td class="daily-group-start">
                                <span class="daily-view">{{ $row['change_start'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input" type="text" name="change_start" value="{{ $row['change_start'] }}" form="{{ $formId }}" data-original="{{ $row['change_start'] }}">
                                @endif
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['change_leave'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input" type="text" name="change_leave" value="{{ $row['change_leave'] }}" form="{{ $formId }}" data-original="{{ $row['change_leave'] }}">
                                @endif
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['change_break_out'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input" type="text" name="change_break_out" value="{{ $row['change_break_out'] }}" form="{{ $formId }}" data-original="{{ $row['change_break_out'] }}">
                                @endif
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['change_end'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input" type="text" name="change_end" value="{{ $row['change_end'] }}" form="{{ $formId }}" data-original="{{ $row['change_end'] }}">
                                @endif
                            </td>
                            <td @class(['daily-value-alert' => $isChangeScheduledOver])>
                                <span class="daily-view">{{ $row['change_scheduled'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input daily-edit-input-num" type="number" step="0.01" name="change_scheduled" value="{{ $row['change_scheduled'] }}" form="{{ $formId }}" data-original="{{ $row['change_scheduled'] }}">
                                @endif
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['overtime'] }}</span>
                                @if ($isEditable)
                                    <input class="daily-edit daily-edit-input daily-edit-input-num" type="number" step="0.01" name="overtime" value="{{ $row['overtime'] }}" form="{{ $formId }}" data-original="{{ $row['overtime'] }}">
                                @endif
                            </td>
                            <td class="f-sen">
                                <span class="daily-view">{{ $row['night_overtime'] }}</span>
                            </td>
                            <td class="daily-group-start daily-work-store-col">
                                <span class="daily-view">{{ $row['work_store'] }}</span>
                                @if ($isEditable)
                                    <select class="daily-edit daily-edit-input daily-edit-input-store" name="work_store" form="{{ $formId }}" data-original="{{ $row['work_store'] }}">
                                        <option value=""></option>
                                        @foreach (($storeOptions ?? []) as $storeOption)
                                            <option value="{{ $storeOption['value'] }}" @selected($row['work_store'] === $storeOption['value'])>{{ $storeOption['label'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td class="daily-note-first{{ $isEditable ? ' daily-note-cell' : '' }}" title="{{ $row['timecard_note'] }}" @if ($isEditable) ondblclick="openDailyNoteEditor(this)" @endif>
                                <span class="daily-view daily-note-text">{{ $row['timecard_note'] }}</span>
                                @if ($isEditable)
                                    <textarea class="daily-note-textarea" name="timecard_note" form="{{ $formId }}" data-original="{{ $row['timecard_note'] }}">{{ $row['timecard_note'] }}</textarea>
                                @endif
                            </td>
                            <td class="{{ $isEditable ? ' daily-note-cell' : '' }}" title="{{ $row['return_note'] }}" @if ($isEditable) ondblclick="openDailyNoteEditor(this)" @endif>
                                <span class="daily-view daily-note-text">{{ $row['return_note'] }}</span>
                                @if ($isEditable)
                                    <textarea class="daily-note-textarea" name="return_note" form="{{ $formId }}" data-original="{{ $row['return_note'] }}">{{ $row['return_note'] }}</textarea>
                                @endif
                            </td>
                            <td>
                                @if ($isEditable)
                                    <div class="daily-actions daily-view">
                                        <button class="btn btn-small daily-btn-muted" type="button" data-action="edit">編集</button>
                                    </div>
                                    <div class="daily-actions daily-edit">
                                        <button class="btn btn-small" type="submit" form="{{ $formId }}">保存</button>
                                        <button class="btn btn-small daily-btn-muted" type="button" data-action="cancel">キャンセル</button>
                                    </div>
                                    <form id="{{ $formId }}" method="post" action="{{ route('admin.attendance.update-daily') }}">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                        <input type="hidden" name="time_card_key" value="{{ $row['time_card_key'] }}">
                                        <input type="hidden" name="work_date" value="{{ $row['work_date'] }}">
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <script>
                function openDailyNoteEditor(cell) {
                    const row = cell.closest('.attendance-daily-row');
                    if (!row) {
                        return;
                    }

                    row.classList.add('is-editing');
                    cell.classList.add('note-editing');

                    const textarea = cell.querySelector('.daily-note-textarea');
                    if (textarea) {
                        textarea.focus();
                        textarea.setSelectionRange(textarea.value.length, textarea.value.length);
                    }
                }

                document.querySelectorAll('.attendance-daily-row').forEach((row) => {
                    const editButton = row.querySelector('[data-action="edit"]');
                    const cancelButton = row.querySelector('[data-action="cancel"]');
                    const fields = row.querySelectorAll('[data-original]');
                    const noteCells = row.querySelectorAll('.daily-note-cell');

                    if (editButton) {
                        editButton.addEventListener('click', () => row.classList.add('is-editing'));
                    }

                    if (cancelButton) {
                        cancelButton.addEventListener('click', () => {
                            fields.forEach((field) => {
                                field.value = field.dataset.original ?? '';
                            });
                            noteCells.forEach((cell) => cell.classList.remove('note-editing'));
                            row.classList.remove('is-editing');
                        });
                    }
                });
            </script>
        @else
            @include('admin_v2.work.attendance.daily_empty_state')
        @endif
    </div>
@endif
