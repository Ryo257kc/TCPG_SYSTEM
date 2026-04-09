<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - ã‚·ãƒ•ãƒˆå¤‰æ›´</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
</head>
<body>
<main class="container">
    @include('staff_portal.shared.app_header', ['displayName' => $displayName ?? '', 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel">
        <h1>ã‚·ãƒ•ãƒˆå¤‰æ›´</h1>
        <div class="meta">åå‰: {{ $staffName }} / æ—¥ä»E {{ $dateLabel }} ({{ $weekLabel }})</div>

        <form method="post" action="{{ route('admin.shift.update', ['timeNo' => $timeNo]) }}">
            @csrf
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">

            <table class="data-table">
                <tr>
                    <th>æ›œæ—¥</th>
                    <td>{{ $weekLabel }}</td>
                </tr>
                <tr>
                    <th>å§‹æ¥­</th>
                    <td><input type="time" id="shiftStart" name="ã‚·ãƒ•ãƒˆå§‹æ¥­" step="900" value="{{ $shiftStart }}"></td>
                </tr>
                <tr>
                    <th>é€€å‡º</th>
                    <td><input type="time" id="shiftExit" name="ã‚·ãƒ•ãƒˆé€€å‡º" step="900" value="{{ $shiftExit }}"></td>
                </tr>
                <tr>
                    <th>å…¥å‡º</th>
                    <td><input type="time" id="shiftInOut" name="ã‚·ãƒ•ãƒˆå…¥å‡º" step="900" value="{{ $shiftInOut }}"></td>
                </tr>
                <tr>
                    <th>çµ‚æ¥­</th>
                    <td><input type="time" id="shiftEnd" name="ã‚·ãƒ•ãƒˆçµ‚æ¥­" step="900" value="{{ $shiftEnd }}"></td>
                </tr>
                <tr>
                    <th>å‹¤å‹™åº—èE</th>
                    <td>
                        <select name="å‹¤å‹™åº—èE">
                            <option value="æœªé¸æŠE @selected($shopCode === '' || $shopCode === null)>â€»å‹¤å‹™åº—èEã‚’é¸ã‚“ã§ãã ã•ã„</option>
                            <option value="003" @selected($shopCode === '003')>ã•ãã‚‰é¼ç¸æ•´éª¨é™¢</option>
                            <option value="004" @selected($shopCode === '004')>ã²ãªãŸé¼ç¸æ•´éª¨é™¢</option>
                            <option value="005" @selected($shopCode === '005')>ã²ãªãŸé¼ç¸ãƒ»Eï½¯E»E°E¼EE/option>
                            <option value="006" @selected($shopCode === '006')>ãƒˆãEã‚¿ãƒ«ã‚±ã‚¢äº‹å‹™æ‰€</option>
                            <option value="007" @selected($shopCode === '007')>ãƒ—ãƒ¬ãƒE‚¸äº‹å‹™æ‰€</option>
                        </select>
                    </td>
                </tr>
            </table>

            <div class="btn-row">
                <button type="submit" name="_action" value="register" class="btn">ç™»éŒ²</button>
                <button type="button" id="clearInputBtn" class="btn">ã‚¯ãƒªã‚¢</button>
                <a class="btn" href="{{ route('admin.shift.change', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId]) }}">æˆ»ã‚E/a>
            </div>
        </form>
    </section>
</main>
<script>
    (function () {
        var btn = document.getElementById('clearInputBtn');
        var shiftStart = document.getElementById('shiftStart');
        var shiftExit = document.getElementById('shiftExit');
        var shiftInOut = document.getElementById('shiftInOut');
        var shiftEnd = document.getElementById('shiftEnd');

        function syncRequirements() {
            if (shiftEnd) {
                var requireEnd = !!(shiftStart && shiftStart.value);
                shiftEnd.required = requireEnd;
                shiftEnd.setCustomValidity(requireEnd && !shiftEnd.value ? 'n‹Æ‚ğ“ü—Í‚µ‚½ê‡‚ÍI‹Æ‚à“ü—Í‚µ‚Ä‚­‚¾‚³‚¢B' : '');
            }
            if (shiftInOut) {
                var requireInOut = !!(shiftExit && shiftExit.value);
                shiftInOut.required = requireInOut;
                shiftInOut.setCustomValidity(requireInOut && !shiftInOut.value ? '‘Şo‚ğ“ü—Í‚µ‚½ê‡‚Í“üo‚à“ü—Í‚µ‚Ä‚­‚¾‚³‚¢B' : '');
            }
        }

        [shiftStart, shiftExit, shiftInOut, shiftEnd].forEach(function (el) {
            if (!el) return;
            el.addEventListener('change', syncRequirements);
            el.addEventListener('input', syncRequirements);
        });
        syncRequirements();

        if (!btn) return;
        btn.addEventListener('click', function () {
            document.querySelectorAll('input[type="time"]').forEach(function (el) {
                el.value = '';
            });
            var shopSelect = document.querySelector('select[name="å‹¤å‹™åº—èE"]');
            if (shopSelect) {
                shopSelect.value = 'æœªé¸æŠE;
            }
            syncRequirements();
        });
    })();
</script>
</body>
</html>
