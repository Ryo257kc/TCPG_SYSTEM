<script>
(function(){
  var editBtn = document.getElementById('edit-toggle-btn');
  var root = document.getElementById('payroll-main');
  if (!root) return;

  var saveUrl = @json(route('admin.payroll.update'));
  var calcRecalculateUrl = @json(route('admin.payroll.recalculate'));
  var calcKoyouUrl = @json(route('admin.payroll.calc-koyou'));
  var calcOvertimeDeductionUrl = @json(route('admin.payroll.calc-overtime-deduction'));
  var calcIncomeTaxUrl = @json(route('admin.payroll.calc-income-tax'));
  var confirmUrl = @json(route('admin.payroll.confirm'));
  var createCandidatesUrl = @json(route('admin.payroll.create-candidates'));
  var createUrl = @json(route('admin.payroll.create'));
  var deleteUrl = @json(route('admin.payroll.delete'));
  var csrf = @json(csrf_token());
  var month = @json($selectedMonth);
  var staffId = @json((string) ($selectedRow['staff_id'] ?? ''));
  var selectedCompanyId = @json((string) ($selectedCompanyId ?? ''));
  var isAttendanceConfirmed = @json(((int)($summary['attendance_checked'] ?? 0)) === 1);
  var isPayrollConfirmed = @json(((int)($summary['edit_lock'] ?? 0)) === 1);
  var attendanceConfirmNote = document.getElementById('attendance-confirm-note');
  var attendanceReflectBtn = document.getElementById('attendance-reflect-btn');
  var confirmBtn = document.getElementById('confirm-btn');
  var recalcBtn = document.getElementById('recalc-btn');
  var payrollCreateBtn = document.getElementById('payroll-create-btn');
  var payrollCreateInline = document.getElementById('payroll-create-inline');
  var payrollCreateList = document.getElementById('payroll-create-list');
  var payrollCreateEmpty = document.getElementById('payroll-create-empty');
  var payrollCreatePaymentDate = document.getElementById('payroll-create-payment-date');
  var payrollCreateShowBtn = document.getElementById('payroll-create-show-btn');
  var payrollCreateSubmitBtn = document.getElementById('payroll-create-submit-btn');
  var payrollDeleteSubmitBtn = document.getElementById('payroll-delete-submit-btn');
  var payrollCreateSelectAllBtn = document.getElementById('payroll-create-select-all-btn');
  var payrollCreateClearBtn = document.getElementById('payroll-create-clear-btn');
  var payrollCreateCloseBtn = document.getElementById('payroll-create-close-btn');
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

  if (editBtn) {
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
  }

  var closePayrollCreateInline = function(){
    if (payrollCreateInline) payrollCreateInline.hidden = true;
  };

  var openPayrollCreateInline = function(){
    if (payrollCreateInline) payrollCreateInline.hidden = false;
  };

  if (attendanceConfirmNote && isAttendanceConfirmed) {
    attendanceConfirmNote.hidden = true;
  }

  var resetPayrollCreateCandidates = function(){
    if (!payrollCreateList || !payrollCreateEmpty) return;
    payrollCreateList.innerHTML = '';
    payrollCreateList.hidden = true;
    payrollCreateEmpty.hidden = true;
  };

  var renderPayrollCreateCandidates = function(candidates){
    if (!payrollCreateList || !payrollCreateEmpty) return;
    payrollCreateList.innerHTML = '';
    payrollCreateEmpty.hidden = candidates.length !== 0;
    payrollCreateList.hidden = candidates.length === 0;

    candidates.forEach(function(candidate){
      var wrapper = document.createElement('label');
      wrapper.className = 'create-inline-item' + (candidate.existing ? ' existing' : '');

      var check = document.createElement('input');
      check.type = 'checkbox';
      check.value = candidate.staff_id;
      check.className = 'payroll-create-check';

      var main = document.createElement('div');
      main.className = 'create-inline-item-main';
      main.innerHTML =
        '<div class="create-inline-item-title">' + candidate.staff_id + ' ' + candidate.staff_name + '</div>';

      var badges = document.createElement('div');
      badges.className = 'create-inline-badges';

      if (candidate.candidate_type === 'retired_prev_month') {
        var status = document.createElement('span');
        status.className = 'create-inline-badge retired';
        status.textContent = '\u524d\u6708\u9000\u8077';
        badges.appendChild(status);
      }

      if (candidate.retire_date) {
        var retire = document.createElement('span');
        retire.className = 'create-inline-badge';
        retire.textContent = candidate.retire_date;
        badges.appendChild(retire);
      }

      main.appendChild(badges);
      wrapper.appendChild(check);
      wrapper.appendChild(main);
      payrollCreateList.appendChild(wrapper);
    });
  };

  var loadPayrollCreateCandidates = function(){
    if (!payrollCreatePaymentDate) return;
    var paymentDate = payrollCreatePaymentDate.value;
    if (!paymentDate) {
      alert('\u4f5c\u6210\u65e5\u3092\u5165\u529b\u3057\u3066\u304f\u3060\u3055\u3044\u3002');
      payrollCreatePaymentDate.focus();
      return;
    }

    if (payrollCreateShowBtn) payrollCreateShowBtn.disabled = true;
    var query = '?month=' + encodeURIComponent(month) + '&company_id=' + encodeURIComponent(selectedCompanyId) + '&payment_date=' + encodeURIComponent(paymentDate);
    fetch(createCandidatesUrl + query, {
      headers: { 'Accept': 'application/json' }
    })
    .then(function(res){ return res.json(); })
    .then(function(json){
      if (!json || json.ok !== true) {
        throw new Error('candidates failed');
      }
      renderPayrollCreateCandidates(json.candidates || []);
    })
    .catch(function(){
      alert('\u7d66\u4e0e\u30c7\u30fc\u30bf\u4f5c\u6210\u5019\u88dc\u306e\u53d6\u5f97\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
    })
    .finally(function(){
      if (payrollCreateShowBtn) payrollCreateShowBtn.disabled = false;
    });
  };

  if (payrollCreateBtn) {
    payrollCreateBtn.addEventListener('click', function(){
      openPayrollCreateInline();
      resetPayrollCreateCandidates();
    });
  }

  if (payrollCreateShowBtn) {
    payrollCreateShowBtn.addEventListener('click', loadPayrollCreateCandidates);
  }

  if (payrollCreateCloseBtn) {
    payrollCreateCloseBtn.addEventListener('click', closePayrollCreateInline);
  }


  if (payrollCreateSelectAllBtn) {
    payrollCreateSelectAllBtn.addEventListener('click', function(){
      document.querySelectorAll('.payroll-create-check').forEach(function(check){ check.checked = true; });
    });
  }

  if (payrollCreateClearBtn) {
    payrollCreateClearBtn.addEventListener('click', function(){
      document.querySelectorAll('.payroll-create-check').forEach(function(check){ check.checked = false; });
    });
  }

  if (payrollCreateSubmitBtn) {
    payrollCreateSubmitBtn.addEventListener('click', function(){
      var ids = Array.prototype.slice.call(document.querySelectorAll('.payroll-create-check:checked')).map(function(check){
        return check.value;
      });
      var paymentDate = payrollCreatePaymentDate ? payrollCreatePaymentDate.value : '';

      if (payrollCreateList && payrollCreateList.children.length === 0) {
        alert('\u8868\u793a\u3092\u62bc\u3057\u3066\u5019\u88dc\u3092\u78ba\u8a8d\u3057\u3066\u304f\u3060\u3055\u3044\u3002');
        return;
      }
      if (ids.length === 0) {
        alert('\u30b9\u30bf\u30c3\u30d5\u3092\u9078\u629e\u3057\u3066\u304f\u3060\u3055\u3044\u3002');
        return;
      }
      if (!paymentDate) {
        alert('\u4f5c\u6210\u65e5\u3092\u5165\u529b\u3057\u3066\u304f\u3060\u3055\u3044\u3002');
        return;
      }

      payrollCreateSubmitBtn.disabled = true;
      fetch(createUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          month: month,
          company_id: selectedCompanyId,
          payment_date: paymentDate,
          staff_ids: ids
        })
      })
      .then(function(res){
        return res.json().then(function(json){ return { status: res.status, json: json }; });
      })
      .then(function(payload){
        var json = payload.json || {};
        if (payload.status >= 400 || json.ok !== true) {
          throw new Error(json.message || 'create failed');
        }
        closePayrollCreateInline();
        location.reload();
      })
      .catch(function(err){
        alert((err && err.message) ? err.message : '\u7d66\u4e0e\u30c7\u30fc\u30bf\u4f5c\u6210\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
      })
      .finally(function(){
        payrollCreateSubmitBtn.disabled = false;
      });
    });
  }

  if (payrollDeleteSubmitBtn) {
    payrollDeleteSubmitBtn.addEventListener('click', function(){
      var ids = Array.prototype.slice.call(document.querySelectorAll('.payroll-create-check:checked')).map(function(check){
        return check.value;
      });
      var paymentDate = payrollCreatePaymentDate ? payrollCreatePaymentDate.value : '';

      if (payrollCreateList && payrollCreateList.children.length === 0) {
        alert('\u8868\u793a\u3092\u62bc\u3057\u3066\u5019\u88dc\u3092\u78ba\u8a8d\u3057\u3066\u304f\u3060\u3055\u3044\u3002');
        return;
      }
      if (ids.length === 0) {
        alert('\u30b9\u30bf\u30c3\u30d5\u3092\u9078\u629e\u3057\u3066\u304f\u3060\u3055\u3044\u3002');
        return;
      }
      if (!paymentDate) {
        alert('\u4f5c\u6210\u65e5\u3092\u5165\u529b\u3057\u3066\u304f\u3060\u3055\u3044\u3002');
        return;
      }
      if (!window.confirm('\u9078\u629e\u3057\u305f\u7d66\u4e0e\u30c7\u30fc\u30bf\u3092\u524a\u9664\u3057\u307e\u3059\u3002\u3088\u308d\u3057\u3044\u3067\u3059\u304b\uff1f')) {
        return;
      }

      payrollDeleteSubmitBtn.disabled = true;
      fetch(deleteUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          payment_date: paymentDate,
          staff_ids: ids
        })
      })
      .then(function(res){
        return res.json().then(function(json){ return { status: res.status, json: json }; });
      })
      .then(function(payload){
        var json = payload.json || {};
        if (payload.status >= 400 || json.ok !== true) {
          throw new Error(json.message || 'delete failed');
        }
        closePayrollCreateInline();
        location.reload();
      })
      .catch(function(err){
        alert((err && err.message) ? err.message : '\u7d66\u4e0e\u30c7\u30fc\u30bf\u524a\u9664\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
      })
      .finally(function(){
        payrollDeleteSubmitBtn.disabled = false;
      });
    });
  }

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
