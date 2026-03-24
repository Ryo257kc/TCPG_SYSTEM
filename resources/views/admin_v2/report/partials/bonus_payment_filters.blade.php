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
            <a
                href="{{ route('admin.report.bonus-payment.csv', ['year' => $selectedYear, 'company' => $selectedCompany, 'payment_month' => $selectedPaymentMonth]) }}"
                class="btn"
            >CSV出力</a>
        @else
            <span class="btn is-disabled">会社と対象月を選択</span>
        @endif
    </form>
</section>
