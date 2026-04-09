<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 基本シフト</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
</head>
<body>
<main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel">
        <h2>基本シフト</h2>

        @if ($statusMessage !== '')
            <div class="status">{{ $statusMessage }}</div>
        @endif

        <form method="get" class="filter-row">
            <label for="month">月:</label>
            <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
            <label for="staff_id">スタッフ:</label>
            <select id="staff_id" name="staff_id">
                <option value="" @selected($selectedStaffId === '')>--</option>
                @foreach ($staffOptions as $staff)
                    <option value="{{ $staff['staff_id'] }}" @selected($staff['staff_id'] === $selectedStaffId)>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</option>
                @endforeach
            </select>
            <button type="submit">表示</button>
        </form>

        <div class="meta-row">
            <div class="staff-title">名前: {{ $selectedStaffName }}</div>
            <a class="btn" href="{{ route('admin.shift.change', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId]) }}">シフト変更へ戻る</a>
        </div>

        @if ($rowCount === 0)
            <div class="empty">基本シフトはありません。</div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 80px;">
                        <col style="width: 100px;">
                    </colgroup>
                    <thead>
                    <tr>
                        <th>曜日</th>
                        <th>始業</th>
                        <th>退出</th>
                        <th>入出</th>
                        <th>終業</th>
                        <th>勤務店舗</th>
                        <th class="action-cell">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $row)
                        @php $formId = 'row-form-' . $row['shift_no']; @endphp
                        <tr data-row-form="{{ $formId }}">
                            <td>{{ $row['week'] }}</td>
                            <td>
                                <span class="display-value">{{ $row['shift_start'] }}</span>
                                <input form="{{ $formId }}" class="inline-input" type="time" name="シフト始業" step="900" value="{{ $row['shift_start'] }}">
                            </td>
                            <td>
                                <span class="display-value">{{ $row['shift_exit'] }}</span>
                                <input form="{{ $formId }}" class="inline-input" type="time" name="シフト退出" step="900" value="{{ $row['shift_exit'] }}">
                            </td>
                            <td>
                                <span class="display-value">{{ $row['shift_in_out'] }}</span>
                                <input form="{{ $formId }}" class="inline-input" type="time" name="シフト入出" step="900" value="{{ $row['shift_in_out'] }}">
                            </td>
                            <td>
                                <span class="display-value">{{ $row['shift_end'] }}</span>
                                <input form="{{ $formId }}" class="inline-input" type="time" name="シフト終業" step="900" value="{{ $row['shift_end'] }}">
                            </td>
                            <td>
                                <span class="display-value">{{ $row['shop_name'] }}</span>
                                <select form="{{ $formId }}" class="inline-input" name="勤務店舗">
                                    <option value="" @selected($row['shop_code'] === '' || $row['shop_code'] === null)>※勤務店舗</option>
                                    <option value="001" @selected($row['shop_code'] === '001')>PG ひなた鍼灸ﾏｯｻｰｼﾞ</option>
                                    <option value="003" @selected($row['shop_code'] === '003')>さくら鍼灸整骨院</option>
                                    <option value="004" @selected($row['shop_code'] === '004')>ひなた鍼灸整骨院</option>
                                    <option value="005" @selected($row['shop_code'] === '005')>TC ひなた鍼灸ﾏｯｻｰｼﾞ</option>
                                    <option value="006" @selected($row['shop_code'] === '006')>TC事務所</option>
                                    <option value="007" @selected($row['shop_code'] === '007')>PG事務所</option>
                                </select>
                            </td>
                            <td>
                                <form id="{{ $formId }}" class="row-form" method="post" action="{{ route('admin.basic-shift.update', ['shiftNo' => $row['shift_no']]) }}">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
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
        var storeOptions = @json($storeOptions ?? []);
        var rows = document.querySelectorAll('tr[data-row-form]');
        rows.forEach(function (row) {
            var formId = row.getAttribute('data-row-form');
            var selectorBase = '[form="' + formId + '"]';
            var start = document.querySelector('input[name="シフト始業"]' + selectorBase);
            var exit = document.querySelector('input[name="シフト退出"]' + selectorBase);
            var inOut = document.querySelector('input[name="シフト入出"]' + selectorBase);
            var end = document.querySelector('input[name="シフト終業"]' + selectorBase);
            var shop = document.querySelector('select[name="勤務店舗"]' + selectorBase);
            var editBtn = row.querySelector('.edit-trigger');
            var cancelBtn = row.querySelector('.cancel-trigger');
            if (!start || !exit || !inOut || !end || !shop || !editBtn) return;
            var selectedShop = shop.value;
            shop.innerHTML = '';
            var emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = '※勤務店舗';
            if (!selectedShop) emptyOption.selected = true;
            shop.appendChild(emptyOption);
            storeOptions.forEach(function (store) {
                var option = document.createElement('option');
                option.value = store.store_code || '';
                option.textContent = store.label || '';
                option.selected = selectedShop === option.value;
                shop.appendChild(option);
            });
            var initial = { start: start.value, exit: exit.value, inOut: inOut.value, end: end.value, shop: shop.value };

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
