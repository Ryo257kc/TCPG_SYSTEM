<section class="rishoku-filter-bar">
    <form method="GET" class="report-filter-grid rishoku-filter-grid rishoku-filter-grid-compact">
        <label class="field field-inline">
            <span>会社</span>
            <select name="company">
                <option value="">全社</option>
                @foreach ($companyOptions as $company)
                    <option value="{{ $company }}" @selected($selectedCompany === $company)>{{ $company }}</option>
                @endforeach
            </select>
        </label>
        <label class="field field-staff field-inline field-inline-wide">
            <span>退職者</span>
            <select name="staff">
                <option value="">選択してください</option>
                @foreach ($staffOptions as $staffOption)
                    <option value="{{ $staffOption['staff_id'] }}" @selected($selectedStaffId === $staffOption['staff_id'])>{{ $staffOption['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label class="field field-months field-inline">
            <span>表示月数</span>
            <select name="months">
                @foreach ([24, 36, 48] as $months)
                    <option value="{{ $months }}" @selected($selectedMonths === $months)>{{ $months }}か月</option>
                @endforeach
            </select>
        </label>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">表示</button>
        </div>
    </form>
</section>
