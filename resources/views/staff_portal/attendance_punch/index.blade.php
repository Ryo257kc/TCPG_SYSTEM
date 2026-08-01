<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 打刻</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .attendance-punch-panel {
            max-width: 760px;
            margin: 0 auto;
        }

        .attendance-punch-now {
            margin: 0 0 20px;
            font-size: 16px;
            line-height: 1.6;
            color: #1f4f96;
            font-weight: 700;
        }

        .attendance-punch-user {
            margin: 0 0 18px;
            font-size: 16px;
            line-height: 1.6;
        }

        .attendance-punch-user strong {
            margin-left: 8px;
            font-size: 18px;
            color: #1b1b1b;
        }

        .attendance-punch-actions {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }

        .attendance-punch-action {
            min-height: 52px;
            border: 1px solid #85a8df;
            border-radius: 8px;
            background: linear-gradient(180deg, #eef5ff 0%, #cfe0fb 100%);
            color: #2255a4;
            font-size: 20px;
            font-weight: 700;
        }

        .attendance-punch-status {
            width: 100%;
            table-layout: fixed;
        }

        .attendance-punch-status td {
            height: 44px;
            text-align: center;
            vertical-align: middle;
            font-size: 18px;
            font-weight: 700;
        }

        .attendance-punch-meta {
            margin-top: 12px;
            color: #6b7280;
            font-size: 13px;
        }

        .attendance-punch-message {
            margin: 0 0 16px;
            color: #9f1f1f;
            font-size: 14px;
            font-weight: 700;
        }

        @media (max-width: 640px) {
            .attendance-punch-actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body class="attendance-punch-page">
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel attendance-punch-panel">
            <div class="content-head">
                <h2 class="content-title">打刻フォーム</h2>
            </div>

            <p class="attendance-punch-now">現在日時： {{ $currentDateTime }}</p>
            <p class="attendance-punch-user">ログイン： <strong>{{ $displayName }}</strong></p>

            @if (!empty($statusMessage))
            <div class="attendance-punch-message">{{ $statusMessage }}</div>
            @endif

            <div class="attendance-punch-actions">
                <button type="button" class="attendance-punch-action">始業</button>
                <button type="button" class="attendance-punch-action">退出</button>
                <button type="button" class="attendance-punch-action">入出</button>
                <button type="button" class="attendance-punch-action">終業</button>
            </div>

            <div class="table-wrap">
                <table class="data-table attendance-punch-status">
                    <thead>
                        <tr>
                            <th>始業</th>
                            <th>退出</th>
                            <th>入出</th>
                            <th>終業</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $punchRow['actual_start'] }}</td>
                            <td>{{ $punchRow['actual_leave'] }}</td>
                            <td>{{ $punchRow['actual_break_out'] }}</td>
                            <td>{{ $punchRow['actual_end'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if ($punchRow['work_store'] !== '')
            <div class="attendance-punch-meta">勤務店舗： {{ $punchRow['work_store'] }}</div>
            @endif

            <div class="back-row">
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
    <script>
        function submitAttendancePunch(punchType) {
            const form = document.createElement('form');
            form.method = 'post';
            form.action = @json(route('attendance.punch.store'));

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = @json(csrf_token());
            form.appendChild(csrf);

            const type = document.createElement('input');
            type.type = 'hidden';
            type.name = 'type';
            type.value = punchType;
            form.appendChild(type);

            document.body.appendChild(form);
            form.submit();
        }

        function bindAttendancePunchButtons() {
            const types = ['start', 'leave', 'break', 'end'];
            document.querySelectorAll('.attendance-punch-action').forEach((button, index) => {
                button.addEventListener('click', () => submitAttendancePunch(types[index] ?? ''));
            });
        }

        bindAttendancePunchButtons();
    </script>
</body>

</html>
