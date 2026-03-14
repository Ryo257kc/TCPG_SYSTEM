<form method="post" action="{{ route('admin.master.calendar.update') }}" class="add-form">
    @csrf
    <input type="hidden" name="year" value="{{ $selectedYear }}">
    <input type="date" name="calendar_day" value="{{ $selectedYear }}-01-01" required>
    <input class="text-input" type="text" name="public_holiday" value="" placeholder="祝日名称">
    <select class="select-input" name="work_holiday">
        <option value="">未設定</option>
        <option value="祝日">祝日</option>
        <option value="休日" selected>休日</option>
    </select>
    <button type="submit">追加</button>
</form>