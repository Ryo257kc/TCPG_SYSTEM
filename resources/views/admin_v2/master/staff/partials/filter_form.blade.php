<form method="get" class="filter-form">
  <input type="text" name="q" value="{{ $keyword }}" placeholder="ID / 氏名 / 店舗コードで検索">
  <select name="employment_filter">
    <option value="active" {{ ($employmentFilter ?? 'active') === 'active' ? 'selected' : '' }}>在籍</option>
    <option value="all" {{ ($employmentFilter ?? 'active') === 'all' ? 'selected' : '' }}>すべて</option>
    <option value="retired" {{ ($employmentFilter ?? 'active') === 'retired' ? 'selected' : '' }}>退職</option>
  </select>
  <button type="submit">検索</button>
</form>
<p class="meta">件数: {{ number_format($rowCount) }} / ソース: {{ $source }}</p>
