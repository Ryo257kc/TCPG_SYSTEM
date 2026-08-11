{{--
    「現在の登録値を表示 → 変わった/変わってない → 変わった場合だけ入力欄」の共通パーツ
    （年末調整のsections/_confirm_or_change.blade.phpと同じ構造）。
    呼び出し側で渡す変数:
    - sectionKey: string  (id生成用、セクション内で一意)
    - title: string
    - currentItems: array<int, array{label:string, value:string}>  現在の登録値（読み取り専用）
    - changedFieldName: string  変更有無チェックボックスのname
    - changedChecked: bool
    - hideToggle: bool (省略時false)  trueならトグルを出さず常に入力欄を表示する
      （申請フォーム自体が「変更を申告する」ためのものなので、本人情報・通勤経路のように
      毎回内容を見て必要なら直すことが前提のセクションで使う）
    - fields: array<int, array{name:string, label:string, value:string, maxlength?:int, type?:string, options?:array<string,string>, checked?:bool}>
      type: text(既定)/number/date/select/checkbox/file。selectはoptions（value=>label）、checkboxはchecked(bool)、
      fileはvalue（現在のファイル名の表示用、空なら非表示）とaccept（省略可）を使う。
    - editable: bool
    - toggleLabel: string (省略時「変わった」)
--}}
<div class="pr-section">
    @if (!empty($title))
    <h3 class="pr-section-title">{{ $title }}</h3>
    @endif

    @php
    $hasRegisteredValue = collect($currentItems)->contains(fn($item) => ($item['value'] ?? '') !== '');
    @endphp
    @if ($hasRegisteredValue)
    <div class="pr-current">
        <p class="pr-current-label">現在の登録内容</p>
        @foreach ($currentItems as $item)
        @if (($item['value'] ?? '') !== '')
        <p class="pr-current-value">{{ $item['label'] }}：{{ $item['value'] }}</p>
        @endif
        @endforeach
    </div>
    @endif

    @php $fieldsMarkup = true; @endphp
    @if (!empty($hideToggle))
    <input type="hidden" name="{{ $changedFieldName }}" value="1">
    <div class="pr-toggle-fields" style="display:grid;">
        @include('staff_portal.profile_request.sections._confirm_or_change_fields', ['fields' => $fields, 'editable' => $editable])
    </div>
    @else
    <div class="pr-toggle">
        <input
            type="checkbox"
            class="pr-toggle-input"
            id="pr-toggle-{{ $sectionKey }}"
            name="{{ $changedFieldName }}"
            value="1"
            {{ $changedChecked ? 'checked' : '' }}
            {{ $editable ? '' : 'disabled' }}>
        <label for="pr-toggle-{{ $sectionKey }}" class="pr-toggle-label">{{ $toggleLabel ?? '変わった' }}</label>

        <div class="pr-toggle-fields">
            @include('staff_portal.profile_request.sections._confirm_or_change_fields', ['fields' => $fields, 'editable' => $editable])
        </div>
    </div>
    @endif
</div>
