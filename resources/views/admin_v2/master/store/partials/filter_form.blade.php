<form method="get" class="filter-form">
  <input type="text" name="q" value="{{ $keyword }}" placeholder="店舗コード・店舗名・会社名・連携先コード">
  @if($selectedStoreCode !== '')
    <input type="hidden" name="store_code" value="{{ $selectedStoreCode }}">
  @endif
  <button type="submit">表示</button>
</form>