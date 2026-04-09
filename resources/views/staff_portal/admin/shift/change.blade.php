<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - シフト変更</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/daily_table_item_plain.css') }}">
</head>
<body>
<main class="container shift-change-page">
        @php
            $showPunchColumns = (bool) ($showPunchColumns ?? false);
            $isSelfOnly = (bool) ($isSelfOnly ?? false);
            $updateRouteName = (string) ($updateRouteName ?? 'admin.shift.update');
        @endphp
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel">
        <h2>シフト変更</h2>

        <form method="get" class="filter-row">
            <label for="month">月</label>
            <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
            <label for="staff_id" @if($isSelfOnly) style="display:none" @endif>スタッフ:</label>
            <select id="staff_id" name="staff_id" @if($isSelfOnly) style="display:none" @endif>
                <option value="" @selected($selectedStaffId === '')>--</option>
                @foreach ($staffOptions as $staff)
                    <option value="{{ $staff['staff_id'] }}" @selected($staff['staff_id'] === $selectedStaffId)}>
                        {{ $staff['staff_id'] }} {{ $staff['staff_name'] }}
                    </option>
                @endforeach
            </select>
            <button type="submit">表示</button>
        </form>
        @if ($selectedStaffName !== '')
            <div class="staff-title-row">
                <div class="staff-title">氏名: {{ $selectedStaffName }}</div>
                <a class="btn staff-basic-btn" href="{{ route('admin.basic-shift', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId]) }}">基本シフト</a>
            </div>
        @endif

        @if ($rowCount === 0)
            <div class="empty">シフトが登録されていません。</div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        @if ($showPunchColumns)
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        @endif
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 80px;">
                        <col style="width: 100px;">
                    </colgroup>

                    <thead>
                    <tr>
                        <th>日付</th>
                        <th>休日区分</th>
                        @if ($showPunchColumns)
                        <th>始業</th>
                        <th>退出</th>
                        <th>入出</th>
                        <th>終業</th>
                        @endif
                        <th>始業</th>
                        <th>退出</th>
                        <th>入出</th>
                        <th>終業</th>
                        <th>店舗</th>
                        <th class="action-cell"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $row)
                        @php $formId = 'shift-row-' . $row['time_no']; @endphp
                        <tr class="{{ in_array($row['holiday_category'] ?? '', ['休日', '祝日', '法休'], true) ? 'holiday' : '' }}" data-row-form="{{ $formId }}">
                            <td>{{ $row['date_label'] }}</td>
                            <td>
                                <span class="display-value">{{ $row['holiday_category'] }}</span>
                                <select form="{{ $formId }}" class="inline-input" name="holiday_category">
                                    <option value="" @selected(($row['holiday_category'] ?? '') === '')>--</option>
                                    <option value="休日" @selected(($row['holiday_category'] ?? '') === '休日')>休日</option>
                                    <option value="祝日" @selected(($row['holiday_category'] ?? '') === '祝日')>祝日</option>
                                    <option value="法休" @selected(($row['holiday_category'] ?? '') === '法休')>法休</option>
                                </select>
                            </td>
                            @if ($showPunchColumns)
                            <td>{{ $row['actual_start'] ?? '' }}</td>
                            <td>{{ $row['actual_exit'] ?? '' }}</td>
                            <td>{{ $row['actual_in_out'] ?? '' }}</td>
                            <td>{{ $row['actual_end'] ?? '' }}</td>
                            @endif
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
                                <span class="display-value">{{ $row['shop_name'] }}</span>
                                <select form="{{ $formId }}" class="inline-input" name="shop_code">
                                    <option value="" @selected($row['shop_code'] === '' || $row['shop_code'] === null)>-- 店舗選択 --</option>
                                    @foreach (($storeOptions ?? []) as $store)
                                        <option value="{{ $store['store_code'] }}" @selected($row['shop_code'] === $store['store_code'])>{{ $store['label'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="action-cell">
                                <form id="{{ $formId }}" class="row-form" method="post" action="{{ route($updateRouteName, ['timeNo' => $row['time_no']]) }}">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                    @if (!$isSelfOnly)
                                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                    @endif
                                </form>
                                <button type="button" class="btn edit-trigger">編集</button>
                                <button form="{{ $formId }}" type="submit" name="_action" value="register" class="btn edit-only">登録</button>
                                <button form="{{ $formId }}" type="submit" name="_action" value="clear" class="btn edit-only" formnovalidate>クリア</button>
                                <button type="button" class="btn edit-only cancel-trigger">戻す</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>


</main>
<script>
    (function () {
        var rows = document.querySelectorAll('tr[data-row-form]');
        rows.forEach(function (row) {
            var editBtn = row.querySelector('.edit-trigger');
            var cancelBtn = row.querySelector('.cancel-trigger');
            var formId = row.getAttribute('data-row-form');
            var selectorBase = '[form="' + formId + '"]';
            var start = document.querySelector('input[name="shift_start"]' + selectorBase);
            var exit = document.querySelector('input[name="shift_exit"]' + selectorBase);
            var inOut = document.querySelector('input[name="shift_in_out"]' + selectorBase);
            var end = document.querySelector('input[name="shift_end"]' + selectorBase);
            var shop = document.querySelector('select[name="shop_code"]' + selectorBase);
            if (!editBtn || !start || !exit || !inOut || !end || !shop) return;

            var initial = { start: start.value, exit: exit.value, inOut: inOut.value, end: end.value, shop: shop.value };

            function syncRequired() {
                var hasAny = !!(start.value || exit.value || inOut.value || end.value);
                shop.required = hasAny;
                shop.setCustomValidity(hasAny && !shop.value ? '時間を登録する場合は、店舗を選択してください。' : '');

                var needEnd = !!start.value;
                end.required = needEnd;
                end.setCustomValidity(needEnd && !end.value ? '始業を入力した場合は、終業を入力してください。' : '');

                var needInOut = !!exit.value;
                inOut.required = needInOut;
                inOut.setCustomValidity(needInOut && !inOut.value ? '退出を入力した場合は、入出を入力してください。' : '');
            }

            function setEditing(on) {
                if (on) row.classList.add('is-editing');
                else row.classList.remove('is-editing');
                syncRequired();
            }

            editBtn.addEventListener('click', function () { setEditing(true); });
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    start.value = initial.start;
                    exit.value = initial.exit;
                    inOut.value = initial.inOut;
                    end.value = initial.end;
                    shop.value = initial.shop;
                    setEditing(false);
                });
            }

            [start, exit, inOut, end, shop].forEach(function (el) {
                el.addEventListener('input', syncRequired);
                el.addEventListener('change', syncRequired);
            });
            syncRequired();
        });
    })();
</script>
</body>
</html>

