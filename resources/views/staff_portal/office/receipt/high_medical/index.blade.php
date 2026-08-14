<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 高額療養費</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .high-medical-panel .data-table {
            font-size: 12px;
        }

        .high-medical-panel .data-table th,
        .high-medical-panel .data-table td {
            font-size: 12px;
        }

        .high-medical-text-right {
            text-align: right;
        }

        .high-medical-edit-control {
            display: none;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            padding: 5px 4px;
        }

        .high-medical-row.is-editing .high-medical-edit-control {
            display: block;
        }

        .high-medical-row.is-editing [data-receipt-editable-display] {
            display: none;
        }

        .high-medical-toggle {
            width: 28px;
            min-width: 28px;
            height: 28px;
            padding: 0;
        }

        .high-medical-detail-cell {
            padding: 8px;
            background: rgba(255, 250, 243, 0.72);
        }

        .high-medical-detail-layout {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            justify-content: space-between;
        }

        .high-medical-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 8px 12px;
            flex: 1 1 auto;
        }

        .high-medical-detail-item {
            display: flex;
            gap: 6px;
            align-items: center;
            min-width: 0;
        }

        .high-medical-detail-label {
            font-weight: 700;
            white-space: nowrap;
        }

        .high-medical-detail-value {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .high-medical-detail-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex: 0 0 auto;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section
            class="panel content-panel high-medical-panel staff-viewport-panel"
            data-high-medical-save-url="{{ route('office.receipt.high_medical.save') }}"
            data-receipt-csrf="{{ csrf_token() }}">
            <div class="content-head">
                <h2 class="content-title">高額療養費</h2>
            </div>

            <form method="get" action="{{ route('office.receipt.high_medical') }}" class="toolbar">
                <div class="toolbar-group">
                    <label for="high-medical-payment-status">選択</label>
                    <select id="high-medical-payment-status" name="payment_status">
                        <option value="unpaid" @selected(($selectedStatus ?? 'unpaid' )==='unpaid' )>未入金</option>
                        <option value="all" @selected(($selectedStatus ?? 'unpaid' )==='all' )>全て</option>
                        <option value="paid" @selected(($selectedStatus ?? 'unpaid' )==='paid' )>入金済</option>
                    </select>

                    <select id="receipt-store" name="store_name">
                        <option value="">-店舗選択-</option>
                        @foreach (($storeOptions ?? []) as $storeName)
                        <option value="{{ $storeName }}" @selected(($selectedStore ?? '' )===$storeName)>{{ $storeName }}</option>
                        @endforeach
                    </select>

                    <select id="high-medical-subject-name" name="subject_name">
                        <option value="">-患者選択-</option>
                        @foreach (($patientOptions ?? []) as $patientName)
                        <option value="{{ $patientName }}" @selected(($selectedSubjectName ?? '' )===$patientName)>{{ $patientName }}</option>
                        @endforeach
                    </select>

                    <select id="high-medical-bank-deposit-date" name="bank_deposit_date">
                        <option value="">-入金日-</option>
                        @foreach (($bankDepositDateOptions ?? []) as $bankDepositDate)
                        <option value="{{ $bankDepositDate }}" @selected(($selectedBankDepositDate ?? '' )===$bankDepositDate)>{{ $bankDepositDate }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn">表示</button>
                </div>
            </form>

            <div class="table-wrap staff-viewport-list-wrap">
                <div class="error" data-receipt-inline-error hidden></div>
                <table class="data-table">
                    <colgroup>
                        <col style="width: 65px;">
                        <col style="width: 70px;">
                        <col style="width: 120px;">
                        <col style="width: 70px;">
                        <col style="width: 75px;">
                        <col style="width: 75px;">
                        <col style="width: 100px;">
                        <col style="width: 55px;">
                        <col style="width: 90px;">
                        <col style="width: 180px;">
                        <col style="width: 40px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>施術月</th>
                            <th>保険者番号</th>
                            <th>保険者名称</th>
                            <th>請求金額</th>
                            <th>通帳<br>入金日</th>
                            <th>通帳<br>入金額</th>
                            <th>通帳選択</th>
                            <th>請求書</th>
                            <th>患者名</th>
                            <th>備考</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($receiptRows ?? []) as $row)
                        <tr class="high-medical-row" data-receipt-row data-receipt-id="{{ $row['insurance_claim_detail_id'] }}">
                            <td>{{ $row['treatment_month'] }}</td>
                            <td>{{ $row['insurer_number'] }}</td>
                            <td class="data-table-wrap">
                                {{ $row['insurer_name'] }}
                            </td>
                            <td class="high-medical-text-right">
                                {{ $row['claim_amount'] }}
                            </td>
                            <td>{{ $row['bank_deposit_date'] }}</td>
                            <td class="high-medical-text-right">
                                <span data-receipt-editable-display data-high-medical-display>{{ $row['bank_deposit_amount'] }}</span>
                                <input type="text" class="high-medical-edit-control" data-high-medical-field="bank_deposit_amount" value="{{ $row['bank_deposit_amount'] }}">
                            </td>
                            <td>{{ $row['bank_account_selection'] }}</td>
                            <td>
                                <span data-receipt-editable-display>
                                    <input type="checkbox" @checked(($row['is_invoiced'] ?? '' ) !=='' ) disabled>
                                </span>
                                <input type="checkbox" class="high-medical-edit-control" data-high-medical-field="is_invoiced" value="1" @checked(($row['is_invoiced'] ?? '' ) !=='' )>
                            </td>
                            <td>
                                <span data-receipt-editable-display data-high-medical-display>{{ $row['subject_name'] }}</span>
                                <input type="text" class="high-medical-edit-control" data-high-medical-field="subject_name" value="{{ $row['subject_name'] }}">
                            </td>
                            <td class="data-table-wrap">
                                <span data-receipt-editable-display data-high-medical-display>{{ $row['remarks'] }}</span>
                                <textarea class="high-medical-edit-control" data-high-medical-field="remarks" rows="2">{{ $row['remarks'] }}</textarea>
                            </td>
                            <td>
                                <button type="button" class="high-medical-toggle" data-receipt-toggle aria-expanded="false" aria-label="詳細を開く">▾</button>
                            </td>
                        </tr>
                        <tr class="high-medical-detail" data-receipt-detail hidden>
                            <td colspan="11" class="high-medical-detail-cell">
                                <div class="high-medical-detail-layout">
                                    <div class="high-medical-detail-grid">
                                        <div class="high-medical-detail-item">
                                            <span class="high-medical-detail-label">高額療養費</span>
                                            <span data-receipt-editable-display><input type="checkbox" @checked(($row['high_cost_medical'] ?? '' ) !=='' ) disabled></span>
                                        </div>
                                        <div class="high-medical-detail-item">
                                            <span class="high-medical-detail-label">保険区分</span>
                                            <span class="high-medical-detail-value">{{ $row['insurance_category'] }}</span>
                                        </div>
                                        <div class="high-medical-detail-item">
                                            <span class="high-medical-detail-label">保険種別</span>
                                            <span class="high-medical-detail-value">{{ $row['insurance_type'] }}</span>
                                        </div>
                                        <div class="high-medical-detail-item">
                                            <span class="high-medical-detail-label">店舗</span>
                                            <span class="high-medical-detail-value">{{ $row['store_name'] }}</span>
                                        </div>
                                        <div class="high-medical-detail-item">
                                            <span class="high-medical-detail-label">社名</span>
                                            <span class="high-medical-detail-value">{{ $row['company_name'] }}</span>
                                        </div>
                                    </div>
                                    <div class="high-medical-detail-actions">
                                        <button type="button" class="btn btn_small" data-high-medical-save>保存</button>
                                        <button type="button" class="btn btn_small" data-high-medical-cancel>取消</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr data-receipt-empty>
                            <td colspan="12">対象データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                <a href="{{ route('office.receipt') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
    @include('staff_portal.office.receipt.high_medical.page_script')

</body>

</html>
