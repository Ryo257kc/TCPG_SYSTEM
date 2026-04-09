<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 勤怠編集</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/attendance-edit.css') }}">
</head>
<body class="attendance-edit-page">
<main class="container">
    @include('staff_portal.shared.app_header', ['displayName' => $displayName ?? '', 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel attendance-edit-panel">
        <h1>勤怠編集</h1>
        <p class="notice">
            打刻漏れは変更実績ではなく、備考欄にご記入ください。<br>
            中抜けや休憩した際はその時刻も入力してください。<br><br>
            差戻しがあった際は理由が記載されますので、その内容を確認の上、再申請してください。
        </p>

        @if (session('errorMessage'))
            <div class="error-box">{{ session('errorMessage') }}</div>
        @endif

        <form method="post" action="{{ route('attendance.update', ['timeNo' => $timeNo]) }}">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="return_mode" value="{{ $returnMode ?? '' }}">
            <input type="hidden" name="return_staff_id" value="{{ $returnStaffId ?? '' }}">
            <input type="hidden" name="return_month" value="{{ $returnMonth ?? '' }}">

            <table>
                <colgroup>
                    <col class="col-label">
                    <col class="col-jitu">
                    <col class="col-shift">
                    <col class="col-change">
                </colgroup>
                <tr>
                    <th>対象者名</th>
                    <td colspan="3">{{ $staffName }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td colspan="3">{{ $dateLabel }}</td>
                </tr>
                <tr>
                    <th>区分</th>
                    <td colspan="3">
                        <select name="区分">
                            <option value="">-- ※有休、休日出勤、欠勤、遅刻・早退を選択 --</option>
                            <option value="有休" @selected(old('区分', $row['kubun']) === '有休')>有休１日</option>
                            <option value="有半" @selected(old('区分', $row['kubun']) === '有半')>有休半日</option>
                            <option value="休出" @selected(old('区分', $row['kubun']) === '休出')>休日出勤</option>
                            <option value="欠勤" @selected(old('区分', $row['kubun']) === '欠勤')>欠勤</option>
                            <option value="遅早" @selected(old('区分', $row['kubun']) === '遅早')>遅刻・早退</option>
                        </select>
                        @if ($row['yukyu_shinsei'] !== '')
                            <div style="margin-top:6px; color:#c53030; font-weight:700;">有休申請: {{ $row['yukyu_shinsei'] }}</div>
                        @endif
                    </td>
                </tr>
                <tr class="center">
                    <th></th><td>実績</td><td>シフト</td><td>変更実績</td>
                </tr>
                <tr class="center">
                    <th>始業</th>
                    <td>{{ $row['jitu_sigyo'] }}</td>
                    <td>{{ $row['shift_sigyo'] }}</td>
                    <td><input class="time-input" type="time" step="900" name="変更始業" value="{{ old('変更始業', $row['change_sigyo']) }}"></td>
                </tr>
                <tr class="center">
                    <th>退出</th>
                    <td>{{ $row['jitu_taisitu'] }}</td>
                    <td>{{ $row['shift_taisitu'] }}</td>
                    <td><input class="time-input" type="time" step="900" name="変更退出" value="{{ old('変更退出', $row['change_taisitu']) }}"></td>
                </tr>
                <tr class="center">
                    <th>入出</th>
                    <td>{{ $row['jitu_irisitu'] }}</td>
                    <td>{{ $row['shift_irisitu'] }}</td>
                    <td><input class="time-input" type="time" step="900" name="変更入出" value="{{ old('変更入出', $row['change_irisitu']) }}"></td>
                </tr>
                <tr class="center">
                    <th>終業</th>
                    <td>{{ $row['jitu_syugyo'] }}</td>
                    <td>{{ $row['shift_syugyo'] }}</td>
                    <td><input class="time-input" type="time" step="900" name="変更終業" value="{{ old('変更終業', $row['change_syugyo']) }}"></td>
                </tr>
                <tr class="center">
                    <th>残業</th>
                    <td colspan="2">小数で入力</td>
                    <td><input class="overtime-input" type="text" name="残業" value="{{ old('残業', $row['overtime']) }}" placeholder="例: 1.5">時間</td>
                </tr>
                <tr>
                    <th>備考</th>
                    <td colspan="3"><textarea name="備考" placeholder="例: 実績漏れ 18時退勤">{{ old('備考', $row['remark']) }}</textarea></td>
                </tr>
                <tr>
                    <th>差戻し理由</th>
                    <td colspan="3">{{ $row['sashimodoshi_biko'] }}</td>
                </tr>
            </table>

            <div class="btn-row">
                @if ($row['can_submit'])
                    <button type="submit" name="_action" value="apply" class="btn apply">申請</button>
                    <button type="button" class="btn clear" id="clearFormBtn">クリア</button>
                @else
                    <span style="font-weight:700; color:#c53030;">申請中</span>
                @endif
            </div>
        </form>

        <div class="back-row">
            <a class="btn back" href="{{ $backUrl ?? route('attendance.monthly', ['month' => $month]) }}">戻る</a>
        </div>
    </section>
</main>
<script>
    (function () {
        var btn = document.getElementById('clearFormBtn');
        var form = btn ? btn.closest('form') : document.querySelector('form');
        if (!form) return;

        var timeInputs = form.querySelectorAll('.time-input');
        var changeStart = timeInputs[0] || null;
        var changeExit = timeInputs[1] || null;
        var changeInOut = timeInputs[2] || null;
        var changeEnd = timeInputs[3] || null;

        function applyConditionalRequired() {
            if (changeEnd) {
                changeEnd.required = !!(changeStart && changeStart.value);
                changeEnd.setCustomValidity(
                    changeEnd.required && !changeEnd.value ? '変更始業を入力した場合、変更終業は必須です。' : ''
                );
            }
            if (changeInOut) {
                changeInOut.required = !!(changeExit && changeExit.value);
                changeInOut.setCustomValidity(
                    changeInOut.required && !changeInOut.value ? '変更退出を入力した場合、変更入出は必須です。' : ''
                );
            }
        }

        [changeStart, changeExit, changeInOut, changeEnd].forEach(function (el) {
            if (el) {
                el.addEventListener('input', applyConditionalRequired);
                el.addEventListener('change', applyConditionalRequired);
            }
        });
        applyConditionalRequired();

        if (!btn) return;
        btn.addEventListener('click', function () {
            form.querySelectorAll('.time-input, .overtime-input').forEach(function (el) { el.value = ''; });
            var memo = form.querySelector('textarea');
            if (memo) memo.value = '';
            var kubun = form.querySelector('select');
            if (kubun) kubun.selectedIndex = 0;
            applyConditionalRequired();
        });
    })();
</script>
</body>
</html>
