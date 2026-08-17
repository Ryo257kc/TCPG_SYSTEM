<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 基本シフト</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">基本シフト</h2>
            </div>

            @if ($statusMessage !== '')
            <div class="status">{{ $statusMessage }}</div>
            @endif

            @php
            $canManageBasicShift = (bool) ($canManageBasicShift ?? false);
            $canSelectBasicShiftStaff = (bool) ($canSelectBasicShiftStaff ?? false);
            @endphp

            <form method="get" class="filter-row">
                <label for="month">月:</label>
                <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
                @if ($canSelectBasicShiftStaff)
                <label for="staff_id">スタッフ:</label>
                <select id="staff_id" name="staff_id">
                    <option value="" @selected($selectedStaffId==='' )>--</option>
                    @foreach ($staffOptions as $staff)
                    <option value="{{ $staff['staff_id'] }}" @selected($staff['staff_id']===$selectedStaffId)>
                        {{ $staff['staff_id'] }} {{ $staff['staff_name'] }}
                    </option>
                    @endforeach
                </select>
                @endif

                <button type="submit">表示</button>
            </form>

            <div class="meta-row">
                <div class="staff-title">名前: {{ $selectedStaffName }}</div>
                <!-- <a class="btn" href="{{ route('admin.shift.change', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId]) }}">シフト変更へ戻る</a> -->
            </div>

            @if ($rowCount === 0)
            <div class="empty">基本シフトはありません。</div>
            @else
            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 80px;">
                        @if ($canManageBasicShift)
                        <col style="width: 100px;">
                        @endif
                    </colgroup>
                    <thead>
                        <tr>
                            <th>曜日</th>
                            <th>始業</th>
                            <th>退出</th>
                            <th>入出</th>
                            <th>終業</th>
                            <th>所定</th>
                            <th>勤務店舗</th>
                            @if ($canManageBasicShift)
                            <th class="action-cell">操作</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                        @php $formId = 'row-form-' . $row['shift_no']; @endphp
                        <tr data-row-form="{{ $formId }}">
                            <td>{{ $row['week'] }}</td>
                            <td>
                                <span class="display-value">{{ $row['shift_start'] }}</span>
                                <input form="{{ $formId }}" class="inline-input" type="time" name="shift_start" step="900" value="{{ $row['shift_start'] }}">
                            </td>
                            <td>
                                <span class="display-value">{{ $row['shift_exit'] }}</span>
                                <input form="{{ $formId }}" class="inline-input" type="time" name="shift_exit" step="900" value="{{ $row['shift_exit'] }}">
                            </td>
                            <td>
                                <span class="display-value">{{ $row['shift_in_out'] }}</span>
                                <input form="{{ $formId }}" class="inline-input" type="time" name="shift_in_out" step="900" value="{{ $row['shift_in_out'] }}">
                            </td>
                            <td>
                                <span class="display-value">{{ $row['shift_end'] }}</span>
                                <input form="{{ $formId }}" class="inline-input" type="time" name="shift_end" step="900" value="{{ $row['shift_end'] }}">
                            </td>
                            <td>
                                <span class="scheduled-hours-preview"></span>
                            </td>
                            <td>
                                <span class="display-value">{{ $row['shop_name'] }}</span>
                                <select form="{{ $formId }}" class="inline-input" name="shop_code">
                                    <option value="" @selected($row['shop_code']==='' || $row['shop_code']===null)>※勤務店舗</option>
                                    @foreach ($storeOptions as $store)
                                    <option value="{{ $store['store_code'] }}" @selected($row['shop_code']===$store['store_code'])>{{ $store['label'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            @if ($canManageBasicShift)
                            <td>
                                <form id="{{ $formId }}" class="row-form" method="post" action="{{ route('admin.basic-shift.inline_update', ['shiftNo' => $row['shift_no']]) }}">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                </form>
                                <button type="button" class="btn edit-trigger">編集</button>
                                <button form="{{ $formId }}" type="submit" name="_action" value="register" class="btn edit-only">登録</button>
                                <button type="button" class="btn edit-only cancel-trigger">戻す</button>
                                <button form="{{ $formId }}" type="submit" name="_action" value="clear" class="btn edit-only" formnovalidate>クリア</button>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            <div>
                <a href="{{ url()->previous() }}" class="btn btn_back">戻る</a>
            </div>
        </section>


    </main>
    <script>
        (function() {
            // 所定時間の計算はApp\Support\AttendanceTime::scheduledHours()と同じ式にする
            // （始業〜退出 + 入出〜終業。退出/入出のどちらか片方しか無ければ始業〜終業のみ）。
            function parseMinutes(value) {
                if (!value) return null;
                var parts = value.split(':');
                if (parts.length < 2) return null;
                var h = parseInt(parts[0], 10);
                var m = parseInt(parts[1], 10);
                if (isNaN(h) || isNaN(m)) return null;
                if (h === 0 && m === 0) return null;
                return (h * 60) + m;
            }

            function minutesBetween(startMinutes, endMinutes) {
                if (endMinutes < startMinutes) endMinutes += 24 * 60;
                return Math.max(0, endMinutes - startMinutes);
            }

            function scheduledHours(startValue, leaveValue, breakOutValue, endValue) {
                var startMinutes = parseMinutes(startValue);
                var endMinutes = parseMinutes(endValue);
                if (startMinutes === null || endMinutes === null) return 0;

                var leaveMinutes = parseMinutes(leaveValue);
                var breakOutMinutes = parseMinutes(breakOutValue);

                if (leaveMinutes !== null && breakOutMinutes !== null) {
                    return (minutesBetween(startMinutes, leaveMinutes) + minutesBetween(breakOutMinutes, endMinutes)) / 60;
                }

                return minutesBetween(startMinutes, endMinutes) / 60;
            }

            function formatHours(num) {
                if (Math.abs(num - Math.round(num)) < 0.00001) return String(Math.round(num));
                return String(Math.round(num * 100) / 100);
            }

            var rows = document.querySelectorAll('tr[data-row-form]');
            rows.forEach(function(row) {
                var formId = row.getAttribute('data-row-form');
                var selectorBase = '[form="' + formId + '"]';
                var start = document.querySelector('input[name="shift_start"]' + selectorBase);
                var exit = document.querySelector('input[name="shift_exit"]' + selectorBase);
                var inOut = document.querySelector('input[name="shift_in_out"]' + selectorBase);
                var end = document.querySelector('input[name="shift_end"]' + selectorBase);
                var shop = document.querySelector('select[name="shop_code"]' + selectorBase);
                var editBtn = row.querySelector('.edit-trigger');
                var cancelBtn = row.querySelector('.cancel-trigger');
                var preview = row.querySelector('.scheduled-hours-preview');

                if (start && exit && inOut && end && preview) {
                    var updatePreview = function() {
                        var startMinutes = parseMinutes(start.value);
                        var endMinutes = parseMinutes(end.value);
                        if (startMinutes === null || endMinutes === null) {
                            preview.textContent = '';
                            return;
                        }
                        preview.textContent = formatHours(scheduledHours(start.value, exit.value, inOut.value, end.value));
                    };
                    [start, exit, inOut, end].forEach(function(el) {
                        el.addEventListener('input', updatePreview);
                        el.addEventListener('change', updatePreview);
                    });
                    updatePreview();
                    row.updateScheduledHoursPreview = updatePreview;
                }

                // 編集・登録操作は編集権限がある行だけに存在する（canManageBasicShift）。
                if (!start || !exit || !inOut || !end || !shop || !editBtn) return;

                var initial = {
                    start: start.value,
                    exit: exit.value,
                    inOut: inOut.value,
                    end: end.value,
                    shop: shop.value
                };

                function syncRequired() {
                    var hasAny = !!(start.value || exit.value || inOut.value || end.value);
                    shop.required = hasAny;
                    shop.setCustomValidity(hasAny && !shop.value ? '時間を入力した場合、勤務店舗は必須です。' : '');

                    var requireEnd = !!start.value;
                    end.required = requireEnd;
                    end.setCustomValidity(requireEnd && !end.value ? '始業を入力した場合、終業は必須です。' : '');

                    var requireInOut = !!exit.value;
                    inOut.required = requireInOut;
                    inOut.setCustomValidity(requireInOut && !inOut.value ? '退出を入力した場合、入出は必須です。' : '');
                }

                function setEditing(on) {
                    if (on) row.classList.add('is-editing');
                    else row.classList.remove('is-editing');
                    syncRequired();
                }

                editBtn.addEventListener('click', function() {
                    setEditing(true);
                });
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function() {
                        start.value = initial.start;
                        exit.value = initial.exit;
                        inOut.value = initial.inOut;
                        end.value = initial.end;
                        shop.value = initial.shop;
                        setEditing(false);
                        if (row.updateScheduledHoursPreview) row.updateScheduledHoursPreview();
                    });
                }

                [start, exit, inOut, end, shop].forEach(function(el) {
                    el.addEventListener('input', syncRequired);
                    el.addEventListener('change', syncRequired);
                });
                syncRequired();
            });
        })();
    </script>
</body>

</html>
