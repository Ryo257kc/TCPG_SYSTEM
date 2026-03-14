@if(session('status'))
  <p>{{ session('status') }}</p>
@endif

<form method="get" style="margin-top:8px;display:flex;gap:8px;align-items:center;">
  <label>会社:</label>
  <select name="office_name">
    <option value="">全社</option>
    @foreach($companyOptions as $c)
      <option value="{{ $c['company_code'] }}" @selected($selectedOfficeName === $c['company_code'])>
        {{ $c['company_code'] }} {{ $c['company_name'] }}
      </option>
    @endforeach
  </select>
  <button type="submit">表示</button>
</form>

<p>対象件数: {{ number_format($rowCount) }} / データ元: {{ $source }}</p>