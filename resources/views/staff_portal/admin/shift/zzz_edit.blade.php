<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - シフト変更</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName ?? '', 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">シフト変更</h2>
            </div>
            <!-- <h1>シフト変更</h1> -->
            <div class="meta">名前: {{ $staffName }} / 日仁E {{ $dateLabel }} ({{ $weekLabel }})</div>

            <form method="post" action="{{ route('admin.shift.update', ['timeNo' => $timeNo]) }}">
                @csrf
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">

                <table class="data-table">
                    <tr>
                        <th>曜日</th>
                        <td>{{ $weekLabel }}</td>
                    </tr>
                    <tr>
                        <th>始業</th>
                        <td><input type="time" id="shiftStart" name="シフト始業" step="900" value="{{ $shiftStart }}"></td>
                    </tr>
                    <tr>
                        <th>退出</th>
                        <td><input type="time" id="shiftExit" name="シフト退出" step="900" value="{{ $shiftExit }}"></td>
                    </tr>
                    <tr>
                        <th>入出</th>
                        <td><input type="time" id="shiftInOut" name="シフト入出" step="900" value="{{ $shiftInOut }}"></td>
                    </tr>
                    <tr>
                        <th>終業</th>
                        <td><input type="time" id="shiftEnd" name="シフト終業" step="900" value="{{ $shiftEnd }}"></td>
                    </tr>
                    <tr>
                        <th>勤務店�E</th>
                        <td>
                            <select name="勤務店�E">
                                <option value="未選抁E @selected($shopCode === '' || $shopCode === null)>※勤務店舗を選んでください</option>
                            <option value=" 003" @selected($shopCode==='003' )>さくら鍼灸整骨院</option>
                                <option value="004" @selected($shopCode==='004' )>ひなた鍼灸整骨院</option>
                                <option value="005" @selected($shopCode==='005' )>ひなた鍼灸・マッサージ</option>
                                <option value="006" @selected($shopCode==='006' )>トータルケア事務所</option>
                                <option value="007" @selected($shopCode==='007' )>プレッジ事務所</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <div class="btn-row">
                    <button type="submit" name="_action" value="register" class="btn">登録</button>
                    <button type="button" id="clearInputBtn" class="btn">クリア</button>
                    <a class="btn" href="{{ route('admin.shift.change', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId]) }}">戻す</a>
                </div>
            </form>
            <div class="home-visit-counter-footer">
                <a href="{{ route('dashboard') }}" class="btn">戻る</a>
            </div>
        </section>
    </main>
    <script>
        (function() {
            var btn = document.getElementById('clearInputBtn');
            var shiftStart = document.getElementById('shiftStart');
            var shiftExit = document.getElementById('shiftExit');
            var shiftInOut = document.getElementById('shiftInOut');
            var shiftEnd = document.getElementById('shiftEnd');

            function syncRequirements() {
                if (shiftEnd) {
                    var requireEnd = !!(shiftStart && shiftStart.value);
                    shiftEnd.required = requireEnd;
                    shiftEnd.setCustomValidity(requireEnd && !shiftEnd.value ? '�n�Ƃ���͂����ꍇ�͏I�Ƃ����͂��Ă��������B' : '');
                }
                if (shiftInOut) {
                    var requireInOut = !!(shiftExit && shiftExit.value);
                    shiftInOut.required = requireInOut;
                    shiftInOut.setCustomValidity(requireInOut && !shiftInOut.value ? '�ޏo����͂����ꍇ�͓��o�����͂��Ă��������B' : '');
                }
            }

            [shiftStart, shiftExit, shiftInOut, shiftEnd].forEach(function(el) {
                if (!el) return;
                el.addEventListener('change', syncRequirements);
                el.addEventListener('input', syncRequirements);
            });
            syncRequirements();

            if (!btn) return;
            btn.addEventListener('click', function() {
                document.querySelectorAll('input[type="time"]').forEach(function(el) {
                    el.value = '';
                });
                var shopSelect = document.querySelector('select[name="勤務店�E"]');
                if (shopSelect) {
                    shopSelect.value = '未選抁E;
                }
                syncRequirements();
            });
        })();
    </script>
</body>

</html>
