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
    $showReturnNote = (bool) ($showReturnNote ?? false);
    $showRemand = (bool) ($showRemand ?? false);
@endphp
<div class="{{ $wrapClass }}">
    <table class="{{ $tableClass }}">
        <thead>
        <tr>
            <th rowspan="2">日付</th>
            <th colspan="{{ $showPaidLeaveUsed ? 4 : 3 }}" class="f-sen">勤怠</th>
            <th colspan="{{ $showActualScheduled ? 5 : 4 }}" class="f-sen">打刻</th>
            <th colspan="{{ $showShiftScheduled ? 5 : 4 }}" class="f-sen">シフト</th>
            <th colspan="{{ 4 + ($showChangeScheduled ? 1 : 0) + 1 + ($showNightOvertime ? 1 : 0) }}" class="f-sen">変更実績</th>
            <th rowspan="2">店舗</th>
            <th rowspan="2" class="remark-col">備考</th>
            @if ($showReturnNote)
                <th rowspan="2" class="remark-col">差戻し備考</th>
            @endif
            @if ($showRemand)
                <th rowspan="2">差戻し</th>
            @endif
        </tr>
        <tr>
            <th>休日<br>区分</th>
            <th>区分</th>
            <th class="f-sen">時間</th>
            @if ($showPaidLeaveUsed)
                <th>有休使用</th>
            @endif
            <th>始業</th><th>退出</th><th>入出</th><th class="f-sen">終業</th>
            @if ($showActualScheduled)
                <th class="f-sen">実働</th>
            @endif
            <th>始業</th><th>退出</th><th>入出</th><th class="f-sen">終業</th>
            @if ($showShiftScheduled)
                <th class="f-sen">時間</th>
            @endif
            <th>始業</th><th>退出</th><th>入出</th><th>終業</th>
            @if ($showChangeScheduled)
                <th>時間</th>
            @endif
            <th class="f-sen">残業</th>
            @if ($showNightOvertime)
                <th>深夜</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            @php
                $resolvedRowClass = is_callable($rowClassResolver) ? $rowClassResolver($row) : '';
            @endphp
            <tr @class([$resolvedRowClass => $resolvedRowClass !== ''])>
                <td>{{ $row['date_label'] ?? '' }}</td>
                <td>{{ $row['holiday_category'] ?? '' }}</td>
                <td>{{ $row['attendance_category'] ?? '' }}</td>
                <td class="f-sen">{{ $row['category_time'] ?? '' }}</td>
                @if ($showPaidLeaveUsed)
                    <td>{{ $row['paid_leave_used'] ?? '' }}</td>
                @endif
                <td>{{ $row['actual_start'] ?? '' }}</td>
                <td>{{ $row['actual_leave'] ?? '' }}</td>
                <td>{{ $row['actual_break_out'] ?? '' }}</td>
                <td class="f-sen">{{ $row['actual_end'] ?? '' }}</td>
                @if ($showActualScheduled)
                    <td class="f-sen">{{ $row['actual_scheduled'] ?? '' }}</td>
                @endif
                <td>{{ $row['shift_start'] ?? '' }}</td>
                <td>{{ $row['shift_leave'] ?? '' }}</td>
                <td>{{ $row['shift_break_out'] ?? '' }}</td>
                <td class="f-sen">{{ $row['shift_end'] ?? '' }}</td>
                @if ($showShiftScheduled)
                    <td class="f-sen">{{ $row['shift_scheduled'] ?? '' }}</td>
                @endif
                <td>{{ $row['change_start'] ?? '' }}</td>
                <td>{{ $row['change_leave'] ?? '' }}</td>
                <td>{{ $row['change_break_out'] ?? '' }}</td>
                <td>{{ $row['change_end'] ?? '' }}</td>
                @if ($showChangeScheduled)
                    <td>{{ $row['change_scheduled'] ?? '' }}</td>
                @endif
                <td class="f-sen">{{ $row['overtime'] ?? '' }}</td>
                @if ($showNightOvertime)
                    <td>{{ $row['night_overtime'] ?? '' }}</td>
                @endif
                <td>{{ $row['work_store'] ?? '' }}</td>
                <td class="remark-col">{{ $row['timecard_note'] ?? '' }}</td>
                @if ($showReturnNote)
                    <td class="remark-col">{{ $row['return_note'] ?? '' }}</td>
                @endif
                @if ($showRemand)
                    <td></td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
