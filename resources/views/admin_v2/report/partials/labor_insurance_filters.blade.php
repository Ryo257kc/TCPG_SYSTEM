<section class="panel filter-panel">
    <form class="report-filter-grid" method="get" action="{{ route('admin.report.labor-insurance.index') }}">
        <label class="field">
            <span>年度</span>
            <select name="year">
                @foreach ($availableYears as $year)
                    <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}年度</option>
                @endforeach
            </select>
        </label>

        <label class="field field-wide">
            <span>会社</span>
            <select name="company">
                <option value="">すべて</option>
                @foreach ($companyOptions as $company)
                    <option value="{{ $company }}" @selected($selectedCompany === $company)>{{ $company }}</option>
                @endforeach
            </select>
        </label>

        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">表示</button>
        </div>
    </form>
</section>
