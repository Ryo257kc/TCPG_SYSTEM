{{-- _confirm_or_change.blade.phpから読み込む、フィールド一覧の描画部分だけを切り出したもの --}}
@foreach ($fields as $field)
<div class="pr-field">
    <label class="pr-field-label">{{ $field['label'] }}</label>
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
    <p class="pr-field-current-file">現在のファイル：{{ $field['value'] }}</p>
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
