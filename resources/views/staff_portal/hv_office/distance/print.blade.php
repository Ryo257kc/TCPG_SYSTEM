<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 移動距離集計 印刷</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        .print-summary {
            margin-top: 12px;
            font-size: 14px;
            line-height: 1.8;
        }
    </style>
</head>

<body>
    @php
    $week = ['日', '月', '火', '水', '木', '金', '土'];
    $formatDistance = static function ($value): string {
    if ($value === null || $value === '' || !is_numeric($value)) {
    return '';
    }
    $number = (float) $value;
    if ($number == 0.0) {
    return '';
    }
    return rtrim(rtrim(number_format($number, 1, '.', ''), '0'), '.');
    };
    $firstRow = $rows->first();
    $staffDisplayName = trim((string) ($firstRow->staff_display_name_ja ?? ''));
    if ($staffDisplayName === '') {
    $staffDisplayName = trim((string) ($firstRow->staff_master_name ?? ''));
    }
    $summaryPatientCount = (int) ($summary->patient_count ?? 0);
    $summaryDuplicateCount = (int) ($summary->duplicate_patient_count ?? 0);
    @endphp

    <button type="button" class="print-button" onclick="window.print()">このページを印刷する</button>

    <section class="print-page">
        <h1 class="print-title">移動距離集計</h1>

        <div class="print-meta">
            <div>月度：{{ $target_month }}</div>
            <div>担当：{{ $staffDisplayName }}</div>
        </div>

        @if($rows->count() === 0)
        <div class="empty-message">表示するデータがありません。</div>
        @else
        <table class="print-table">
            <colgroup>
                <col style="width: 26%;">
                <col style="width: 18%;">
                <col style="width: 14%;">
                <col style="width: 18%;">
                <col style="width: 24%;">
            </colgroup>
            <thead>
                <tr>
                    <th>施術日</th>
                    <th>休日区分</th>
                    <th>人数</th>
                    <th>距離</th>
                    <th>担当</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                @php
                $treatmentDate = $row->treatment_date ? \Carbon\Carbon::parse($row->treatment_date) : null;
                $patientCount = (int) ($row->patient_count ?? 0) - (int) ($row->duplicate_patient_count ?? 0);
                $rowStaffDisplayName = trim((string) ($row->staff_display_name_ja ?? ''));
                if ($rowStaffDisplayName === '') {
                $rowStaffDisplayName = trim((string) ($row->staff_master_name ?? ''));
                }
                @endphp
                <tr>
                    <td>{{ $treatmentDate ? $treatmentDate->format('Y-m-d') . ' (' . $week[(int) $treatmentDate->format('w')] . ')' : '' }}</td>
                    <td>{{ $row->holiday_category }}</td>
                    <td>{{ number_format($patientCount) }}</td>
                    <td>{{ $formatDistance($row->total_distance) }}</td>
                    <td>{{ $rowStaffDisplayName }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p class="print-summary">
            人数：{{ number_format($summaryPatientCount - $summaryDuplicateCount) }}名（重複人数：{{ number_format($summaryDuplicateCount) }}名）<br>
            距離：{{ $formatDistance($summary->total_distance ?? null) }}km
        </p>
        @endif
    </section>
</body>

</html>
