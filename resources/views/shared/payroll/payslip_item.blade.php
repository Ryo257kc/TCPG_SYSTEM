@if (empty($rawRows) || count($rawRows) === 0)
<div class="payroll-sheet-empty">当月の明細はありません。</div>
@else
@php
$first = $rawRows[0];
$sikyuMonth = !empty($first['supply_month']) ? \Carbon\Carbon::parse($first['supply_month'])->format('Y年n月分') : '';
$showYen = fn($v) => (($v === null || $v === '' || (float)$v === 0.0) ? '' : number_format((float)$v));
$showVal = fn($v) => (($v === null || $v === '' || (float)$v === 0.0) ? '' : $v);
$showAny = fn($v) => ($v === null ? '' : (string)$v);
$makeAmountItem = fn($label, $amount) => [((float)($amount ?? 0) === 0.0) ? '' : $label, $showYen($amount)];
$makeValueItem = fn($label, $value) => [((string)$value === '' || (float)$value === 0.0) ? '' : $label, $showVal($value)];
@endphp

<div class="payroll-sheet-wrap">
    <div class="payroll-sheet-title">
        <h2>{{ $sikyuMonth }} 給与明細書</h2>
    </div>
    <div class="payroll-sheet-subtitle">
        <div>名前: {{ $showAny($first['staff_name'] ?? $targetStaffName ?? '') }}</div>
        <div>≪{{ $showAny($first['section_name'] ?? $storeName ?? '') }}≫ {{ $showAny($first['office_name'] ?? $companyName ?? '') }}</div>
    </div>

    @foreach ($rawRows as $row)
    @php
    $payTotal = $row['supply_sum'] ?? null;
    $deductTotal = $row['deduction_sum'] ?? null;
    $otherTotal = (float)($row['cost_liquidation'] ?? 0) + (float)($row['company_advance_cost'] ?? 0)-(float)($row['adjustment_year_end'] ?? 0);
    $transfer = (float)($row['transfer_amount'] ?? 0);

    $attendanceItems = [
    $makeValueItem('出勤日数', $row['work_in_num'] ?? ''),
    $makeValueItem('出勤時間', $row['work_time'] ?? ''),
    $makeValueItem('休日出勤日数', $row['work_horiday_num'] ?? ''),
    $makeValueItem('休日出勤時間', $row['work_time_num'] ?? ''),
    $makeValueItem('欠勤日数', $row['absence_num'] ?? ''),
    $makeValueItem('遅刻早退時間', $row['late_time'] ?? ''),
    $makeValueItem('普通残業時間', $row['overtime'] ?? ''),
    $makeValueItem('有休日数', $row['horiday_true'] ?? ''),
    $makeValueItem('有休残日数', $row['horiday_true_num'] ?? ''),
    ['税額表', $showAny($targetTaxCategory ?? '')],
    ['扶養人数', $showAny($row['fuyo_sum'] ?? '')],
    ];
    $attendanceItems = array_values(array_filter($attendanceItems, fn($it) => (string)($it[0] ?? '') !== '' || (string)($it[1] ?? '') !== ''));

    $payItems = [
    $makeAmountItem('基本給', $row['basic_salary'] ?? 0),
    $makeAmountItem('役員報酬', $row['allowance_amo_2'] ?? 0),
    $makeAmountItem('役職手当', $row['allowance_amo_16'] ?? 0),
    $makeAmountItem('職務手当', $row['allowance_amo_13'] ?? 0),
    $makeAmountItem('資格手当', $row['allowance_amo_11'] ?? 0),
    $makeAmountItem('請求手当', $row['allowance_amo_12'] ?? 0),
    $makeAmountItem('固定残業手当', $row['allowance_amo_17'] ?? 0),
    $makeAmountItem('管理手当', $row['allowance_amo_3'] ?? 0),
    $makeAmountItem('歩合手当', $row['allowance_amo_4'] ?? 0),
    $makeAmountItem('調整手当', $row['allowance_amo_5'] ?? 0),
    $makeAmountItem('残業手当', $row['allowance_amo_8'] ?? 0),
    $makeAmountItem('休日出勤手当', $row['allowance_amo_7'] ?? 0),
    $makeAmountItem('非課税通勤費', (float)($row['allowance_amo_6'] ?? 0) + (float)($row['traffic_addition'] ?? 0)),
    $makeAmountItem('課税通勤費', $row['allowance_amo_10'] ?? 0),
    $makeAmountItem('遅刻早退控除', $row['late_deduction'] ?? 0),
    $makeAmountItem('欠勤控除', $row['absence_deduction'] ?? 0),
    ];
    if (!empty($isBonus)) {
    array_unshift($payItems, $makeAmountItem('賞与支給額', $row['bonus_amo'] ?? 0));
    }
    $payItems = array_values(array_filter($payItems, fn($it) => (string)($it[0] ?? '') !== '' || (string)($it[1] ?? '') !== ''));

    $deductItems = [
    $makeAmountItem('健康保険料', $row['kenpo'] ?? 0),
    $makeAmountItem('介護保険料', $row['kaigo'] ?? 0),
    $makeAmountItem('子ども支援金', $row['child_support_funds'] ?? 0),
    $makeAmountItem('厚生年金保険', $row['kounen'] ?? 0),
    $makeAmountItem('雇用保険料', $row['koyou'] ?? 0),
    $makeAmountItem('住民税', $row['resident_tax'] ?? 0),
    $makeAmountItem('所得税', $row['income_tax'] ?? 0),
    $makeAmountItem('定額減税額', $row['koujyo_1'] ?? 0),
    $makeAmountItem('調整控除', $row['adjustment_cost'] ?? 0),
    ];
    $deductItems = array_values(array_filter($deductItems, fn($it) => (string)($it[0] ?? '') !== '' || (string)($it[1] ?? '') !== ''));

    $otherItems = [
    $makeAmountItem('立替経費清算', $row['cost_liquidation'] ?? 0),
    $makeAmountItem('年末調整額', $row['adjustment_year_end'] ?? 0),
    ];
    $otherItems = array_values(array_filter($otherItems, fn($it) => (string)($it[0] ?? '') !== '' || (string)($it[1] ?? '') !== ''));

    $lineCount = max(count($attendanceItems), count($payItems), count($deductItems), count($otherItems), 1);
    @endphp

    <div class="payroll-sheet-table-wrap">
        <table class="payroll-sheet-table">
            <colgroup>
                <col style="width:16%">
                <col style="width:9%">
                <col style="width:16%">
                <col style="width:9%">
                <col style="width:16%">
                <col style="width:9%">
                <col style="width:16%">
                <col style="width:9%">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="2">勤怠</th>
                    <th colspan="2">支給</th>
                    <th colspan="2">控除</th>
                    <th colspan="2">その他</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < $lineCount; $i++)
                    @php
                    $a=$attendanceItems[$i] ?? ['', '' ];
                    $p=$payItems[$i] ?? ['', '' ];
                    $d=$deductItems[$i] ?? ['', '' ];
                    $o=$otherItems[$i] ?? ['', '' ];
                    @endphp
                    <tr>
                    <td>{{ $a[0] }}</td>
                    <td>{{ $a[1] }}</td>
                    <td>{{ $p[0] }}</td>
                    <td>{{ $p[1] }}</td>
                    <td>{{ $d[0] }}</td>
                    <td>{{ $d[1] }}</td>
                    <td>{{ $o[0] }}</td>
                    <td>{{ $o[1] }}</td>
                    </tr>
                    @endfor
                    <tr class="payroll-sheet-sum-row">
                        <td colspan="2"></td>
                        <td>支給合計</td>
                        <td>{{ $showYen($payTotal) }}</td>
                        <td>控除合計</td>
                        <td>{{ $showYen($deductTotal) }}</td>
                        <td>その他合計</td>
                        <td>{{ $showYen($otherTotal) }}</td>
                    </tr>
            </tbody>
        </table>
    </div>

    @if (!empty($row['link']) || !empty($row['kyuyo_memo']))
    <div class="payroll-sheet-memo">
        @if (!empty($row['link']))
        <a href="{{ url('files/'.$row['link']) }}" target="_blank" rel="noopener">住民税通知書</a>
        @endif
        @if (!empty($row['kyuyo_memo']))
        <span style="margin-left:10px;">{{ $row['kyuyo_memo'] }}</span>
        @endif
    </div>
    @endif

    <div class="payroll-sheet-transfer">振込支給額 {{ $showYen($transfer) }}円</div>
    @endforeach
</div>
@endif
