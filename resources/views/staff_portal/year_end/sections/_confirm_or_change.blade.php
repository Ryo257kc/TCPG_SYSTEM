{{--
    「現在の登録値を表示 → 変わった/変わってない → 変わった場合だけ入力欄」の共通パーツ。
    呼び出し側で渡す変数:
    - sectionKey: string  (id生成用、セクション内で一意)
    - title: string
    - currentItems: array<int, array{label:string, value:string}>  現在の登録値（読み取り専用）
    - changedFieldName: string  変更有無チェックボックスのname
    - changedChecked: bool
    - fields: array<int, array{name:string, label:string, value:string, maxlength?:int, type?:string, options?:array<string,string>, checked?:bool}>
      type: text(既定)/number/date/select/checkbox/file。selectはoptions（value=>label）、checkboxはchecked(bool)、
      fileはvalue（現在のファイル名の表示用、空なら非表示）とaccept（省略可）を使う。
    - editable: bool
    - toggleLabel: string (省略時「変わった」)
--}}
<div class="year-end-section">
    @if (!empty($title))
    <h3 class="year-end-section-title">{{ $title }}</h3>
    @endif

    @php
    $hasRegisteredValue = collect($currentItems)->contains(fn($item) => ($item['value'] ?? '') !== '');
    @endphp
    @if ($hasRegisteredValue)
    <div class="year-end-current">
        <p class="year-end-current-label">現在の登録内容</p>
        @foreach ($currentItems as $item)
        @if (($item['value'] ?? '') !== '')
        <p class="year-end-current-value">{{ $item['label'] }}：{{ $item['value'] }}</p>
        @endif
        @endforeach
    </div>
    @endif

    <div class="year-end-toggle">
        <input
            type="checkbox"
            class="year-end-toggle-input"
            id="year-end-toggle-{{ $sectionKey }}"
            name="{{ $changedFieldName }}"
            value="1"
            {{ $changedChecked ? 'checked' : '' }}
            {{ $editable ? '' : 'disabled' }}>
        <label for="year-end-toggle-{{ $sectionKey }}" class="year-end-toggle-label">{{ $toggleLabel ?? '変わった' }}</label>

        <div class="year-end-toggle-fields">
            @foreach ($fields as $field)
            <div class="year-end-field">
                <label class="year-end-field-label">{{ $field['label'] }}</label>
                @if (($field['type'] ?? 'text') === 'select')
                <select name="{{ $field['name'] }}" {{ $editable ? '' : 'disabled' }}>
                    @foreach (($field['options'] ?? []) as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" {{ (string) $field['value'] === (string) $optionValue ? 'selected' : '' }}>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                @elseif (($field['type'] ?? 'text') === 'checkbox')
                <input type="checkbox" name="{{ $field['name'] }}" value="1" {{ !empty($field['checked']) ? 'checked' : '' }} {{ $editable ? '' : 'disabled' }}>
                @elseif (($field['type'] ?? 'text') === 'file')
                <input type="file" name="{{ $field['name'] }}" accept="{{ $field['accept'] ?? '' }}" {{ $editable ? '' : 'disabled' }}>
                @if (!empty($field['value']))
                <p class="year-end-field-current-file">現在のファイル：{{ $field['value'] }}</p>
                @endif
                @else
                <input
                    type="{{ $field['type'] ?? 'text' }}"
                    name="{{ $field['name'] }}"
                    value="{{ $field['value'] }}"
                    @if(!empty($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
                    {{ $editable ? '' : 'disabled' }}>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
