<div class="wrap">
  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>会社</th>
        <th>表示順</th>
        <th>AmountKey</th>
        <th>手当名称</th>
        <th>労保</th>
        <th>社保</th>
        <th>課税</th>
        <th>固定賃金</th>
        <th>割増基礎</th>
        <th>控除基礎</th>
        <th>更新</th>
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
          <td><button type="submit">更新</button></td>
        </form>
      </tr>
    @empty
      <tr><td colspan="12">データがありません。</td></tr>
    @endforelse
    </tbody>
  </table>
</div>