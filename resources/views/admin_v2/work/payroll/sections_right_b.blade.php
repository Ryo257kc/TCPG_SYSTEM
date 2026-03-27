@foreach($rightB as $title=>$items)
@php
$source = $title === $masterTitle
    ? $kihonMasterView
    : ($title === $shahoTitle ? $shahoView : $residentView);
@endphp
<section class="card readonly-card"><h2 class="section-title">{!! $title !!}</h2><table class="kv">
@foreach($items as $it) @php
[$k,$m]=$it;
$val = $m===-1 ? $tFrom($source,$k) : $nFrom($source,$k,$m);
@endphp
@php $rowClass = in_array($k, $totalRowKeys, true) ? 'total-row' : ''; @endphp
<tr class="{{ $rowClass }}"><td>{!! $l($k) !!}</td><td>{{ $val }}</td></tr>
@endforeach
</table></section>
@endforeach
