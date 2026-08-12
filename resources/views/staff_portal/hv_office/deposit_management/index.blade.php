<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 入金管理表</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .deposit-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .deposit-toolbar label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .deposit-toolbar input,
        .deposit-toolbar select {
            min-height: 30px;
        }

        .deposit-confirmed {
            color: #b00020;
            font-weight: 700;
        }

        .deposit-total-row td {
            font-weight: 700;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">入金管理表</h2>
            </div>

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="get" action="{{ route('hv_office.deposit_management') }}" class="deposit-toolbar">
                <label>
                    対象月:
                    <input type="month" name="target_month" value="{{ $target_month }}">
                </label>
                @if($canSelectStaff)
                <select name="payment_staff">
                    <option value="">--</option>
                    @foreach($staffOptions as $option)
                    @php
                    $staffLabel = $option['display_name_ja'] !== '' ? $option['display_name_ja'] : $option['staff_name'];
                    @endphp
                    <option value="{{ $option['staff_id'] }}" @selected((string) $target_staff_id===(string) $option['staff_id'])>{{ $staffLabel }}</option>
                    @endforeach
                </select>
                @endif
                <button type="submit" class="btn">表示</button>
                <button type="submit" class="btn" formaction="{{ route('hv_office.deposit_management.print') }}" formtarget="_blank">印刷</button>
            </form>

            <p>
                @if($isAllConfirmed)
                <span class="deposit-confirmed">確定日：{{ $confirmedAt ? \Carbon\Carbon::parse($confirmedAt)->format('Y-m-d H:i:s') : '' }}</span>
                @else
                この入金管理表は未確定です
                @endif
            </p>

            @if($rows->count() === 0)
            <div class="empty">「{{ $target_month }}」のデータがありません。</div>
            @else
            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>患者名</th>
                            <th>負担割合</th>
                            <th>距離(Km)</th>
                            <th>医療助成</th>
                            <th>単価×回数</th>
                            <th>請求金額</th>
                            <th>自費</th>
                            <th>過不足金額</th>
                            <th>集金額</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        @php
                        $isPrivate = (int) ($row->is_private_charge ?? 0) === 1;
                        $rowTotal = (float) $row->unit_price * (float) $row->billing_count;
                        $collectedDisplay = (int) ($row->is_bank_transfer ?? 0) === 1
                            ? '振込'
                            : number_format((float) ($row->collected_amount ?? 0));
                        @endphp
                        <tr>
                            <td>{{ $row->patient_name }}</td>
                            <td>{{ $row->burden_ratio }}割</td>
                            <td>{{ (float) ($row->window_distance ?? 0) == 0.0 ? '' : number_format((float) $row->window_distance, 1) }}</td>
                            <td>{{ (int) ($row->subsidy_limit_count ?? 0) === 0 ? '' : '〇' }}</td>
                            <td>{{ number_format((float) $row->unit_price) }} × {{ $row->billing_count }}</td>
                            <td>{{ $isPrivate ? '' : number_format($rowTotal) }}</td>
                            <td>{{ $isPrivate ? number_format($rowTotal) : '' }}</td>
                            <td>{{ number_format((float) ($row->adjustment_amount ?? 0)) }}</td>
                            <td>{{ $collectedDisplay }}</td>
                        </tr>
                        @endforeach
                        <tr class="deposit-total-row">
                            <td colspan="5">合計</td>
                            <td>{{ number_format($totals['billing_total']) }}</td>
                            <td>{{ number_format($totals['private_total']) }}</td>
                            <td>{{ number_format($totals['adjustment_total']) }}</td>
                            <td>{{ number_format($totals['collected_total']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif

            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>
