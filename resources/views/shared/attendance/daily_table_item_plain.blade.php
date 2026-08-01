@php
$rows = $rows ?? [];
$tableClass = trim((string) ($tableClass ?? 'data-table'));
$wrapClass = trim((string) ($wrapClass ?? 'table-wrap'));
$rowClassResolver = $rowClassResolver ?? null;
$showPaidLeaveUsed = (bool) ($showPaidLeaveUsed ?? false);
$showActualScheduled = (bool) ($showActualScheduled ?? false);
$showShiftScheduled = (bool) ($showShiftScheduled ?? false);
$showChangeScheduled = (bool) ($showChangeScheduled ?? false);
$showNightOvertime = (bool) ($showNightOvertime ?? false);
@endphp
<div class="{{ $wrapClass }}">
    <table class="{{ $tableClass }} f_size13">
        <colgroup>
            <col style="width: 70px;">
            <col style="width: 40px;">
            <col style="width: 40px;">
            <col style="width: 40px;">
            <col style="width: 40px;">
            <col style="width: 45px;">
            <col style="width: 45px;">
            <col style="width: 45px;">
            <col style="width: 45px;">
            <col style="width: 45px;">
            <col style="width: 45px;">
            <col style="width: 45px;">
            <col style="width: 45px;">
            <col style="width: 45px;">
            <col style="width: 45px;">
            <col style="width: 45px;">
            <col style="width: 45px;">
            <col style="width: 40px;">
            <col style="width: 40px;">
            <col style="width: 60px;">
            <col style="width: 100px;">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">日付</th>
                <th colspan="{{ $showPaidLeaveUsed ? 4 : 4 }}" class="f-sen">勤怠</th>
                <th colspan="4" class="f-sen">打刻</th>
                <th colspan="4" class="f-sen">シフト</th>
                <th colspan="6" class="f-sen">変更実績</th>
                <th rowspan="2">店舗</th>
                <th rowspan="2" class="remark-col">備考</th>
            </tr>
            <tr>
                <th>休日<br>区分</th>
                <th class="att-col-kubun">区分</th>
                <th class="att-col-time">時間</th>
                <th class="att-col-paid f-sen">有休</th>
                <th>始業</th>
                <th>退出</th>
                <th>入出</th>
                <th class="f-sen">終業</th>
                <th>始業</th>
                <th>退出</th>
                <th>入出</th>
                <th class="f-sen">終業</th>
                <th>始業</th>
                <th>退出</th>
                <th>入出</th>
                <th>終業</th>
                <th>残業</th>
                <th class="f-sen">所定</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
            @php
            $resolvedRowClass = is_callable($rowClassResolver) ? $rowClassResolver($row) : '';
            @endphp
            <tr @class([$resolvedRowClass=> $resolvedRowClass !== ''])>
                <td>
                    @if (!empty($row['date_url']))
                    <a class="btn btn_small" href="{{ $row['date_url'] }}">{{ $row['date_label'] ?? '' }}</a>
                    @else
                    {{ $row['date_label'] ?? '' }}
                    @endif
                </td>
                <td>{{ $row['holiday_category'] ?? '' }}</td>
                <td class="att-col-kubun">{{ $row['attendance_category'] ?? '' }}</td>
                <td class="att-col-time">{{ $row['category_time'] ?? '' }}</td>
                <td class="att-col-paid f-sen">{{ $row['paid_leave_used'] ?? '' }}</td>
                <td>{{ $row['actual_start'] ?? '' }}</td>
                <td>{{ $row['actual_leave'] ?? '' }}</td>
                <td>{{ $row['actual_break_out'] ?? '' }}</td>
                <td class="f-sen">{{ $row['actual_end'] ?? '' }}</td>
                <td>{{ $row['shift_start'] ?? '' }}</td>
                <td>{{ $row['shift_leave'] ?? '' }}</td>
                <td>{{ $row['shift_break_out'] ?? '' }}</td>
                <td class="f-sen">{{ $row['shift_end'] ?? '' }}</td>
                <td>{{ $row['change_start'] ?? '' }}</td>
                <td>{{ $row['change_leave'] ?? '' }}</td>
                <td>{{ $row['change_break_out'] ?? '' }}</td>
                <td>{{ $row['change_end'] ?? '' }}</td>
                <td>{{ $row['overtime'] ?? '' }}</td>
                <td class="f-sen">{{ $row['change_scheduled'] ?? '' }}</td>
                <td>{{ $row['work_store'] ?? '' }}</td>
                <td class="remark-col">{{ $row['timecard_note'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
