@foreach($attendance as $title => $items)
<section class="card"><h2 class="section-title">{!! $title !!}</h2><table class="kv">
@foreach($items as $it)
@php
    [$k, $m] = $it;
    $val = $m === -1 ? $t($k) : $n($k, $m);
    $delta = ($k === 'holiday_true_num' && $m !== -1) ? $deltaFrom($k, $m) : '';
    $attendanceVal = $m === -1 ? '' : $attendanceValueFrom($k, $m);
    $attendanceClass = ($attendanceVal !== '' && $attendanceVal !== $val) ? 'is-diff' : 'is-match';
    $rowClass = in_array($k, $totalRowKeys, true) ? 'total-row' : '';
@endphp
<tr class="{{ $rowClass }}"><td>{!! $l($k) !!}</td><td><span class="view-only">{{ $val }}@if($attendanceVal !== '') <small class="attendance-source {{ $attendanceClass }}">勤怠: {{ $attendanceVal }}</small>@endif @if($delta !== '') <small style="display:block;color:#c15353;font-weight:700;">{{ $delta }}</small>@endif</span>@if(!in_array($k, $totalRowKeys, true))<input class="edit-input edit-only {{ $m===-1 ? 'text' : '' }}" type="text" value="{{ $val }}" data-key="{{ $k }}">@endif</td></tr>
@endforeach
</table></section>
@endforeach
@foreach($sales as $title => $items)
<section class="card"><h2 class="section-title">{!! $title !!}</h2><table class="kv">
@foreach($items as $it)
@php
    [$k, $m] = $it;
    $val = $m === -1 ? $t($k) : $n($k, $m);
    $delta = ($k === 'holiday_true_num' && $m !== -1) ? $deltaFrom($k, $m) : '';
    $rowClass = in_array($k, $totalRowKeys, true) ? 'total-row' : '';
@endphp
<tr class="{{ $rowClass }}"><td>{!! $l($k) !!}</td><td><span class="view-only">{{ $val }}@if($delta !== '') <small style="display:block;color:#c15353;font-weight:700;">{{ $delta }}</small>@endif</span>@if(!in_array($k, $totalRowKeys, true))<input class="edit-input edit-only {{ $m===-1 ? 'text' : '' }}" type="text" value="{{ $val }}" data-key="{{ $k }}">@endif</td></tr>
@endforeach
</table></section>
@endforeach
