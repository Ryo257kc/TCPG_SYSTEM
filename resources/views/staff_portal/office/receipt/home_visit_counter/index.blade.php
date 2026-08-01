<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 往診窓口一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .content-panel .data-table th,
        .content-panel .data-table td {
            font-size: 12px;
        }

        .home-visit-counter-readonly {
            display: block !important;
        }

        .home-visit-counter-edit-control {
            display: none !important;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            padding: 5px 6px;
        }

        [data-home-visit-row].is-editing .home-visit-counter-editable-display {
            display: none !important;
        }

        [data-home-visit-row].is-editing .home-visit-counter-edit-control {
            display: block !important;
        }

        .home-visit-counter-money-cell {
            text-align: right;
        }

        .home-visit-counter-toggle {
            width: 28px;
            min-width: 28px;
            height: 28px;
            padding: 0;
            font-size: 11px;
        }

        .home-visit-counter-detail-cell {
            padding: 10px 12px;
            background: rgba(255, 250, 243, 0.72);
        }

        .home-visit-counter-detail-layout {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .home-visit-counter-detail-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 180px));
            gap: 6px 8px;
            flex: 1 1 auto;
        }

        .home-visit-counter-detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }

        .home-visit-counter-detail-label {
            font-size: 11px;
            font-weight: 700;
        }

        .home-visit-counter-detail-value {
            min-height: 20px;
            padding: 5px 6px;
            background: #fff;
            word-break: break-word;
        }

        .home-visit-counter-detail-actions {
            display: flex;
            justify-content: flex-start;
            gap: 6px;
            flex-wrap: nowrap;
            flex: 0 0 auto;
            align-self: flex-end;
        }

        .home-visit-counter-detail-actions .btn {
            padding: 4px 8px;
            font-size: 12px;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .content-panel.home-visit-counter-panel.staff-viewport-panel {
            display: flex !important;
            flex-direction: column !important;
            height: calc(100vh - 150px) !important;
            min-height: 0 !important;
            overflow: hidden !important;
        }

        .home-visit-counter-panel .staff-viewport-list-wrap {
            flex: 1 1 auto;
            height: auto !important;
            min-height: 0;
            max-height: none !important;
            overflow: auto !important;
        }

        .home-visit-counter-panel .data-table thead {
            position: static !important;
        }

        .home-visit-counter-panel .data-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel home-visit-counter-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">往診窓口一覧</h2>
            </div>

            <form method="get" action="{{ route('office.receipt.home_visit_counter') }}" class="toolbar">
                <div class="toolbar-group">
                    <label for="home-visit-target-month">施術月</label>
                    <input id="home-visit-target-month" name="target_month" type="month" value="{{ $targetMonth ?? now()->format('Y-m') }}">

                    @php
                    $currentStore = trim((string) request()->query('store_name', $selectedStore ?? ''));
                    $currentPatientName = trim((string) request()->query('patient_name', $selectedPatientName ?? ''));
                    @endphp

                    <select id="receipt-store" class="edit-input" name="store_name">
                        <option value="">-店舗選択-</option>

                        @if ($currentStore !== '')
                        <option value="{{ $currentStore }}" selected>{{ $currentStore }}</option>
                        @endif

                        @foreach (($storeOptions ?? []) as $storeName)
                        @php
                        $optionStore = trim((string) $storeName);
                        @endphp

                        @if ($optionStore !== '' && $optionStore !== $currentStore)
                        <option value="{{ $optionStore }}">{{ $optionStore }}</option>
                        @endif
                        @endforeach
                    </select>

                    <input id="home-visit-patient-name" name="patient_name" type="text" list="home-visit-patient-name-options" value="{{ $currentPatientName }}" placeholder="患者名">
                    <datalist id="home-visit-patient-name-options">
                        @foreach (($patientNameOptions ?? []) as $patientNameOption)
                        <option value="{{ $patientNameOption }}"></option>
                        @endforeach
                    </datalist>
                    <button type="submit" class="btn btn-primary margin_l20">表示</button>
                    <button type="submit" class="btn btn-primary" name="unpaid_only" value="1">未入金</button>

                </div>
                @unless ($isReceiptMonthlyClosed ?? false)
                <button type="button" class="btn btn-primary" data-home-visit-add>新規追加</button>
                @endunless
                @if ($isReceiptMonthlyClosed ?? false)
                <div class="notice">月次処理済 {{ $receiptMonthlyClosingProcessedAt }}</div>
                @endif

            </form>
            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width: 100px;">
                        <col style="width: 100px;">
                        <col style="width: 80px;">
                        <col style="width: 80px;">
                        <col style="width: 80px;">
                        <col style="width: 80px;">
                        <col style="width: 100px;">
                        <col style="width: 120px;">
                        <col style="width: 220px;">
                        <col style="width: 40px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>入金名称</th>
                            <th>患者名</th>
                            <th>入金日</th>
                            <th>入金額</th>
                            <th>残金</th>
                            <th>現金回収</th>
                            <th>担当者</th>
                            <th>店舗</th>
                            <th>備考</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody data-home-visit-body>
                        @forelse (($homeVisitRows ?? []) as $row)
                        <tr
                            data-home-visit-row
                            data-home-visit-id="{{ $row['insurance_claim_detail_id'] }}"
                            data-home-visit-store="{{ $row['store_name'] }}"
                            data-home-visit-treatment-month="{{ $row['treatment_month_raw'] }}"
                            data-home-visit-insurer-number="{{ $row['insurer_number'] }}"
                            data-home-visit-insurer-name="{{ $row['insurer_name'] }}"
                            data-home-visit-draft="0">
                            <td>
                                <span class="home-visit-counter-readonly home-visit-counter-editable-display" data-home-visit-display="deposit_name">{{ $row['deposit_name'] }}</span>
                                <select class="home-visit-counter-edit-control" data-home-visit-field="deposit_name">
                                    <option value=""></option>
                                    @foreach (($depositNameOptions ?? []) as $depositNameOption)
                                    <option value="{{ $depositNameOption }}" @selected($depositNameOption===($row['deposit_name_raw'] ?? '' ))>{{ $depositNameOption }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <span class="home-visit-counter-readonly home-visit-counter-editable-display" data-home-visit-display="patient_name">{{ $row['patient_name'] }}</span>
                                <select class="home-visit-counter-edit-control" data-home-visit-field="patient_name">
                                    <option value=""></option>
                                    @foreach (($patientNameOptions ?? []) as $patientNameOption)
                                    <option value="{{ $patientNameOption }}" @selected($patientNameOption===($row['patient_name_raw'] ?? '' ))>{{ $patientNameOption }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <span class="home-visit-counter-readonly @if($is_admin ?? false) home-visit-counter-editable-display @endif" data-home-visit-display="payment_date_text">{{ $row['payment_date_text'] }}</span>
                                @if($is_admin ?? false)
                                <input type="text" class="home-visit-counter-edit-control" data-home-visit-field="payment_date_text" value="{{ $row['payment_date_text'] }}">
                                @endif
                            </td>
                            <td class="home-visit-counter-money-cell">
                                <span class="home-visit-counter-readonly @if($is_admin ?? false) home-visit-counter-editable-display @endif" data-home-visit-display="payment_amount">{{ $row['payment_amount'] }}</span>
                                @if($is_admin ?? false)
                                <input type="text" class="home-visit-counter-edit-control" data-home-visit-field="payment_amount" value="{{ $row['payment_amount'] }}">
                                @endif
                            </td>
                            <td class="home-visit-counter-money-cell">
                                <span class="home-visit-counter-readonly" data-home-visit-display="remaining_amount">{{ $row['remaining_amount'] }}</span>
                            </td>
                            <td class="home-visit-counter-money-cell">
                                <span class="home-visit-counter-readonly home-visit-counter-editable-display" data-home-visit-display="cash_collected_amount">{{ $row['cash_collected_amount'] }}</span>
                                <input type="text" class="home-visit-counter-edit-control" data-home-visit-field="cash_collected_amount" value="{{ $row['cash_collected_amount_raw'] }}">
                            </td>
                            <td>
                                <span class="home-visit-counter-readonly home-visit-counter-editable-display" data-home-visit-display="staff_name">{{ $row['staff_name'] }}</span>
                                <select class="home-visit-counter-edit-control" data-home-visit-field="staff_name">
                                    <option value=""></option>
                                    @foreach (($staffNameOptions ?? []) as $staffNameOption)
                                    <option value="{{ $staffNameOption['value'] }}" @selected($staffNameOption['value']===($row['staff_id_raw'] ?? '' ))>{{ $staffNameOption['label'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <span class="home-visit-counter-readonly" data-home-visit-display="store_name">{{ $row['store_name'] }}</span>
                            </td>
                            <td class="data-table-wrap">
                                <span class="home-visit-counter-readonly home-visit-counter-editable-display" data-home-visit-display="remarks">{{ $row['remarks'] }}</span>
                                <input type="text" class="home-visit-counter-edit-control" data-home-visit-field="remarks" value="{{ $row['remarks_raw'] }}">
                            </td>
                            <td>
                                <button type="button" class="home-visit-counter-toggle" data-home-visit-toggle aria-expanded="false">▾</button>
                            </td>
                        </tr>
                        <tr class="home-visit-counter-detail" data-home-visit-detail hidden>
                            <td colspan="10" class="home-visit-counter-detail-cell">
                                <div class="home-visit-counter-detail-layout">
                                    <div class="home-visit-counter-detail-grid">
                                        <div class="home-visit-counter-detail-item">
                                            <span class="home-visit-counter-detail-label">施術月</span>
                                            <span class="home-visit-counter-detail-value" data-home-visit-detail-display="treatment_month">{{ $row['treatment_month'] }}</span>
                                        </div>
                                        <div class="home-visit-counter-detail-item">
                                            <span class="home-visit-counter-detail-label">保険者番号</span>
                                            <span class="home-visit-counter-detail-value" data-home-visit-detail-display="insurer_number">{{ $row['insurer_number'] }}</span>
                                        </div>
                                        <div class="home-visit-counter-detail-item">
                                            <span class="home-visit-counter-detail-label">保険者名称</span>
                                            <span class="home-visit-counter-detail-value" data-home-visit-detail-display="insurer_name">{{ $row['insurer_name'] }}</span>
                                        </div>
                                    </div>
                                    <div class="home-visit-counter-detail-actions">
                                        @unless ($isReceiptMonthlyClosed ?? false)
                                        <button type="button" class="btn btn-primary" data-home-visit-save>保存</button>
                                        <button type="button" class="btn" data-home-visit-cancel>取消</button>
                                        <button type="button" class="btn btn-danger" data-home-visit-delete>削除</button>
                                        @endunless
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr data-home-visit-empty>
                            <td colspan="10">対象データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="right">現金回収 合計</th>
                            <th class="home-visit-counter-money-cell">{{ number_format((float) ($cashCollectedTotal ?? 0)) }}</th>
                            <th colspan="4"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div>
                <a href="{{ route('office.receipt') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>

    <form id="home-visit-save-form" method="post" action="{{ route('office.receipt.home_visit_counter.save') }}" hidden>
        @csrf
        <input type="hidden" name="insurance_claim_detail_id" value="">
        <input type="hidden" name="target_month" value="{{ $targetMonth ?? now()->format('Y-m') }}">
        <input type="hidden" name="row_treatment_month" value="">
        <input type="hidden" name="store_name" value="{{ $selectedStore ?? '' }}">
        <input type="hidden" name="filter_store_name" value="{{ $selectedStore ?? '' }}">
        <input type="hidden" name="filter_patient_name" value="{{ $selectedPatientName ?? '' }}">
        <input type="hidden" name="filter_unpaid_only" value="{{ ($showUnpaidOnly ?? false) ? '1' : '' }}">
        <input type="hidden" name="deposit_name" value="">
        <input type="hidden" name="patient_name" value="">
        @if($is_admin ?? false)
        <input type="hidden" name="payment_date_text" value="">
        <input type="hidden" name="payment_amount" value="">
        @endif
        <input type="hidden" name="cash_collected_amount" value="">
        <input type="hidden" name="staff_name" value="">
        <input type="hidden" name="remarks" value="">
    </form>

    <form id="home-visit-delete-form" method="post" action="{{ route('office.receipt.home_visit_counter.delete') }}" hidden>
        @csrf
        <input type="hidden" name="insurance_claim_detail_id" value="">
        <input type="hidden" name="target_month" value="{{ $targetMonth ?? now()->format('Y-m') }}">
        <input type="hidden" name="store_name" value="{{ $selectedStore ?? '' }}">
        <input type="hidden" name="filter_store_name" value="{{ $selectedStore ?? '' }}">
        <input type="hidden" name="filter_patient_name" value="{{ $selectedPatientName ?? '' }}">
        <input type="hidden" name="filter_unpaid_only" value="{{ ($showUnpaidOnly ?? false) ? '1' : '' }}">
    </form>

    <template id="home-visit-row-template">
        <tr
            data-home-visit-row
            data-home-visit-id=""
            data-home-visit-store=""
            data-home-visit-treatment-month=""
            data-home-visit-insurer-number="99999999"
            data-home-visit-insurer-name="{{ $insurerName99999999 ?? '' }}"
            data-home-visit-draft="1">
            <td>
                <span class="home-visit-counter-readonly home-visit-counter-editable-display" data-home-visit-display="deposit_name"></span>
                <select class="home-visit-counter-edit-control" data-home-visit-field="deposit_name">
                    <option value=""></option>
                    @foreach (($depositNameOptions ?? []) as $depositNameOption)
                    <option value="{{ $depositNameOption }}">{{ $depositNameOption }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <span class="home-visit-counter-readonly home-visit-counter-editable-display" data-home-visit-display="patient_name"></span>
                <select class="home-visit-counter-edit-control" data-home-visit-field="patient_name">
                    <option value=""></option>
                    @foreach (($patientNameOptions ?? []) as $patientNameOption)
                    <option value="{{ $patientNameOption }}">{{ $patientNameOption }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <span class="home-visit-counter-readonly @if($is_admin ?? false) home-visit-counter-editable-display @endif" data-home-visit-display="payment_date_text"></span>
                @if($is_admin ?? false)
                <input type="text" class="home-visit-counter-edit-control" data-home-visit-field="payment_date_text" value="">
                @endif
            </td>
            <td class="home-visit-counter-money-cell">
                <span class="home-visit-counter-readonly @if($is_admin ?? false) home-visit-counter-editable-display @endif" data-home-visit-display="payment_amount"></span>
                @if($is_admin ?? false)
                <input type="text" class="home-visit-counter-edit-control" data-home-visit-field="payment_amount" value="">
                @endif
            </td>
            <td class="home-visit-counter-money-cell">
                <span class="home-visit-counter-readonly" data-home-visit-display="remaining_amount">0</span>
            </td>
            <td class="home-visit-counter-money-cell">
                <span class="home-visit-counter-readonly home-visit-counter-editable-display" data-home-visit-display="cash_collected_amount"></span>
                <input type="text" class="home-visit-counter-edit-control" data-home-visit-field="cash_collected_amount" value="">
            </td>
            <td>
                <span class="home-visit-counter-readonly home-visit-counter-editable-display" data-home-visit-display="staff_name"></span>
                <select class="home-visit-counter-edit-control" data-home-visit-field="staff_name">
                    <option value=""></option>
                    @foreach (($staffNameOptions ?? []) as $staffNameOption)
                    <option value="{{ $staffNameOption['value'] }}">{{ $staffNameOption['label'] }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <span class="home-visit-counter-readonly" data-home-visit-display="store_name"></span>
            </td>
            <td class="data-table-wrap">
                <span class="home-visit-counter-readonly home-visit-counter-editable-display" data-home-visit-display="remarks"></span>
                <input type="text" class="home-visit-counter-edit-control" data-home-visit-field="remarks" value="">
            </td>
            <td>
                <button type="button" class="home-visit-counter-toggle" data-home-visit-toggle aria-expanded="false">▾</button>
            </td>
        </tr>
    </template>

    <template id="home-visit-detail-template">
        <tr class="home-visit-counter-detail" data-home-visit-detail hidden>
            <td colspan="10" class="home-visit-counter-detail-cell">
                <div class="home-visit-counter-detail-layout">
                    <div class="home-visit-counter-detail-grid">
                        <div class="home-visit-counter-detail-item">
                            <span class="home-visit-counter-detail-label">施術月</span>
                            <span class="home-visit-counter-detail-value" data-home-visit-detail-display="treatment_month"></span>
                        </div>
                        <div class="home-visit-counter-detail-item">
                            <span class="home-visit-counter-detail-label">保険者番号</span>
                            <span class="home-visit-counter-detail-value" data-home-visit-detail-display="insurer_number">99999999</span>
                        </div>
                        <div class="home-visit-counter-detail-item">
                            <span class="home-visit-counter-detail-label">保険者名称</span>
                            <span class="home-visit-counter-detail-value" data-home-visit-detail-display="insurer_name">{{ $insurerName99999999 ?? '' }}</span>
                        </div>
                    </div>
                    <div class="home-visit-counter-detail-actions">
                        <button type="button" class="btn btn-primary" data-home-visit-save>保存</button>
                        <button type="button" class="btn" data-home-visit-cancel>取消</button>
                        <button type="button" class="btn btn-danger" data-home-visit-delete>削除</button>
                    </div>
                </div>
            </td>
        </tr>
    </template>

    @include('staff_portal.office.receipt.home_visit_counter.page_script')
</body>

</html>
