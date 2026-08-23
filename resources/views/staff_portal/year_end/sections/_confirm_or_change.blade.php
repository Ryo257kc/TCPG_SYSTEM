{{--
    「現在の登録値を表示 → 変わった/変わってない → 変わった場合だけ入力欄」の共通パーツ。
    呼び出し側で渡す変数:
    - sectionKey: string  (id生成用、セクション内で一意)
    - title: string
    - currentItems: array<int, array{label:string, value:string}>  現在の登録値（読み取り専用）
    - changedFieldName: string  変更有無チェックボックス（またははい/いいえ）のname
    - changedChecked: bool|null  requireAnswer時はtrue/false/null（null=未回答、はい/いいえどちらも選ばせない）
    - requireAnswer: bool (省略可、既定false)  trueならチェックボックスではなく必須のはい/いいえラジオにする
      （未回答のまま保存できないようにするため。次のセクションへ進む唯一の手段が「答えて保存する」に
      なるよう、答え忘れたまま保存ボタンを押しても通らないようにする用途）
    - fields: array<int, array{name:string, label:string, value:string, maxlength?:int, type?:string, options?:array<string,string>, checked?:bool}>
      type: text(既定)/number/date/select/checkbox/file。selectはoptions（value=>label）、checkboxはchecked(bool)、
      fileはvalue（現在のファイル名の表示用、空なら非表示）とaccept（省略可）を使う。
    - editable: bool
    - toggleLabel: string (省略時「変わった」)
    - rowMode: bool (省略可、既定false)  trueなら、タイトルと同じ行の右側に「編集」ボタン
      （＋deleteFieldNameがあれば「削除」ボタン）を並べる。既存の一覧行（扶養親族・保険料控除）用。
    - deleteFieldName: string|null  rowMode時、削除チェックボックスのname（省略時は削除ボタンなし）
    - deleteDisabled: bool (省略可)
    - deleteDisabledReason: string|null (省略可)  削除できない理由の補足
    - missingAttachmentWarning: string|null (省略可)  非空ならタイトル行に赤字で表示（添付漏れ警告）
--}}
<div class="year-end-section">
    @if (!empty($title) && empty($rowMode))
    <h3 class="year-end-section-title">{{ $title }}</h3>
    @endif

    @if (!empty($rowMode))
    <div class="year-end-row-header">
        <div class="year-end-row-header-title">
            @if (!empty($title))
            <h3 class="year-end-section-title">{{ $title }}</h3>
            @endif
            @if (!empty($missingAttachmentWarning))
            <p class="year-end-attachment-warning">⚠ {{ $missingAttachmentWarning }}</p>
            @endif
        </div>
        <div class="year-end-row-actions">
            <label class="year-end-row-action-btn" for="year-end-toggle-{{ $sectionKey }}">編集</label>
            @if (!empty($deleteFieldName))
            <label class="year-end-row-action-btn year-end-row-action-btn-danger year-end-row-delete-label">
                <span class="year-end-row-delete-label-text">削除</span>
                <input type="checkbox" class="year-end-row-delete-checkbox" name="{{ $deleteFieldName }}" value="1" {{ (!empty($deleteDisabled) || empty($editable)) ? 'disabled' : '' }} style="display:none;">
            </label>
            @if (!empty($deleteDisabled) && !empty($deleteDisabledReason))
            <p class="year-end-note">{{ $deleteDisabledReason }}</p>
            @endif
            @endif
        </div>
    </div>
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
        @if (!empty($requireAnswer))
        <div class="year-end-required-choice">
            <label class="year-end-field-label">{{ $toggleLabel ?? '変わった' }}</label>
            <div class="year-end-required-choice-options">
                <label class="year-end-required-choice-option">
                    <input
                        type="radio"
                        class="year-end-toggle-input"
                        name="{{ $changedFieldName }}"
                        value="1"
                        {{ $changedChecked === true ? 'checked' : '' }}
                        {{ $editable ? 'required' : 'disabled' }}>
                    はい
                </label>
                <label class="year-end-required-choice-option">
                    <input
                        type="radio"
                        class="year-end-toggle-input-no"
                        name="{{ $changedFieldName }}"
                        value="0"
                        {{ $changedChecked === false ? 'checked' : '' }}
                        {{ $editable ? 'required' : 'disabled' }}>
                    いいえ
                </label>
            </div>
        </div>
        @else
        <input
            type="checkbox"
            class="year-end-toggle-input"
            id="year-end-toggle-{{ $sectionKey }}"
            name="{{ $changedFieldName }}"
            value="1"
            {{ $changedChecked ? 'checked' : '' }}
            {{ $editable ? '' : 'disabled' }}
            @if(!empty($rowMode)) style="display:none;" @endif>
        @if (empty($rowMode))
        <label for="year-end-toggle-{{ $sectionKey }}" class="year-end-toggle-label">{{ $toggleLabel ?? '変わった' }}</label>
        @endif
        @endif

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
