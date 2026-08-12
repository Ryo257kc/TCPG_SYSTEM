<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 往診月間</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <style>
            .home-visit-row {
                background-color: #fff3cd;
            }
        </style>

        <section class="panel">
            <div class="content-head">
                <h2 class="content-title">往診月報</h2>
            </div>

            @if(session('status'))
            <div class="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif


            <div class="toolbar-group filter-row ">
                <form method="get" action="{{ route('home_visit.monthly_report') }}" class="mb-4">
                    <label for="month">対象月</label>
                    <input type="month" id="month" name="month" value="{{ $month }}">
                    <button type="submit" class="btn">表示</button>
                </form>

                <div class="table-wrap">

                    @if($rows->isEmpty())
                    <p>この月のデータはありません。</p>
                    @else
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>施術日</th>
                                <th>件数</th>
                                <th>本人確定状況</th>
                                <th>確定日時</th>
                                <th>日報</th>
                                @if($isVisitManagement || $isAdmin)
                                <th>管理操作</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                            @php
                            $treatmentDate = \Carbon\Carbon::parse($row->treatment_date);
                            @endphp

                            <tr>
                                <td>
                                    {{ $treatmentDate->format('Y-m-d') }}
                                    （{{ ['日','月','火','水','木','金','土'][$treatmentDate->dayOfWeek] }}）
                                </td>

                                <td class="text-center">
                                    {{ $row->count }}
                                </td>

                                <td style="{{ empty($row->is_confirmed) ? 'background:#fff3cd;' : '' }} text-align:center;">
                                    {{ empty($row->is_confirmed) ? '未確認' : '確認済' }}
                                </td>

                                <td class="text-center">
                                    {{ !empty($row->confirmed_at) ? \Carbon\Carbon::parse($row->confirmed_at)->format('Y-m-d H:i') : '' }}
                                </td>

                                <td class="text-center">
                                    <a href="{{ route('home_visit.daily_report', ['date' => $treatmentDate->format('Y-m-d')]) }}" class="btn">
                                        開く
                                    </a>
                                </td>
                                @if($isVisitManagement || $isAdmin)
                                <td class="text-center">
                                    @if(!empty($row->is_confirmed) && empty($row->is_management_fixed))
                                    <form method="post" action="{{ route('home_visit.monthly_report.adminConfirm') }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $treatmentDate->format('Y-m-d') }}">
                                        <button type="submit" class="btn apply">管理確定</button>
                                    </form>
                                    @elseif(!empty($row->is_management_fixed))
                                    <form method="post" action="{{ route('home_visit.monthly_report.unconfirm') }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $treatmentDate->format('Y-m-d') }}">
                                        <button type="submit" class="btn apply">解除</button>
                                    </form>
                                    @else
                                    <span>-</span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>

            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>


</body>

</html>
