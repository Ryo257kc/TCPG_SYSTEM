<form method="get" class="filter-form">
    <input type="text" name="q" value="{{ $keyword }}" placeholder="会社名・会社コード・事業所番号">
    <button type="submit">表示</button>
</form>

<div class="meta">件数: {{ number_format($rowCount) }} / ソース: {{ $source }}</div>
