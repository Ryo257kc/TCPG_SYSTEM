@php
    $metrics = (array) ($detailMetrics ?? []);
    $shiftScheduledTotal = 0.0;
    $actualScheduledTotal = 0.0;
    $changeScheduledTotal = 0.0;
    $categorySummary = [];

    foreach (($dailyRows ?? []) as $row) {
        $shiftWork = (float) ($row['shift']['work'] ?? 0);
        $actualWork = (float) ($row['actual']['work'] ?? 0);
        $changeRaw = $row['change']['work'] ?? null;
        $changeWork = (is_null($changeRaw) || $changeRaw === '') ? $shiftWork : (float) $changeRaw;

        $shiftScheduledTotal += $shiftWork;
        $actualScheduledTotal += $actualWork;
        $changeScheduledTotal += $changeWork;

        $category = trim((string) ($row['category'] ?? ''));
        if ($category === '') {
            continue;
        }
        if (!isset($categorySummary[$category])) {
            $categorySummary[$category] = ['days' => 0, 'hours' => 0.0];
        }
        $categorySummary[$category]['days']++;
        $categorySummary[$category]['hours'] += (float) ($row['category_time'] ?? 0);
    }

    $fmt = static function ($v): string {
        $n = (float) $v;
        if (abs($n) < 0.000001) {
            return '0';
        }
        if (abs($n - (int) $n) < 0.000001) {
            return (string) (int) $n;
        }
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    };
@endphp

<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin:0 0 8px;">
    <div style="border:1px solid #dce7f7;border-radius:8px;background:#f8fbff;padding:6px 8px;">
        <div style="font-size:12px;font-weight:700;color:#1f4f8f;margin-bottom:6px;">勤怠</div>
        <div style="display:grid;grid-template-columns:1fr auto;gap:3px 8px;font-size:12px;">
            <div>出勤日数</div><div>{{ $metrics['work_in_num'] ?? 0 }}</div>
            <div>欠勤日数</div><div>{{ $metrics['absence_num'] ?? 0 }}</div>
            <div>休出日数</div><div>{{ $metrics['work_horiday_num'] ?? 0 }}</div>
            <div>有休日数</div><div>{{ $fmt($metrics['horiday_true'] ?? 0) }}</div>
        </div>
    </div>

    <div style="border:1px solid #dce7f7;border-radius:8px;background:#f8fbff;padding:6px 8px;">
        <div style="font-size:12px;font-weight:700;color:#1f4f8f;margin-bottom:6px;">実働時間</div>
        <div style="display:grid;grid-template-columns:1fr auto;gap:3px 8px;font-size:12px;">
            <div>出勤時間</div><div>{{ $fmt($changeScheduledTotal) }}</div>
            <div>残業</div><div>{{ $fmt($metrics['overtime'] ?? 0) }}</div>
            <div>深夜</div><div>{{ $fmt($metrics['night_over_time'] ?? 0) }}</div>
            <div>休出時間</div><div>{{ $fmt($metrics['work_horiday_time'] ?? 0) }}</div>
            <div>遅早時間</div><div>{{ $fmt($metrics['late_early_time'] ?? 0) }}</div>
        </div>
    </div>

    <div style="border:1px solid #dce7f7;border-radius:8px;background:#f8fbff;padding:6px 8px;">
        <div style="font-size:12px;font-weight:700;color:#1f4f8f;margin-bottom:6px;">所定合計</div>
        <div style="display:grid;grid-template-columns:1fr auto;gap:3px 8px;font-size:12px;">
            <div>シフト所定合計</div><div>{{ $fmt($shiftScheduledTotal) }}</div>
            <div>打刻所定合計</div><div>{{ $fmt($actualScheduledTotal) }}</div>
            <div>変更所定合計</div><div>{{ $fmt($changeScheduledTotal) }}</div>
        </div>
    </div>

    <div style="border:1px solid #dce7f7;border-radius:8px;background:#f8fbff;padding:6px 8px;">
        <div style="font-size:12px;font-weight:700;color:#1f4f8f;margin-bottom:4px;">勤怠区分集計（日数 / 時間）</div>
        <div style="display:grid;grid-template-columns:1fr auto auto;gap:4px 8px;font-size:12px;">
            @forelse($categorySummary as $name => $vals)
                <span>{{ $name }}</span>
                <span>{{ (int) ($vals['days'] ?? 0) }}日</span>
                <span>{{ $fmt($vals['hours'] ?? 0) }}h</span>
            @empty
                <span>区分データなし</span>
                <span></span>
                <span></span>
            @endforelse
        </div>
    </div>
</div>

