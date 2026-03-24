<section class="panel filter-panel">
    <form method="GET" action="{{ route('admin.paid-leave.index') }}" class="filter-form">
        <label class="field field-staff">
            <span>スタッフ</span>
            <select name="staff_id">
                <option value="">選択してください</option>
                @foreach ($staffOptions as $option)
                    <option value="{{ $option['staff_id'] }}" {{ $selectedStaffId === $option['staff_id'] ? 'selected' : '' }}>
                        {{ $option['label'] }}
                    </option>
                @endforeach
            </select>
        </label>
        <div class="field actions">
            <button type="submit" class="btn btn-primary">表示</button>
        </div>
    </form>
</section>
