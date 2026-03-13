@php
    $baseParams = ['month' => $selectedMonth, 'company_id' => $selectedCompanyId];
@endphp
<div class="attendance-list-toolbar">
    <form method="post" action="{{ route('admin.attendance.reflect') }}">
        @csrf
        <input type="hidden" name="month" value="{{ $selectedMonth }}">
        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
        <button class="btn" type="submit">&#21220;&#24608;&#19968;&#25324;&#21453;&#26144;</button>
    </form>
</div>
<div style="overflow:auto; border:1px solid #dce7f7; border-radius:10px; margin-bottom:10px;">
    <table>
        <thead>
        <tr>
            <th>&#35443;&#32048;</th>
            <th>ID</th>
            <th>&#21517;&#21069;</th>
            <th>&#37096;&#32626;</th>
            <th>&#24215;&#33303;</th>
            <th class="center">&#21220;&#24608;&#30906;&#35469;</th>
            <th class="num">&#20986;&#21220;&#26085;&#25968;</th>
            <th class="num">&#27424;&#21220;&#26085;&#25968;</th>
            <th class="num">&#20241;&#20986;&#26085;&#25968;</th>
            <th class="num">&#26377;&#20241;&#26085;&#25968;</th>
            <th class="num">&#23455;&#20685;&#26178;&#38291;</th>
            <th class="num">&#27531;&#26989;</th>
            <th class="num">&#28145;&#22812;</th>
            <th class="num">&#20241;&#20986;&#26178;&#38291;</th>
            <th class="num">&#36933;&#26089;&#26178;&#38291;</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($rows as $row)
            @php
                $m = (array) ($row['metrics'] ?? []);
                $isDetail = $selectedStaffId === $row['staff_id'];
                $toggleParams = $baseParams + ['staff_id' => $isDetail ? '' : $row['staff_id']];
                $confirmFormId = 'attendance-confirm-' . $row['staff_id'];
                $isAttendanceChecked = !empty($m['attendance_checked']);
                $showOrBlank = static fn ($value, int $decimals = 2): string => ((float) $value) === 0.0 ? '' : number_format((float) $value, $decimals);
            @endphp
            <tr @class(['attendance-list-row', 'is-active' => $isDetail])>
                <td><a class="btn btn-small" href="{{ route('admin.attendance.index', $toggleParams) }}">{!! $isDetail ? '&#19968;&#35239;' : '&#26085;&#21029;' !!}</a></td>
                <td>{{ $row['staff_id'] }}</td>
                <td>{{ $row['staff_name'] }}</td>
                <td>{{ $row['division'] }}</td>
                <td>{{ $row['store_name'] }}</td>
                <td class="center">
                    <button
                        @class([
                            'btn',
                            'btn-small',
                            'attendance-confirm-toggle',
                            'is-confirmed' => $isAttendanceChecked,
                            'is-unconfirmed' => !$isAttendanceChecked,
                        ])
                        type="submit"
                        form="{{ $confirmFormId }}"
                    >{!! $isAttendanceChecked ? '&#30906;&#23450;&#28168;' : '&#26410;&#30906;&#23450;' !!}</button>
                    <form id="{{ $confirmFormId }}" method="post" action="{{ route('admin.attendance.confirm') }}">
                        @csrf
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                        <input type="hidden" name="staff_id" value="{{ $row['staff_id'] }}">
                        <input type="hidden" name="checked" value="{{ $isAttendanceChecked ? 0 : 1 }}">
                    </form>
                </td>
                <td class="num">{{ number_format((float) ($m['work_in_num'] ?? 0), 2) }}</td>
                <td class="num">{{ $showOrBlank($m['absence_num'] ?? 0) }}</td>
                <td class="num">{{ $showOrBlank($m['work_holiday_num'] ?? 0) }}</td>
                <td class="num">{{ $showOrBlank($m['holiday_true'] ?? 0) }}</td>
                <td class="num">{{ number_format((float) ($m['work_time'] ?? 0), 2) }}</td>
                <td class="num">{{ $showOrBlank($m['overtime'] ?? 0) }}</td>
                <td class="num">{{ $showOrBlank($m['night_over_time'] ?? 0) }}</td>
                <td class="num">{{ $showOrBlank($m['holiday_work_time'] ?? 0) }}</td>
                <td class="num">{{ $showOrBlank($m['late_early_time'] ?? 0) }}</td>
            </tr>
        @empty
            <tr><td class="center" colspan="15">&#34920;&#31034;&#12487;&#12540;&#12479;&#12364;&#12354;&#12426;&#12414;&#12379;&#12435;&#12290;</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
