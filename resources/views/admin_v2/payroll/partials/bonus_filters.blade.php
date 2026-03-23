<form method="GET" class="filters">
  <label for="payment_date">支給日</label>
  <select id="payment_date" name="payment_date">
    @foreach ($availablePaymentDates as $paymentDate)
      <option value="{{ $paymentDate }}" @selected($selectedPaymentDate === $paymentDate)>{{ $paymentDate }}</option>
    @endforeach
  </select>
  <label for="company_id">会社</label>
  <select id="company_id" name="company_id">
    <option value="">全社</option>
    @foreach ($companyOptions as $company)
      <option value="{{ $company }}" @selected($selectedCompanyId === $company)>{{ $company }}</option>
    @endforeach
  </select>
  <label for="staff_id">スタッフ</label>
  <select id="staff_id" name="staff_id">
    <option value=""></option>
    @foreach ($staffRows as $staff)
      <option value="{{ $staff['staff_id'] }}" @selected($selectedStaffId === $staff['staff_id'])>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</option>
    @endforeach
  </select>
  <button class="btn primary" type="submit">表示</button>
  <button class="btn" type="button" id="bonus-create-btn">賞与データ作成</button>
</form>