<div style="overflow:auto; border:1px solid #dce7f7; border-radius:10px;">
    <table style="width:100%; border-collapse:collapse; min-width:1300px; background:#fff; font-size:11px; table-layout:fixed;">
        <colgroup>
            <col style="width:66px">
            <col style="width:48px">
            <col style="width:48px">
            <col style="width:44px">
            <col style="width:44px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:42px">
            <col style="width:44px">
            <col style="width:48px">
            <col style="width:52px">
            <col style="width:180px">
        </colgroup>
        <thead>
        <tr style="background:#f8fbff;">
            <th rowspan="2" style="text-align:center; padding:3px 1px;">日付</th>
            <th rowspan="2" style="text-align:center; padding:3px 1px; line-height:1.1;">休日<br>区分</th>
            <th rowspan="2" style="text-align:center; padding:3px 1px; line-height:1.1;">勤怠<br>区分</th>
            <th rowspan="2" style="text-align:center; padding:3px 1px; line-height:1.1;">区分<br>時間</th>
            <th rowspan="2" style="text-align:center; padding:3px 1px; line-height:1.1;">有休<br>使用</th>
            <th colspan="5" style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">シフト</th>
            <th colspan="5" style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">打刻</th>
            <th colspan="7" style="text-align:center; vertical-align:middle; padding:3px 1px; border-left:1px solid #dce7f7;">変更実績</th>
            <th rowspan="2" style="text-align:center; vertical-align:middle; padding:3px 1px; border-left:1px solid #dce7f7;">勤務店舗</th>
            <th rowspan="2" style="text-align:center; vertical-align:middle; padding:3px 1px; border-left:1px solid #dce7f7;">備考</th>
        </tr>
        <tr style="background:#f8fbff;">
            <th style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">始業</th>
            <th style="text-align:center; padding:3px 1px;">退出</th>
            <th style="text-align:center; padding:3px 1px;">入出</th>
            <th style="text-align:center; padding:3px 1px;">終業</th>
            <th style="text-align:center; padding:3px 1px;">所定</th>

            <th style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">始業</th>
            <th style="text-align:center; padding:3px 1px;">退出</th>
            <th style="text-align:center; padding:3px 1px;">入出</th>
            <th style="text-align:center; padding:3px 1px;">終業</th>
            <th style="text-align:center; padding:3px 1px;">所定</th>

            <th style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">始業</th>
            <th style="text-align:center; padding:3px 1px;">退出</th>
            <th style="text-align:center; padding:3px 1px;">入出</th>
            <th style="text-align:center; padding:3px 1px;">終業</th>
            <th style="text-align:center; padding:3px 1px;">所定</th>
            <th style="text-align:center; padding:3px 1px;">残業</th>
            <th style="text-align:center; padding:3px 1px;">深夜</th>
        </tr>
        </thead>
        <tbody>
        @forelse($dailyRows as $d)
            @php
                $dateLabel = !empty($d['date']) ? date('n/j', strtotime((string) $d['date'])) . '(' . ($d['weekday'] ?? '') . ')' : '';
                $isHoliday = in_array(trim((string) ($d['work_holiday'] ?? '')), ['休日', '法休'], true);
                $s = $d['shift'] ?? [];
                $a = $d['actual'] ?? [];
                $c = $d['change'] ?? [];
                $shiftWorkDisp = (string) ($s['work'] ?? '');
                $actualWorkDisp = (string) ($a['work'] ?? '');
                $changeWorkRaw = (string) ($c['work'] ?? '');
                $changeWorkDisp = $changeWorkRaw !== '' ? $changeWorkRaw : $shiftWorkDisp;
                $shiftWorkNum = is_numeric($shiftWorkDisp) ? (float) $shiftWorkDisp : null;
                $changeWorkNum = is_numeric($changeWorkDisp) ? (float) $changeWorkDisp : null;
                $isOverEight = $changeWorkNum !== null && $changeWorkNum > 8;
                $isMismatch = $changeWorkRaw !== '' && $shiftWorkNum !== null && $changeWorkNum !== null && abs($changeWorkNum - $shiftWorkNum) > 0.0001;
                $changeWorkWarnStyle = ($isOverEight || $isMismatch)
                    ? 'background:#fff1f1;color:#b42318;font-weight:700;'
                    : '';
            @endphp
            <tr @if($isHoliday) style="background:#f5f6f8;color:#6b7280;" @endif>
                <td style="text-align:center; padding:2px 1px; white-space:nowrap;">{{ $dateLabel }}</td>
                <td style="text-align:center; padding:2px 1px; white-space:nowrap;">{{ $d['work_holiday'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 1px; white-space:nowrap;">{{ $d['category'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 1px; white-space:nowrap;">{{ $d['category_time'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 1px; white-space:nowrap;">{{ $d['paid_leave_used'] ?? '' }}</td>

                <td style="text-align:center; padding:2px 0; white-space:nowrap; border-left:1px solid #dce7f7;">{{ $s['start'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $s['leave'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $s['break_out'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $s['end'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $shiftWorkDisp }}</td>

                <td style="text-align:center; padding:2px 0; white-space:nowrap; border-left:1px solid #dce7f7;">{{ $a['start'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $a['leave'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $a['break_out'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $a['end'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $actualWorkDisp }}</td>

                <td style="text-align:center; padding:2px 0; white-space:nowrap; border-left:1px solid #dce7f7;">{{ $c['start'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $c['leave'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $c['break_out'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $c['end'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;{{ $changeWorkWarnStyle }}">{{ $changeWorkDisp }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $d['overtime'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $d['night_over_time'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 1px; white-space:nowrap; border-left:1px solid #dce7f7;">{{ $d['section'] ?? '' }}</td>
                <td style="text-align:left; padding:2px 4px; white-space:nowrap; border-left:1px solid #dce7f7;">{{ $d['remark'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td style="text-align:center; padding:8px 4px;" colspan="24">対象月の日別勤怠データがありません。</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
