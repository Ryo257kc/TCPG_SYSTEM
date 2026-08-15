<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 入金確認一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">

    <style>
        .payment-confirmation-panel {
            min-height: 0;
        }

        .payment-confirmation-panes {
            display: flex;
            flex: 1 1 auto;
            min-height: 0;
            flex-direction: column;
            gap: 12px;
        }

        .payment-confirmation-pane-top,
        .payment-confirmation-pane-bottom {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .payment-confirmation-pane-top {
            flex: 1 1 auto;
            min-height: 0;
        }

        .payment-confirmation-pane-bottom {
            display: none;
        }

        .payment-confirmation-check-group,
        .payment-confirmation-candidate-detail,
        .payment-confirmation-detail-grid {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            gap: 6px 14px;
        }

        .payment-confirmation-check-group label,
        .payment-confirmation-candidate-detail-item,
        .payment-confirmation-detail-item {
            display: inline-flex;
            align-items: baseline;
            gap: 6px;
        }

        .payment-confirmation-check-label,
        .payment-confirmation-candidate-detail-value,
        .payment-confirmation-detail-value {
            font-weight: 700;
        }

        .payment-confirmation-list-wrap,
        .payment-confirmation-candidate-wrap {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        .payment-confirmation-has-selection .payment-confirmation-pane-top {
            flex: 0 0 auto;
            min-height: 0;
        }

        .payment-confirmation-has-selection .payment-confirmation-list-wrap {
            flex: 0 0 auto;
            height: 54px;
            max-height: 54px;
        }

        .payment-confirmation-has-selection .payment-confirmation-list-wrap tbody tr:not(.payment-confirmation-selected-row) {
            display: none;
        }

        .payment-confirmation-has-selection .payment-confirmation-pane-bottom {
            display: flex;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }

        .payment-confirmation-candidate-wrap {
            flex: 1 1 auto;
        }


        /* .payment-confirmation-candidate-table th,
        .payment-confirmation-candidate-table td {
            font-size: 12px;
        } */

        .payment-confirmation-select-btn,
        .payment-confirmation-candidate-toggle {
            min-width: 28px;
            padding: 2px 6px;
            font-size: 11px;
            line-height: 1.2;
        }

        .payment-confirmation-selected-row>td {
            background: #fff7a8;
        }

        .payment-confirmation-edit-control {
            display: none;
        }

        .payment-confirmation-candidate-row.is-editing .payment-confirmation-readonly {
            display: none;
        }

        .payment-confirmation-candidate-row.is-editing .payment-confirmation-edit-control {
            display: inline-block;
        }

        .payment-confirmation-candidate-row.is-editing .payment-confirmation-cell-textarea.payment-confirmation-edit-control {
            display: block;
        }

        .payment-confirmation-cell-input,
        .payment-confirmation-cell-textarea {
            width: 100%;
            min-width: 0;
        }

        .payment-confirmation-cell-checkbox {
            width: 16px;
            height: 16px;
        }

        .payment-confirmation-candidate-detail-row>td {
            background: #f7faff;
            position: relative;
            padding: 10px;
        }

        .payment-confirmation-candidate-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            position: absolute;
            top: 8px;
            right: 10px;
        }

        .payment-confirmation-detail-item-wide {
            width: 100%;
        }

        .payment-confirmation-candidate-detail-label,
        .payment-confirmation-detail-label {
            font-size: 11px;
            color: #5f6f83;
            white-space: nowrap;
        }

        .payment-confirmation-detail {
            display: flex;
            flex-direction: column;
            flex: 0 1 auto;
            min-height: 0;
            /* gap: 8px; */
            padding: 10px 12px;
            border: 1px solid #ccd7e6;
            border-radius: 10px;
            background: #f7faff;
        }

        .payment-confirmation-detail-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .payment-confirmation-detail-title {
            margin: 0;
            font-size: 15px;
        }

        .payment-confirmation-wrap {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .payment-confirmation-candidate-placeholder,
        .payment-confirmation-empty-state {
            padding: 18px 20px;
            border: 1px dashed #b6c5d8;
            border-radius: 10px;
            background: #fff;
            color: #4d6078;
        }

        .payment-confirmation-candidate-placeholder {
            display: none;
        }

        .payment-confirmation-detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }

        .payment-confirmation-detail-actions {
            justify-content: flex-end;
        }

        .payment-confirmation-deposit-filter {
            align-items: center;
            justify-content: center;
        }

        .payment-confirmation-detail-control-row {
            display: grid;
            grid-template-columns: minmax(180px, auto) minmax(280px, 1fr) auto;
            column-gap: 16px;
            width: 100%;
            align-items: center;
        }

        .payment-confirmation-detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 14px;
            align-items: baseline;
        }



        @media (max-width: 768px) {
            .payment-confirmation-detail-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section @class([ 'panel' , 'content-panel' , 'staff-viewport-panel' , 'payment-confirmation-has-selection'=> !empty($selectedPaymentRow),
            ])
            data-payment-save-url="{{ route('office.receipt.payment_confirmation.save') }}"
            data-payment-delete-url="{{ route('office.receipt.payment_confirmation.delete') }}"
            data-payment-csrf="{{ csrf_token() }}"
            data-payment-journal-entry-id="{{ $selectedPaymentRow['journal_entry_id'] ?? '' }}">
            <div class="content-head" id="payment-confirmation-top">
                <h2 class="content-title">入金確認一覧</h2>
                @if (!empty($journalImportedThrough))
                <p class="f_size12">
                    仕訳取込済み：
                    @foreach ($journalImportedThrough as $imported)
                    {{ $imported['company_name_short'] }} {{ $imported['latest_occurred_at'] }}まで@if (!$loop->last)　/@endif
                    @endforeach
                </p>
                @endif
            </div>

            <form method="get" action="{{ route('office.receipt.payment_confirmation') }}" class="toolbar">
                <div class="toolbar-group">
                    <label for="payment-confirmation-target-month">作成月</label>
                    <input id="payment-confirmation-target-month" name="target_month" type="month" value="{{ $targetMonth }}">

                    <label for="payment-confirmation-bank-account">通帳選択</label>
                    <select id="payment-confirmation-bank-account" name="bank_account">
                        <option value="">--</option>
                        @foreach (($bankAccountOptions ?? []) as $bankAccount)
                        <option value="{{ $bankAccount }}" @selected(($selectedBankAccount ?? '' )===$bankAccount)>{{ $bankAccount }}</option>
                        @endforeach
                    </select>

                    <div class="payment-confirmation-check-group">
                        <span class="payment-confirmation-check-label">入金チェック</span>
                        <label><input type="radio" name="payment_check" value="all" @checked(($paymentCheck ?? 'all' )==='all' )> 全て</label>
                        <label><input type="radio" name="payment_check" value="checked" @checked(($paymentCheck ?? '' )==='checked' )> 入金済</label>
                        <label><input type="radio" name="payment_check" value="unchecked" @checked(($paymentCheck ?? '' )==='unchecked' )> 未入金</label>
                    </div>

                    <button type="submit" class="btn btn-primary margin_l20">表示</button>
                </div>
            </form>

            <div class="payment-confirmation-panes">
                <section class="payment-confirmation-pane payment-confirmation-pane-top">
                    <div class="table-wrap payment-confirmation-list-wrap">
                        <table class="data-table f_size12">
                            <colgroup>
                                <col style="width: 80px;">
                                <col style="width: 80px;">
                                <col style="width: 70px;">
                                <col style="width: 60px;">
                                <col style="width: 60px;">
                                <col style="width: 60px;">
                                <col style="width: 90px;">
                                <col style="width: 100px;">
                                <col style="width: 40px;">
                            </colgroup>
                            <thead class="line_13">
                                <tr>
                                    <th>入金名称</th>
                                    <th>品目</th>
                                    <th>入金日</th>
                                    <th>入金額</th>
                                    <th>確認済</th>
                                    <th>残額</th>
                                    <th>通帳</th>
                                    <th>備考</th>
                                    <th>選択</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($paymentRows ?? []) as $row)
                                <tr @class(['payment-confirmation-selected-row'=> (($selectedPaymentRow['journal_entry_id'] ?? 0) === $row['journal_entry_id'])])>
                                    <td>{{ $row['deposit_name'] }}</td>
                                    <td class="staff-wrap-text">{{ $row['item_name'] }}</td>
                                    <td>{{ $row['occurred_at'] }}</td>
                                    <td class="right">{{ $row['deposit_amount'] }}</td>
                                    <td class="right"><span data-payment-entry-confirmed="{{ $row['journal_entry_id'] }}">{{ $row['confirmed_amount'] }}</span></td>
                                    <td class="right"><span data-payment-entry-remaining="{{ $row['journal_entry_id'] }}">{{ $row['remaining_amount'] }}</span></td>
                                    <td class="staff-wrap-text">{{ $row['bank_account_selection'] }}</td>
                                    <td class="payment-confirmation-wrap staff-wrap-text">{{ $row['remarks'] }}</td>
                                    <td>
                                        <a
                                            href="{{ route('office.receipt.payment_confirmation', ['target_month' => $targetMonth, 'bank_account' => $selectedBankAccount, 'payment_check' => $paymentCheck, 'journal_entry_id' => $row['journal_entry_id'], 'detail_tab' => ($detailTab ?? 'unpaid')]) }}#payment-confirmation-top"
                                            class="btn payment-confirmation-select-btn">選択</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9">対象データがありません。</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="payment-confirmation-pane payment-confirmation-pane-bottom">
                    <div class="payment-confirmation-detail">

                        @if (!empty($selectedPaymentRow))
                        <div class="payment-confirmation-detail-grid">
                            <div class="payment-confirmation-detail-item payment-confirmation-detail-control-row">
                                <div class="payment-confirmation-detail-meta">
                                    <span class="payment-confirmation-detail-label">店舗</span>
                                    <span class="payment-confirmation-detail-value">{{ $selectedPaymentRow['store_name'] }}</span>
                                    <span class="payment-confirmation-detail-label">社名</span>
                                    <span class="payment-confirmation-detail-value">{{ $selectedPaymentRow['company_name'] }}</span>
                                </div>
                                <form method="get" action="{{ route('office.receipt.payment_confirmation') }}" class="toolbar-group f_size12 payment-confirmation-deposit-filter">
                                    <input type="hidden" name="target_month" value="{{ $targetMonth }}">
                                    <input type="hidden" name="bank_account" value="{{ $selectedBankAccount }}">
                                    <input type="hidden" name="payment_check" value="{{ $paymentCheck }}">
                                    <input type="hidden" name="journal_entry_id" value="{{ $selectedPaymentRow['journal_entry_id'] }}">
                                    <input type="hidden" name="detail_tab" value="{{ $detailTab ?? 'unpaid' }}">
                                    <input class="filter-search-input" id="payment-confirmation-detail-insurer-keyword" name="detail_insurer_keyword" type="text" value="{{ $detailInsurerKeyword ?? '' }}" list="payment-confirmation-detail-insurer-keyword-options" placeholder="保険者検索">

                                    <datalist id="payment-confirmation-detail-insurer-keyword-options">
                                        @foreach (($detailInsurerOptions ?? []) as $insurerOption)
                                        <option value="{{ $insurerOption }}"></option>
                                        @endforeach
                                    </datalist>
                                    <input class="filter-search-input" id="payment-confirmation-detail-deposit-name" name="detail_deposit_name" type="text" value="{{ $detailDepositName ?? '' }}" list="payment-confirmation-detail-deposit-name-options" placeholder="入金名称検索">
                                    <datalist id="payment-confirmation-detail-deposit-name-options">
                                        @foreach (($detailDepositNameOptions ?? []) as $depositName)
                                        <option value="{{ $depositName }}"></option>
                                        @endforeach
                                    </datalist>
                                    <button type="submit" class="btn btn_small">表示</button>
                                </form>
                                <div class="toolbar-group payment-confirmation-detail-actions">
                                    <a href="{{ route('office.receipt.payment_confirmation', ['target_month' => $targetMonth, 'bank_account' => $selectedBankAccount, 'payment_check' => $paymentCheck, 'journal_entry_id' => $selectedPaymentRow['journal_entry_id'], 'detail_tab' => 'all', 'detail_insurer_keyword' => $detailInsurerKeyword ?? '', 'detail_deposit_name' => $detailDepositName ?? '']) }}#payment-confirmation-top" @class(['btn', 'btn_small' , 'btn-primary'=> (($detailTab ?? 'unpaid') === 'all')])>全て</a>
                                    <a href="{{ route('office.receipt.payment_confirmation', ['target_month' => $targetMonth, 'bank_account' => $selectedBankAccount, 'payment_check' => $paymentCheck, 'journal_entry_id' => $selectedPaymentRow['journal_entry_id'], 'detail_tab' => 'zero_payment', 'detail_insurer_keyword' => $detailInsurerKeyword ?? '', 'detail_deposit_name' => $detailDepositName ?? '']) }}#payment-confirmation-top" @class(['btn', 'btn_small' , 'btn-primary'=> (($detailTab ?? 'unpaid') === 'zero_payment')])>未入金全て</a>
                                    <a href="{{ route('office.receipt.payment_confirmation', ['target_month' => $targetMonth, 'bank_account' => $selectedBankAccount, 'payment_check' => $paymentCheck, 'journal_entry_id' => $selectedPaymentRow['journal_entry_id'], 'detail_tab' => 'settled', 'detail_insurer_keyword' => $detailInsurerKeyword ?? '', 'detail_deposit_name' => $detailDepositName ?? '']) }}#payment-confirmation-top" @class(['btn', 'btn_small' , 'btn-primary'=> (($detailTab ?? 'unpaid') === 'settled')])>　済　</a>
                                    <a href="{{ route('office.receipt.payment_confirmation', ['target_month' => $targetMonth, 'bank_account' => $selectedBankAccount, 'payment_check' => $paymentCheck, 'journal_entry_id' => $selectedPaymentRow['journal_entry_id'], 'detail_tab' => 'unpaid', 'detail_insurer_keyword' => $detailInsurerKeyword ?? '', 'detail_deposit_name' => $detailDepositName ?? '']) }}#payment-confirmation-top" @class(['btn', 'btn_small' , 'btn-primary'=> (($detailTab ?? 'unpaid') === 'unpaid')])>未入金</a>
                                </div>
                            </div>

                        </div>

                        <div class="table-wrap payment-confirmation-candidate-wrap">
                            <table class="data-table f_size12">
                                <colgroup>
                                    <col style="width: 50px;">
                                    <col style="width: 140px;">
                                    <col style="width: 40px;">
                                    <col style="width: 40px;">
                                    <col style="width: 40px;">
                                    <col style="width: 40px;">
                                    <col style="width: 35px;">
                                    <col style="width: 35px;">
                                    <col style="width: 50px;">
                                    <col style="width: 40px;">
                                    <col style="width: 40px;">
                                    <col style="width: 60px;">
                                    <col style="width: 130px;">
                                    <col style="width: 30px;">
                                </colgroup>
                                <thead class="line_13">
                                    <tr>
                                        <th>施術月</th>
                                        <th>保険者名称</th>
                                        <th>集計<br>区分</th>
                                        <th>請求金額</th>
                                        <th>修正額</th>
                                        <th>返戻額</th>
                                        <th>高額<br>療養費</th>
                                        <th>回収<br>不可</th>
                                        <th>入金日</th>
                                        <th>入金額</th>
                                        <th>残額</th>
                                        <th>患者名</th>
                                        <th>備考</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse (($candidateReceiptRows ?? []) as $row)
                                    <tr class="payment-confirmation-candidate-row" data-payment-candidate-row data-payment-candidate-id="{{ $row['insurance_claim_detail_id'] }}" data-payment-source-id="{{ $row['insurance_claim_detail_id'] }}">
                                        <td>{{ $row['treatment_month'] }}</td>
                                        <td class="staff-wrap-text">{{ $row['insurer_name'] }}</td>
                                        <td class="staff-wrap-text">{{ $row['insurance_type'] }}</td>
                                        <td class="right">{{ $row['claim_amount'] }}</td>
                                        <td class="right">
                                            <span class="payment-confirmation-readonly">{{ $row['adjustment_amount'] }}</span>
                                            <input type="text" class="payment-confirmation-cell-input payment-confirmation-edit-control right" value="{{ $row['adjustment_amount'] }}">
                                        </td>
                                        <td class="right">
                                            <span class="payment-confirmation-readonly">{{ $row['returned_amount'] }}</span>
                                            <input type="text" class="payment-confirmation-cell-input payment-confirmation-edit-control right" value="{{ $row['returned_amount'] }}">
                                        </td>
                                        <td>
                                            <input type="checkbox" class="payment-confirmation-cell-checkbox payment-confirmation-readonly" @checked(!empty($row['high_cost_amount'])) disabled>
                                            <input type="checkbox" class="payment-confirmation-cell-checkbox payment-confirmation-edit-control" @checked(!empty($row['high_cost_amount']))>
                                        </td>
                                        <td>
                                            <input type="checkbox" class="payment-confirmation-cell-checkbox payment-confirmation-readonly" @checked(!empty($row['unrecoverable_amount']) || !empty($row['is_uncollectible'])) disabled>
                                            <input type="checkbox" class="payment-confirmation-cell-checkbox payment-confirmation-edit-control" @checked(!empty($row['unrecoverable_amount']) || !empty($row['is_uncollectible']))>
                                        </td>
                                        <td>
                                            <span class="payment-confirmation-readonly" data-payment-date-display>{{ $row['payment_date_display'] }}</span>
                                            <span class="payment-confirmation-edit-control" data-payment-date-display>{{ $row['payment_date_display'] }}</span>
                                            <input type="hidden" data-payment-date-key value="{{ $row['payment_date_text'] }}">
                                        </td>
                                        <td class="right">
                                            <span class="payment-confirmation-readonly">{{ $row['payment_amount'] }}</span>
                                            <input type="text" class="payment-confirmation-cell-input payment-confirmation-edit-control right" value="{{ $row['payment_amount'] }}">
                                        </td>
                                        <td class="right"><span class="payment-confirmation-readonly">{{ $row['remaining_amount'] }}</span></td>
                                        <td>
                                            <span class="payment-confirmation-readonly">{{ $row['patient_name'] }}</span>
                                            <input type="text" class="payment-confirmation-cell-input payment-confirmation-edit-control" value="{{ $row['patient_name'] }}">
                                        </td>
                                        <td class="payment-confirmation-wrap staff-wrap-text">
                                            <span class="payment-confirmation-readonly">{{ $row['remarks'] }}</span>
                                            <textarea class="payment-confirmation-cell-textarea payment-confirmation-edit-control payment-confirmation-wrap staff-wrap-text" rows="1">{{ $row['remarks'] }}</textarea>
                                        </td>
                                        <td>
                                            <button type="button" class="btn_small" data-payment-candidate-toggle aria-expanded="false">+</button>
                                        </td>
                                    </tr>
                                    <tr class="payment-confirmation-candidate-detail-row" data-payment-candidate-detail hidden>
                                        <td colspan="14">
                                            <div class="payment-confirmation-candidate-detail">
                                                <div class="payment-confirmation-candidate-detail-item">
                                                    <span class="payment-confirmation-candidate-detail-label">保険者番号</span>
                                                    <span class="payment-confirmation-candidate-detail-value">{{ $row['insurer_number'] }}</span>
                                                </div>
                                                <div class="payment-confirmation-candidate-detail-item">
                                                    <span class="payment-confirmation-candidate-detail-label">入金名称</span>
                                                    <span class="payment-confirmation-candidate-detail-value">{{ $row['deposit_name'] }}</span>
                                                </div>
                                                <div class="payment-confirmation-candidate-detail-item">
                                                    <span class="payment-confirmation-candidate-detail-label">店舗</span>
                                                    <span class="payment-confirmation-candidate-detail-value">{{ $row['store_name'] }}</span>
                                                </div>
                                            </div>
                                            <div class="payment-confirmation-candidate-actions">
                                                <button type="button" class="btn payment-confirmation-select-btn" data-payment-save>保存</button>
                                                <button type="button" class="btn payment-confirmation-select-btn" data-payment-duplicate>複製</button>
                                                <button type="button" class="btn payment-confirmation-select-btn" data-payment-delete>削除</button>
                                                <button type="button" class="btn payment-confirmation-select-btn" data-payment-date-toggle data-payment-date-display-value="{{ $selectedPaymentRow['occurred_at'] ?? '' }}" data-payment-date-key-value="{{ $selectedPaymentRow['journal_breakdown'] ?? '' }}">
                                                    {{ filled($row['payment_date_display']) ? '入金日を外す' : '入金日を入れる' }}
                                                </button>
                                                <button type="button" class="btn payment-confirmation-select-btn" data-payment-cancel>取消</button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="12">候補内訳がありません。</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="payment-confirmation-candidate-placeholder">
                            この下に、選択した入金に対する候補内訳一覧を載せていきます。
                        </div>
                        @else
                        <div class="payment-confirmation-empty-state">
                            上の一覧から入金を選択してください。
                        </div>
                        @endif
                    </div>
                </section>
            </div>

            <div class="back_ma">
                <a href="{{ route('office.receipt') }}" class="btn btn_back">戻る</a>
            </div>

        </section>
    </main>
    @include('staff_portal.office.receipt.payment_confirmation.page_script')
</body>

</html>
