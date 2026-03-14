@if (session('status'))<p>{{ session('status') }}</p>@endif

<div style="margin-top:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0;">
        <label for="year">対象年</label>
        <select id="year" name="year">
            @foreach ($yearOptions as $year)
                <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
            @endforeach
        </select>
        <button type="submit">表示</button>
    </form>

    <form method="post" action="{{ route('admin.master.calendar.import-public-holidays') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0;">
        @csrf
        <input type="hidden" name="year" value="{{ $selectedYear }}">
        <button type="submit">祝日を自動取得</button>
    </form>
</div>

<p class="hint">祝日名称と会社休日の名称を必要に応じて入力します。</p>
<p>件数: {{ number_format($rowCount) }} / ソース: {{ $source }}</p>