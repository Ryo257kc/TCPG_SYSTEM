@if (!empty($detailStaff))
    @php
        $hasStaffApproval = collect($dailyRows)->contains(static fn ($row) => ($row['has_staff_approval'] ?? '0') === '1');
        $hasManagerApproval = collect($dailyRows)->contains(static fn ($row) => ($row['has_manager_approval'] ?? '0') === '1');
        $attendanceCategories = ["\u{632F}\u{51FA}", "\u{4EE3}\u{51FA}", "\u{632F}\u{4F11}", "\u{4EE3}\u{4F11}", "\u{6B20}\u{52E4}", "\u{6709}\u{4F11}", "\u{6709}\u{534A}", "\u{4F11}\u{51FA}", "\u{6CD5}\u{51FA}", "\u{51FA}\u{5F35}", "\u{9045}\u{65E9}"];
    @endphp
    <div class="daily-panel">
        <div class="daily-head">
            <div class="daily-title">{{ $detailStaff['staff_id'] }} {{ $detailStaff['staff_name'] }} &#12398;&#26085;&#21029;</div>
            <div class="daily-statuses">
                <span @class(['daily-status-badge', 'is-on' => $hasStaffApproval])>&#26412;&#20154;&#25215;&#35469;: {!! $hasStaffApproval ? '&#26377;' : '&#28961;' !!}</span>
                <span @class(['daily-status-badge', 'is-on' => $hasManagerApproval])>&#31649;&#29702;&#32773;&#25215;&#35469;: {!! $hasManagerApproval ? '&#26377;' : '&#28961;' !!}</span>
            </div>
        </div>
        @if (!empty($dailySummary))
            <div class="daily-summary">
                <div class="daily-summary-group">
                    <div class="daily-summary-title">&#26085;&#25968;</div>
                    <div class="daily-summary-row">
                        <div class="daily-summary-label">&#20986;&#21220;&#26085;&#25968;</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['work_days'] ?? 0), 0) }}</div>
                        <div class="daily-summary-label">&#20241;&#20986;&#26085;&#25968;</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['holiday_work_days'] ?? 0), 0) }}</div>
                        <div class="daily-summary-label">&#27424;&#21220;&#26085;&#25968;</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['absence_days'] ?? 0), 0) }}</div>
                        <div class="daily-summary-label">&#36933;&#26089;&#26085;&#25968;</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['late_early_days'] ?? 0), 0) }}</div>
                        <div class="daily-summary-label">&#26377;&#20241;&#26085;&#25968;</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['paid_leave_days'] ?? 0), 2) }}</div>
                    </div>
                </div>
                <div class="daily-summary-group">
                    <div class="daily-summary-title">&#25152;&#23450;</div>
                    <div class="daily-summary-row">
                        <div class="daily-summary-label">&#12471;&#12501;&#12488;&#25152;&#23450;&#21512;&#35336;</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['shift_scheduled_total'] ?? 0), 2) }}</div>
                        <div class="daily-summary-label">&#25171;&#21051;&#25152;&#23450;&#21512;&#35336;</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['actual_scheduled_total'] ?? 0), 2) }}</div>
                        <div class="daily-summary-label">&#22793;&#26356;&#23455;&#32318;&#25152;&#23450;&#21512;&#35336;</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['change_scheduled_total'] ?? 0), 2) }}</div>
                    </div>
                </div>
                <div class="daily-summary-group">
                    <div class="daily-summary-title">&#26178;&#38291;</div>
                    <div class="daily-summary-row">
                        <div class="daily-summary-label">&#27531;&#26989;&#38598;&#35336;</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['overtime_total'] ?? 0), 2) }}</div>
                        <div class="daily-summary-label">&#28145;&#22812;&#38598;&#35336;</div>
                        <div class="daily-summary-value">{{ number_format((float) ($dailySummary['night_overtime_total'] ?? 0), 2) }}</div>
                        @foreach (($dailySummary['category_totals'] ?? []) as $category => $total)
                            <div class="daily-summary-label">{{ $category }}&#26178;&#38291;</div>
                            <div class="daily-summary-value">{{ number_format((float) $total, 2) }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        @if (!empty($dailyRows))
            <div class="daily-wrap">
                <table class="daily-table">
                    <thead>
                    <tr>
                        <th rowspan="2">&#26085;&#20184;</th>
                        <th colspan="3">&#21306;&#20998;</th>
                        <th rowspan="2">&#26377;&#20241;<br>&#20351;&#29992;</th>
                        <th rowspan="2">&#21220;&#21209;&#24215;&#33303;</th>
                        <th class="daily-group-start" colspan="5">&#25171;&#21051;</th>
                        <th class="daily-group-start" colspan="5">&#12471;&#12501;&#12488;</th>
                        <th class="daily-group-start" colspan="7">&#22793;&#26356;&#23455;&#32318;</th>
                        <th class="daily-group-start" colspan="2">&#20633;&#32771;</th>
                    </tr>
                    <tr>
                        <th>&#20241;&#26085;</th>
                        <th>&#21220;&#24608;</th>
                        <th>&#26178;&#38291;</th>
                        <th class="daily-group-start">&#22987;&#26989;</th>
                        <th>&#36864;&#20986;</th>
                        <th>&#20837;&#20986;</th>
                        <th>&#32066;&#26989;</th>
                        <th>&#25152;&#23450;</th>
                        <th class="daily-group-start">&#22987;&#26989;</th>
                        <th>&#36864;&#20986;</th>
                        <th>&#20837;&#20986;</th>
                        <th>&#32066;&#26989;</th>
                        <th>&#25152;&#23450;</th>
                        <th class="daily-group-start">&#22987;&#26989;</th>
                        <th>&#36864;&#20986;</th>
                        <th>&#20837;&#20986;</th>
                        <th>&#32066;&#26989;</th>
                        <th>&#25152;&#23450;</th>
                        <th>&#27531;&#26989;</th>
                        <th>&#28145;&#22812;</th>
                        <th class="daily-group-start">&#21220;&#24608;</th>
                        <th>&#24046;&#25147;&#12375;</th>
                        <th>&#25805;&#20316;</th>
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
                                <select class="daily-edit daily-edit-input daily-edit-input-category" name="attendance_category" form="{{ $formId }}" data-original="{{ $row['attendance_category'] }}">
                                    <option value=""></option>
                                    @foreach ($attendanceCategories as $attendanceCategory)
                                        <option value="{{ $attendanceCategory }}" @selected($row['attendance_category'] === $attendanceCategory)>{{ $attendanceCategory }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="num">
                                <span class="daily-view">{{ $row['category_time'] }}</span>
                                <input class="daily-edit daily-edit-input daily-edit-input-num" type="number" step="0.01" name="category_time" value="{{ $row['category_time'] }}" form="{{ $formId }}" data-original="{{ $row['category_time'] }}">
                            </td>
                            <td class="num">
                                <span class="daily-view">{{ $row['paid_leave_used'] }}</span>
                                <input class="daily-edit daily-edit-input daily-edit-input-num" type="number" step="0.01" name="paid_leave_used" value="{{ $row['paid_leave_used'] }}" form="{{ $formId }}" data-original="{{ $row['paid_leave_used'] }}">
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['work_store'] }}</span>
                                <select class="daily-edit daily-edit-input daily-edit-input-store" name="work_store" form="{{ $formId }}" data-original="{{ $row['work_store'] }}">
                                    <option value=""></option>
                                    @foreach (($storeOptions ?? []) as $storeOption)
                                        <option value="{{ $storeOption['value'] }}" @selected($row['work_store'] === $storeOption['value'])>{{ $storeOption['label'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="daily-group-start">{{ $row['actual_start'] }}</td>
                            <td>{{ $row['actual_leave'] }}</td>
                            <td>{{ $row['actual_break_out'] }}</td>
                            <td>{{ $row['actual_end'] }}</td>
                            <td>{{ $row['actual_scheduled'] }}</td>
                            <td class="daily-group-start">
                                <span class="daily-view">{{ $row['shift_start'] }}</span>
                                <input class="daily-edit daily-edit-input" type="text" name="shift_start" value="{{ $row['shift_start'] }}" form="{{ $formId }}" data-original="{{ $row['shift_start'] }}">
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['shift_leave'] }}</span>
                                <input class="daily-edit daily-edit-input" type="text" name="shift_leave" value="{{ $row['shift_leave'] }}" form="{{ $formId }}" data-original="{{ $row['shift_leave'] }}">
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['shift_break_out'] }}</span>
                                <input class="daily-edit daily-edit-input" type="text" name="shift_break_out" value="{{ $row['shift_break_out'] }}" form="{{ $formId }}" data-original="{{ $row['shift_break_out'] }}">
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['shift_end'] }}</span>
                                <input class="daily-edit daily-edit-input" type="text" name="shift_end" value="{{ $row['shift_end'] }}" form="{{ $formId }}" data-original="{{ $row['shift_end'] }}">
                            </td>
                            <td>{{ $row['shift_scheduled'] }}</td>
                            <td class="daily-group-start">
                                <span class="daily-view">{{ $row['change_start'] }}</span>
                                <input class="daily-edit daily-edit-input" type="text" name="change_start" value="{{ $row['change_start'] }}" form="{{ $formId }}" data-original="{{ $row['change_start'] }}">
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['change_leave'] }}</span>
                                <input class="daily-edit daily-edit-input" type="text" name="change_leave" value="{{ $row['change_leave'] }}" form="{{ $formId }}" data-original="{{ $row['change_leave'] }}">
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['change_break_out'] }}</span>
                                <input class="daily-edit daily-edit-input" type="text" name="change_break_out" value="{{ $row['change_break_out'] }}" form="{{ $formId }}" data-original="{{ $row['change_break_out'] }}">
                            </td>
                            <td>
                                <span class="daily-view">{{ $row['change_end'] }}</span>
                                <input class="daily-edit daily-edit-input" type="text" name="change_end" value="{{ $row['change_end'] }}" form="{{ $formId }}" data-original="{{ $row['change_end'] }}">
                            </td>
                            <td @class(['daily-value-alert' => $isChangeScheduledOver])>
                                <span class="daily-view">{{ $row['change_scheduled'] }}</span>
                                <input class="daily-edit daily-edit-input daily-edit-input-num" type="number" step="0.01" name="change_scheduled" value="{{ $row['change_scheduled'] }}" form="{{ $formId }}" data-original="{{ $row['change_scheduled'] }}">
                            </td>
                            <td class="num">
                                <span class="daily-view">{{ $row['overtime'] }}</span>
                                <input class="daily-edit daily-edit-input daily-edit-input-num" type="number" step="0.01" name="overtime" value="{{ $row['overtime'] }}" form="{{ $formId }}" data-original="{{ $row['overtime'] }}">
                            </td>
                            <td class="num">
                                <span class="daily-view">{{ $row['night_overtime'] }}</span>
                            </td>
                            <td class="daily-group-start daily-note-cell" title="{{ $row['timecard_note'] }}" ondblclick="openDailyNoteEditor(this)">
                                <span class="daily-view daily-note-text">{{ $row['timecard_note'] }}</span>
                                <textarea class="daily-note-textarea" name="timecard_note" form="{{ $formId }}" data-original="{{ $row['timecard_note'] }}">{{ $row['timecard_note'] }}</textarea>
                            </td>
                            <td class="daily-note-cell" title="{{ $row['return_note'] }}" ondblclick="openDailyNoteEditor(this)">
                                <span class="daily-view daily-note-text">{{ $row['return_note'] }}</span>
                                <textarea class="daily-note-textarea" name="return_note" form="{{ $formId }}" data-original="{{ $row['return_note'] }}">{{ $row['return_note'] }}</textarea>
                            </td>
                            <td>
                                <div class="daily-actions daily-view">
                                    <button class="btn btn-small daily-btn-muted" type="button" data-action="edit">&#32232;&#38598;</button>
                                </div>
                                <div class="daily-actions daily-edit">
                                    <button class="btn btn-small" type="submit" form="{{ $formId }}">&#20445;&#23384;</button>
                                    <button class="btn btn-small daily-btn-muted" type="button" data-action="cancel">&#21462;&#28040;</button>
                                </div>
                                <form id="{{ $formId }}" method="post" action="{{ route('admin.attendance.update-daily') }}">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                    <input type="hidden" name="time_card_key" value="{{ $row['time_card_key'] }}">
                                    <input type="hidden" name="work_date" value="{{ $row['work_date'] }}">
                                </form>
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
            @include('admin_v2.attendance.partials.daily_empty_state')
        @endif
    </div>
@endif
