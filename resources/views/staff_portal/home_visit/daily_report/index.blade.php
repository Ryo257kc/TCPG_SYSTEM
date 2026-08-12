<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 往診日報</title>
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
                <h2 class="content-title">往診日報</h2>
            </div>

            @if(session('status'))
            <div class="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif

            <div class="toolbar-group filter-row">
                <form method="get" action="{{ route('home_visit.daily_report') }}">
                    <input type="date" name="date" value="{{ $date }}">
                    @if($canSelectStaff ?? false)
                    <select name="staff_name" class="margin_l20">
                        @foreach(($staffOptions ?? []) as $staff)
                        <option value="{{ $staff['staff_id'] }}" {{ ($targetStaffId ?? '') === $staff['staff_id'] ? 'selected' : '' }}>
                            {{ $staff['staff_name'] }}
                        </option>
                        @endforeach
                    </select>
                    @endif
                    <button class="margin_l20" type="submit">表示</button>
                    @if($items->where('is_confirmed', 1)->count() > 0)
                    <input type="button" value="印刷" class="btn margin_l20" onclick="window.open('{{ route('home_visit.daily_report.print', ['date' => $date, 'staff_name' => $targetStaffId ?? '']) }}', '_blank')">
                    @endif
                    <input type="button" value="前週へ複製" class="btn margin_l20"
                        onclick="if(confirm('前週に複製します。よろしいですか？')) location.href='{{ route('home_visit.daily_report.copy_week', ['date' => $date, 'staff_name' => $targetStaffId ?? '']) }}'">
                </form>
                @if($items->where('is_confirmed', 1)->count() === 0)
                <form method="post" action="{{ route('home_visit.daily_report.quick_store') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <input type="hidden" name="staff_name" value="{{ $targetStaffId ?? '' }}">
                    <button type="submit" class="btn margin_l20">新規追加</button>
                </form>

                <form method="post" action="{{ route('home_visit.monthly_report.confirm') }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <input type="hidden" name="staff_name" value="{{ $targetStaffId ?? '' }}">
                    <button class="btn apply margin_l20">確定</button>
                </form>
                @endif
            </div>

            @if(count($items) === 0)
            <div class="empty">対象日のデータがありません。</div>
            @else

            <font size="small">（※黄色=往診）</font>
            @if($items->first() && $items->first()->is_confirmed == 1)
            <font color="red">
                {{ \Carbon\Carbon::parse($items->first()->confirmed_at)->format('Y-m-d H:i:s') }}　確定済
            </font>
            @endif

            <div class="table-wrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width: 40px;">
                        <col style="width: 140px;">
                        <col style="width: 50px;">
                        <col style="width: 50px;">
                        <col style="width: 50px;">
                        <col style="width: 70px;">
                        <col style="width: 50px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 40px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>患者名</th>
                            <th>距離</th>
                            <th>開始</th>
                            <th>終了</th>
                            <th>日報店舗</th>
                            <th>標準距離</th>
                            <th>売上金額</th>
                            <th>自費</th>
                            <th>未収金</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        @php
                        $start = $item->start_time ? \Carbon\Carbon::parse($item->start_time)->format('H:i') : '';
                        $end = $item->end_time ? \Carbon\Carbon::parse($item->end_time)->format('H:i') : '';
                        @endphp
                        <tr class="{{ $item->is_home_visit ? 'home-visit-row' : '' }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->patient_name }}</td>
                            <td>
                                {{ (is_numeric($item->distance) && $item->distance != 0)
                                    ? (floor($item->distance) == $item->distance
                                        ? (int) $item->distance
                                        : rtrim(rtrim($item->distance, '0'), '.'))
                                    : '' }}
                            </td>
                            <td>{{ $start !== '00:00' ? $start : '' }}</td>
                            <td>{{ $end !== '00:00' ? $end : '' }}</td>
                            <td>{{ $item->daily_report_store_name }}</td>
                            <td>{{ formatNumber($item->standard_distance) }}</td>
                            <td>{{ formatNumber(((float) ($item->sales_amount ?? 0)) * 100) }}</td>
                            <td>{{ formatNumber($item->private_fee) }}</td>
                            <td>{{ formatNumber($item->uncollected_amount) }}</td>
                            <td>
                                @if(!empty($item->daily_report_id))
                                <a href="{{ route('home_visit.daily_report.edit', ['nippouNo' => $item->daily_report_id]) }}" class="btn btn_small">詳細</a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if($summary && ($summary->total_patients || $summary->duplicate_patients || $summary->total_distance))
            <p>
                人数：{{ ($summary->total_patients ?? 0) - ($summary->duplicate_patients ?? 0) }}名
                （重複人数：{{ $summary->duplicate_patients ?? 0 }}名）<br>
                距離：
                {{ (is_numeric($summary->total_distance) && $summary->total_distance != 0)
                    ? (floor($summary->total_distance) == $summary->total_distance
                        ? (int) $summary->total_distance
                        : rtrim(rtrim($summary->total_distance, '0'), '.'))
                    : '' }}km
            </p>
            @endif

            <div>
                <button type="button" class="btn_back" onclick="history.back()">戻る</button>
            </div>
        </section>
    </main>

</body>

</html>
