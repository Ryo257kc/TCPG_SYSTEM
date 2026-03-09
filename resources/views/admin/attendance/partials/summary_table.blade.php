<div style="overflow:auto; border:1px solid #dce7f7; border-radius:10px; margin-bottom:10px;">
    <table style="width:100%; border-collapse:collapse; min-width:1100px; background:#fff; font-size:12px;">
        <thead>
        <tr style="background:#f8fbff;">
            <th style="text-align:left; padding:6px 8px;">ID</th>
            <th style="text-align:left; padding:6px 8px;">名前</th>
            <th style="text-align:left; padding:6px 8px;">雇用形態</th>
            <th style="text-align:left; padding:6px 8px;">勤怠確認</th>
            <th style="text-align:left; padding:6px 8px;">詳細</th>
            <th style="text-align:right; padding:6px 8px;">出勤日数</th>
            <th style="text-align:right; padding:6px 8px;">欠勤日数</th>
            <th style="text-align:right; padding:6px 8px;">休出日数</th>
            <th style="text-align:right; padding:6px 8px;">出勤時間</th>
            <th style="text-align:right; padding:6px 8px;">有休日数</th>
            <th style="text-align:right; padding:6px 8px;">残業</th>
            <th style="text-align:right; padding:6px 8px;">深夜残業</th>
            <th style="text-align:left; padding:6px 8px;">確認操作</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            @php
                $m = (array) ($row['metrics'] ?? []);
                $isSelectedRow = ((string) $selectedStaffId !== '' && (string) $selectedStaffId === (string) $row['staff_id']);
                $detailQuery = ['month' => $selectedMonth, 'company_id' => $selectedCompanyId];
                if (!$isSelectedRow) {
                    $detailQuery['staff_id'] = $row['staff_id'];
                }
            @endphp
            <tr>
                <td style="text-align:left; padding:6px 8px;">{{ $row['staff_id'] }}</td>
                <td style="text-align:left; padding:6px 8px;">{{ $row['staff_name'] }}</td>
                <td style="text-align:left; padding:6px 8px;">{{ $row['division'] ?? '' }}</td>
                <td style="text-align:left; padding:6px 8px;">
                    <span style="font-size:11px;border-radius:999px;padding:2px 8px;border:1px solid;display:inline-block;{{ $row['attendance_checked'] ? 'background:#e8f6ec;border-color:#b7dfc3;color:#1f6b35;' : 'background:#fff2e6;border-color:#ffd5b1;color:#8a4b14;' }}">
                        {{ $row['attendance_checked'] ? '確定済' : '未確認' }}
                    </span>
                </td>
                <td style="text-align:left; padding:6px 8px;">
                    <a href="{{ route('admin.attendance.index', $detailQuery) }}" style="display:inline-block;border:1px solid #d3dff0;border-radius:8px;padding:5px 10px;text-decoration:none;color:#1f4f8f;background:#fff;">
                        {{ $isSelectedRow ? '非表示' : '表示' }}
                    </a>
                </td>
                <td style="text-align:right; padding:6px 8px;">{{ $m['work_in_num'] ?? 0 }}</td>
                <td style="text-align:right; padding:6px 8px;">{{ $m['absence_num'] ?? 0 }}</td>
                <td style="text-align:right; padding:6px 8px;">{{ $m['work_horiday_num'] ?? 0 }}</td>
                <td style="text-align:right; padding:6px 8px;">{{ $m['work_time'] ?? 0 }}</td>
                <td style="text-align:right; padding:6px 8px;">{{ $m['horiday_true'] ?? 0 }}</td>
                <td style="text-align:right; padding:6px 8px;">{{ $m['overtime'] ?? 0 }}</td>
                <td style="text-align:right; padding:6px 8px;">{{ $m['night_over_time'] ?? 0 }}</td>
                <td style="text-align:left; padding:6px 8px;">
                    <form method="POST" action="{{ route('admin.attendance.check') }}">
                        @csrf
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                        <input type="hidden" name="target_staff_id" value="{{ $row['staff_id'] }}">
                        <input type="hidden" name="checked" value="{{ $row['attendance_checked'] ? '0' : '1' }}">
                        <button type="submit" style="border:1px solid #d3dff0;border-radius:8px;padding:5px 10px;{{ $row['attendance_checked'] ? 'background:#fff;color:#1f2937;' : 'background:#1f4f8f;color:#fff;border-color:#1f4f8f;' }}">
                            {{ $row['attendance_checked'] ? '確認解除' : '未確認' }}
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="13" style="text-align:left; padding:8px 10px;">対象スタッフがありません。</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
