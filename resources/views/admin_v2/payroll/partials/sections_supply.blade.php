<div style="display:grid;gap:8px;">
@foreach($supply as $title=>$items)
<section class="card"><h2 class="section-title">{!! $title !!}</h2><table class="kv">
@foreach($items as $it) @php [$k,$m]=$it; $val = $m===-1 ? $t($k) : $n($k,$m); $delta = $m===-1 ? '' : $deltaFrom($k,$m); $rowClass = in_array($k, $totalRowKeys, true) ? 'total-row' : ''; @endphp
<tr class="{{ $rowClass }}"><td>{!! $l($k) !!}</td><td><span class="view-only">{{ $val }}@if($delta !== '') <small style="display:block;color:#c15353;font-weight:700;">{{ $delta }}</small>@endif</span><input class="edit-input edit-only {{ $m===-1 ? 'text' : '' }}" type="text" value="{{ $val }}"></td></tr>
@endforeach
</table></section>
@endforeach
</div>
