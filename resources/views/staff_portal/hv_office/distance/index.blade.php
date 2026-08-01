<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 移動距離集計</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .distance-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .distance-toolbar label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .distance-toolbar input,
        .distance-toolbar select {
            min-height: 30px;
        }

        .distance-summary {
            margin-top: 14px;
            line-height: 1.8;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">移動距離集計</h2>
            </div>

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="get" action="{{ route('hv_office.distance') }}" class="distance-toolbar">
                <label>
                    日付:
                    <input type="month" name="target_month" value="{{ $target_month }}">
                </label>
                <select name="staff_name">
                    <option>--</option>
                    @foreach($staff_name_options as $option)
                    @php
                    $staffLabel = $option['display_name_ja'] !== '' ? $option['display_name_ja'] : $option['staff_name'];
                    @endphp
                    <option value="{{ $option['staff_id'] }}" @selected((string) $target_staff_id===(string) $option['staff_id'])>{{ $staffLabel }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn">表示</button>
                <button type="submit" class="btn" formaction="{{ route('hv_office.distance.print') }}" formtarget="_blank">印刷</button>
            </form>

            @if($rows->count() === 0)
            <div class="empty">「{{ $target_month }}」のデータがありません。</div>
            @else
            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table">
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
                        $week = ['日', '月', '火', '水', '木', '金', '土'];
                        $patientCount = (int) ($row->patient_count ?? 0) - (int) ($row->duplicate_patient_count ?? 0);
                        $distance = (float) ($row->total_distance ?? 0);
                        $staffDisplayName = trim((string) ($row->staff_display_name_ja ?? ''));
                        if ($staffDisplayName === '') {
                        $staffDisplayName = trim((string) ($row->staff_master_name ?? ''));
                        }
                        @endphp
                        <tr>
                            <td>{{ $treatmentDate ? $treatmentDate->format('Y-m-d') . ' (' . $week[(int) $treatmentDate->format('w')] . ')' : '' }}</td>
                            <td>{{ $row->holiday_category }}</td>
                            <td>{{ number_format($patientCount) }}</td>
                            <td>{{ $distance == 0.0 ? '' : $row->total_distance }}</td>
                            <td>{{ $staffDisplayName }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @php
            $summaryPatientCount = (int) ($summary->patient_count ?? 0);
            $summaryDuplicateCount = (int) ($summary->duplicate_patient_count ?? 0);
            $summaryDistance = (float) ($summary->total_distance ?? 0);
            @endphp
            <p class="distance-summary">
                人数：{{ number_format($summaryPatientCount - $summaryDuplicateCount) }}名（重複人数：{{ number_format($summaryDuplicateCount) }}名）<br>
                距離：{{ $summaryDistance == 0.0 ? '' : $summary->total_distance }}km
            </p>
            @endif

            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>
