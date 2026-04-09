<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 基本シフト変更</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
</head>
<body>
<main class="container">
    @include('staff_portal.shared.app_header', ['displayName' => $displayName ?? '', 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel">
        <h1>基本シフト変更</h1>
        <div class="meta">名前: {{ $targetStaffName }} / 曜日: {{ $week }}</div>

        @if ($statusMessage !== '')
            <div class="status">{{ $statusMessage }}</div>
        @endif

        <form method="post" action="{{ route('admin.basic-shift.update', ['shiftNo' => $shiftNo]) }}">
            @csrf
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">

            <table class="data-table">
                <tr><th>曜日</th><td>{{ $week }}</td></tr>
                <tr><th>始業</th><td><input type="time" name="シフト始業" step="900" value="{{ $shiftStart }}"></td></tr>
                <tr><th>退出</th><td><input type="time" name="シフト退出" step="900" value="{{ $shiftExit }}"></td></tr>
                <tr><th>入出</th><td><input type="time" name="シフト入出" step="900" value="{{ $shiftInOut }}"></td></tr>
                <tr><th>終業</th><td><input type="time" name="シフト終業" step="900" value="{{ $shiftEnd }}"></td></tr>
                <tr>
                    <th>勤務店舗</th>
                    <td>
                        <select id="shopCode" name="勤務店舗">
                            <option value="" @selected($shopCode === '' || $shopCode === null)>※勤務店舗を選んでください</option>
                            <option value="001" @selected($shopCode === '001')>PG ひなた鍼灸ﾏｯｻｰｼﾞ</option>
                            <option value="003" @selected($shopCode === '003')>さくら鍼灸整骨院</option>
                            <option value="004" @selected($shopCode === '004')>ひなた鍼灸整骨院</option>
                            <option value="005" @selected($shopCode === '005')>TC ひなた鍼灸ﾏｯｻｰｼﾞ</option>
                            <option value="006" @selected($shopCode === '006')>TC事務所</option>
                            <option value="007" @selected($shopCode === '007')>PG事務所</option>
                        </select>
                    </td>
                </tr>
            </table>

            <div class="btn-row">
                <button type="submit" class="btn" name="_action" value="register">登録</button>
                <button type="submit" class="btn" name="_action" value="clear" formnovalidate>クリア</button>
                <a class="btn" href="{{ route('admin.shift.change', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId]) }}">戻る</a>
            </div>
        </form>
    </section>
</main>
<script>
    var storeOptions = @json($storeOptions ?? []);
    (function () {
        var ids = ['シフト始業', 'シフト退出', 'シフト入出', 'シフト終業'];
        var timeInputs = ids.map(function (name) { return document.querySelector('input[name="' + name + '"]'); }).filter(Boolean);
        var shopSelect = document.getElementById('shopCode');
        if (!shopSelect) return;

        var selectedShop = shopSelect.value;
        shopSelect.innerHTML = '';
        var emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = '※勤務店舗を選んでください';
        if (!selectedShop) emptyOption.selected = true;
        shopSelect.appendChild(emptyOption);
        storeOptions.forEach(function (store) {
            var option = document.createElement('option');
            option.value = store.store_code || '';
            option.textContent = store.label || '';
            option.selected = selectedShop === option.value;
            shopSelect.appendChild(option);
        });

        function syncRequired() {
            var hasAnyTime = timeInputs.some(function (el) { return !!el.value; });
            shopSelect.required = hasAnyTime;
            shopSelect.setCustomValidity(hasAnyTime && !shopSelect.value ? '時間を入力した場合、勤務店舗は必須です。' : '');

            var shiftStart = document.querySelector('input[name="シフト始業"]');
            var shiftExit = document.querySelector('input[name="シフト退出"]');
            var shiftInOut = document.querySelector('input[name="シフト入出"]');
            var shiftEnd = document.querySelector('input[name="シフト終業"]');
            if (shiftEnd) {
                var requireEnd = !!(shiftStart && shiftStart.value);
                shiftEnd.required = requireEnd;
                shiftEnd.setCustomValidity(requireEnd && !shiftEnd.value ? '始業を入力した場合、終業は必須です。' : '');
            }
            if (shiftInOut) {
                var requireInOut = !!(shiftExit && shiftExit.value);
                shiftInOut.required = requireInOut;
                shiftInOut.setCustomValidity(requireInOut && !shiftInOut.value ? '退出を入力した場合、入出は必須です。' : '');
            }
        }

        timeInputs.forEach(function (el) {
            el.addEventListener('input', syncRequired);
            el.addEventListener('change', syncRequired);
        });
        shopSelect.addEventListener('change', syncRequired);
        syncRequired();
    })();
</script>
</body>
</html>
