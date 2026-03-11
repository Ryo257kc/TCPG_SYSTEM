<div style="display:grid;gap:8px;">
@foreach($rightA as $title=>$items)
@php $source = $title === $baseInfoTitle ? $baseInfoView : $summary; @endphp
<section class="card readonly-card"><h2 class="section-title">{!! $title !!}</h2><table class="kv">
@foreach($items as $it) @php
[$k,$m]=$it;
$val = $m===-1 ? $tFrom($source,$k) : $nFrom($source,$k,$m);
if (in_array($k, ['social_join', 'employment_join'], true)) {
    $val = ((int)$val === 1) ? '有' : '無';
}
@endphp
@if ($k === 'memo')
<tr><td colspan="2" class="memo-wrap"><div class="memo-label">{!! $l($k) !!}</div><div class="memo-value" title="{{ $val }}">{{ $val }}</div></td></tr>
@else
@php $rowClass = in_array($k, $totalRowKeys, true) ? 'total-row' : ''; @endphp
<tr class="{{ $rowClass }}"><td>{!! $l($k) !!}</td><td>{{ $val }}</td></tr>
@endif
@endforeach
</table></section>
@endforeach
</div>
