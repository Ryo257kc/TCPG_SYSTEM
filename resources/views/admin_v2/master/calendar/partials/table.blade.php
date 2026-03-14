<div class="wrap">
    <table>
        <thead>
        <tr>
            <th>日付</th>
            <th>曜日</th>
            <th>祝日名称</th>
            <th>会社休日</th>
            <th>更新</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($rows as $row)
            @php
                $hasCompanyHoliday = $row['work_holiday'] !== '';
                $isCompanyOnlyHoliday = $hasCompanyHoliday && $row['work_holiday'] !== '祝日';
                $formId = 'calendar-update-' . str_replace('-', '', $row['calendar_day']);
                $deleteFormId = 'calendar-delete-' . str_replace('-', '', $row['calendar_day']);
            @endphp
            <tr class="calendar-row {{ $isCompanyOnlyHoliday ? 'is-company-holiday-row' : '' }}">
                <td>{{ $row['date_label'] }}</td>
                <td>{{ $row['weekday_label'] }}</td>
                <td class="public-holiday-cell">
                    <span class="view-field value-text {{ $row['public_holiday'] === '' ? 'empty' : '' }}">{{ $row['public_holiday'] !== '' ? $row['public_holiday'] : '-' }}</span>
                    <input class="edit-field text-input" type="text" name="public_holiday" value="{{ $row['public_holiday'] }}" placeholder="祝日名称" data-original="{{ $row['public_holiday'] }}" form="{{ $formId }}">
                </td>
                <td class="company-holiday-cell">
                    <span class="view-field value-text {{ $row['work_holiday'] === '' ? 'empty' : '' }}">{{ $row['work_holiday'] !== '' ? $row['work_holiday'] : '-' }}</span>
                    <select class="edit-field select-input" name="work_holiday" data-original="{{ $row['work_holiday'] }}" form="{{ $formId }}">
                        <option value="">未設定</option>
                        <option value="祝日" @selected($row['work_holiday'] === '祝日')>祝日</option>
                        <option value="休日" @selected($row['work_holiday'] === '休日')>休日</option>
                    </select>
                </td>
                <td>
                    <div class="inline-actions view-field">
                        <button class="btn-edit" type="button" data-action="edit">編集</button>
                        <button class="btn-delete" type="submit" form="{{ $deleteFormId }}">削除</button>
                    </div>
                    <div class="inline-actions edit-field">
                        <button type="submit" form="{{ $formId }}">保存</button>
                        <button class="btn-cancel" type="button" data-action="cancel">キャンセル</button>
                    </div>
                    <form id="{{ $formId }}" method="post" action="{{ route('admin.master.calendar.update') }}">
                        @csrf
                        <input type="hidden" name="calendar_day" value="{{ $row['calendar_day'] }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                    </form>
                    <form id="{{ $deleteFormId }}" method="post" action="{{ route('admin.master.calendar.delete') }}">
                        @csrf
                        <input type="hidden" name="calendar_day" value="{{ $row['calendar_day'] }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">祝日・会社休日はまだありません</td></tr>
        @endforelse
        </tbody>
    </table>
</div>