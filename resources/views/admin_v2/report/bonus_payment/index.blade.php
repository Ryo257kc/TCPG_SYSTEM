<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 賞与支払届一覧</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/report.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/santei.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/bonus_payment.css') }}">
</head>
<body>
@include('admin_v2.shared.global_nav')
<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM 賞与支払届一覧</div>
    </div>

    <section class="panel report-main-panel">
        <section class="panel santei-filter-panel">
            <form method="GET" class="santei-filter-form bonus-filter-form">
                <label class="field">
                    <span>対象年</span>
                    <select name="year">
                        @foreach ($availableYears as $year)
                            <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}年</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>会社</span>
                    <select name="company">
                        <option value="">全社</option>
                        @foreach ($companyOptions as $company)
                            <option value="{{ $company }}" @selected($selectedCompany === $company)>{{ $company }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field field-payment-date">
                    <span>対象月</span>
                    <select name="payment_month">
                        <option value="">全月</option>
                        @foreach ($paymentMonthOptions as $paymentMonth)
                            <option value="{{ $paymentMonth }}" @selected($selectedPaymentMonth === $paymentMonth)>{{ $paymentMonth }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="btn btn-primary">表示</button>
                @if ($selectedCompany !== '' && $selectedPaymentMonth !== '')
                    <a href="{{ route('admin.report.bonus-payment.csv', ['year' => $selectedYear, 'company' => $selectedCompany, 'payment_month' => $selectedPaymentMonth]) }}" class="btn">CSV出力</a>
                @else
                    <span class="btn is-disabled">会社と対象月を選択</span>
                @endif
            </form>
        </section>

        <section class="panel santei-table-panel">
            <div class="santei-head">
                <div>
                    <h2>確認一覧</h2>
                    <p>{{ $selectedYear }}年の賞与支払届CSVに載せる確認用データです。</p>
                </div>
                <div class="santei-meta">{{ count($rows) }}件</div>
            </div>

            <div class="table-wrap">
                <table class="santei-table bonus-payment-table">
                    <thead>
                    <tr>
                        <th>整理</th>
                        <th>氏名</th>
                        <th>氏名カナ</th>
                        <th>生年月日</th>
                        <th>支給日</th>
                        <th>会社</th>
                        <th>店舗</th>
                        <th>通貨額</th>
                        <th>合計賞与額</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['syaho_seiri_num_padded'] }}</td>
                            <td>{{ $row['staff_name'] !== '' ? $row['staff_name'] : '-' }}</td>
                            <td>{{ $row['staff_name_furi'] !== '' ? $row['staff_name_furi'] : '-' }}</td>
                            <td>{{ $row['birthday_wareki'] !== '' ? $row['birthday_wareki'] : '-' }}</td>
                            <td>{{ $row['payment_date'] !== '' ? $row['payment_date'] : '-' }}</td>
                            <td>{{ $row['company_name'] !== '' ? $row['company_name'] : '-' }}</td>
                            <td>{{ $row['store_name'] !== '' ? $row['store_name'] : '-' }}</td>
                            <td class="num">{{ number_format((float) ($row['bonus_amo'] ?? 0)) }}</td>
                            <td class="num">{{ number_format((float) ($row['bonus_total_rounded'] ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty-cell">対象データがありません。</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>
</body>
</html>
