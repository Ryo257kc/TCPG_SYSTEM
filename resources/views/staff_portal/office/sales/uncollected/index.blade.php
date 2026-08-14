<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 未収入金一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">未収入金一覧</h2>
            </div>

            <form method="get" class="toolbar">
                <div class="toolbar-group">
                    <label for="uncollected-payment-month">印刷月</label>
                    <input id="uncollected-payment-month" name="payment_month" type="month" value="{{ $paymentMonth ?? now()->format('Y-m') }}">迄
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
                </div>
            </form>

            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table f_size13">
                    <colgroup>
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
                    </colgroup>
                    <thead>
                        <tr>
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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($uncollectedRows ?? []) as $row)
                        <tr>
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
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13">対象データがありません。</td>
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
