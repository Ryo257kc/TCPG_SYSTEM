<div class="toolbar">
  <h2>店舗マスタ</h2>
  <div class="toolbar-links">
    <a class="btn" href="{{ route('admin.master.company') }}">会社マスタ</a>
    <a class="btn" href="{{ route('admin.master.staff') }}">スタッフマスタ</a>
    <a class="btn" href="{{ route('admin.master.allowance') }}">手当設定</a>
    <a class="btn" href="{{ route('admin.master.calendar') }}">カレンダー</a>
    <a class="btn" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
  </div>
</div>

@if(session('status'))
  <p class="status">{{ session('status') }}</p>
@endif

<p class="meta">件数: {{ number_format($rowCount) }} / ソース: {{ $source }}</p>