<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 往診月間回数</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <style>
        .monthly-visit-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 8px 12px;
            margin-bottom: 12px;
            min-width: 0;
        }

        .monthly-visit-field {
            display: grid;
            gap: 3px;
            font-size: 12px;
        }

        .monthly-visit-field input,
        .monthly-visit-field select {
            min-width: 80px;
        }

        /* .monthly-visit-panel .status,
        .monthly-visit-panel .error {
            margin: 0 0 10px;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.5;
        } */


        .monthly-visit-table-wrap {
            overflow: auto;
            max-height: 52vh;
            min-width: 0;
            max-width: 100%;
        }

        .monthly-visit-panel {
            min-width: 0;
            max-width: 100%;
            overflow-x: hidden;
        }

        .monthly-visit-table th,
        .monthly-visit-table td {
            white-space: nowrap;
            text-align: center;
            padding: 2px 2px;
            font-size: 12px;
        }

        .monthly-visit-table .text-left {
            text-align: left;
        }

        .monthly-visit-table-wrap .monthly-visit-table th:nth-child(1),
        .monthly-visit-table-wrap .monthly-visit-table td:nth-child(1) {
            position: sticky;
            left: 0;
            z-index: 2;
            background: rgba(255, 255, 255, 0.96);
        }

        .monthly-visit-table-wrap .monthly-visit-table th:nth-child(2),
        .monthly-visit-table-wrap .monthly-visit-table td:nth-child(2) {
            position: sticky;
            left: 100px;
            z-index: 2;
            background: rgba(255, 255, 255, 0.96);
        }

        .monthly-visit-table-wrap .monthly-visit-table th:nth-child(3),
        .monthly-visit-table-wrap .monthly-visit-table td:nth-child(3) {
            position: sticky;
            left: 160px;
            z-index: 2;
            background: rgba(255, 255, 255, 0.96);
        }

        .monthly-visit-table-wrap .monthly-visit-table thead th:nth-child(-n+3) {
            z-index: 3;
            background: #fff4e8;
        }

        .monthly-visit-day-cell {
            width: 30px;
            min-width: 30px;
        }

        .monthly-visit-day-multi {
            color: #d0021b;
            font-weight: 700;
        }

        .monthly-visit-detail-button-cell {
            width: 30px;
            min-width: 30px;
            max-width: 30px;
        }

        .monthly-visit-detail-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            padding: 0;
            line-height: 1;
            font-size: 0;
            text-decoration: none;
        }

        .monthly-visit-detail-button::before {
            content: "+";
            font-size: 14px;
        }

        .monthly-visit-selected-row {
            background-color: #fff3cd;
        }

        .monthly-visit-detail {
            margin-top: 14px;
        }

        .monthly-visit-detail-sub-row td {
            background: rgba(255, 250, 243, 0.96);
        }

        .monthly-visit-view-value {
            display: inline-block;
        }

        .monthly-visit-edit-input,
        .monthly-visit-save-button,
        .monthly-visit-cancel-button {
            display: none;
        }

        .monthly-visit-detail-row.is-editing .monthly-visit-view-value,
        .monthly-visit-detail-row.is-editing .monthly-visit-edit-button {
            display: none;
        }

        .monthly-visit-detail-row.is-editing .monthly-visit-edit-input {
            display: inline-block;
            font-size: 12px;
            box-sizing: border-box;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        .monthly-visit-detail-row.is-editing input.monthly-visit-edit-input[type="date"] {
            font-size: 12px;
            line-height: 1.2;
        }

        .monthly-visit-detail-row.is-editing .monthly-visit-save-button,
        .monthly-visit-detail-row.is-editing .monthly-visit-cancel-button {
            display: inline-flex;
        }

        .monthly-visit-detail-action-cell {
            white-space: nowrap;
            vertical-align: middle;
            text-align: center;
        }

        .monthly-visit-detail-action-cell .btn_small {
            margin: 2px 0;
        }

        .monthly-visit-detail-row.is-editing .monthly-visit-detail-action-cell {
            display: table-cell;
        }

        .monthly-visit-detail-row.is-editing .monthly-visit-detail-action-cell .monthly-visit-save-button,
        .monthly-visit-detail-row.is-editing .monthly-visit-detail-action-cell .monthly-visit-cancel-button {
            display: flex;
            margin-left: auto;
            margin-right: auto;
            justify-content: center;
        }

        .monthly-visit-patient-name-filter {
            width: 140px;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel sales-panel staff-viewport-panel monthly-visit-panel">

            <div class="content-head">
                <h2 class="content-title">往診月間回数</h2>
            </div>

            @if(session('status'))
            <div class="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif
            <div class="error" data-monthly-visit-print-error style="display: none;"></div>

            <form method="get" action="{{ route('home_visit.monthly_visit') }}" class="monthly-visit-toolbar" data-print-url="{{ route('home_visit.monthly_visit.print') }}">
                <label class="monthly-visit-field">
                    <span>施術月</span>
                    <input type="month" name="target_month" value="{{ $target_month }}">
                </label>

                <label class="monthly-visit-field">
                    <span>切替</span>
                    <select name="switch_type">
                        <option value="">--</option>
                        <option value="1" {{ $switch_type === '1' ? 'selected' : '' }}>鍼灸</option>
                        <option value="2" {{ $switch_type === '2' ? 'selected' : '' }}>往療</option>
                        <option value="3" {{ $switch_type === '3' ? 'selected' : '' }}>マッサージ</option>
                        <option value="4" {{ $switch_type === '4' ? 'selected' : '' }}>日報店舗空白</option>
                    </select>
                </label>

                <label class="monthly-visit-field">
                    <span>日報店舗</span>
                    <select name="daily_report_store_name">
                        <option value="">全て</option>
                        @foreach($daily_report_store_name_options as $option)
                        <option value="{{ $option }}" {{ $daily_report_store_name === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="monthly-visit-field">
                    <span>請求担当</span>
                    <select name="billing_staff">
                        <option value="">全て</option>
                        @foreach($billing_staff_options as $option)
                        <option value="{{ $option['staff_id'] }}" {{ $billing_staff === $option['staff_id'] ? 'selected' : '' }}>{{ $option['staff_name'] }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="monthly-visit-field">
                    <span>日報施設</span>
                    <select name="daily_report_facility_name">
                        <option value="">全て</option>
                        @foreach($daily_report_facility_name_options as $option)
                        <option value="{{ $option }}" {{ $daily_report_facility_name === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="monthly-visit-field">
                    <span>患者名</span>
                    <input class="monthly-visit-patient-name-filter" type="text" name="patient_name" value="{{ $patient_name }}">
                </label>

                <label class="monthly-visit-field">
                    <span>印刷</span>
                    <select name="print_type" data-monthly-visit-print-type>
                        <option value=""></option>
                        <option value="1">往療個別</option>
                        <option value="2">往療一括</option>
                        <option value="3">日別集計</option>
                    </select>
                </label>

                <button type="submit" class="btn">表示</button>
                <input type="hidden" name="patient_id" value="{{ $patient_id }}">
                <a href="{{ route('home_visit.monthly_visit') }}" class="btn">クリア</a>
            </form>

            <div class="table-wrap monthly-visit-table-wrap">

                <table class="data-table monthly-visit-table">
                    <colgroup>
                        <col style="width: 100px;">
                        <col style="width: 60px;">
                        <col style="width: 80px;">
                        @foreach($days as $day)
                        <col style="width: 30px;">
                        @endforeach
                        <col style="width: 40px;">
                        <col style="width: 90px;">
                        <col style="width: 65px;">
                        <col style="width: 65px;">
                        <col style="width: 30px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>患者名</th>
                            <th>同意区分</th>
                            <th>同意日</th>
                            @foreach($days as $day)
                            <th class="monthly-visit-day-cell">{{ $day }}</th>
                            @endforeach
                            <th>回数</th>
                            <th>日報施設</th>
                            <th>請求担当</th>
                            <th>日報店舗</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr class="{{ (string) $patient_id === (string) $row->patient_id ? 'monthly-visit-selected-row' : '' }}">
                            <td class="text-left">{{ $row->patient_name }}</td>
                            <td>{{ $row->consent_category }}</td>
                            <td>
                                @if(!empty($row->consent_date) && \Carbon\Carbon::parse($row->consent_date)->format('Y-m-d') !== '1900-01-01')
                                {{ \Carbon\Carbon::parse($row->consent_date)->format('Y/m/d') }}
                                @endif
                            </td>
                            @foreach($days as $day)
                            @php
                            $dayValue = $row->day_values[$day] ?? '';
                            $isMultiVisit = is_numeric($dayValue) && (int) $dayValue >= 2;
                            @endphp
                            <td class="monthly-visit-day-cell {{ $isMultiVisit ? 'monthly-visit-day-multi' : '' }}">{{ $dayValue }}</td>
                            @endforeach
                            <td>{{ $row->total_count }}</td>
                            <td class="text-left">{{ $row->daily_report_facility_name }}</td>
                            <td>{{ $row->billing_staff_staff_name }}</td>
                            <td>{{ $row->daily_report_store_name }}</td>
                            <td class="monthly-visit-detail-button-cell">
                                <a class="btn_small monthly-visit-detail-button" href="{{ route('home_visit.monthly_visit', [
                                    'target_month' => $target_month,
                                    'switch_type' => $switch_type,
                                    'daily_report_store_name' => $daily_report_store_name,
                                    'billing_staff' => $billing_staff,
                                    'daily_report_facility_name' => $daily_report_facility_name,
                                    'patient_name' => $patient_name,
                                    'patient_id' => $row->patient_id,
                                ]) }}">詳細</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ 9 + count($days) }}">対象データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="monthly-visit-detail">
                <h2 class="content-title">詳細</h2>

                @if($patient_id === '')
                <p>一覧の詳細を押すと、対象患者の詳細を表示します。</p>
                @elseif($selectedDetails->isEmpty())
                <p>対象明細がありません。</p>
                @else
                @foreach($selectedDetails as $detail)
                <form id="monthly-visit-detail-form-{{ $detail->daily_report_id }}" method="post" action="{{ route('home_visit.monthly_visit.update', ['daily_report_id' => $detail->daily_report_id]) }}">
                    @csrf
                    <input type="hidden" name="target_month" value="{{ $target_month }}">
                    <input type="hidden" name="switch_type" value="{{ $switch_type }}">
                    <input type="hidden" name="daily_report_store_name" value="{{ $daily_report_store_name }}">
                    <input type="hidden" name="billing_staff" value="{{ $billing_staff }}">
                    <input type="hidden" name="daily_report_facility_name" value="{{ $daily_report_facility_name }}">
                    <input type="hidden" name="patient_name" value="{{ $patient_name }}">
                    <input type="hidden" name="patient_id" value="{{ $patient_id }}">
                </form>
                @endforeach
                <div class="table-wrap staff-viewport-list-wrap">
                    <table class="data-table monthly-visit-table">
                        <thead>
                            <colgroup>
                                <col style="width: 30px;">
                                <col style="width: 30px;">
                                <col style="width: 30px;">
                                <col style="width: 20px;">
                                <col style="width: 20px;">
                                <col style="width: 20px;">
                                <col style="width: 20px;">
                                <col style="width: 30px;">
                                <col style="width: 20px;">
                                <col style="width: 80px;">
                                <col style="width: 80px;">
                                <col style="width: 20px;">
                            </colgroup>
                            <tr>
                                <th rowspan="2">施術日</th>
                                <th rowspan="2">申請日<br>（鍼灸）</th>
                                <th rowspan="2">申請日<br>（ﾏｯｻｰｼﾞ）</th>
                                <th rowspan="2">ﾏｯｻｰｼﾞ<br>施術</th>
                                <th rowspan="2">変形<br>徒手</th>
                                <th rowspan="2">施術<br>報告書</th>
                                <th rowspan="2">レセ<br>往療</th>
                                <th>日報施設</th>
                                <th>鍼灸</th>
                                <th>往療の起点</th>
                                <th>起点住所</th>
                                <th rowspan="2">操作</th>
                            </tr>
                            <tr>
                                <!-- <th></th> -->
                                <!-- <th>鍼灸</th>
                                <th>ﾏｯｻｰｼﾞ</th> -->
                                <!-- <th></th>
                                <th></th>
                                <th></th>
                                <th></th> -->
                                <th>請求担当</th>
                                <th>ﾏｯｻｰｼﾞ</th>
                                <th>日報町名</th>
                                <th>正式住所</th>
                                <!-- <th></th> -->
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedDetails as $detail)
                            @php
                            $monthlyVisitDetailFormId = 'monthly-visit-detail-form-' . $detail->daily_report_id;
                            $treatment_date = !empty($detail->treatment_date) ? \Carbon\Carbon::parse($detail->treatment_date)->format('Y-m-d') : '';
                            $added_at = !empty($detail->added_at) ? \Carbon\Carbon::parse($detail->added_at)->format('Y-m-d') : '';
                            $ma_applied_at = !empty($detail->ma_applied_at) ? \Carbon\Carbon::parse($detail->ma_applied_at)->format('Y-m-d') : '';
                            @endphp
                            <tr class="monthly-visit-detail-row">
                                <td rowspan="2">
                                    <span class="monthly-visit-view-value">
                                        @if(!empty($detail->treatment_date))
                                        {{ \Carbon\Carbon::parse($detail->treatment_date)->format('Y/m/d') }}
                                        @endif
                                    </span>
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="text" name="treatment_date" value="{{ $treatment_date }}">
                                </td>
                                <td rowspan="2">
                                    <span class="monthly-visit-view-value">
                                        @if(!empty($detail->added_at))
                                        {{ \Carbon\Carbon::parse($detail->added_at)->format('Y/m/d') }}
                                        @endif
                                    </span>
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="text" name="added_at" value="{{ $added_at }}">
                                </td>
                                <td rowspan="2">
                                    <span class="monthly-visit-view-value">
                                        @if(!empty($detail->ma_applied_at))
                                        {{ \Carbon\Carbon::parse($detail->ma_applied_at)->format('Y/m/d') }}
                                        @endif
                                    </span>
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="text" name="ma_applied_at" value="{{ $ma_applied_at }}">
                                </td>
                                <td rowspan="2">
                                    <span class="monthly-visit-view-value"><input type="checkbox" disabled {{ (int) $detail->is_ma_treatment === 1 ? 'checked' : '' }}></span>
                                    <input form="{{ $monthlyVisitDetailFormId }}" type="hidden" name="is_ma_treatment" value="0">
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="checkbox" name="is_ma_treatment" value="1" {{ (int) $detail->is_ma_treatment === 1 ? 'checked' : '' }}>
                                </td>
                                <td rowspan="2">
                                    <span class="monthly-visit-view-value"><input type="checkbox" disabled {{ (int) $detail->is_deformity_manual_therapy === 1 ? 'checked' : '' }}></span>
                                    <input form="{{ $monthlyVisitDetailFormId }}" type="hidden" name="is_deformity_manual_therapy" value="0">
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="checkbox" name="is_deformity_manual_therapy" value="1" {{ (int) $detail->is_deformity_manual_therapy === 1 ? 'checked' : '' }}>
                                </td>
                                <td rowspan="2">
                                    <span class="monthly-visit-view-value"><input type="checkbox" disabled {{ (int) $detail->is_treatment_report_submitted === 1 ? 'checked' : '' }}></span>
                                    <input form="{{ $monthlyVisitDetailFormId }}" type="hidden" name="is_treatment_report_submitted" value="0">
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="checkbox" name="is_treatment_report_submitted" value="1" {{ (int) $detail->is_treatment_report_submitted === 1 ? 'checked' : '' }}>
                                </td>
                                <td rowspan="2">
                                    <span class="monthly-visit-view-value"><input type="checkbox" disabled {{ (int) $detail->is_receipt_home_visit === 1 ? 'checked' : '' }}></span>
                                    <input form="{{ $monthlyVisitDetailFormId }}" type="hidden" name="is_receipt_home_visit" value="0">
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="checkbox" name="is_receipt_home_visit" value="1" {{ (int) $detail->is_receipt_home_visit === 1 ? 'checked' : '' }}>
                                </td>
                                <td>
                                    <span class="monthly-visit-view-value">{{ $detail->daily_report_facility_name }}</span>
                                    <select class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" name="daily_report_facility_name">
                                        <option value=""></option>
                                        @if((string) ($detail->daily_report_facility_name ?? '') !== '' && !in_array((string) $detail->daily_report_facility_name, $daily_report_facility_name_options, true))
                                        <option value="{{ $detail->daily_report_facility_name }}" selected>{{ $detail->daily_report_facility_name }}</option>
                                        @endif
                                        @foreach($daily_report_facility_name_options as $option)
                                        <option value="{{ $option }}" {{ (string) $detail->daily_report_facility_name === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <span class="monthly-visit-view-value">{{ $detail->receipt_home_visit_distance }}</span>
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="text" name="receipt_home_visit_distance" value="{{ $detail->receipt_home_visit_distance }}">
                                </td>
                                <td>
                                    <span class="monthly-visit-view-value">{{ $detail->home_visit_start_point }}</span>
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="text" name="home_visit_start_point" value="{{ $detail->home_visit_start_point }}">
                                </td>
                                <td>
                                    <span class="monthly-visit-view-value">{{ $detail->start_point_address }}</span>
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="text" name="start_point_address" value="{{ $detail->start_point_address }}">
                                </td>
                                <td rowspan="2" class="monthly-visit-detail-action-cell">
                                    @if($isVisitManagement)
                                    <button type="button" class="btn_small monthly-visit-edit-button" data-monthly-visit-edit>編集</button>
                                    <button type="submit" form="{{ $monthlyVisitDetailFormId }}" class="btn_small monthly-visit-save-button">保存</button>
                                    <button type="button" class="btn_small monthly-visit-cancel-button" data-monthly-visit-cancel>取消</button>
                                    @endif
                                </td>
                            </tr>
                            <tr class="monthly-visit-detail-row monthly-visit-detail-sub-row">
                                <!-- <td></td> -->
                                <!-- <td></td> -->
                                <!-- <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td> -->
                                <td>
                                    <span class="monthly-visit-view-value">{{ $detail->daily_report_billing_staff_staff_name }}</span>
                                    <select class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" name="daily_report_billing_staff">
                                        <option value=""></option>
                                        @foreach($billing_staff_options as $option)
                                        <option value="{{ $option['staff_id'] }}" {{ (string) $detail->daily_report_billing_staff === (string) $option['staff_id'] ? 'selected' : '' }}>{{ $option['display_name_ja'] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <span class="monthly-visit-view-value">{{ $detail->receipt_home_visit_distance_ma }}</span>
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="text" name="receipt_home_visit_distance_ma" value="{{ $detail->receipt_home_visit_distance_ma }}">
                                </td>
                                <td>
                                    <span class="monthly-visit-view-value">{{ $detail->daily_report_town_name }}</span>
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="text" name="daily_report_town_name" value="{{ $detail->daily_report_town_name }}">
                                </td>
                                <td>
                                    <span class="monthly-visit-view-value">{{ $detail->daily_report_address }}</span>
                                    <input class="monthly-visit-edit-input" form="{{ $monthlyVisitDetailFormId }}" type="text" name="daily_report_address" value="{{ $detail->daily_report_address }}">
                                </td>
                                <!-- <td></td> -->
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
    <script>
        const monthlyVisitForm = document.querySelector('.monthly-visit-toolbar');
        const monthlyVisitPrintType = document.querySelector('[data-monthly-visit-print-type]');
        if (monthlyVisitForm && monthlyVisitPrintType) {
            monthlyVisitPrintType.addEventListener('change', () => {
                if (monthlyVisitPrintType.value === '') return;

                const printError = document.querySelector('[data-monthly-visit-print-error]');
                const patientId = monthlyVisitForm.querySelector('[name="patient_id"]')?.value.trim() ?? '';
                const patientName = monthlyVisitForm.querySelector('[name="patient_name"]')?.value.trim() ?? '';

                if (monthlyVisitPrintType.value === '1' && patientId === '' && patientName === '') {
                    if (printError) {
                        printError.textContent = '個別印刷は患者を選択、または患者名を入力してください。';
                        printError.style.display = '';
                    }
                    monthlyVisitPrintType.value = '';
                    return;
                }

                if (printError) {
                    printError.textContent = '';
                    printError.style.display = 'none';
                }

                const url = new URL(monthlyVisitForm.dataset.printUrl, window.location.origin);
                new FormData(monthlyVisitForm).forEach((value, key) => {
                    if (String(value) !== '') {
                        url.searchParams.set(key, String(value));
                    }
                });
                window.open(url.toString(), '_blank');
                monthlyVisitPrintType.value = '';
            });
        }

        document.querySelectorAll('[data-monthly-visit-edit]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = button.closest('.monthly-visit-detail-row');
                if (!row) return;

                row.classList.add('is-editing');
                const subRow = row.nextElementSibling?.classList.contains('monthly-visit-detail-sub-row') ?
                    row.nextElementSibling :
                    null;
                if (subRow) {
                    subRow.classList.add('is-editing');
                }
            });
        });

        document.querySelectorAll('[data-monthly-visit-cancel]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = button.closest('.monthly-visit-detail-row');
                if (!row) return;

                const formId = row.querySelector('[form]')?.getAttribute('form');
                const form = formId ? document.getElementById(formId) : null;
                if (form) {
                    form.reset();
                }

                row.classList.remove('is-editing');
                const subRow = row.nextElementSibling?.classList.contains('monthly-visit-detail-sub-row') ?
                    row.nextElementSibling :
                    null;
                if (subRow) {
                    subRow.classList.remove('is-editing');
                }
            });
        });
    </script>
</body>

</html>
