<section class="panel santei-filter-panel">
    <form method="GET" class="santei-filter-form">
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
        <button type="submit" class="btn btn-primary">表示</button>
        @if ($selectedCompany !== '')
            <a
                class="btn"
                href="{{ route('admin.report.santei.csv', ['year' => $selectedYear, 'company' => $selectedCompany]) }}"
            >CSV出力</a>
        @endif
    </form>
</section>
