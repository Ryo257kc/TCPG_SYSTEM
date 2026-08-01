<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 日報詳細</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .daily-summary-detail-block+.daily-summary-detail-block {
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .daily-summary-detail-title {
            color: #374151;
            font-size: 12px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .daily-summary-detail-input-table th {
            line-height: 1.1;
            padding: 1px 2px;
            color: #374151;
            background: #e5e7eb;
            border: 1px solid #d1d5db;
        }

        .daily-summary-detail-input-table td {
            line-height: 1.1;
            padding: 1px 2px;
            color: #374151;
            /* background: #e5e7eb; */
            border: 1px solid #d1d5db;
        }

        .daily-summary-remarks-textarea {
            width: 100%;
        }

        .daily-summary-receipt-burden-cell {
            align-items: center;
            display: flex;
            gap: 4px;
        }

        .daily-summary-receipt-burden-cell input[type="text"] {
            min-width: 0;
            width: calc(100% - 24px);
        }

        .daily-summary-receipt-burden-cell input[type="checkbox"] {
            flex: 0 0 auto;
        }

        .daily-summary-access-form {
            display: grid;
            grid-template-columns: 0.5fr 0.8fr 0.8fr 0.6fr 1fr;
            gap: 8px;
        }

        .daily-summary-access-group {
            background: #f3f4f6;
            border-top: 1px solid #d1d5db;
            /* border-left: 1px solid #d8c4b0; */
        }

        .daily-summary-access-group th {
            width: 70px;
        }

        .daily-summary-access-group-title {
            background: #e5e7eb;
            border-right: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            border-left: 1px solid #d1d5db;
            color: #374151;
            font-size: 12px;
            font-weight: 700;
            margin: 0;
            padding: 3px 6px;
        }

        .daily-summary-actions {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
            padding: 6px 0 0;
            margin-top: 20px;
            margin-right: 10px;
        }

        .daily-summary-patient-actions {
            align-items: center;
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
        }

        .daily-summary-patient-actions form {
            align-items: center;
            display: flex;
            gap: 8px;
            margin: 0;
        }

        .daily-summary-filter-actions {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .daily-summary-filter-actions form {
            align-items: center;
            display: flex;
            gap: 8px;
            margin: 0;
        }

        .daily-summary-patient-table tfoot th {
            position: sticky;
            bottom: 0;
            z-index: 2;
        }

        .daily-order-new {
            background-color: #9ed8ff !important;
        }

        .daily-order-new-like {
            background-color: #fff36a !important;
        }

        .daily-summary-header-form {
            margin-bottom: 8px;
        }

        .daily-summary-header-toggle-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 6px;
        }

        .daily-summary-header-form.is-collapsed .daily-summary-header-grid,
        .daily-summary-header-form.is-collapsed .daily-summary-header-actions {
            display: none;
        }

        .daily-summary-header-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr 1fr;
            gap: 8px;
            align-items: stretch;
        }

        .daily-summary-header-card {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
        }

        .daily-summary-header-card-title {
            background: #e5e7eb;
            border-bottom: 1px solid #d1d5db;
            color: #374151;
            font-size: 12px;
            font-weight: 700;
            margin: 0;
            padding: 3px 6px;
        }

        .daily-summary-header-fields {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto minmax(0, 1fr);
            gap: 4px 8px;
            padding: 6px;
            align-items: center;
        }

        .daily-summary-header-label {
            color: #4b5563;
            font-size: 12px;
            white-space: nowrap;
        }

        .daily-summary-header-value {
            min-height: 24px;
        }

        .daily-summary-header-value input,
        .daily-summary-header-value textarea {
            width: 100%;
        }

        .daily-summary-header-note {
            padding: 6px;
        }

        .daily-summary-header-note textarea {
            min-height: 90px;
            resize: vertical;
            width: 100%;
        }

        .daily-summary-header-actions {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
            margin-top: 6px;
        }

        .daily-summary-header-form:not(.is-editing) .daily-summary-header-edit,
        .daily-summary-header-form:not(.is-editing) [data-daily-summary-header-save],
        .daily-summary-header-form:not(.is-editing) [data-daily-summary-header-cancel],
        .daily-summary-header-form.is-editing .daily-summary-header-display,
        .daily-summary-header-form.is-editing [data-daily-summary-header-edit] {
            display: none;
        }

        .daily-summary-staff-table {
            width: 100%;
        }

        .daily-summary-staff-table th,
        .daily-summary-staff-table td {
            padding: 3px 5px;
        }

        .daily-summary-print-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }

        .daily-summary-detail-cell {
            background: #f3f4f6;
        }

        @media (max-height: 800px) {

            .daily-summary-detail-cell input[type="text"],
            .daily-summary-detail-cell select,
            .daily-summary-detail-cell textarea {
                padding: 2px 4px;
                font-size: 12px;
                line-height: 1.1;
                border-radius: 4px;
            }

            .daily-summary-detail-cell input[type="checkbox"] {
                width: 14px;
                height: 14px;
            }
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">{{ $dailySummary['日付'] }}</h2>
                <h3 class="daily-summary-header-value">{{ $dailySummary['日報集計店舗'] }}</h3>
                <div class="daily-summary-header-toggle-row">
                    <button type="button" class="btn_small" data-daily-summary-header-toggle aria-expanded="true">サマリー</button>
                </div>
                <div class="daily-summary-print-actions">
                    <a
                        href="{{ route('office.store_daily_report.daily_summary.print', ['daily_summary_id' => $dailySummary['日報集計No']]) }}"
                        class="btn"
                        target="_blank"
                        rel="noopener">日報印刷</a>
                    <a
                        href="{{ route('office.store_daily_report.daily_summary.print', ['daily_summary_id' => $dailySummary['日報集計No'], 'print_mode' => 'modified']) }}"
                        class="btn"
                        target="_blank"
                        rel="noopener">修正日報</a>
                </div>
            </div>


            @if (session('statusMessage'))
            <div class="status-message status">{{ session('statusMessage') }}</div>
            @endif

            @if (session('errorMessage'))
            <div class="status-message error">{{ session('errorMessage') }}</div>
            @endif

            @php
            $isDailySummaryConfirmed = ($dailySummary['確定'] ?? '') !== '';
            $canSaveDailySummaryDetail = !$isDailySummaryConfirmed || ($isPaymentCheck ?? false);
            $canSaveDailySummaryExpense = ($isPaymentCheck ?? false)
            ? !($isDailySummaryMonthlyClosed ?? false)
            : !$isDailySummaryConfirmed;
            $isCheckedValue = static fn($value): bool => !in_array(strtolower(trim((string) ($value ?? ''))), ['', '0', 'false'], true);
            @endphp

            <form method="post" action="{{ route('office.store_daily_report.daily_summary.detail.header_save') }}" class="daily-summary-header-form" data-daily-summary-header-form>
                @csrf
                <input type="hidden" name="daily_summary_id" value="{{ $dailySummary['日報集計No'] }}">
                <div class="daily-summary-header-grid">
                    <section class="daily-summary-header-card">
                        <h3 class="daily-summary-header-card-title">日報集計</h3>
                        <div class="daily-summary-header-fields">

                            <div class="daily-summary-header-label">来院人数</div>
                            <div class="daily-summary-header-value">
                                <span class="daily-summary-header-display">{{ $dailySummary['来院人数'] }}</span>
                                <input class="daily-summary-header-edit text-right" type="text" name="来院人数" value="{{ $dailySummary['来院人数'] }}">
                            </div>
                            <div class="daily-summary-header-label">レセコン</div>
                            <div class="daily-summary-header-value">
                                <span class="daily-summary-header-display">{{ $dailySummary['レセコン'] }}</span>
                                <input class="daily-summary-header-edit text-right" type="text" name="レセコン" value="{{ $dailySummary['レセコン'] }}">
                            </div>



                            <div class="daily-summary-header-label">予約人数</div>
                            <div class="daily-summary-header-value">
                                <span class="daily-summary-header-display">{{ $dailySummary['予約人数'] }}</span>
                                <input class="daily-summary-header-edit text-right" type="text" name="予約人数" value="{{ $dailySummary['予約人数'] }}">
                            </div>
                            <div class="daily-summary-header-label">先生別計</div>
                            <div class="daily-summary-header-value">
                                <span class="daily-summary-header-display">{{ $dailySummary['先生別計'] }}</span>
                                <input class="daily-summary-header-edit text-right" type="text" name="先生別計" value="{{ $dailySummary['先生別計'] }}">
                            </div>
                            <div class="daily-summary-header-label">院内人数</div>
                            <div class="daily-summary-header-value">
                                <span class="daily-summary-header-display">{{ $dailySummary['院内人数'] }}</span>
                                <input class="daily-summary-header-edit text-right" type="text" name="院内人数" value="{{ $dailySummary['院内人数'] }}">
                            </div>



                            <div class="daily-summary-header-label">差額</div>
                            <div class="daily-summary-header-value">
                                <span class="daily-summary-header-display">{{ $dailySummary['差額'] }}</span>
                                <input class="daily-summary-header-edit text-right" type="text" name="差額" value="{{ $dailySummary['差額'] }}">
                            </div>

                            <div class="daily-summary-header-label">交通事故</div>
                            <div class="daily-summary-header-value">
                                <span class="daily-summary-header-display">{{ $dailySummary['交通事故'] }}</span>
                                <input class="daily-summary-header-edit text-right" type="text" name="交通事故" value="{{ $dailySummary['交通事故'] }}">
                            </div>
                            <div class="daily-summary-header-label">レジ</div>
                            <div class="daily-summary-header-value">
                                <span class="daily-summary-header-display">{{ $dailySummary['レジ'] }}</span>
                                <input class="daily-summary-header-edit text-right" type="text" name="レジ" value="{{ $dailySummary['レジ'] }}">
                            </div>
                            <div class="daily-summary-header-label">レセ負担金</div>
                            <div class="daily-summary-header-value text-right">{{ $dailySummary['レセ負担金'] }}</div>
                            <div class="daily-summary-header-label">カード</div>
                            <div class="daily-summary-header-value">
                                <span class="daily-summary-header-display">{{ $dailySummary['カード'] }}</span>
                                <input class="daily-summary-header-edit text-right" type="text" name="カード" value="{{ $dailySummary['カード'] }}">
                            </div>
                            <div class="daily-summary-header-label">銀行預入金額</div>
                            <div class="daily-summary-header-value">
                                <span class="daily-summary-header-display">{{ $dailySummary['銀行預入金額'] }}</span>
                                <input class="daily-summary-header-edit text-right" type="text" name="銀行預入金額" value="{{ $dailySummary['銀行預入金額'] }}">
                            </div>
                            <div class="daily-summary-header-label">誤差チェック</div>
                            <div class="daily-summary-header-value">
                                <span class="daily-summary-header-display">{{ $dailySummary['誤差チェック'] }}</span>
                                <input class="daily-summary-header-edit text-right" type="text" name="誤差チェック" value="{{ $dailySummary['誤差チェック'] }}">
                            </div>

                            <div class="daily-summary-header-label">確定</div>
                            <div class="daily-summary-header-value">{{ $dailySummary['確定'] }}</div>
                            <!-- <div class="daily-summary-header-label">確定日</div>
                            <div class="daily-summary-header-value">{{ $dailySummary['確定日'] }}</div> -->
                        </div>
                    </section>

                    <section class="daily-summary-header-card">
                        <h3 class="daily-summary-header-card-title">担当者別集計</h3>
                        <table class="data-table f_size12 daily-summary-staff-table">
                            <thead>
                                <tr>
                                    <th>担当者</th>
                                    <th>件数</th>
                                    <th>自費</th>
                                    <th>保険負担</th>
                                    <th>請求金額</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($staffSummaryRows ?? []) as $staffSummary)
                                <tr>
                                    <td>{{ $staffSummary['担当者名'] }}</td>
                                    <td class="text-right">{{ $staffSummary['件数'] }}</td>
                                    <td class="text-right">{{ $staffSummary['自費計'] }}</td>
                                    <td class="text-right">{{ $staffSummary['保険負担計'] }}</td>
                                    <td class="text-right">{{ $staffSummary['請求金額計'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">データがありません。</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </section>

                    <section class="daily-summary-header-card">
                        <h3 class="daily-summary-header-card-title">表紙表示欄</h3>
                        <div class="daily-summary-header-note">
                            <div class="daily-summary-header-display">{{ $dailySummary['備考'] }}</div>
                            <textarea class="daily-summary-header-edit" name="備考">{{ $dailySummary['備考'] }}</textarea>
                        </div>
                    </section>
                </div>
                @if (!($isDailySummaryMonthlyClosed ?? false))
                @if ($canSaveDailySummaryDetail)
                <div class="daily-summary-header-actions">
                    <button type="button" class="btn_small" data-daily-summary-header-edit>編集</button>
                    <button type="submit" class="btn_small" data-daily-summary-header-save>保存</button>
                    <button type="button" class="btn_small" data-daily-summary-header-cancel>取消</button>
                </div>
                @endif
                @endif
            </form>

            @php
            $patientSummaryOptionRows = collect($patientSummaryRows ?? []);
            $patientDetailOptionRows = collect($patientDetailRowsByPatientNo ?? [])->flatMap(fn($rows) => $rows);
            $makeOptions = fn($values) => collect($values)
            ->map(fn($value) => trim((string) $value))
            ->filter(fn($value) => $value !== '')
            ->unique()
            ->values();

            $newPatientOptions = $makeOptions($patientSummaryOptionRows->pluck('新患'));
            $patientNameOptions = $makeOptions($patientSummaryOptionRows->pluck('患者名'));
            $ratioOptions = $makeOptions($patientSummaryOptionRows->pluck('割合'));
            $staffOptions = $patientDetailOptionRows
            ->mapWithKeys(function (array $row): array {
            $staffId = trim((string) ($row['担当者ID'] ?? ''));
            $staffName = trim((string) ($row['staff_display_name'] ?? ''));

            return $staffId === '' ? [] : [$staffId => ($staffName !== '' ? $staffName : $staffId)];
            })
            ->all();
            $itemOptions = $makeOptions($patientDetailOptionRows->pluck('項目'));
            $menuOptions = $makeOptions($menuOptions ?? []);
            $countOptions = $makeOptions($patientDetailOptionRows->pluck('回数表示'));
            @endphp

            <datalist id="daily-summary-patient-name-options">
                @foreach ($patientNameOptions as $option)
                <option value="{{ $option }}"></option>
                @endforeach
            </datalist>
            <datalist id="daily-summary-count-options">
                @foreach ($countOptions as $option)
                <option value="{{ $option }}"></option>
                @endforeach
            </datalist>
            @php
            $expenseItemOptions = ['返金', '未収金', '物品購入費', '本日回収金', 'チャージ金'];
            @endphp

            <div class="filter-row daily-summary-filter-actions">
                <form method="get" action="{{ route('office.store_daily_report.daily_summary.detail') }}">
                    <input type="hidden" name="daily_summary_id" value="{{ $dailySummary['日報集計No'] }}">
                    <input type="hidden" name="filter_patient_name" value="{{ $filterPatientName ?? '' }}">
                    <!-- <label>担当者 -->
                    <select name="filter_staff_id">
                        <option value="">担当者全て</option>
                        @foreach (($filterStaffOptions ?? []) as $staffId => $staffName)
                        <option value="{{ $staffId }}" @selected(($filterStaffId ?? '' )===(string) $staffId)>{{ $staffName }}</option>
                        @endforeach
                    </select>
                    <!-- </label> -->
                    <label>
                        <input type="checkbox" name="filter_modified_only" value="1" @checked($filterModifiedOnly ?? false)>
                        chのみ
                    </label>
                    <button type="submit" class="btn">表示</button>
                    <a href="{{ route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummary['日報集計No']]) }}" class="btn">クリア</a>
                </form>
                @if ($canSaveDailySummaryDetail)
                <div class="daily-summary-patient-actions">
                    <form method="get" action="{{ route('office.store_daily_report.daily_summary.detail') }}">
                        <input type="hidden" name="daily_summary_id" value="{{ $dailySummary['日報集計No'] }}">
                        <input type="hidden" name="filter_staff_id" value="{{ $filterStaffId ?? '' }}">
                        @if ($filterModifiedOnly ?? false)
                        <input type="hidden" name="filter_modified_only" value="1">
                        @endif
                        <input type="text" name="filter_patient_name" value="{{ $filterPatientName ?? '' }}" list="daily-summary-patient-name-options" placeholder="患者名検索" data-daily-summary-patient-search>
                        <button type="submit" class="btn">検索</button>
                    </form>
                    <form method="post" action="{{ route('office.store_daily_report.daily_summary.detail.add_patient') }}">
                        @csrf
                        <input type="hidden" name="daily_summary_id" value="{{ $dailySummary['日報集計No'] }}">
                        <input type="hidden" name="日付" value="{{ $targetDate ?? '' }}">
                        <input type="hidden" name="店舗" value="{{ $dailySummary['日報集計店舗'] }}">
                        <input type="hidden" name="日別順" value="{{ $nextDailyOrder ?? '' }}">
                        <input type="hidden" name="患者名" value="{{ $filterPatientName ?? '' }}" data-daily-summary-add-patient-name>
                        <!-- <label>患者名 -->
                        <!-- <input type="text" name="患者名" list="daily-summary-patient-name-options" placeholder="患者名"> -->
                        <!-- </label> -->
                        @if (!($isDailySummaryMonthlyClosed ?? false))
                        <button type="submit" class="btn">新規追加</button>
                        @endif
                    </form>
                    @if (!($isDailySummaryMonthlyClosed ?? false))
                    <form method="post" action="{{ route('office.store_daily_report.daily_summary.detail.bulk_ch') }}">
                        @csrf
                        <input type="hidden" name="daily_summary_id" value="{{ $dailySummary['日報集計No'] }}">
                        <button type="submit" class="btn" onclick="return confirm('一括chを更新しますか？')">一括ch</button>
                    </form>
                    @endif
                </div>
                @endif
            </div>

            <div class="daily-summary-detail-block">
                <div class="daily-summary-filter-actions">
                    <button type="button" class="btn btn_small" data-daily-summary-expense-toggle aria-expanded="false">経費入力▼</button>
                </div>
                <div class="daily-summary-detail-cell" data-daily-summary-expense-panel hidden>
                    <div class="daily-summary-detail-title">{{ $dailySummary['日付'] }}　{{ $dailySummary['日報集計店舗'] }}</div>
                    @if ($canSaveDailySummaryExpense)
                    <form method="post" action="{{ route('office.store_daily_report.daily_summary.detail.expense_save') }}">
                        @csrf
                        <input type="hidden" name="daily_summary_id" value="{{ $dailySummary['日報集計No'] }}">
                        <table class="data-table f_size12 daily-summary-detail-input-table">
                            <thead>
                                <tr>
                                    <th>項目</th>
                                    <th>品名</th>
                                    <th>金額</th>
                                    <th>修正金額</th>
                                    <th>非表示</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (($dailySummaryExpenseRows ?? []) as $expenseRow)
                                <tr>
                                    <td>
                                        <input type="hidden" name="expense_rows[{{ $loop->index }}][レジＮｏ]" value="{{ $expenseRow['レジＮｏ'] }}">
                                        <input type="hidden" name="expense_rows[{{ $loop->index }}][_delete]" value="0" data-daily-summary-expense-delete>
                                        <select name="expense_rows[{{ $loop->index }}][項目]" required>
                                            <option value=""></option>
                                            @foreach ($expenseItemOptions as $option)
                                            <option value="{{ $option }}" @selected(($expenseRow['項目'] ?? '' )===$option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="expense_rows[{{ $loop->index }}][品名]" value="{{ $expenseRow['品名'] }}"></td>
                                    <td><input type="text" class="text-right" name="expense_rows[{{ $loop->index }}][金額]" value="{{ $expenseRow['金額'] }}" data-daily-summary-expense-amount></td>
                                    <td><input type="text" class="text-right" name="expense_rows[{{ $loop->index }}][金額1]" value="{{ $expenseRow['金額1'] }}" data-daily-summary-expense-adjusted></td>
                                    <td class="text-center"><input type="checkbox" name="expense_rows[{{ $loop->index }}][非表示ch]" value="1" @checked($expenseRow['非表示ch'] ?? false)></td>
                                    <td><button type="button" class="btn_small" data-daily-summary-delete-expense>削除</button></td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td>
                                        @php
                                        $newExpenseIndex = count($dailySummaryExpenseRows ?? []);
                                        @endphp
                                        <input type="hidden" name="expense_rows[{{ $newExpenseIndex }}][レジＮｏ]" value="">
                                        <input type="hidden" name="expense_rows[{{ $newExpenseIndex }}][_delete]" value="0" data-daily-summary-expense-delete>
                                        <select name="expense_rows[{{ $newExpenseIndex }}][項目]">
                                            <option value=""></option>
                                            @foreach ($expenseItemOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="expense_rows[{{ $newExpenseIndex }}][品名]"></td>
                                    <td><input type="text" class="text-right" name="expense_rows[{{ $newExpenseIndex }}][金額]" data-daily-summary-expense-amount></td>
                                    <td><input type="text" class="text-right" name="expense_rows[{{ $newExpenseIndex }}][金額1]" data-daily-summary-expense-adjusted></td>
                                    <td class="text-center"><input type="checkbox" name="expense_rows[{{ $newExpenseIndex }}][非表示ch]" value="1"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="daily-summary-actions">
                            <button type="submit" class="btn">保存</button>
                        </div>
                    </form>
                    @elseif (!empty($dailySummaryExpenseRows ?? []))
                    <table class="data-table f_size12 daily-summary-detail-input-table">
                        <thead>
                            <tr>
                                <th>項目</th>
                                <th>品名</th>
                                <th>金額</th>
                                <th>修正金額</th>
                                <th>非表示</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (($dailySummaryExpenseRows ?? []) as $expenseRow)
                            <tr>
                                <td>{{ $expenseRow['項目'] }}</td>
                                <td>{{ $expenseRow['品名'] }}</td>
                                <td class="text-right">{{ $expenseRow['金額'] }}</td>
                                <td class="text-right">{{ $expenseRow['金額1'] }}</td>
                                <td class="text-center">{{ ($expenseRow['非表示ch'] ?? false) ? '非表示' : '' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table f_size12 daily-summary-patient-table">
                    <colgroup>
                        <col style="width: 20px;">
                        <col style="width: 20px;">
                        <col style="width: 50px;">
                        <col style="width: 20px;">
                        <col style="width: 30px;">
                        <col style="width: 70px;">
                        <col style="width: 70px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 70px;">
                        <col style="width: 20px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>日別順</th>
                            <th>時刻</th>
                            <th>患者名</th>
                            <th>ch</th>
                            <th>担当者</th>
                            <th>メニュー</th>
                            <th>回数</th>
                            <th>自費</th>
                            <th>保険負担</th>
                            <th>レセ差額</th>
                            <th>請求金額</th>
                            <th>備考</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($patientSummaryRows ?? []) as $row)
                        @php
                        $patientNo = (string) ($row['患者No'] ?? '');
                        $detailRows = $patientDetailRowsByPatientNo[$patientNo] ?? [];
                        $dailyOrderClass = match (trim((string) ($row['新患'] ?? ''))) {
                        '新患' => 'daily-order-new',
                        '新患扱い' => 'daily-order-new-like',
                        default => '',
                        };
                        @endphp
                        <tr>
                            <td class="{{ $dailyOrderClass }}">{{ $row['日別順'] }}</td>
                            <td>{{ $row['時刻'] }}</td>
                            <td>{{ $row['患者名'] }}</td>
                            <td class="text-center"><input type="checkbox" @checked((int) ($row['ch'] ?? 0)===1) disabled></td>
                            <td>{{ $row['staff_display_name'] }}</td>
                            <td>{{ $row['メニュー集計'] }}</td>
                            <td>{{ $row['回数表示'] }}</td>
                            <td class="text-right">{{ $row['自費計'] }}</td>
                            <td class="text-right">{{ $row['保険負担計'] }}</td>
                            <td class="text-right">{{ $row['レセ差額'] }}</td>
                            <td class="text-right">{{ $row['請求金額計'] }}</td>
                            <td>{{ $row['備考'] }}</td>
                            <td>
                                <button type="button" class="btn_small" data-daily-summary-detail-toggle>+</button>
                            </td>
                        </tr>
                        <tr data-daily-summary-detail-row hidden>
                            <td colspan="13" class="daily-summary-detail-cell">
                                <form method="post" action="{{ route('office.store_daily_report.daily_summary.detail.save') }}" data-daily-summary-detail-form>
                                    @csrf
                                    <input type="hidden" name="daily_summary_id" value="{{ $dailySummary['日報集計No'] }}">
                                    <input type="hidden" name="No" value="{{ $row['No'] }}">
                                    <input type="hidden" name="患者No" value="{{ $row['患者No'] }}">
                                    <input type="hidden" name="日付" value="{{ $row['日付'] }}">
                                    <input type="hidden" name="店舗" value="{{ $dailySummary['日報集計店舗'] }}">
                                    <div class="daily-summary-detail-block">
                                        <!-- <p class="daily-summary-detail-title">患者情報</p> -->
                                        <div class="daily-summary-access-form">
                                            <div class="daily-summary-access-group">
                                                <p class="daily-summary-access-group-title">患者基本</p>
                                                <table class="data-table f_size12">
                                                    <tbody>
                                                        <tr>
                                                        <tr>
                                                            <th>日別順</th>
                                                            <td>{{ $row['日別順'] }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>日付</th>
                                                            <td>{{ $row['日付'] }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>時刻</th>
                                                            <td><input type="text" name="時刻" value="{{ $row['時刻'] }}"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="daily-summary-access-group">
                                                <p class="daily-summary-access-group-title">患者基本</p>
                                                <table class="data-table f_size12">
                                                    <tbody>
                                                        <tr>
                                                            <th>新患</th>
                                                            <td>
                                                                <select name="新患">
                                                                    <option value=""></option>
                                                                    @foreach ($newPatientOptions as $option)
                                                                    <option value="{{ $option }}" @selected((string) ($row['新患'] ?? '' )===(string) $option)>{{ $option }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>患者名</th>
                                                            <td>
                                                                <input type="text" name="患者名" value="{{ $row['患者名'] }}" list="daily-summary-patient-name-options">
                                                            </td>
                                                        </tr>
                                                        <!-- <tr>
                                                            <th>割合</th>
                                                            <td>
                                                                <select name="割合">
                                                                    <option value=""></option>
                                                                    @foreach ($ratioOptions as $option)
                                                                    <option value="{{ $option }}" @selected((string) ($row['割合'] ?? '' )===(string) $option)>{{ $option }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                        </tr> -->
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="daily-summary-access-group">
                                                <p class="daily-summary-access-group-title">保険証</p>
                                                <table class="data-table f_size12">
                                                    <tbody>
                                                        <tr>
                                                            <th>保険証</th>
                                                            <td>割合
                                                                <select name="割合">
                                                                    <option value=""></option>
                                                                    @foreach ($ratioOptions as $option)
                                                                    <option value="{{ $option }}" @selected((string) ($row['割合'] ?? '' )===(string) $option)>{{ $option }}</option>
                                                                    @endforeach
                                                                </select>／
                                                                <select name="保険証">
                                                                    <option value="" @selected((string) ($row['保険証'] ?? '' )==='' )></option>
                                                                    <option value="0" @selected((string) ($row['保険証'] ?? '' )==='0' )>持参</option>
                                                                    <option value="1" @selected((string) ($row['保険証'] ?? '' )==='1' )>忘れ</option>
                                                                    <option value="2" @selected((string) ($row['保険証'] ?? '' )==='2' )>回収</option>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>回収日</th>
                                                            <td><input type="text" name="回収日" value="{{ $row['回収日'] }}"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="daily-summary-access-group">
                                                <p class="daily-summary-access-group-title">レセ金額</p>
                                                <table class="data-table f_size12">
                                                    <tbody>
                                                        <tr>
                                                            <th>レセ負担金</th>
                                                            <td class="daily-summary-receipt-burden-cell"><input type="text" name="レセ負担金" value="{{ $row['レセ負担金'] }}"><input type="checkbox" name="負担金ch" value="1" @checked($isCheckedValue($row['負担金ch'] ?? null))></td>
                                                        </tr>
                                                        <tr>
                                                            <th>保険請求</th>
                                                            <td><input type="text" name="保険請求" value="{{ $row['保険請求'] }}"></td>
                                                        </tr>
                                                        <tr>
                                                            <th>差額</th>
                                                            <td><input type="text" name="差額" value="{{ $row['差額'] }}"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="daily-summary-access-group">
                                                <p class="daily-summary-access-group-title">備考</p>
                                                <table class="data-table f_size12">
                                                    <tbody>
                                                        <tr>
                                                            <!-- <th width="80">日報備考</th> -->
                                                            <td><textarea class="daily-summary-remarks-textarea" name="日報備考" rows="4">{{ $row['日報備考'] }}</textarea></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                @if (!($isDailySummaryMonthlyClosed ?? false))
                                                <div class="daily-summary-actions">
                                                    @if ($canSaveDailySummaryDetail)
                                                    <button type="button" class="btn" data-daily-summary-add-detail>新規行</button>
                                                    <button type="submit" class="btn" name="action" value="save">保存</button>
                                                    @endif
                                                    <button type="button" class="btn" data-daily-summary-cancel>取消</button>
                                                    @if (!$isDailySummaryConfirmed || ($isAdmin ?? false))
                                                    <button type="submit" class="btn" name="action" value="delete_patient" data-confirm-message="削除しますか？">削除</button>
                                                    @endif
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="daily-summary-detail-block">
                                        <!-- <p class="daily-summary-detail-title">明細入力</p> -->
                                        <table class="data-table f_size11 daily-summary-detail-input-table">
                                            <colgroup>
                                                <col style="width: 80px;">
                                                <col style="width: 70px;">
                                                <col style="width: 70px;">
                                                <col style="width: 35px;">
                                                <col style="width: 35px;">
                                                <col style="width: 40px;">
                                                <col style="width: 40px;">
                                                <col style="width: 40px;">
                                                <col style="width: 40px;">
                                                <col style="width: 40px;">
                                                <col style="width: 50px;">
                                                <col style="width: 20px;">
                                                <col style="width: 20px;">
                                                <col style="width: 20px;">
                                                @if (!$isDailySummaryConfirmed)
                                                <col style="width: 18px;">
                                                @endif
                                            </colgroup>
                                            <thead>
                                                <tr class="f_size11">
                                                    <th>メニュー</th>
                                                    <th>回数</th>
                                                    <th>備考</th>
                                                    <th>チャージ</th>
                                                    <th>自費</th>
                                                    <th>保険負担</th>
                                                    <th>レセ差額</th>
                                                    <th>請求金額</th>
                                                    <th>カード<br>手数料</th>
                                                    <th>担当者</th>
                                                    <th>項目</th>
                                                    <th>日報<br>除外</th>
                                                    <th>先生別<br>除外</th>
                                                    <th>一括</th>
                                                    @if (!$isDailySummaryConfirmed)
                                                    <th>削除</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody data-daily-summary-detail-body>
                                                @forelse ($detailRows as $detailRow)
                                                <tr data-daily-summary-detail-input-row>
                                                    <td>
                                                        <input type="hidden" name="detail_rows[{{ $loop->index }}][先生別No]" value="{{ $detailRow['先生別No'] }}">
                                                        <input type="hidden" name="detail_rows[{{ $loop->index }}][_delete]" value="0" data-daily-summary-detail-delete>
                                                        <select name="detail_rows[{{ $loop->index }}][メニュー]">
                                                            <option value=""></option>
                                                            @foreach ($menuOptions as $option)
                                                            <option value="{{ $option }}" @selected((string) ($detailRow['メニュー集計'] ?? '' )===(string) $option)>{{ $option }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="detail_rows[{{ $loop->index }}][回数]" value="{{ $detailRow['回数表示'] }}" list="daily-summary-count-options">
                                                    </td>
                                                    <td><input type="text" name="detail_rows[{{ $loop->index }}][備考]" value="{{ $detailRow['備考'] }}"></td>
                                                    <td class="text-center"><input type="checkbox" name="detail_rows[{{ $loop->index }}][charge]" value="1" @checked((bool) ($detailRow['チャージ'] ?? false))></td>
                                                    <td><input type="text" class="right" name="detail_rows[{{ $loop->index }}][自費]" value="{{ $detailRow['自費'] }}"></td>
                                                    <td><input type="text" class="right" name="detail_rows[{{ $loop->index }}][保険負担]" value="{{ $detailRow['保険負担'] }}"></td>
                                                    <td><input type="text" class="right" name="detail_rows[{{ $loop->index }}][レセ差額]" value="{{ $detailRow['レセ差額'] }}"></td>
                                                    <td><input type="text" class="right" name="detail_rows[{{ $loop->index }}][請求金額]" value="{{ $detailRow['請求金額'] }}"></td>
                                                    <td><input type="text" class="right" name="detail_rows[{{ $loop->index }}][カード手数料]" value="{{ $detailRow['カード手数料'] }}"></td>
                                                    <td>
                                                        <select name="detail_rows[{{ $loop->index }}][担当者ID]">
                                                            <option value=""></option>
                                                            @foreach ($staffOptions as $staffId => $staffName)
                                                            <option value="{{ $staffId }}" @selected((string) ($detailRow['担当者ID'] ?? '' )===(string) $staffId)>{{ $staffName }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <!-- <select name="detail_rows[{{ $loop->index }}][項目]"> -->
                                                        <!-- <option value=""></option> -->
                                                        <select name="detail_rows[{{ $loop->index }}][項目]">
                                                            <option value=""></option>
                                                            <option value="物品販売" @selected((string) ($detailRow['項目'] ?? '' )==='物品販売' )>物品販売</option>
                                                        </select>

                                                        <!-- @foreach ($itemOptions as $option)
                                                            <option value="{{ $option }}" @selected((string) ($detailRow['項目'] ?? '' )===(string) $option)>{{ $option }}</option>
                                                            @endforeach -->
                                                        <!-- </select> -->
                                                    </td>
                                                    <td class="text-center"><input type="checkbox" name="detail_rows[{{ $loop->index }}][計算外]" value="1" @checked((bool) ($detailRow['計算外'] ?? false))></td>
                                                    <td class="text-center"><input type="checkbox" name="detail_rows[{{ $loop->index }}][先生別外]" value="1" @checked((bool) ($detailRow['先生別外'] ?? false))></td>
                                                    <td class="text-center"><input type="checkbox" name="detail_rows[{{ $loop->index }}][ch]" value="1" @checked((bool) ($detailRow['ch'] ?? false))></td>
                                                    @if (!$isDailySummaryConfirmed)
                                                    <td><button type="button" class="btn_small" data-daily-summary-delete-detail>×</button></td>
                                                    @endif
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="{{ !$isDailySummaryConfirmed ? 15 : 14 }}">対象データがありません。</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13">対象データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @php
                    $patientSummaryTotals = collect($patientSummaryRows ?? [])->reduce(function (array $totals, array $row): array {
                    $toNumber = fn($value): float => (float) str_replace(',', '', (string) ($value ?? 0));

                    $totals['件数'] += (int) ($row['人数'] ?? 0);
                    $totals['自費計'] += $toNumber($row['自費計'] ?? 0);
                    $totals['保険負担計'] += $toNumber($row['保険負担計'] ?? 0);
                    $totals['レセ差額'] += $toNumber($row['レセ差額'] ?? 0);
                    $totals['請求金額計'] += $toNumber($row['請求金額計'] ?? 0);

                    return $totals;
                    }, [
                    '件数' => 0,
                    '自費計' => 0,
                    '保険負担計' => 0,
                    'レセ差額' => 0,
                    '請求金額計' => 0,
                    ]);
                    @endphp
                    <tfoot>
                        <tr>
                            <th colspan="6">合計</th>
                            <th class="text-right">{{ $patientSummaryTotals['件数'] }}</th>
                            <th class="text-right">{{ number_format($patientSummaryTotals['自費計']) }}</th>
                            <th class="text-right">{{ number_format($patientSummaryTotals['保険負担計']) }}</th>
                            <th class="text-right">{{ number_format($patientSummaryTotals['レセ差額']) }}</th>
                            <th class="text-right">{{ number_format($patientSummaryTotals['請求金額計']) }}</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div>
                <a href="{{ url()->previous() }}" class="btn btn_back">戻る</a>
                <!-- <a href="{{ route('office.store_daily_report.daily_summary') }}" class="btn btn_back">戻る</a> -->
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-daily-summary-detail-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const detailRow = button.closest('tr')?.nextElementSibling;
                if (!detailRow?.matches('[data-daily-summary-detail-row]')) {
                    return;
                }

                const isOpen = !detailRow.hidden;
                detailRow.hidden = isOpen;
                button.textContent = isOpen ? '+' : '-';
            });
        });

        const dailySummaryDetailOptions = {
            menuOptions: @json($menuOptions),
            countOptions: @json($countOptions),
            staffOptions: @json($staffOptions),
            itemOptions: @json($itemOptions),
        };

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const optionHtml = (values) => ['<option value=""></option>']
            .concat((Array.isArray(values) ? values.map((value) => [value, value]) : Object.entries(values))
                .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`))
            .join('');

        document.querySelectorAll('[data-daily-summary-header-form]').forEach((form) => {
            form.querySelector('[data-daily-summary-header-edit]')?.addEventListener('click', () => {
                form.classList.add('is-editing');
            });

            form.querySelector('[data-daily-summary-header-cancel]')?.addEventListener('click', () => {
                form.classList.remove('is-editing');
            });
        });

        const dailySummaryHeaderForm = document.querySelector('[data-daily-summary-header-form]');
        const dailySummaryHeaderToggle = document.querySelector('[data-daily-summary-header-toggle]');
        if (dailySummaryHeaderForm && dailySummaryHeaderToggle) {
            const setDailySummaryHeaderCollapsed = (isCollapsed) => {
                dailySummaryHeaderForm.classList.toggle('is-collapsed', isCollapsed);
                dailySummaryHeaderToggle.setAttribute('aria-expanded', String(!isCollapsed));
                dailySummaryHeaderToggle.textContent = isCollapsed ? 'サマリー△' : 'サマリー▼';
            };

            setDailySummaryHeaderCollapsed(window.innerHeight <= 800);
            dailySummaryHeaderToggle.addEventListener('click', () => {
                setDailySummaryHeaderCollapsed(!dailySummaryHeaderForm.classList.contains('is-collapsed'));
            });
        }

        const dailySummaryExpenseToggle = document.querySelector('[data-daily-summary-expense-toggle]');
        const dailySummaryExpensePanel = document.querySelector('[data-daily-summary-expense-panel]');
        dailySummaryExpenseToggle?.addEventListener('click', () => {
            if (!dailySummaryExpensePanel) {
                return;
            }

            const isOpen = !dailySummaryExpensePanel.hidden;
            dailySummaryExpensePanel.hidden = isOpen;
            dailySummaryExpenseToggle.setAttribute('aria-expanded', String(!isOpen));
        });

        document.querySelectorAll('[data-daily-summary-expense-amount]').forEach((amountInput) => {
            const row = amountInput.closest('tr');
            const adjustedInput = row?.querySelector('[data-daily-summary-expense-adjusted]');
            if (!adjustedInput) {
                return;
            }

            adjustedInput.dataset.autoFilled = adjustedInput.value === '' ? '1' : '0';
            adjustedInput.addEventListener('input', () => {
                adjustedInput.dataset.autoFilled = '0';
            });
            amountInput.addEventListener('input', () => {
                if (adjustedInput.value === '' || adjustedInput.dataset.autoFilled === '1') {
                    adjustedInput.value = amountInput.value;
                    adjustedInput.dataset.autoFilled = '1';
                }
            });
        });

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-daily-summary-delete-expense]');
            if (!button) {
                return;
            }

            if (!window.confirm('削除しますか？')) {
                return;
            }

            const row = button.closest('tr');
            const deleteInput = row?.querySelector('[data-daily-summary-expense-delete]');
            if (!row || !deleteInput) {
                return;
            }

            deleteInput.value = '1';
            row.hidden = true;
            button.closest('form')?.requestSubmit();
        });

        document.querySelectorAll('[data-daily-summary-detail-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const submitter = event.submitter;
                const message = submitter?.dataset?.confirmMessage;
                if (message && !window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });

        const dailySummaryPatientSearch = document.querySelector('[data-daily-summary-patient-search]');
        const dailySummaryAddPatientName = document.querySelector('[data-daily-summary-add-patient-name]');
        dailySummaryPatientSearch?.addEventListener('input', () => {
            if (dailySummaryAddPatientName) {
                dailySummaryAddPatientName.value = dailySummaryPatientSearch.value;
            }
        });

        document.querySelectorAll('[data-daily-summary-add-detail]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('[data-daily-summary-detail-form]');
                const body = form?.querySelector('[data-daily-summary-detail-body]');
                if (!body) {
                    return;
                }

                body.querySelector('td[colspan]')?.closest('tr')?.remove();
                const index = body.querySelectorAll('[data-daily-summary-detail-input-row]').length;
                const row = document.createElement('tr');
                row.dataset.dailySummaryDetailInputRow = '';
                row.innerHTML = `
                    <td>
                        <input type="hidden" name="detail_rows[${index}][先生別No]" value="">
                        <input type="hidden" name="detail_rows[${index}][_delete]" value="0" data-daily-summary-detail-delete>
                        <select name="detail_rows[${index}][メニュー]">${optionHtml(dailySummaryDetailOptions.menuOptions)}</select>
                    </td>
                    <td><input type="text" name="detail_rows[${index}][回数]" value="" list="daily-summary-count-options"></td>
                    <td><input type="text" name="detail_rows[${index}][備考]" value=""></td>
                    <td class="text-center"><input type="checkbox" name="detail_rows[${index}][charge]" value="1"></td>
                    <td><input type="text" class="text-right" name="detail_rows[${index}][自費]" value=""></td>
                    <td><input type="text" class="text-right" name="detail_rows[${index}][保険負担]" value=""></td>
                    <td><input type="text" class="text-right" name="detail_rows[${index}][レセ差額]" value=""></td>
                    <td><input type="text" class="text-right" name="detail_rows[${index}][請求金額]" value=""></td>
                    <td><input type="text" class="text-right" name="detail_rows[${index}][カード手数料]" value=""></td>
                    <td><select name="detail_rows[${index}][担当者ID]">${optionHtml(dailySummaryDetailOptions.staffOptions)}</select></td>
                    <td><select name="detail_rows[${index}][項目]">${optionHtml(dailySummaryDetailOptions.itemOptions)}</select></td>
                    <td class="text-center"><input type="checkbox" name="detail_rows[${index}][計算外]" value="1"></td>
                    <td class="text-center"><input type="checkbox" name="detail_rows[${index}][先生別外]" value="1"></td>
                    <td class="text-center"><input type="checkbox" name="detail_rows[${index}][ch]" value="1"></td>
                    @if (!$isDailySummaryConfirmed)
                    <td><button type="button" class="btn_small" data-daily-summary-delete-detail>×</button></td>
                    @endif
                `;
                body.appendChild(row);
            });
        });

        document.querySelectorAll('[data-daily-summary-cancel]').forEach((button) => {
            button.addEventListener('click', () => {
                window.location.reload();
            });
        });

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-daily-summary-delete-detail]');
            if (!button) {
                return;
            }

            const row = button.closest('[data-daily-summary-detail-input-row]');
            const deleteInput = row?.querySelector('[data-daily-summary-detail-delete]');
            const teacherDailyReportNo = row?.querySelector('input[name$="[先生別No]"]')?.value ?? '';
            if (!row || !deleteInput) {
                return;
            }

            if (!window.confirm('明細行を削除しますか？')) {
                return;
            }

            if (teacherDailyReportNo === '') {
                row.remove();
                return;
            }

            deleteInput.value = '1';
            row.hidden = true;
        });
    </script>
</body>

</html>
