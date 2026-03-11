@php
    $metrics = (array) ($detailMetrics ?? []);
    $categoryOptions = array_values(array_filter((array) ($attendanceCategoryOptions ?? []), fn ($v) => trim((string) $v) !== ''));
    $storeOptionsList = array_values((array) ($storeOptions ?? []));
    $storeNameToCode = [];
    foreach ($storeOptionsList as $opt) {
        $n = trim((string) ($opt['name'] ?? ''));
        $c = trim((string) ($opt['code'] ?? ''));
        if ($n !== '' && $c !== '' && !isset($storeNameToCode[$n])) {
            $storeNameToCode[$n] = $c;
        }
    }

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
            <div>遅早控除時間</div><div>{{ $fmt($metrics['late_early_time'] ?? 0) }}</div>
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
                <span>勤怠区分データなし</span><span></span><span></span>
            @endforelse
        </div>
    </div>
</div>

<div style="overflow:auto; border:1px solid #dce7f7; border-radius:10px;">
    <table style="width:100%; border-collapse:collapse; min-width:1320px; background:#fff; font-size:11px; table-layout:fixed;">
        <colgroup>
            <col style="width:52px"><col style="width:30px"><col style="width:56px"><col style="width:56px"><col style="width:56px">
            <col style="width:50px"><col style="width:50px"><col style="width:50px"><col style="width:50px"><col style="width:34px">
            <col style="width:36px"><col style="width:36px"><col style="width:36px"><col style="width:36px"><col style="width:36px">
            <col style="width:50px"><col style="width:50px"><col style="width:50px"><col style="width:50px"><col style="width:50px"><col style="width:50px"><col style="width:50px">
            <col style="width:40px"><col style="width:92px"><col style="width:28px"><col style="width:92px"><col style="width:56px">
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
            <th colspan="7" style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">変更実績</th>
            <th rowspan="2" style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7; line-height:1.1;">勤務<br>店舗</th>
            <th rowspan="2" style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">備考</th>
            <th rowspan="2" style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">差戻</th>
            <th rowspan="2" style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7; line-height:1.1;">差戻<br>備考</th>
            <th rowspan="2" style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">操作</th>
        </tr>
        <tr style="background:#f8fbff;">
            <th style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">始業</th><th style="text-align:center; padding:3px 1px;">退出</th><th style="text-align:center; padding:3px 1px;">入出</th><th style="text-align:center; padding:3px 1px;">終業</th><th style="text-align:center; padding:3px 1px;">所定</th>
            <th style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">始業</th><th style="text-align:center; padding:3px 1px;">退出</th><th style="text-align:center; padding:3px 1px;">入出</th><th style="text-align:center; padding:3px 1px;">終業</th><th style="text-align:center; padding:3px 1px;">所定</th>
            <th style="text-align:center; padding:3px 1px; border-left:1px solid #dce7f7;">始業</th><th style="text-align:center; padding:3px 1px;">退出</th><th style="text-align:center; padding:3px 1px;">入出</th><th style="text-align:center; padding:3px 1px;">終業</th><th style="text-align:center; padding:3px 1px;">所定</th><th style="text-align:center; padding:3px 1px;">残業</th><th style="text-align:center; padding:3px 1px;">深夜</th>
        </tr>
        </thead>
        <tbody>
        @forelse($dailyRows as $idx => $d)
            @php
                $dateLabel = !empty($d['date']) ? date('n/j', strtotime((string) $d['date'])) . '(' . ($d['weekday'] ?? '') . ')' : '';
                $isHoliday = in_array(trim((string) ($d['work_holiday'] ?? '')), ['休日', '法休'], true)
                    || trim((string) ($d['category'] ?? '')) === '公休';
                $s = $d['shift'] ?? [];
                $a = $d['actual'] ?? [];
                $c = $d['change'] ?? [];
                $rowId = 'day-edit-' . $idx;
                $shiftWorkDisp = (string) ($s['work'] ?? '');
                $changeWorkRaw = (string) ($c['work'] ?? '');
                $changeWorkDisp = $changeWorkRaw !== '' ? $changeWorkRaw : $shiftWorkDisp;
                $sectionCurrent = trim((string) ($d['section_raw'] ?? ''));
                $isReturned = ((int)($d['returned_flag'] ?? 0) === 1);
                if ($sectionCurrent === '') {
                    $sectionLabel = trim((string) ($d['section'] ?? ''));
                    $sectionCurrent = $storeNameToCode[$sectionLabel] ?? '';
                }
            @endphp
            <tr
                @if($isReturned)
                    style="background:#fff1f2;"
                @elseif($isHoliday)
                    style="background:#f5f6f8;color:#6b7280;"
                @endif
            >
                <td style="text-align:center; padding:2px 1px; white-space:nowrap;">{{ $dateLabel }}</td>
                <td style="text-align:center; padding:2px 1px; white-space:nowrap;">{{ $d['work_holiday'] ?? '' }}</td>

                <td style="text-align:center; padding:2px 1px; white-space:nowrap;">
                    <span class="v-show">{{ $d['category'] ?? '' }}</span>
                    <select form="{{ $rowId }}" class="v-edit" name="category" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                        <option value=""></option>
                        @foreach ($categoryOptions as $opt)
                            <option value="{{ $opt }}" @selected((string)($d['category'] ?? '') === (string)$opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="text-align:center; padding:2px 1px; white-space:nowrap;">
                    <span class="v-show">{{ $d['category_time'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" name="category_time" value="{{ $d['category_time'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 1px; white-space:nowrap;">
                    <span class="v-show">{{ $d['paid_leave_used'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" name="paid_leave_used" value="{{ $d['paid_leave_used'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>

                <td style="text-align:center; padding:2px 0; white-space:nowrap; border-left:1px solid #dce7f7;">
                    <span class="v-show">{{ $s['start'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" type="text" inputmode="numeric" maxlength="5" name="shift_start" value="{{ $s['start'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">
                    <span class="v-show">{{ $s['leave'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" type="text" inputmode="numeric" maxlength="5" name="shift_leave" value="{{ $s['leave'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">
                    <span class="v-show">{{ $s['break_out'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" type="text" inputmode="numeric" maxlength="5" name="shift_break_out" value="{{ $s['break_out'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">
                    <span class="v-show">{{ $s['end'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" type="text" inputmode="numeric" maxlength="5" name="shift_end" value="{{ $s['end'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">
                    <span class="v-show keep-visible">{{ $shiftWorkDisp }}</span>
                    <input form="{{ $rowId }}" type="hidden" name="shift_work" value="{{ $shiftWorkDisp }}">
                </td>

                <td style="text-align:center; padding:2px 0; white-space:nowrap; border-left:1px solid #dce7f7;">{{ $a['start'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $a['leave'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $a['break_out'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $a['end'] ?? '' }}</td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">{{ $a['work'] ?? '' }}</td>

                <td style="text-align:center; padding:2px 0; white-space:nowrap; border-left:1px solid #dce7f7;">
                    <span class="v-show">{{ $c['start'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" type="text" inputmode="numeric" maxlength="5" name="change_start" value="{{ $c['start'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">
                    <span class="v-show">{{ $c['leave'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" type="text" inputmode="numeric" maxlength="5" name="change_leave" value="{{ $c['leave'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">
                    <span class="v-show">{{ $c['break_out'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" type="text" inputmode="numeric" maxlength="5" name="change_break_out" value="{{ $c['break_out'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">
                    <span class="v-show">{{ $c['end'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" type="text" inputmode="numeric" maxlength="5" name="change_end" value="{{ $c['end'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">
                    <span class="v-show">{{ $changeWorkDisp }}</span>
                    <input form="{{ $rowId }}" class="v-edit" name="change_work" value="{{ $changeWorkDisp }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">
                    <span class="v-show">{{ $d['overtime'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" name="overtime" value="{{ $d['overtime'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 0; white-space:nowrap;">
                    <span class="v-show">{{ $d['night_over_time'] ?? '' }}</span>
                    <input form="{{ $rowId }}" class="v-edit" name="night_over_time" value="{{ $d['night_over_time'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                </td>
                <td style="text-align:center; padding:2px 1px; white-space:nowrap; border-left:1px solid #dce7f7;">
                    <span class="v-show">{{ $d['section'] ?? '' }}</span>
                    <select form="{{ $rowId }}" class="v-edit" name="section" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;text-align:center;">
                        <option value=""></option>
                        @foreach ($storeOptionsList as $opt)
                            <option value="{{ $opt['code'] ?? '' }}" @selected($sectionCurrent !== '' && $sectionCurrent === (string)($opt['code'] ?? ''))>
                                {{ $opt['name'] ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td style="text-align:left; padding:2px 4px; white-space:nowrap; border-left:1px solid #dce7f7;">
                    <span class="v-show" title="{{ $d['remark'] ?? '' }}" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $d['remark'] ?? '' }}
                    </span>
                    <input form="{{ $rowId }}" class="v-edit" name="remark" value="{{ $d['remark'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;">
                </td>
                <td style="text-align:center; padding:2px 1px; white-space:nowrap; border-left:1px solid #dce7f7;">
                    <span class="v-show" @if($isReturned) style="color:#b91c1c;font-weight:700;" @endif>
                        @if($isReturned)有@endif
                    </span>
                    <label class="v-edit" style="display:none;align-items:center;justify-content:center;">
                        <input form="{{ $rowId }}" type="hidden" name="returned_flag" value="0">
                        <input form="{{ $rowId }}" type="checkbox" name="returned_flag" value="1" @checked($isReturned)>
                    </label>
                </td>
                <td style="text-align:left; padding:2px 4px; white-space:nowrap; border-left:1px solid #dce7f7;">
                    <span class="v-show" title="{{ $d['returned_note'] ?? '' }}" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $d['returned_note'] ?? '' }}
                    </span>
                    <input form="{{ $rowId }}" class="v-edit" name="returned_note" value="{{ $d['returned_note'] ?? '' }}" style="display:none;width:100%;max-width:100%;box-sizing:border-box;font-size:13px;padding:3px 4px;">
                </td>
                <td style="text-align:center; padding:1px 0; border-left:1px solid #dce7f7; white-space:nowrap;">
                    <form id="{{ $rowId }}" method="POST" action="{{ route('admin.attendance.day-update') }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId ?? '' }}">
                        <input type="hidden" name="staff_id" value="{{ $detailStaffId ?? '' }}">
                        <input type="hidden" name="work_date" value="{{ $d['date'] ?? '' }}">
                        <input type="hidden" name="time_no" value="{{ $d['time_no'] ?? '' }}">
                    </form>
                    <button type="button" class="btn-edit" style="font-size:10px;padding:1px 3px;border:1px solid #c7d7ef;border-radius:6px;background:#fff;">編集</button>
                    <button type="submit" form="{{ $rowId }}" class="btn-save" style="display:none;font-size:10px;padding:1px 3px;border:1px solid #1f4f8f;border-radius:6px;background:#1f4f8f;color:#fff;">保存</button>
                    <button type="button" class="btn-cancel" style="display:none;font-size:10px;padding:1px 3px;border:1px solid #c7d7ef;border-radius:6px;background:#fff;">取消</button>
                </td>
            </tr>
        @empty
            <tr><td style="text-align:center; padding:8px 4px;" colspan="27">対象月の日別勤怠データがありません。</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<script>
(() => {
  document.querySelectorAll('tr').forEach((tr) => {
    const edit = tr.querySelector('.btn-edit');
    const save = tr.querySelector('.btn-save');
    const cancel = tr.querySelector('.btn-cancel');
    if (!edit || !save || !cancel) return;

    const shows = tr.querySelectorAll('.v-show');
    const edits = tr.querySelectorAll('.v-edit');
    const toEdit = () => {
      shows.forEach(e => { if (!e.classList.contains('keep-visible')) e.style.display = 'none'; });
      edits.forEach(e => e.style.display = 'inline-block');
      edit.style.display = 'none';
      save.style.display = 'inline-block';
      cancel.style.display = 'inline-block';
    };
    const toView = () => {
      shows.forEach(e => e.style.display = 'inline');
      edits.forEach(e => e.style.display = 'none');
      edit.style.display = 'inline-block';
      save.style.display = 'none';
      cancel.style.display = 'none';
    };
    edit.addEventListener('click', toEdit);
    cancel.addEventListener('click', toView);
  });
})();
</script>
