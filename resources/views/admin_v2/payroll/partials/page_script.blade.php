<script>
(function(){
  var editBtn = document.getElementById('edit-toggle-btn');
  var root = document.getElementById('payroll-main');
  if (!editBtn || !root) return;

  var saveUrl = @json(route('admin.payroll.update'));
  var calcRecalculateUrl = @json(route('admin.payroll.recalculate'));
  var calcKoyouUrl = @json(route('admin.payroll.calc-koyou'));
  var calcOvertimeDeductionUrl = @json(route('admin.payroll.calc-overtime-deduction'));
  var calcIncomeTaxUrl = @json(route('admin.payroll.calc-income-tax'));
  var confirmUrl = @json(route('admin.payroll.confirm'));
  var csrf = @json(csrf_token());
  var month = @json($selectedMonth);
  var staffId = @json((string) ($selectedRow['staff_id'] ?? ''));
  var selectedCompanyId = @json((string) ($selectedCompanyId ?? ''));
  var isAttendanceConfirmed = @json(((int)($summary['attendance_checked'] ?? 0)) === 1);
  var isPayrollConfirmed = @json(((int)($summary['edit_lock'] ?? 0)) === 1);
  var attendanceReflectBtn = document.getElementById('attendance-reflect-btn');
  var confirmBtn = document.getElementById('confirm-btn');
  var recalcBtn = document.getElementById('recalc-btn');
  var koyouBtn = document.getElementById('calc-koyou-btn');
  var overtimeDeductionBtn = document.getElementById('calc-overtime-deduction-btn');
  var incomeTaxBtn = document.getElementById('calc-income-tax-btn');

  var setConfirmStateUi = function(confirmed){
    [editBtn, attendanceReflectBtn, recalcBtn, koyouBtn, overtimeDeductionBtn, incomeTaxBtn].forEach(function(btn){
      if (btn) btn.disabled = true;
    });
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.textContent = confirmed ? '\u672a\u78ba\u5b9a\u306b\u623b\u3059' : '\u78ba\u5b9a';
    }
  };

  var unlockButtons = function(){
    [editBtn, attendanceReflectBtn, recalcBtn, koyouBtn, overtimeDeductionBtn, incomeTaxBtn].forEach(function(btn){
      if (btn) btn.disabled = false;
    });
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.textContent = '\u78ba\u5b9a';
    }
  };

  editBtn.addEventListener('click', function(){
    if (!root.classList.contains('is-editing')) {
      root.classList.add('is-editing');
      editBtn.textContent = '\u4fdd\u5b58';
      return;
    }

    editBtn.disabled = true;
    var values = {};
    root.querySelectorAll('.edit-input[data-key]').forEach(function(input){
      var key = (input.getAttribute('data-key') || '').trim();
      if (!key) return;
      values[key] = input.value;
    });

    fetch(saveUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        staff_id: staffId,
        month: month,
        values: values,
        company_id: selectedCompanyId
      })
    })
    .then(function(res){ return res.json(); })
    .then(function(json){
      if (!json || json.ok !== true) {
        throw new Error('save failed');
      }
      root.classList.remove('is-editing');
      editBtn.textContent = '\u7de8\u96c6';
      location.reload();
    })
    .catch(function(){
      alert('\u4fdd\u5b58\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
    })
    .finally(function(){
      editBtn.disabled = false;
    });
  });

  if (recalcBtn && staffId !== '') {
    recalcBtn.addEventListener('click', function(){
      recalcBtn.disabled = true;
      fetch(calcRecalculateUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          staff_id: staffId,
          month: month,
          company_id: selectedCompanyId
        })
      })
      .then(function(res){ return res.json(); })
      .then(function(json){
        if (!json || json.ok !== true) {
          throw new Error('recalc failed');
        }
        location.reload();
      })
      .catch(function(){
        alert('\u518d\u8a08\u7b97\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
      })
      .finally(function(){
        recalcBtn.disabled = false;
      });
    });
  }

  if (overtimeDeductionBtn && staffId !== '') {
    overtimeDeductionBtn.addEventListener('click', function(){
      overtimeDeductionBtn.disabled = true;
      fetch(calcOvertimeDeductionUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          staff_id: staffId,
          month: month,
          company_id: selectedCompanyId
        })
      })
      .then(function(res){ return res.json(); })
      .then(function(json){
        if (!json || json.ok !== true) {
          throw new Error('calc failed');
        }
        location.reload();
      })
      .catch(function(){
        alert('\u5272\u5897\u30fb\u63a7\u9664\u8a08\u7b97\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
      })
      .finally(function(){
        overtimeDeductionBtn.disabled = false;
      });
    });
  }

  if (koyouBtn && staffId !== '') {
    koyouBtn.addEventListener('click', function(){
      koyouBtn.disabled = true;
      fetch(calcKoyouUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          staff_id: staffId,
          month: month
        })
      })
      .then(function(res){ return res.json(); })
      .then(function(json){
        if (!json || json.ok !== true) {
          throw new Error('calc failed');
        }
        location.reload();
      })
      .catch(function(){
        alert('\u96c7\u7528\u4fdd\u967a\u306e\u8a08\u7b97\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
      })
      .finally(function(){
        koyouBtn.disabled = false;
      });
    });
  }

  if (incomeTaxBtn && staffId !== '') {
    incomeTaxBtn.addEventListener('click', function(){
      incomeTaxBtn.disabled = true;
      fetch(calcIncomeTaxUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          staff_id: staffId,
          month: month
        })
      })
      .then(function(res){ return res.json(); })
      .then(function(json){
        if (!json || json.ok !== true) {
          throw new Error('calc failed');
        }
        if (json.trace) {
          console.log('income-tax-trace', json.trace);
        }
        location.reload();
      })
      .catch(function(){
        alert('\u6240\u5f97\u7a0e\u306e\u8a08\u7b97\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
      })
      .finally(function(){
        incomeTaxBtn.disabled = false;
      });
    });
  }

  if (confirmBtn && staffId !== '') {
    confirmBtn.addEventListener('click', function(){
      if (!isPayrollConfirmed && !isAttendanceConfirmed) {
        alert('\u52e4\u6020\u672a\u78ba\u5b9a\u306e\u305f\u3081\u7d66\u4e0e\u78ba\u5b9a\u3067\u304d\u307e\u305b\u3093\u3002');
        return;
      }
      confirmBtn.disabled = true;
      fetch(confirmUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          staff_id: staffId,
          month: month,
          checked: isPayrollConfirmed ? 0 : 1
        })
      })
      .then(function(res){
        return res.json().then(function(json){ return { status: res.status, json: json }; });
      })
      .then(function(payload){
        var json = payload.json || {};
        if (payload.status >= 400 || json.ok !== true) {
          throw new Error(json.message || 'confirm failed');
        }
        isPayrollConfirmed = !!json.checked;
        location.reload();
      })
      .catch(function(err){
        alert((err && err.message) ? err.message : '\u78ba\u5b9a\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
      })
      .finally(function(){
        confirmBtn.disabled = false;
      });
    });
  }

  if (isPayrollConfirmed) {
    setConfirmStateUi(true);
  } else {
    unlockButtons();
  }
})();
</script>