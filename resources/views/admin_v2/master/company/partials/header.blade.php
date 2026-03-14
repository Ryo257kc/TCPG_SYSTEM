<div class="toolbar">
    <h2>会社マスタ</h2>
    <div class="toolbar-links">
        <a class="btn" href="{{ route('admin.master.staff') }}">スタッフマスタ</a>
        <a class="btn" href="{{ route('admin.master.store') }}">店舗マスタ</a>
        <a class="btn" href="{{ route('admin.master.allowance') }}">手当設定</a>
        <a class="btn" href="{{ route('admin.master.calendar') }}">カレンダー</a>
        <a class="btn" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
    </div>
</div>

@if (session('status'))
    <div class="status">{{ session('status') }}</div>
@endif
