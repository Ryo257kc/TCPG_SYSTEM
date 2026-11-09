<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 未収入金一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .receivable-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .receivable-tabs .is-active {
            background: #1f4f8f;
            color: #fff;
        }

        .return-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin: 10px 0;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])
        @include('shared.status_message')

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">未収入金一覧</h2>
            </div>

            @if ($canProcessReturns ?? false)
            <nav class="receivable-tabs" aria-label="未収入金の表示切替">
                <a class="btn {{ !($isReturnTab ?? false) ? 'is-active' : '' }}" href="{{ route('office.sales.uncollected', ['payment_month' => $paymentMonth, 'company_id' => $selectedCompanyId, 'store_category' => $selectedStoreCategory]) }}">未収入金</a>
                <a class="btn {{ ($isReturnTab ?? false) ? 'is-active' : '' }}" href="{{ route('office.sales.uncollected', ['tab' => 'returns', 'payment_month' => $paymentMonth, 'company_id' => $selectedCompanyId, 'store_category' => $selectedStoreCategory]) }}">返戻</a>
            </nav>
            @endif

            <form method="get" class="toolbar">
                <div class="toolbar-group">
                    <label for="uncollected-payment-month">{{ ($isReturnTab ?? false) ? '決算月' : '印刷月' }}</label>
                    <input id="uncollected-payment-month" name="payment_month" type="month" value="{{ $paymentMonth ?? now()->format('Y-m') }}">{{ ($isReturnTab ?? false) ? '' : '迄' }}
                    @if ($isReturnTab ?? false)
                    <input type="hidden" name="tab" value="returns">
                    <label for="return-status">処理状況</label>
                    <select id="return-status" name="return_status">
                        <option value="unprocessed" @selected($returnStatus==='unprocessed' )>未処理</option>
                        <option value="processed" @selected($returnStatus==='processed' )>処理済み</option>
                    </select>
                    @endif
                    <span class="margin_l20"></span>
                    <select id="uncollected-store-category" name="store_category">
                        <option value="">-店舗選択-</option>
                        @foreach (($storeCategoryOptions ?? []) as $storeCategory)
                        <option value="{{ $storeCategory }}" @selected(($selectedStoreCategory ?? '' )===$storeCategory)>{{ $storeCategory }}</option>
                        @endforeach
                    </select>
                    <select id="uncollected-company-id" name="company_id">
                        <option value="">-事業所選択-</option>
                        @foreach (($companyOptions ?? []) as $company)
                        <option value="{{ $company['company_id'] }}" @selected(($selectedCompanyId ?? '' )===$company['company_id'])>{{ trim(str_replace('株式会社', '', (string) $company['company_name'])) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn">表示</button>
                    @if ($isReturnTab ?? false)
                    <button type="submit" class="btn" formaction="{{ route('office.sales.uncollected.returns.print') }}" formtarget="_blank">確認シート・PDF</button>
                    @if ($returnStatus === 'processed')
                    <button type="submit" class="btn" formaction="{{ route('office.sales.uncollected.returns.csv') }}" onclick="if (!this.form.elements.company_id.value) { alert('会社を選択してください。'); return false; }">仕訳CSV</button>
                    @endif
                    @else
                    <button
                        type="submit"
                        class="btn"
                        formaction="{{ route('office.sales.uncollected.print') }}"
                        formtarget="_blank">未収入金一覧</button>
                    <button
                        type="submit"
                        class="btn"
                        formaction="{{ route('office.sales.uncollected.detail_print') }}"
                        formtarget="_blank">個別一覧</button>
                    @endif
                </div>
            </form>

            @if ($isReturnTab ?? false)
            <div class="return-summary">
                <span>{{ count($uncollectedRows) }}件</span>
                <span>請求金額：{{ $returnTotals['claim_amount'] }}</span>
                <span>修正額：{{ $returnTotals['adjustment_amount'] }}</span>
                <span>返戻額：{{ $returnTotals['returned_amount'] }}</span>
            </div>
            <form id="return-processing-form" method="post" action="{{ route('office.sales.uncollected.returns.process') }}" class="toolbar" onsubmit="this.elements.payment_month.value = document.getElementById('uncollected-payment-month').value; return confirm('選択した明細の返戻仕訳処理日を ' + this.elements.payment_month.value + ' の末日で保存します。よろしいですか？');">
                @csrf
                <input type="hidden" name="payment_month" value="{{ $paymentMonth }}">
                <input type="hidden" name="return_status" value="{{ $returnStatus }}">
                <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                <input type="hidden" name="store_category" value="{{ $selectedStoreCategory }}">
                <div class="toolbar-group">
                    <button type="submit" class="btn">選択した明細の処理日を保存</button>
                </div>
            </form>
            @endif
            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table f_size13">
                    <colgroup>
                        @if ($isReturnTab ?? false)
                        <col style="width: 35px;">
                        @endif
                        <col style="width: 50px;">
                        <col style="width: 100px;">
                        <col style="width: 60px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 100px;">
                        <col style="width: 60px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 70px;">
                        <col style="width: 50px;">
                        @if ($isReturnTab ?? false)
                        <col style="width: 60px;">
                        <col style="width: 50px;">
                        <col style="width: 85px;">
                        @endif
                    </colgroup>
                    <thead>
                        <tr>
                            @if ($isReturnTab ?? false)
                            <th><input type="checkbox" aria-label="表示中の返戻明細を全選択" onchange="document.querySelectorAll('.return-detail-selection').forEach(function(input) { input.checked = this.checked; }, this)"></th>
                            @endif
                            <th>保険者番号</th>
                            <th>保険者名称</th>
                            <th>施術月</th>
                            <th>請求金額</th>
                            <th>修正額</th>
                            <th>現金回収</th>
                            <th>返戻額</th>
                            <th>備考</th>
                            <th>入金日</th>
                            <th>入金額</th>
                            <th>残額</th>
                            <th>店舗</th>
                            <th>社名</th>
                            @if ($isReturnTab ?? false)
                            <th>保険区分</th>
                            <th>回収<br>不可</th>
                            <th>返戻仕訳<br>処理日</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($uncollectedRows ?? []) as $row)
                        <tr>
                            @if ($isReturnTab ?? false)
                            <td><input class="return-detail-selection" form="return-processing-form" type="checkbox" name="detail_ids[]" value="{{ $row['insurance_claim_detail_id'] }}" aria-label="この返戻明細の処理日を登録"></td>
                            @endif
                            <td>{{ $row['insurer_number'] }}</td>
                            <td class="staff-wrap-text">{{ $row['insurer_name'] }}</td>
                            <td>{{ $row['treatment_month'] }}</td>
                            <td class="right">{{ $row['claim_amount'] }}</td>
                            <td class="right">{{ $row['adjustment_amount'] }}</td>
                            <td class="right">{{ $row['cash_collected_amount'] }}</td>
                            <td class="right">{{ $row['returned_amount'] }}</td>
                            <td class="staff-wrap-text">{{ $row['remarks'] }}</td>
                            <td>{{ $row['payment_date_text'] }}</td>
                            <td class="right">{{ $row['payment_amount'] }}</td>
                            <td class="right">{{ $row['remaining_amount'] }}</td>
                            <td>{{ $row['store_category'] }}</td>
                            <td class="staff-wrap-text">{{ trim(str_replace('株式会社', '', (string) $row['company_name'])) }}</td>
                            @if ($isReturnTab ?? false)
                            <td>{{ $row['insurance_type'] }}</td>
                            <td>{{ $row['is_uncollectible'] ? '✓' : '' }}</td>
                            <td>{{ $row['returned_journal_processed_at'] }}</td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ ($isReturnTab ?? false) ? 17 : 13 }}">対象データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>
