<table>
    <tbody>
    @forelse($rows as $k => $v)
        @php
            $editable = isset($editablePayloadKeys[$k]);
            $raw = is_bool($v) ? ($v ? '1' : '0') : (string)$v;
            $disp = $raw;
            if (is_numeric(str_replace(',', '', trim($raw)))) {
                $num = (float)str_replace(',', '', trim($raw));
                $disp = (abs($num - round($num)) < 0.000001)
                    ? number_format((int)round($num))
                    : number_format($num, 2);
            }
        @endphp
        <tr>
            <th>{{ $displayLabelMap[$k] ?? $k }}</th>
            <td>
                <span class="cell-view">{{ $disp }}</span>
                @if($editable)
                    <input class="num-input" type="text" name="fields[{{ $k }}]" value="{{ $raw }}">
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="2" class="empty">データなし</td></tr>
    @endforelse
    </tbody>
</table>