<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TCPG SYSTEM &#25163;&#24403;&#35373;&#23450;</title>
<style>
body{font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif;background:#ecf2fb;margin:0;padding:18px}
.card{background:#fff;border:1px solid #d3dff0;border-radius:14px;padding:16px}
.btn{display:inline-flex;padding:8px 12px;border:1px solid #b7ccef;border-radius:10px;background:#e7effc;color:#1f4f8f;text-decoration:none;font-weight:700}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{border:1px solid #d3dff0;padding:6px;white-space:nowrap;text-align:center}
th{background:#f5f8fd}
.wrap{overflow:auto}
input,select,button{padding:6px 8px;border:1px solid #d3dff0;border-radius:8px}
button{background:#1f4f8f;color:#fff;border-color:#1f4f8f}
input[type="text"],input[type="number"]{width:100%}
.chk{width:20px;height:20px}
.allow-row{cursor:pointer}
.allow-row:hover td{background:#f7fbff}
.allow-row.allow-active td{background:#e8f1ff}
.allow-row:focus-within td{background:#eef5ff}
</style>
</head>
<body>
<div class="card">
  <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;align-items:center;">
    <h2 style="margin:0;">&#25163;&#24403;&#35373;&#23450;</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn" href="{{ route('admin.master.company') }}">&#20250;&#31038;&#12510;&#12473;&#12479;</a>
      <a class="btn" href="{{ route('admin.master.staff') }}">&#12473;&#12479;&#12483;&#12501;&#12510;&#12473;&#12479;</a>
      <a class="btn" href="{{ route('admin.master.store') }}">&#24215;&#33303;&#12510;&#12473;&#12479;</a>
      <a class="btn" href="{{ route('admin.master.calendar') }}">&#12459;&#12524;&#12531;&#12480;&#12540;</a>
      <a class="btn" href="{{ route('admin.dashboard') }}">&#12480;&#12483;&#12471;&#12517;&#12508;&#12540;&#12489;</a>
    </div>
  </div>

  @if(session('status'))
    <p>{{ session('status') }}</p>
  @endif

  <form method="get" style="margin-top:8px;display:flex;gap:8px;align-items:center;">
    <label>&#20250;&#31038;:</label>
    <select name="office_name">
      <option value="">&#20840;&#31038;</option>
      @foreach($companyOptions as $c)
        <option value="{{ $c['company_code'] }}" @selected($selectedOfficeName === $c['company_code'])>
          {{ $c['company_code'] }} {{ $c['company_name'] }}
        </option>
      @endforeach
    </select>
    <button type="submit">&#34920;&#31034;</button>
  </form>

  <p>&#23550;&#35937;&#20214;&#25968;: {{ number_format($rowCount) }} / &#12487;&#12540;&#12479;&#20803;: {{ $source }}</p>

  <div class="wrap">
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>&#20250;&#31038;</th>
          <th>&#34920;&#31034;&#38918;</th>
          <th>AmountKey</th>
          <th>&#25163;&#24403;&#21517;&#31216;</th>
          <th>&#21172;&#20445;</th>
          <th>&#31038;&#20445;</th>
          <th>&#35506;&#31246;</th>
          <th>&#22266;&#23450;&#36067;&#37329;</th>
          <th>&#21106;&#22679;&#22522;&#30990;</th>
          <th>&#25511;&#38500;&#22522;&#30990;</th>
          <th>&#26356;&#26032;</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $row)
        <tr class="allow-row" data-allowance-no="{{ $row['allowance_no'] }}">
          <form method="post" action="{{ route('admin.master.allowance.update') }}">
            @csrf
            <input type="hidden" name="allowance_no" value="{{ $row['allowance_no'] }}">
            <input type="hidden" name="office_name_filter" value="{{ $selectedOfficeName }}">
            <td>{{ $row['allowance_no'] }}</td>
            <td>{{ $row['office_name'] }}</td>
            <td><input type="number" name="display_order" min="1" max="999" value="{{ $row['display_order'] }}" style="width:72px"></td>
            <td><input type="text" name="amount_column_key" value="{{ $row['amount_column_key'] }}"></td>
            <td><input type="text" name="allowance_name" value="{{ $row['allowance_name'] }}"></td>
            <td><input class="chk" type="checkbox" name="rou_target" value="1" @checked((int)$row['rou_target']===1)></td>
            <td><input class="chk" type="checkbox" name="syaho_target" value="1" @checked((int)$row['syaho_target']===1)></td>
            <td><input class="chk" type="checkbox" name="tax_target" value="1" @checked((int)$row['tax_target']===1)></td>
            <td><input class="chk" type="checkbox" name="kotei_wage" value="1" @checked((int)$row['kotei_wage']===1)></td>
            <td><input class="chk" type="checkbox" name="warimasi_kiso" value="1" @checked((int)$row['warimasi_kiso']===1)></td>
            <td><input class="chk" type="checkbox" name="koujyo_kiso" value="1" @checked((int)$row['koujyo_kiso']===1)></td>
            <td><button type="submit">&#26356;&#26032;</button></td>
          </form>
        </tr>
      @empty
        <tr><td colspan="12">&#12487;&#12540;&#12479;&#12364;&#12354;&#12426;&#12414;&#12379;&#12435;&#12290;</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
<script>
(function(){
  var key = 'allowance_active_no';
  var rows = Array.prototype.slice.call(document.querySelectorAll('.allow-row[data-allowance-no]'));
  if (!rows.length) return;

  function apply(no){
    rows.forEach(function(r){
      r.classList.toggle('allow-active', (r.getAttribute('data-allowance-no') || '') === String(no || ''));
    });
  }

  var saved = sessionStorage.getItem(key);
  if (saved) apply(saved);

  rows.forEach(function(r){
    r.addEventListener('click', function(){
      var no = r.getAttribute('data-allowance-no') || '';
      sessionStorage.setItem(key, no);
      apply(no);
    });
  });
})();
</script>
</body>
</html>
