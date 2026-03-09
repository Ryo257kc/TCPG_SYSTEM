<section class="bulk-panel">
    <p class="bulk-title">対象スタッフ選択（シフト一括作成・削除）</p>
    <div class="bulk-grid">
        @foreach($displayStaffRows as $staff)
            <label class="bulk-check">
                <input
                    type="checkbox"
                    class="js-target-staff"
                    value="{{ $staff['staff_id'] }}"
                    @checked($selectedStaffId !== '' && $selectedStaffId === $staff['staff_id'])
                >
                <span>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</span>
            </label>
        @endforeach
    </div>
    <div class="bulk">
        <button type="button" class="js-select-all">全選択</button>
        <button type="button" class="js-clear-all">全解除</button>
    </div>
</section>
